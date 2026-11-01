<?php

declare(strict_types=1);

namespace App\Modules\Operations\Services;

use App\Models\StsamsOperationsAudit;
use App\Models\User;

final class OperationsAudit
{
    public function record(?User $actor, string $subjectType, int $subjectId, string $action, array $metadata = []): void
    {
        // Intentional minimal metadata: never store passwords, tokens, NIN, document contents, or payment keys.
        StsamsOperationsAudit::create([
            'actor_id' => $actor?->id, 'subject_type' => $subjectType,
            'subject_id' => $subjectId, 'action' => $action,
            'metadata' => $metadata, 'created_at' => now(),
        ]);
    }
}
