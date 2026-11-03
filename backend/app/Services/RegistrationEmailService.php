<?php

namespace App\Services;

use App\Mail\RegistrationDetailsMail;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class RegistrationEmailService
{
    public function sendRegistration(Registration $registration, ?string $checkoutUrl = null): bool
    {
        if ($registration->payment_status !== 'paid') {
            Log::info('KFS registration email skipped because payment is not confirmed.', [
                'registration_reference' => $registration->registration_code,
                'payment_status' => $registration->payment_status,
            ]);

            return false;
        }

        $registration->loadMissing('race');

        return $this->deliver($registration->email, [
            'locale' => app()->getLocale(),
            'reference' => $registration->registration_code,
            'race_number' => $registration->bib_number,
            'name' => trim($registration->first_name . ' ' . $registration->last_name),
            'race_name' => $registration->race?->name ?? '—',
            'distance' => $registration->race?->distance_label ?? '—',
            'kit' => $registration->kit_type,
            'shirt_size' => $registration->shirt_size,
            'team_name' => $registration->team_name,
            'amount' => (float) $registration->amount,
            'discount_amount' => (float) $registration->discount_amount,
            'currency' => $registration->currency,
            'payment_status' => $registration->payment_status,
            'registered_at' => $registration->created_at?->timezone('Africa/Cairo')->format('d M Y, h:i A'),
            'checkout_url' => $checkoutUrl,
            'is_group' => false,
            'runners' => [],
        ]);
    }

    public function sendTeam(Team $team, ?string $checkoutUrl = null): bool
    {
        $team->loadMissing('registrations.race');
        $payment = $team->payments()->latest()->first();

        if ($payment?->status !== 'paid') {
            Log::info('KFS team registration email skipped because payment is not confirmed.', [
                'team_reference' => $team->registration_code,
                'payment_status' => $payment?->status,
            ]);

            return false;
        }

        $paymentDetails = $payment?->payload ?? [];

        $runners = $team->registrations->map(fn (Registration $registration) => [
            'reference' => $registration->registration_code,
            'race_number' => $registration->bib_number,
            'name' => trim($registration->first_name . ' ' . $registration->last_name),
            'race_name' => $registration->race?->name ?? '—',
            'distance' => $registration->race?->distance_label ?? '—',
            'kit' => $registration->kit_type,
            'shirt_size' => $registration->shirt_size,
            'amount' => (float) $registration->amount,
        ])->all();

        return $this->deliver($team->email, [
            'locale' => app()->getLocale(),
            'reference' => $team->registration_code,
            'name' => $team->contact_name,
            'team_name' => $team->name,
            'race_name' => null,
            'distance' => null,
            'kit' => null,
            'shirt_size' => null,
            'amount' => (float) ($payment?->amount ?? 0),
            'discount_amount' => (float) ($paymentDetails['discount_amount'] ?? 0),
            'currency' => $payment?->currency ?? 'EGP',
            'payment_status' => $payment?->status ?? $team->status,
            'registered_at' => $team->created_at?->timezone('Africa/Cairo')->format('d M Y, h:i A'),
            'checkout_url' => $checkoutUrl,
            'is_group' => true,
            'runners' => $runners,
        ]);
    }

    /** Send one paid-payment confirmation, guarded against duplicate callbacks. */
    public function sendPaidPaymentConfirmation(Payment $payment): bool
    {
        $claimToken = (string) Str::uuid();
        $recipientIds = DB::transaction(function () use ($payment, $claimToken) {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment || $lockedPayment->status !== 'paid') {
                return null;
            }

            $payload = $lockedPayment->payload ?? [];
            if (filled($payload['confirmation_email_sent_at'] ?? null) || filled($payload['confirmation_email_claim'] ?? null)) {
                return null;
            }

            $payload['confirmation_email_claim'] = $claimToken;
            $lockedPayment->update(['payload' => $payload]);

            return [
                'registration_id' => $lockedPayment->registration_id,
                'team_id' => $lockedPayment->team_id,
            ];
        });

        if (! $recipientIds) {
            return false;
        }

        $sent = false;
        try {
            if ($recipientIds['registration_id']) {
                $registration = Registration::query()->with('race')->find($recipientIds['registration_id']);
                $sent = $registration ? $this->sendRegistration($registration) : false;
            } elseif ($recipientIds['team_id']) {
                $team = Team::query()->with('registrations.race')->find($recipientIds['team_id']);
                $sent = $team ? $this->sendTeam($team) : false;
            }
        } catch (Throwable $exception) {
            Log::warning('Paid KFS registration confirmation email could not be prepared.', [
                'payment_id' => $payment->id,
                'exception' => $exception::class,
            ]);
        }

        DB::transaction(function () use ($payment, $claimToken, $sent) {
            $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
            if (! $lockedPayment) {
                return;
            }

            $payload = $lockedPayment->payload ?? [];
            if (($payload['confirmation_email_claim'] ?? null) !== $claimToken) {
                return;
            }

            unset($payload['confirmation_email_claim']);
            if ($sent) {
                $payload['confirmation_email_sent_at'] = now()->toIso8601String();
            }

            $lockedPayment->update(['payload' => $payload]);
        });

        return $sent;
    }

    private function deliver(string $recipient, array $details): bool
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('KFS registration email skipped because the recipient address is invalid.', [
                'registration_reference' => $details['reference'],
            ]);

            return false;
        }

        if (config('mail.default') === 'log') {
            Log::warning('KFS registration email is not being delivered because MAIL_MAILER is set to log.', [
                'registration_reference' => $details['reference'],
            ]);

            return false;
        }

        try {
            Mail::to($recipient)->send(new RegistrationDetailsMail($details));
            return true;
        } catch (Throwable $exception) {
            Log::warning('KFS registration email delivery failed.', [
                'registration_reference' => $details['reference'],
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
