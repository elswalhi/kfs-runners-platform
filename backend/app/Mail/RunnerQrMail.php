<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class RunnerQrMail extends Mailable
{
    use Queueable;

    public function __construct(public array $details, public string $qrPng)
    {
    }

    public function build()
    {
        return $this->subject(__('KFS Runners kit pickup QR reminder'))
            ->view('emails.runner-qr')
            ->with([
                'details' => $this->details,
                'qrPng' => $this->qrPng,
            ])
            ->attachData($this->qrPng, 'kfs-runner-qr-' . $this->details['race_number'] . '.png', [
                'mime' => 'image/png',
            ]);
    }
}
