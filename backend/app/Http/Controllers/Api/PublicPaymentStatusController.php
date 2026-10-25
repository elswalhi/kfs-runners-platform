<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Team;
use App\Services\EasyKashCashApi;
use Illuminate\Support\Facades\DB;

class PublicPaymentStatusController extends Controller
{
    public function show(string $registrationCode, EasyKashCashApi $easyKash)
    {
        $registration = Registration::query()
            ->where('registration_code', $registrationCode)
            ->first();

        if ($registration) {
            $payment = $registration->payments()->latest()->first();
            abort_unless($payment, 404);

            if ($payment->status === 'pending') {
                $this->syncWithEasyKash($payment, $registration, null, $easyKash);
                $registration->refresh();
                $payment->refresh();
            }

            return response()->json(['data' => $this->paymentData($registration->registration_code, $registration->payment_status, $payment)]);
        }

        $team = Team::query()->where('registration_code', $registrationCode)->firstOrFail();
        $payment = $team->payments()->latest()->first();
        abort_unless($payment, 404);

        if ($payment->status === 'pending') {
            $this->syncWithEasyKash($payment, null, $team, $easyKash);
            $team->refresh();
            $payment->refresh();
        }

        return response()->json(['data' => [
            ...$this->paymentData($team->registration_code, $payment->status, $payment),
            'team' => ['name' => $team->name, 'member_count' => $team->registrations()->count()],
        ]]);
    }

    private function syncWithEasyKash(Payment $payment, ?Registration $registration, ?Team $team, EasyKashCashApi $easyKash): void
    {
        $reference = $registration ? $registration->registration_code : $team?->registration_code;
        if (! $reference) {
            return;
        }

        $inquiry = $easyKash->inquire($reference);
        if (! $inquiry || ! isset($inquiry['status'])) {
            return;
        }

        $providerStatus = strtoupper(trim((string) $inquiry['status']));
        $paymentStatus = match ($providerStatus) {
            'PAID' => 'paid',
            'FAILED', 'CANCELED', 'CANCELLED', 'EXPIRED' => 'failed',
            'REFUNDED' => 'refunded',
            default => 'pending',
        };

        if ($paymentStatus === 'pending') {
            return;
        }

        DB::transaction(function () use ($payment, $paymentStatus, $providerStatus, $inquiry, $registration, $team) {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment || ($lockedPayment->status === 'paid' && $paymentStatus !== 'paid')) {
                return;
            }

            $lockedPayment->update([
                'status' => $paymentStatus,
                'gateway_reference' => isset($inquiry['easykashRef']) ? (string) $inquiry['easykashRef'] : $lockedPayment->gateway_reference,
                'verified_at' => $paymentStatus === 'paid' ? now() : $lockedPayment->verified_at,
                'payload' => array_merge($lockedPayment->payload ?? [], [
                    'provider_status' => $providerStatus,
                    'payment_method' => (string) ($inquiry['PaymentMethod'] ?? ''),
                    'inquire_synced' => true,
                ]),
            ]);

            if ($registration) {
                $registration->update([
                    'payment_status' => $paymentStatus,
                    'updated_at' => now(),
                ]);
            }

            if ($team) {
                $teamRegistrationChanges = [
                    'payment_status' => $paymentStatus,
                    'updated_at' => now(),
                ];
                if ($paymentStatus === 'paid') {
                    $teamRegistrationChanges['status'] = 'confirmed';
                }
                Registration::query()->where('team_id', $team->id)->update($teamRegistrationChanges);
                if ($paymentStatus === 'paid') {
                    $team->update(['status' => 'confirmed', 'updated_at' => now()]);
                }
            }
        });
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
