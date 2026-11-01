<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StsamsAdmissionApplication;
use App\Modules\Admissions\Services\ApplicationPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class PaymentController extends Controller
{
    public function initialize(Request $request, StsamsAdmissionApplication $application, ApplicationPaymentService $payments): RedirectResponse
    {
        Gate::authorize('update', $application);
        return redirect()->away($payments->initiate($application));
    }

    public function callback(Request $request, ApplicationPaymentService $payments): RedirectResponse
    {
        $reference = $request->query('reference');
        abort_unless(is_string($reference) && preg_match('/^[A-Za-z0-9.=-]{8,80}$/', $reference), 400);
        $payment = $payments->confirm($reference);
        if (auth()->check() && auth()->id() === $payment->application->user_id) {
            return redirect()->route('stsams.applicant.edit', $payment->application_id)
                ->with('success', 'Paystack payment verified. You can now submit your complete application.');
        }
        return redirect()->route('stsams.login')->with('success', 'Payment verified. Please sign in to continue your application.');
    }

    public function receipt(StsamsAdmissionApplication $application): View
    {
        Gate::authorize('view', $application);
        $payment = $application->payments()->where('status', 'paid')->latest()->firstOrFail();
        return view('stsams.applicant.receipt', ['application' => $application->load(['applicant', 'programme', 'admissionSession']), 'payment' => $payment]);
    }

    public function acknowledgement(StsamsAdmissionApplication $application): View
    {
        Gate::authorize('view', $application);
        abort_if($application->status === 'draft', 404);
        return view('stsams.applicant.acknowledgement', ['application' => $application->load(['applicant', 'programme', 'admissionSession'])]);
    }
}
