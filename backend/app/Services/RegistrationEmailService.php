<?php

namespace App\Services;

use App\Mail\RegistrationDetailsMail;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RegistrationEmailService
{
    public function sendRegistration(Registration $registration, ?string $checkoutUrl = null): void
    {
        $registration->loadMissing('race');

        $this->deliver($registration->email, [
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

    public function sendTeam(Team $team, ?string $checkoutUrl = null): void
    {
        $team->loadMissing('registrations.race');
        $payment = $team->payments()->latest()->first();
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

        $this->deliver($team->email, [
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

    private function deliver(string $recipient, array $details): void
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('KFS registration email skipped because the recipient address is invalid.', [
                'registration_reference' => $details['reference'],
            ]);

            return;
        }

        if (config('mail.default') === 'log') {
            Log::warning('KFS registration email is not being delivered because MAIL_MAILER is set to log.', [
                'registration_reference' => $details['reference'],
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send(new RegistrationDetailsMail($details));
        } catch (Throwable $exception) {
            Log::warning('KFS registration email delivery failed.', [
                'registration_reference' => $details['reference'],
                'exception' => $exception::class,
            ]);
        }
    }
}
