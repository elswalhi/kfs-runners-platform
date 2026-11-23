<?php

namespace Tests\Feature;

use App\Mail\BusWalletBookingNotification;
use App\Models\BusBooking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusBookingDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_link_opens_protected_booking_and_admin_can_confirm_payment(): void
    {
        $booking = BusBooking::create(['reference' => 'test-reference', 'name' => 'Test Runner', 'phone' => '01234567890', 'amount' => 300, 'payment_status' => 'pending', 'receipt_path' => 'bus-receipts/example.jpg']);
        $payment = Payment::create(['bus_booking_id' => $booking->id, 'provider' => 'wallet', 'amount' => 300, 'currency' => 'EGP', 'status' => 'pending']);
        $url = route('admin.bus.show', $booking->id);

        $this->assertStringContainsString($url, (new BusWalletBookingNotification($booking))->render());
        $this->get($url)->assertRedirect(route('login'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Test Runner')->assertSee(route('admin.bus.receipt.preview', $booking->id))->assertSee('Confirm payment');
        $this->from($url)->patch(route('admin.bus.payment', $booking), ['status' => 'paid'])->assertRedirect($url);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('paid', $payment->fresh()->status);

        $booking->delete();
        $this->get($url)->assertOk()->assertDontSee('Confirm payment');
    }
}
