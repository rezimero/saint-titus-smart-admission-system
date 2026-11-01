<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsDocumentRequirement;
use App\Models\StsamsProgramme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CatalogueController extends Controller
{
    private function authorizeSetup(Request $request): void { abort_unless($request->user()?->can('settings.manage'), 403); }

    public function index(Request $request): View
    {
        $this->authorizeSetup($request);
        return view('stsams.admin.catalogue', [
            'programmes' => StsamsProgramme::query()->with('documentRequirements')->orderBy('name')->get(),
            'sessions' => StsamsAdmissionSession::query()->orderByDesc('id')->get(),
            'documentTypes' => config('stsams_documents.types'),
            'admissionTracks' => config('stsams_documents.admission_tracks'),
        ]);
    }

    public function programme(Request $request): RedirectResponse
    {
        $this->authorizeSetup($request);
        $data = $request->validate([
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:32', Rule::unique('stsams_programmes', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'admission_track' => ['required', Rule::in(array_keys(config('stsams_documents.admission_tracks')))],
        ]);
        StsamsProgramme::query()->create($data + ['is_active' => true]);
        return back()->with('success', 'Programme created. Verify its document requirements below.');
    }

    public function programmeRequirements(Request $request, StsamsProgramme $programme): RedirectResponse
    {
        $this->authorizeSetup($request);
        $types = array_keys(config('stsams_documents.types'));
        $data = $request->validate([
            'admission_track' => ['required', Rule::in(array_keys(config('stsams_documents.admission_tracks')))],
            'requirements' => ['required', 'array'],
            'requirements.*' => [Rule::in(['required', 'optional', 'conditional', 'not_applicable'])],
        ]);
        if (array_diff($types, array_keys($data['requirements'])) || array_diff(array_keys($data['requirements']), $types)) {
            throw ValidationException::withMessages(['requirements' => 'A selection is required for each configured document type.']);
        }
        // Baseline identity and O'Level documents remain required for all programmes.
        foreach (['passport', 'birth_certificate', 'origin_certificate', 'olevel_first'] as $required) {
            if ($data['requirements'][$required] !== 'required') {
                throw ValidationException::withMessages(['requirements' => 'Core admission documents must remain required.']);
            }
        }
        DB::transaction(function () use ($programme, $data): void {
            $programme->update(['admission_track' => $data['admission_track']]);
            foreach ($data['requirements'] as $type => $value) {
                StsamsDocumentRequirement::query()->updateOrCreate(
                    ['programme_id' => $programme->id, 'document_type' => $type],
                    ['requirement' => $value]
                );
            }
        });
        return back()->with('success', 'Programme requirements updated. Changes affect new and existing drafts.');
    }

    public function session(Request $request): RedirectResponse
    {
        $this->authorizeSetup($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('stsams_admission_sessions', 'name')],
            'opens_on' => ['nullable', 'date'],
            'closes_on' => ['nullable', 'date', 'after_or_equal:opens_on'],
            'application_fee_naira' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ]);
        StsamsAdmissionSession::query()->create([
            'name' => $data['name'], 'opens_on' => $data['opens_on'] ?? null,
            'closes_on' => $data['closes_on'] ?? null,
            'application_fee_kobo' => $this->toKobo($data['application_fee_naira']),
            'is_open' => false,
        ]);
        return back()->with('success', 'Session created. Application fee is set to the approved amount.');
    }

    public function setSessionFee(Request $request, StsamsAdmissionSession $session): RedirectResponse
    {
        $this->authorizeSetup($request);
        $data = $request->validate(['application_fee_naira' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/']]);
        $newFeeKobo = $this->toKobo($data['application_fee_naira']);

        DB::transaction(function () use ($session, $newFeeKobo): void {
            // Serialize concurrent administrator fee updates for this session.
            $locked = StsamsAdmissionSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->application_fee_kobo === $newFeeKobo) {
                return; // Repeated submission of the same amount is harmless.
            }

            $applications = DB::table('stsams_admission_applications')
                ->where('admission_session_id', $locked->id);

            if ($locked->application_fee_kobo !== null) {
                // An advertised/established fee cannot change after any draft exists.
                if ((clone $applications)->exists()) {
                    throw ValidationException::withMessages([
                        'application_fee_naira' => 'This session already has applications. Its established fee cannot be changed; arrange a new session or a formally approved financial adjustment.',
                    ]);
                }
            } else {
                // Legacy sessions may have drafts created before fees were introduced.
                // First-time configuration is safe only when no application has progressed
                // beyond draft and no payment attempt has been recorded at any amount.
                $progressed = (clone $applications)->where('status', '!=', 'draft')->exists();
                $paymentExists = DB::table('stsams_application_payments')
                    ->whereIn('application_id', (clone $applications)->select('id'))
                    ->exists();
                if ($progressed || $paymentExists) {
                    throw ValidationException::withMessages([
                        'application_fee_naira' => 'Cannot configure this fee while submitted applications or payment records exist. Refer this session for financial review.',
                    ]);
                }
            }

            $locked->update(['application_fee_kobo' => $newFeeKobo]);
        }, 3);

        return back()->with('success', 'Session fee saved. Existing drafts may continue at the approved fee.');
    }

    public function openSession(Request $request, StsamsAdmissionSession $session): RedirectResponse
    {
        $this->authorizeSetup($request);
        if ($session->application_fee_kobo === null) {
            throw ValidationException::withMessages(['application_fee_naira' => 'Set the approved application fee (or 0 for a waiver) before opening.']);
        }
        DB::transaction(function () use ($session): void {
            StsamsAdmissionSession::query()->update(['is_open' => false]);
            $session->update(['is_open' => true]);
        });
        return back()->with('success', 'Admission session opened.');
    }

    public function closeSession(Request $request, StsamsAdmissionSession $session): RedirectResponse
    {
        $this->authorizeSetup($request);
        $session->update(['is_open' => false]);
        return back()->with('success', 'Admission session closed.');
    }

    private function toKobo(string $naira): int
    {
        $parts = explode('.', $naira, 2);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '0', 2, '0');
    }
}
