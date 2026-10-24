<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;

class PublicPaymentStatusController extends Controller
{
    public function show(string $registrationCode)
    {
        $registration = Registration::query()
            ->where('registration_code', $registrationCode)
            ->firstOrFail();
        $payment = $registration->payments()->latest()->first();
        abort_unless($payment, 404);

        $details = $payment->payload ?? [];

        return response()->json(['data' => [
            'reference' => $registration->registration_code,
            'status' => $registration->payment_status,
            'voucher' => $details['voucher'] ?? null,
            'provider' => $details['provider'] ?? null,
            'payment_method' => $details['payment_method'] ?? null,
            'expiry_date' => $details['expiryDate'] ?? null,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
        ]]);
    }
}
