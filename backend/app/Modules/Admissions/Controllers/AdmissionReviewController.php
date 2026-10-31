<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Modules\Admissions\Services\AdmissionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdmissionReviewController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        $query = StsamsAdmissionApplication::query()->with(['applicant', 'programme', 'admissionSession'])->latest();
        if ($request->filled('status') && in_array($request->query('status'), ['draft', 'submitted', 'under_review', 'approved', 'rejected'], true)) {
            $query->where('status', $request->query('status'));
        }
        return view('stsams.staff.index', ['applications' => $query->paginate(20)->withQueryString()]);
    }

    public function show(StsamsAdmissionApplication $application): View
    {
        Gate::authorize('view', $application);
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        return view('stsams.staff.show', [
            'application' => $application->load(['applicant', 'programme', 'admissionSession', 'documents', 'events.actor']),
        ]);
    }

    public function transition(Request $request, StsamsAdmissionApplication $application, AdmissionWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        $data = $request->validate([
            'status' => ['required', Rule::in(['under_review', 'approved', 'rejected'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        Gate::authorize($data['status'] === 'under_review' ? 'review' : 'decide', $application);
        // Per-outcome permission is checked again inside the locking transaction.
        $workflow->transition($application, $request->user(), $data['status'], $data['note'] ?? null);
        return back()->with('success', 'Application status updated.');
    }
}
