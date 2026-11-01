<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsProgramme;
use App\Modules\Admissions\Services\AdmissionWorkflowService;
use App\Modules\Admissions\Services\DocumentRequirements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ApplicantController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('create', StsamsAdmissionApplication::class);
        return view('stsams.applicant.index', [
            'applications' => StsamsAdmissionApplication::query()->where('user_id', $request->user()->id)
                ->with(['programme', 'admissionSession'])->latest()->get(),
            'programmes' => StsamsProgramme::query()->where('is_active', true)->orderBy('name')->get(),
            'sessions' => StsamsAdmissionSession::query()->where('is_open', true)->orderByDesc('id')->get()
                ->filter(fn (StsamsAdmissionSession $session) => $session->acceptsApplications()),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        Gate::authorize('create', StsamsAdmissionApplication::class);
        $data = $request->validate([
            'programme_id' => ['required', 'integer', Rule::exists('stsams_programmes', 'id')->where('is_active', true)],
            'admission_session_id' => ['required', 'integer', Rule::exists('stsams_admission_sessions', 'id')->where('is_open', true)],
        ]);
        $session = StsamsAdmissionSession::query()->findOrFail($data['admission_session_id']);
        if (!$session->acceptsApplications()) {
            throw ValidationException::withMessages(['admission_session_id' => 'This admission window is not currently open.']);
        }
        $application = DB::transaction(function () use ($request, $data): StsamsAdmissionApplication {
            $application = StsamsAdmissionApplication::query()->firstOrCreate(
                ['user_id' => $request->user()->id] + $data,
                ['status' => 'draft']
            );
            if ($application->wasRecentlyCreated) {
                $application->events()->create([
                    'actor_id' => $request->user()->id, 'from_status' => null, 'to_status' => 'draft',
                ]);
            }
            return $application;
        });
        return redirect()->route('stsams.applicant.edit', $application);
    }

    public function edit(StsamsAdmissionApplication $application, DocumentRequirements $requirements): View
    {
        Gate::authorize('view', $application);
        abort_unless($application->user_id === auth()->id(), 403);
        return view('stsams.applicant.edit', [
            'application' => $application->load(['programme', 'admissionSession', 'documents', 'events.actor', 'payments']),
            'documentRequirements' => $requirements->forProgramme($application->programme),
        ]);
    }

    public function update(Request $request, StsamsAdmissionApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:32'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:2000'],
            'prior_school' => ['required', 'string', 'max:200'],
            'qualification' => ['required', 'string', 'max:160'],
        ]);
        $application->update($data);
        return back()->with('success', 'Draft saved.');
    }

    public function submit(Request $request, StsamsAdmissionApplication $application, AdmissionWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('submit', $application);
        $workflow->submit($application, $request->user());
        return back()->with('success', 'Application submitted successfully.');
    }
}
