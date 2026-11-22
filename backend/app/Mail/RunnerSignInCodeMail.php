<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RunnerSignInCodeMail extends Mailable
{
    public function __construct(
        private readonly string $code,
        private readonly int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your KFS Runners sign-in code'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.runner-sign-in-code', with: [
            'code' => $this->code,
            'expiresInMinutes' => $this->expiresInMinutes,
        ]);
    }
}
