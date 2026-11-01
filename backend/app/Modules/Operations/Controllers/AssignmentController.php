<?php

declare(strict_types=1);

namespace App\Modules\Operations\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Models\User;
use App\Modules\Operations\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class AssignmentController extends Controller
{
    public function claim(Request $request, StsamsAdmissionApplication $application, AssignmentService $service): RedirectResponse
    {
        Gate::authorize('viewAny', StsamsAdmissionApplication::class);
        $service->claim($application, $request->user());
        return back()->with('success', 'Application assigned to your review queue.');
    }

    public function assign(Request $request, StsamsAdmissionApplication $application, AssignmentService $service): RedirectResponse
    {
        Gate::authorize('applications.assign');
        $data = $request->validate(['reviewer_id' => ['nullable', 'integer', Rule::exists('users', 'id')]]);
        $reviewer = isset($data['reviewer_id']) ? User::findOrFail($data['reviewer_id']) : null;
        $service->assign($application, $request->user(), $reviewer);
        return back()->with('success', 'Review assignment updated and audited.');
    }
}
