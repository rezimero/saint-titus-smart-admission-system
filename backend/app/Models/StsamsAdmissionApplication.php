<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StsamsAdmissionApplication extends Model
{
    protected $table = 'stsams_admission_applications';
    protected $fillable = [
        'user_id', 'programme_id', 'admission_session_id', 'reference', 'status',
        'phone', 'date_of_birth', 'address', 'prior_school', 'qualification', 'submitted_at',
    ];
    protected function casts(): array { return ['date_of_birth' => 'date', 'submitted_at' => 'datetime']; }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'assigned_reviewer_id'); }
    public function applicant(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function programme(): BelongsTo { return $this->belongsTo(StsamsProgramme::class, 'programme_id'); }
    public function admissionSession(): BelongsTo { return $this->belongsTo(StsamsAdmissionSession::class, 'admission_session_id'); }
    public function documents(): HasMany { return $this->hasMany(StsamsApplicationDocument::class, 'application_id'); }
    public function events(): HasMany { return $this->hasMany(StsamsApplicationEvent::class, 'application_id')->orderBy('id'); }
    public function payments(): HasMany { return $this->hasMany(StsamsApplicationPayment::class, 'application_id'); }
    public function hasVerifiedPayment(): bool { return $this->payments()->where('status', 'paid')->exists(); }
}
