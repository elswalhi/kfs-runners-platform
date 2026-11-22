<?php

namespace App\Mail;

use App\Models\BusBooking;
use Illuminate\Mail\Mailable;

class BusBookingConfirmationMail extends Mailable
{
    public function __construct(public BusBooking $booking)
    {
    }

    public function build(): self
    {
        return $this->subject('تأكيد حجز باص سباق زايد الخيري | KFS Runners')
            ->view('emails.bus-booking-confirmation');
    }
}
