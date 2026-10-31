<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StsamsApplicationEvent extends Model
{
    protected $table = 'stsams_application_events';
    protected $fillable = ['application_id', 'actor_id', 'from_status', 'to_status', 'note'];
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
