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
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AdmissionReviewController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        $actor = $request->user();
        $base = StsamsAdmissionApplication::query()->where('status', '!=', 'draft');
        $canViewAll = $actor->canAny(['applications.view_all', 'applications.offer_admission', 'applications.reject']) || $actor->hasRole('superadmin');
        if (!$canViewAll) {
            $base->where(function ($q) use ($actor): void {
                $q->where('assigned_reviewer_id', $actor->id)->orWhereNull('assigned_reviewer_id');
            });
        }
        $stats = [
            'submitted' => (clone $base)->where('status', 'submitted')->count(),
            'under_review' => (clone $base)->where('status', 'under_review')->count(),
            'offered' => (clone $base)->where('status', 'offered')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'mine' => StsamsAdmissionApplication::where('assigned_reviewer_id', $actor->id)
                ->whereIn('status', ['submitted', 'under_review'])->count(),
            'unassigned' => (clone $base)->whereNull('assigned_reviewer_id')
                ->whereIn('status', ['submitted', 'under_review'])->count(),
        ];
        $query = (clone $base)->with(['applicant', 'programme', 'admissionSession', 'reviewer'])->latest();
        $scope = $request->query('scope');
        if ($scope === 'mine') {
            $query->where('assigned_reviewer_id', $actor->id);
        } elseif ($scope === 'unassigned') {
            $query->whereNull('assigned_reviewer_id');
        }
        $statuses = ['submitted', 'under_review', 'offered', 'rejected'];
        if (in_array($request->query('status'), $statuses, true)) {
            $query->where('status', $request->query('status'));
        }
        return view('stsams.staff.index', [
            'applications' => $query->paginate(20)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function show(StsamsAdmissionApplication $application): View
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        Gate::authorize('view', $application);
        return view('stsams.staff.show', [
            'application' => $application->load(['applicant','programme','admissionSession','documents','events.actor','payments','reviewer']),
            'reviewers' => User::permission('applications.review')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function transition(Request $request, StsamsAdmissionApplication $application, AdmissionWorkflowService $workflow): RedirectResponse
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        $data = $request->validate([
            'status' => ['required', Rule::in(['under_review','offered','rejected'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $ability = match ($data['status']) {
            'under_review' => 'review',
            'offered' => 'offer',
            'rejected' => 'reject',
        };
        Gate::authorize($ability, $application);
        $workflow->transition($application, $request->user(), $data['status'], $data['note'] ?? null);
        return back()->with('success', $data['status'] === 'offered'
            ? 'Admission offer recorded. This is not enrolment confirmation.'
            : 'Application decision recorded.');
    }
}
