<?php

declare(strict_types=1);

namespace App\Modules\Operations\Services;

use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsApplicationDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DocumentReviewService
{
    public function __construct(private OperationsAudit $audit) {}

    public function decide(StsamsAdmissionApplication $application, StsamsApplicationDocument $document, User $actor, string $decision, ?string $note): void
    {
        abort_unless($actor->can('applications.review'), 403);
        abort_unless($document->application_id === $application->id, 404);
        DB::transaction(function () use ($application, $document, $actor, $decision, $note): void {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            if ($locked->status !== 'under_review' || (int) $locked->assigned_reviewer_id !== $actor->id && !$actor->hasRole('superadmin')) {
                throw ValidationException::withMessages(['review' => 'You must be the assigned reviewer of an application under review.']);
            }
            $file = StsamsApplicationDocument::where('application_id', $locked->id)->lockForUpdate()->findOrFail($document->id);
            if ($decision === 'verified' && app()->environment('production') && $file->scan_status !== 'clean') {
                throw ValidationException::withMessages(['document' => 'Malware scanning must pass before verification.']);
            }
            if ($decision === 'rejected' && blank($note)) {
                throw ValidationException::withMessages(['note' => 'State why the document is rejected.']);
            }
            $before = $file->verification_status;
            $file->forceFill([
                'verification_status' => $decision, 'verified_by' => $actor->id,
                'verified_at' => now(), 'verification_note' => $note,
            ])->save();
            $this->audit->record($actor, 'document', $file->id, 'document.'.$decision, [
                'application_id' => $locked->id, 'from' => $before, 'to' => $decision,
            ]);
        });
    }
}
