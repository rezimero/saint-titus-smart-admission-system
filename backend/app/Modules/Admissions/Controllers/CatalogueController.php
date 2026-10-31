<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionSession;
use App\Models\StsamsProgramme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CatalogueController extends Controller
{
    private function authorizeSetup(Request $request): void
    {
        abort_unless($request->user()?->can('settings.manage'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeSetup($request);
        return view('stsams.admin.catalogue', [
            'programmes' => StsamsProgramme::query()->orderBy('name')->get(),
            'sessions' => StsamsAdmissionSession::query()->orderByDesc('id')->get(),
        ]);
    }

    public function programme(Request $request): RedirectResponse
    {
        $this->authorizeSetup($request);
        $data = $request->validate([
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:32', Rule::unique('stsams_programmes', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);
        StsamsProgramme::query()->create($data + ['is_active' => true]);
        return back()->with('success', 'Programme created.');
    }

    public function session(Request $request): RedirectResponse
    {
        $this->authorizeSetup($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('stsams_admission_sessions', 'name')],
            'opens_on' => ['nullable', 'date'],
            'closes_on' => ['nullable', 'date', 'after_or_equal:opens_on'],
        ]);
        StsamsAdmissionSession::query()->create($data + ['is_open' => false]);
        return back()->with('success', 'Admission session created. Open it when ready.');
    }

    public function openSession(Request $request, StsamsAdmissionSession $session): RedirectResponse
    {
        $this->authorizeSetup($request);
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
}
