<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Services;

use App\Models\StsamsAdmissionApplication;
use App\Models\StsamsApplicationPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ApplicationPaymentService
{
    public function __construct(private PaystackGateway $gateway) {}

    public function initiate(StsamsAdmissionApplication $application): string
    {
        $payment = DB::transaction(function () use ($application): StsamsApplicationPayment {
            $locked = StsamsAdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);
            $fee = $locked->admissionSession->application_fee_kobo;
            if ($locked->status !== 'draft' || !$locked->admissionSession->acceptsApplications() || $fee === null || $fee <= 0) {
                throw ValidationException::withMessages(['payment' => 'Payment is not available for this application.']);
            }
            if ($locked->hasVerifiedPayment()) {
                throw ValidationException::withMessages(['payment' => 'Your application fee has already been confirmed.']);
            }
            return $locked->payments()->create([
                'reference' => 'STSAMS-'.strtoupper((string) Str::ulid()),
                'gateway' => 'paystack',
                'status' => 'initiated',
                'amount_kobo' => $fee,
                'currency' => 'NGN',
                'payer_email' => $locked->applicant->email,
            ]);
        });
        try {
            return $this->gateway->initialize(
                $payment->payer_email, $payment->amount_kobo, $payment->reference,
                route('stsams.payments.callback')
            );
        } catch (\Throwable $error) {
            $payment->update(['status' => 'initialization_failed']);
            throw $error;
        }
    }

    public function confirm(string $reference): StsamsApplicationPayment
    {
        $payment = StsamsApplicationPayment::query()->where('reference', $reference)->firstOrFail();
        if ($payment->status === 'paid') { return $payment; }
        $data = $this->gateway->verify($reference);
        if (($data['status'] ?? null) !== 'success'
            || ($data['reference'] ?? null) !== $payment->reference
            || !isset($data['amount']) || (int) $data['amount'] !== $payment->amount_kobo
            || strtoupper((string) ($data['currency'] ?? '')) !== $payment->currency
            || strcasecmp((string) ($data['customer']['email'] ?? ''), $payment->payer_email) !== 0) {
            throw ValidationException::withMessages(['payment' => 'Payment is not confirmed. Check the amount and retry verification later.']);
        }
        // Repeated callbacks/webhooks are idempotent. Lock a row before marking paid.
        return DB::transaction(function () use ($payment, $data): StsamsApplicationPayment {
            $locked = StsamsApplicationPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === 'paid') { return $locked; }
            $transactionId = isset($data['id']) ? (string) $data['id'] : null;
            if ($transactionId !== null && StsamsApplicationPayment::query()
                ->where('gateway_transaction_id', $transactionId)->where('id', '!=', $locked->id)->exists()) {
                throw ValidationException::withMessages(['payment' => 'Duplicate gateway transaction detected.']);
            }
            $locked->update([
                'status' => 'paid',
                'gateway_transaction_id' => $transactionId,
                'verified_at' => now(),
            ]);
            return $locked;
        });
    }
}
