<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StsamsOperationsAudit extends Model
{
    public $timestamps = false;
    protected $table = 'stsams_operations_audits';
    protected $fillable = ['actor_id', 'subject_type', 'subject_id', 'action', 'metadata', 'created_at'];
    protected function casts(): array { return ['metadata' => 'array', 'created_at' => 'datetime']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
