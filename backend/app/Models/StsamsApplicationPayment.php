<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StsamsApplicationPayment extends Model
{
    protected $table = 'stsams_application_payments';
    protected $fillable = ['application_id', 'reference', 'gateway', 'status', 'amount_kobo', 'currency', 'payer_email', 'gateway_transaction_id', 'verified_at'];
    protected function casts(): array { return ['amount_kobo' => 'integer', 'verified_at' => 'datetime']; }
    public function application(): BelongsTo { return $this->belongsTo(StsamsAdmissionApplication::class, 'application_id'); }
}
