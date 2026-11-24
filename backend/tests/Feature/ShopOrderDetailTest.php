<?php

namespace Tests\Feature;

use App\Mail\ShopWalletOrderNotification;
use App\Models\Payment;
use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_email_links_to_admin_protected_order_details_and_confirmation(): void
    {
        $order = ShopOrder::create([
            'reference' => 'SHOP-test-reference', 'name' => 'Test Buyer', 'email' => 'buyer@example.test',
            'phone' => '01234567890', 'total' => 350, 'payment_method' => 'wallet', 'payment_status' => 'pending', 'status' => 'pending',
            'receipt_path' => 'shop-receipts/test.jpg',
        ]);
        Payment::create(['shop_order_id' => $order->id, 'provider' => 'wallet', 'amount' => 350, 'currency' => 'EGP', 'status' => 'pending', 'transaction_reference' => $order->reference]);
        $url = route('admin.shop.orders.show', $order->id);
        $this->assertStringContainsString($url, (new ShopWalletOrderNotification($order))->render());
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get($url)->assertOk()->assertSee('Test Buyer')->assertSee('Confirm payment')->assertSee(route('admin.shop.orders.receipt', $order->id));
        $this->from($url)->patch(route('admin.shop.orders.update', $order->id), ['action' => 'confirm_payment'])->assertRedirect($url);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->get($url)->assertOk()->assertSee('Mark pending');
        $order->delete();
        $this->get($url)->assertOk()->assertSee('This order has been deleted.')->assertDontSee('Mark pending')->assertDontSee('Confirm payment');
    }
}
