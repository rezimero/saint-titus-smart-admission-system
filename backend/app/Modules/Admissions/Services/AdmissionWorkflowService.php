<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Services;

use App\Models\StsamsAdmissionApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Modules\Operations\Services\OperationsAudit;

final class AdmissionWorkflowService
{
    public function __construct(private DocumentRequirements $requirements) {}

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
            $missing = $this->requirements->missing($locked);
            if ($missing !== []) {
                throw ValidationException::withMessages(['document' => 'Upload these required documents: '.implode(', ', $missing)]);
            }
            if (app()->isProduction() && $locked->documents()->where('scan_status', '!=', 'clean')->exists()) {
                throw ValidationException::withMessages(['document' => 'Uploaded documents must pass malware scanning before submission.']);
            }
            $fee = $locked->admissionSession->application_fee_kobo;
            if ($fee === null) {
                throw ValidationException::withMessages(['payment' => 'The institution has not configured the application fee.']);
            }
            if ($fee > 0 && !$locked->payments()->where('status', 'paid')->where('amount_kobo', $fee)->exists()) {
                throw ValidationException::withMessages(['payment' => 'Complete and verify the application fee payment before submitting.']);
            }
            $locked->forceFill([
                'reference' => sprintf('STS-%s-%07d', now()->format('Y'), $locked->id),
                'status' => 'submitted', 'submitted_at' => now(),
            ])->save();
            $locked->events()->create([
                'actor_id' => $actor->id, 'from_status' => 'draft', 'to_status' => 'submitted', 'note' => null,
            ]);
        });
    }

    public function transition(StsamsAdmissionApplication $application, User $actor, string $target, ?string $note): void
    {
        DB::transaction(function () use ($application, $actor, $target, $note): void {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            $previous = $locked->status;
            $permitted = match ($target) {
                'under_review' => $previous === 'submitted' && $actor->can('applications.review')
                    && ((int) $locked->assigned_reviewer_id === $actor->id || $actor->hasRole('superadmin')),
                'offered' => $previous === 'under_review' && $actor->can('applications.offer_admission'),
                'rejected' => $previous === 'under_review' && $actor->can('applications.reject'),
                default => false,
            };
            if (!$permitted) {
                throw ValidationException::withMessages(['status' => 'This decision is not permitted at the current stage.']);
            }
            if ($target === 'under_review' && $locked->assigned_reviewer_id === null && $actor->hasRole('superadmin')) {
                $locked->forceFill(['assigned_reviewer_id' => $actor->id, 'assigned_by' => $actor->id, 'assigned_at' => now()])->save();
            }
            if ($target === 'offered') {
                if (blank($note)) {
                    throw ValidationException::withMessages(['note' => 'Record a documented reason for the admission offer.']);
                }
                $total = $locked->documents()->count();
                if ($total === 0 || $locked->documents()->where('verification_status', '!=', 'verified')->exists()) {
                    throw ValidationException::withMessages(['document' => 'Every submitted document must be reviewed and verified before an offer.']);
                }
                if (app()->environment('production') && $locked->documents()->where('scan_status', '!=', 'clean')->exists()) {
                    throw ValidationException::withMessages(['document' => 'Document malware scanning is incomplete.']);
                }
                $reviewerId = $locked->events()->where('to_status', 'under_review')->latest('id')->value('actor_id');
                if (!$actor->hasRole('superadmin') && (int) $reviewerId === $actor->id) {
                    throw ValidationException::withMessages(['status' => 'A different authorized officer must issue the offer (four-eyes control).']);
                }
            }
            if ($target === 'rejected' && blank($note)) {
                throw ValidationException::withMessages(['note' => 'A reason is required for rejection.']);
            }
            $locked->update(['status' => $target]);
            $locked->events()->create([
                'actor_id' => $actor->id,
                'from_status' => $previous, 'to_status' => $target, 'note' => $note,
            ]);
            app(OperationsAudit::class)->record($actor, 'application', $locked->id, 'application.'.$target, [
                'from' => $previous, 'to' => $target,
            ]);
        });
    }
}
