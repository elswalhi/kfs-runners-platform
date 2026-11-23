<?php

namespace App\Mail;

use App\Models\BusBooking;
use Illuminate\Mail\Mailable;

class BusWalletBookingNotification extends Mailable
{
    public function __construct(public BusBooking $booking) {}

    public function build(): self
    {
        return $this->subject('حجز باص جديد يحتاج مراجعة التحويل')
            ->view('emails.bus-wallet-booking-notification');
    }
}
