<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class RegistrationDetailsMail extends Mailable
{
    use Queueable;

    public function __construct(public array $details)
    {
    }

    public function build()
    {
        $subject = $this->details['payment_status'] === 'paid'
            ? __('Your KFS Runners registration is confirmed')
            : __('Your KFS Runners registration details');

        $mail = $this->subject($subject)
            ->view('emails.registration-details')
            ->with(['details' => $this->details]);

        foreach ($this->details['qr_codes'] ?? [] as $qrCode) {
            $mail->attachData($qrCode['png'], 'kfs-runner-qr-' . $qrCode['reference'] . '.png', [
                'mime' => 'image/png',
            ]);
        }

        return $mail;
    }
}
