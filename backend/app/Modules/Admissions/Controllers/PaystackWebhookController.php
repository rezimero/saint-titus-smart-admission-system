<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admissions\Services\ApplicationPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, ApplicationPaymentService $payments): JsonResponse
    {
        $secret = (string) config('stsams_payments.secret_key', '');
        $signature = (string) $request->header('x-paystack-signature', '');
        if ($secret === '' || !hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if ($request->input('event') !== 'charge.success') { return response()->json(['status' => 'ignored']); }
        $reference = $request->input('data.reference');
        if (!is_string($reference) || !preg_match('/^[A-Za-z0-9.=-]{8,80}$/', $reference)) {
            return response()->json(['message' => 'Invalid reference'], 422);
        }
        try { $payments->confirm($reference); }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException) { return response()->json(['message' => 'Unknown payment'], 404); }
        catch (ValidationException) { return response()->json(['message' => 'Verification unavailable or inconsistent'], 503); }
        return response()->json(['status' => 'ok']);
    }
}
