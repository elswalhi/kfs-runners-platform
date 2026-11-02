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
        if (! $inquiry) {
            return;
        }

        $providerStatus = isset($inquiry['status']) && is_scalar($inquiry['status'])
            ? strtoupper(trim((string) $inquiry['status']))
            : null;
        $paymentStatus = match ($providerStatus) {
            'PAID' => 'paid',
            'FAILED', 'CANCELED', 'CANCELLED', 'EXPIRED' => 'failed',
            'REFUNDED' => 'refunded',
            null, '' => null,
            default => 'pending',
        };

        $details = array_filter([
            'payment_method' => $this->scalarValue($inquiry, ['PaymentMethod', 'paymentMethod', 'payment_method']),
            'provider' => $this->providerName($inquiry),
            'voucher' => $this->scalarValue($inquiry, ['voucher', 'Voucher', 'voucherNumber', 'voucher_number']),
            'expiryDate' => $this->scalarValue($inquiry, ['expiryDate', 'expiry_date', 'ExpiryDate']),
            'provider_reference' => $this->scalarValue($inquiry, ['providerRefNum', 'provider_reference', 'providerReference']),
            'provider_status' => $providerStatus,
            'inquire_synced' => true,
        ], static fn ($value) => $value !== null && $value !== '');

        if ($paymentStatus === null && count($details) === 1) {
            return;
        }

        DB::transaction(function () use ($payment, $paymentStatus, $providerStatus, $inquiry, $details, $registration, $team) {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment) {
                return;
            }

            $changes = [
                'payload' => array_merge($lockedPayment->payload ?? [], $details),
            ];
            if (isset($inquiry['easykashRef']) && is_scalar($inquiry['easykashRef'])) {
                $changes['gateway_reference'] = (string) $inquiry['easykashRef'];
            }

            // Inquiry details can safely expose the voucher while pending, but only a
            // provider status may change the payment ledger or registration state.
            if ($paymentStatus !== null && ! ($lockedPayment->status === 'paid' && $paymentStatus !== 'paid')) {
                $changes['status'] = $paymentStatus;
                $changes['verified_at'] = $paymentStatus === 'paid' ? now() : $lockedPayment->verified_at;
            }
            $lockedPayment->update($changes);

            if ($paymentStatus === null || ($lockedPayment->status === 'paid' && $paymentStatus !== 'paid')) {
                return;
            }

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
            'provider_reference' => $details['provider_reference'] ?? null,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
        ];
    }

    private function scalarValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                $value = trim((string) $data[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function providerName(array $data): ?string
    {
        $provider = $this->scalarValue($data, ['provider', 'Provider']);
        if ($provider !== null) {
            return $provider;
        }

        $method = $this->scalarValue($data, ['PaymentMethod', 'paymentMethod', 'payment_method']);
        if ($method && stripos($method, 'fawry') !== false) {
            return 'Fawry';
        }
        if ($method && stripos($method, 'aman') !== false) {
            return 'Aman';
        }

        return null;
    }
}
