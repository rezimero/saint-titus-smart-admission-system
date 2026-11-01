<?php

declare(strict_types=1);

namespace App\Modules\Operations\Services;

use App\Models\StsamsAdmissionApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignmentService
{
    public function __construct(private OperationsAudit $audit) {}

    public function claim(StsamsAdmissionApplication $application, User $actor): void
    {
        abort_unless($actor->can('applications.review'), 403);
        DB::transaction(function () use ($application, $actor): void {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            if (!in_array($locked->status, ['submitted', 'under_review'], true) || $locked->assigned_reviewer_id !== null) {
                throw ValidationException::withMessages(['assignment' => 'The application is already assigned or unavailable.']);
            }
            $locked->forceFill(['assigned_reviewer_id' => $actor->id, 'assigned_by' => $actor->id, 'assigned_at' => now()])->save();
            $this->audit->record($actor, 'application', $locked->id, 'review.claimed', ['reviewer_id' => $actor->id]);
        });
    }

    public function assign(StsamsAdmissionApplication $application, User $actor, ?User $reviewer): void
    {
        abort_unless($actor->can('applications.assign'), 403);
        if ($reviewer && (!$reviewer->can('applications.review') || $reviewer->hasRole('applicant'))) {
            throw ValidationException::withMessages(['reviewer_id' => 'Reviewer must have admissions review authority.']);
        }
        DB::transaction(function () use ($application, $actor, $reviewer): void {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            if (!in_array($locked->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['assignment' => 'Only active review cases may be assigned.']);
            }
            $before = $locked->assigned_reviewer_id;
            $locked->forceFill([
                'assigned_reviewer_id' => $reviewer?->id, 'assigned_by' => $actor->id,
                'assigned_at' => $reviewer ? now() : null,
            ])->save();
            $this->audit->record($actor, 'application', $locked->id, 'review.assigned', [
                'from_reviewer_id' => $before, 'to_reviewer_id' => $reviewer?->id,
            ]);
        });
    }
}
