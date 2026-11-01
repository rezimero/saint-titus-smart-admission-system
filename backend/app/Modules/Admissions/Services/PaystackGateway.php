<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class PaystackGateway
{
    private function key(): string
    {
        $key = (string) config('stsams_payments.secret_key', '');
        if (!str_starts_with($key, 'sk_test_') && !str_starts_with($key, 'sk_live_')) {
            throw ValidationException::withMessages(['payment' => 'Online payments are not configured. Contact the admissions office.']);
        }
        if (!app()->isProduction() && str_starts_with($key, 'sk_live_')) {
            throw ValidationException::withMessages(['payment' => 'Live payments are disabled outside production. Use Paystack test keys.']);
        }
        if (app()->isProduction() && !str_starts_with($key, 'sk_live_')) {
            throw ValidationException::withMessages(['payment' => 'Production payment gateway configuration is incomplete.']);
        }
        return $key;
    }

    public function initialize(string $email, int $amountKobo, string $reference, string $callback): string
    {
        $response = Http::acceptJson()->withToken($this->key())->timeout(20)->connectTimeout(5)
            ->post(config('stsams_payments.base_url').'/transaction/initialize', [
                'email' => $email,
                'amount' => (string) $amountKobo,
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => $callback,
            ]);
        $url = $response->json('data.authorization_url');
        if (!$response->successful() || $response->json('status') !== true || !is_string($url)) {
            throw ValidationException::withMessages(['payment' => 'The payment gateway could not start the transaction. Please retry.']);
        }
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'checkout.paystack.com') {
            throw ValidationException::withMessages(['payment' => 'The payment gateway returned an invalid checkout address.']);
        }
        return $url;
    }

    public function verify(string $reference): array
    {
        if (!preg_match('/^[a-zA-Z0-9.=-]{8,80}$/', $reference)) {
            throw ValidationException::withMessages(['payment' => 'Invalid payment reference.']);
        }
        $response = Http::acceptJson()->withToken($this->key())->timeout(20)->connectTimeout(5)
            ->get(config('stsams_payments.base_url').'/transaction/verify/'.rawurlencode($reference));
        if (!$response->successful() || $response->json('status') !== true || !is_array($response->json('data'))) {
            throw ValidationException::withMessages(['payment' => 'Payment confirmation is temporarily unavailable. Please retry.']);
        }
        return $response->json('data');
    }
}
