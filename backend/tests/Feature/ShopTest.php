<?php
namespace Tests\Feature;

use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\ShopWalletOrderNotification;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_sizes_are_shown_from_smallest_to_largest(): void
    {
        $product = ShopProduct::firstOrFail();
        $product->update(['is_published' => true]);
        $this->get(route('shop.show', $product))->assertOk()->assertSeeInOrder([
            'XS · Teal', 'S · Teal', 'M · Teal', 'L · Teal', 'XL · Teal', 'XXL · Teal',
        ]);
    }

    public function test_signed_easykash_callback_updates_the_store_order_without_the_browser(): void
    {
        config(['easykash.api_key' => 'test-key', 'easykash.hmac_secret' => 'test-secret']);
        Http::fake(['*' => Http::response(['redirectUrl' => 'https://easykash.net/pay/test'], 200)]);
        $product = ShopProduct::with('variants')->firstOrFail();
        $product->update(['is_published' => true]);
        $this->post(route('shop.cart.add'), ['variant_id' => $product->variants->first()->id, 'quantity' => 1]);
        $this->post(route('shop.checkout'), ['name' => 'Test Customer', 'email' => 'customer@example.test', 'phone' => '01234567890', 'payment_method' => 'easykash', 'displayed_total' => 35000])->assertRedirect('https://easykash.net/pay/test');
        $order = ShopOrder::firstOrFail();
        $payload = ['ProductCode' => 'shop', 'Amount' => '350.00', 'ProductType' => 'store', 'PaymentMethod' => 'card', 'status' => 'PAID', 'easykashRef' => 'gateway-ref', 'customerReference' => $order->reference];
        $payload['signatureHash'] = hash_hmac('sha512', implode('', $payload), 'test-secret');
        $invalid = $payload; $invalid['signatureHash'] = str_repeat('0', 128);
        $this->postJson('/api/payments/easykash/callback', $invalid)->assertUnauthorized();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->postJson('/api/payments/easykash/callback', $payload)->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', $order->payment()->first()->status);
    }

    public function test_wallet_checkout_reserves_stock_and_admin_confirms_payment(): void
    {
        Storage::fake('local');
        Mail::fake();
        $product = ShopProduct::with('variants')->firstOrFail();
        $this->assertFalse($product->is_published);
        $product->update(['is_published' => true]);
        $variant = $product->variants->first();
        $this->get(route('shop.show', $product))->assertOk()->assertSee('350.00');
        $this->post(route('shop.cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])->assertRedirect(route('shop.cart'));
        $this->get(route('shop.cart'))->assertOk()->assertSee('700.00');
        $response = $this->post(route('shop.checkout'), ['name' => 'Test Customer', 'email' => 'customer@example.test', 'phone' => '01234567890', 'payment_method' => 'wallet', 'displayed_total' => 70000, 'receipt' => UploadedFile::fake()->image('receipt.jpg')]);
        $order = ShopOrder::with('payment')->firstOrFail();
        Mail::assertSent(ShopWalletOrderNotification::class, fn ($mail) => $mail->hasTo('swalhiitsme@gmail.com') && $mail->order->id === $order->id);
        $response->assertRedirect(route('shop.status', $order->reference));
        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertEquals(700, $order->total);
        $this->assertSame('pending', $order->payment_status);
        $this->get(route('admin.shop.orders.receipt', $order))->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.shop.index'))->assertOk()->assertSee('KFS Runners Official Team T-Shirt');
        $this->get(route('admin.shop.orders'))->assertOk()->assertSee('Test Customer');
        $this->get(route('admin.shop.sales'))->assertOk()->assertDontSee('Test Customer');
        $this->patch(route('admin.shop.orders.update', $order), ['action' => 'confirm_payment'])->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', $order->payment()->first()->status);
        $this->get(route('admin.shop.sales'))->assertOk()->assertSee('Test Customer')->assertSee('700.00');
    }

    public function test_store_rejects_quantity_that_exceeds_inventory(): void
    {
        $product = ShopProduct::with('variants')->firstOrFail();
        $product->update(['is_published' => true]);
        $variant = $product->variants->first();
        $this->post(route('shop.cart.add'), ['variant_id' => $variant->id, 'quantity' => 11])->assertSessionHasErrors('quantity');
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_price_changes_require_checkout_review(): void
    {
        Storage::fake('local');
        $product = ShopProduct::with('variants')->firstOrFail();
        $product->update(['is_published' => true]);
        $variant = $product->variants->first();
        $this->post(route('shop.cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $product->update(['price' => 400]);
        $this->post(route('shop.checkout'), ['name' => 'Customer', 'email' => 'customer@example.test', 'phone' => '01234567890', 'payment_method' => 'wallet', 'displayed_total' => 35000, 'receipt' => UploadedFile::fake()->image('receipt.jpg')])->assertSessionHasErrors('cart');
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertDatabaseCount('shop_orders', 0);
    }
}
