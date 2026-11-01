<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StsamsStaffAccessAudit extends Model
{
    protected $table = 'stsams_staff_access_audits';
    protected $guarded = [];
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function staffUser(): BelongsTo { return $this->belongsTo(User::class, 'staff_user_id'); }
    protected function casts(): array
    {
        return ['before_access' => 'array', 'after_access' => 'array'];
    }
}
