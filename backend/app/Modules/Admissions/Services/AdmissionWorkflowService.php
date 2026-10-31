<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Services;

use App\Models\StsamsAdmissionApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdmissionWorkflowService
{
    public function submit(StsamsAdmissionApplication $application, User $actor): void
    {
        DB::transaction(function () use ($application, $actor): void {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            if ($locked->status !== 'draft' || $locked->user_id !== $actor->id) {
                throw ValidationException::withMessages(['application' => 'This application is no longer an editable draft.']);
            }
            if (!$locked->admissionSession->acceptsApplications() || !$locked->programme->is_active) {
                throw ValidationException::withMessages(['application' => 'The session or programme is no longer accepting applications.']);
            }
            foreach (['phone', 'date_of_birth', 'address', 'prior_school', 'qualification'] as $field) {
                if (blank($locked->{$field})) {
                    throw ValidationException::withMessages([$field => 'Complete all required details before submission.']);
                }
            }
            if (!$locked->documents()->exists()) {
                throw ValidationException::withMessages(['document' => 'Upload at least one supporting document before submission.']);
            }
            $locked->forceFill([
                'reference' => sprintf('STS-%s-%07d', now()->format('Y'), $locked->id),
                'status' => 'submitted', 'submitted_at' => now(),
            ])->save();
            $this->event($locked, $actor, 'draft', 'submitted', null);
        });
    }

    public function transition(StsamsAdmissionApplication $application, User $actor, string $target, ?string $note): void
    {
        DB::transaction(function () use ($application, $actor, $target, $note): void {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            $previous = $locked->status;
            $allowed = match ($target) {
                'under_review' => $previous === 'submitted' && $actor->can('applications.review'),
                'approved' => $previous === 'under_review' && $actor->can('applications.approve'),
                'rejected' => $previous === 'under_review' && $actor->can('applications.reject'),
                default => false,
            };
            if (!$allowed) {
                throw ValidationException::withMessages(['status' => 'This decision is not permitted at the current stage.']);
            }
            if ($target === 'rejected' && blank($note)) {
                throw ValidationException::withMessages(['note' => 'A reason is required for rejection.']);
            }
            $locked->update(['status' => $target]);
            $this->event($locked, $actor, $previous, $target, $note);
        });
    }

    private function event(StsamsAdmissionApplication $application, User $actor, string $from, string $to, ?string $note): void
    {
        $application->events()->create([
            'actor_id' => $actor->id, 'from_status' => $from, 'to_status' => $to, 'note' => $note,
        ]);
    }
}
