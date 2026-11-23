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

    public function test_admin_can_create_and_confirm_a_manual_booking_with_only_a_name(): void
    {
        $this->post(route('admin.bus.store'), ['name' => 'Manual Runner'])->assertRedirect();
        $this->assertDatabaseCount('bus_bookings', 0);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.bus.store'), [])->assertSessionHasErrorsIn('manualBooking', 'name');
        $this->post(route('admin.bus.store'), ['name' => 'Manual Runner'])->assertRedirect(route('admin.bus.index'));
        $booking = BusBooking::where('name', 'Manual Runner')->firstOrFail();
        $this->assertSame('pending', $booking->payment_status);
        $this->assertEquals(350, $booking->amount);
        $this->assertSame('manual', $booking->payment->provider);
        $this->get(route('admin.bus.index'))->assertSee('Manual Runner')->assertSee('Manual booking');
        $this->patch(route('admin.bus.payment', $booking), ['status' => 'paid']);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('paid', $booking->payment()->first()->status);
    }

    public function test_status_filter_applies_to_the_list_and_export(): void
    {
        BusBooking::create(['reference' => 'paid-ref', 'name' => 'Paid Runner', 'phone' => '01234567890', 'amount' => 300, 'payment_status' => 'paid']);
        BusBooking::create(['reference' => 'pending-ref', 'name' => 'Pending Runner', 'phone' => '01234567890', 'amount' => 300, 'payment_status' => 'pending']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('admin.bus.index', ['status' => 'paid']))->assertOk()->assertSee('Paid Runner')->assertDontSee('Pending Runner')->assertSee('Confirmed')->assertDontSee('>Failed<', false)->assertDontSee('>Refunded<', false);
        $this->get(route('admin.bus.index', ['status' => 'pending']))->assertOk()->assertSee('Pending Runner')->assertDontSee('Paid Runner');
        $response = $this->get(route('admin.bus.export', ['status' => 'paid']));
        $this->assertStringContainsString('Paid Runner', $response->streamedContent());
        $this->assertStringNotContainsString('Pending Runner', $response->streamedContent());
    }

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
