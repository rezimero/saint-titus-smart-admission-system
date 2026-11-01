<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StsamsApplicationDocument extends Model
{
    protected $table = 'stsams_application_documents';
    protected $fillable = ['application_id', 'uploaded_by', 'type', 'path', 'original_name', 'mime_type', 'size_bytes', 'scan_status', 'sha256'];
    public function application(): BelongsTo { return $this->belongsTo(StsamsAdmissionApplication::class, 'application_id'); }
}
