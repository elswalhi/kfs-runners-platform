<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Team;

class PublicPaymentStatusController extends Controller
{
    public function show(string $registrationCode)
    {
        $registration = Registration::query()
            ->where('registration_code', $registrationCode)
            ->first();
        if ($registration) {
            $payment = $registration->payments()->latest()->first();
            abort_unless($payment, 404);

            return response()->json(['data' => $this->paymentData($registration->registration_code, $registration->payment_status, $payment)]);
        }

        $team = Team::query()->where('registration_code', $registrationCode)->firstOrFail();
        $payment = $team->payments()->latest()->first();
        abort_unless($payment, 404);

        return response()->json(['data' => [
            ...$this->paymentData($team->registration_code, $payment->status, $payment),
            'team' => ['name' => $team->name, 'member_count' => $team->registrations()->count()],
        ]]);
    }

    private function paymentData(string $reference, string $status, $payment): array
    {
        $details = $payment->payload ?? [];

        return [
            'reference' => $reference,
            'status' => $status,
            'voucher' => $details['voucher'] ?? null,
            'provider' => $details['provider'] ?? null,
            'payment_method' => $details['payment_method'] ?? null,
            'expiry_date' => $details['expiryDate'] ?? null,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
        ];
    }
}
