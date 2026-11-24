<?php
namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use App\Models\ShopProductVariant;
use App\Services\EasyKashCashApi;
use App\Mail\ShopWalletOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopController
{
    public function index() { return view('site.shop.index', ['products' => ShopProduct::where('is_published', true)->with('images')->get()]); }
    public function image(ShopProductImage $image)
    {
        abort_unless($image->product?->is_published || (auth()->check() && auth()->user()->role === 'admin'), 404);
        abort_unless(preg_match('/\Astorage\/shop-products\/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)\z/i', $image->path), 404);
        $path = substr($image->path, strlen('storage/'));
        abort_unless(Storage::disk('public')->exists($path), 404);
        return response()->file(Storage::disk('public')->path($path), ['X-Content-Type-Options' => 'nosniff']);
    }
    public function show(ShopProduct $product) { abort_unless($product->is_published, 404); return view('site.shop.show', ['product' => $product->load(['images', 'variants'])]); }
    public function cart(Request $request)
    {
        $cart = $request->session()->get('shop_cart', []);
        $variants = ShopProductVariant::with('product.images')->whereIn('id', array_keys($cart))->get()->filter(fn ($v) => $v->product?->is_published);
        return view('site.shop.cart', compact('cart', 'variants'));
    }
    public function add(Request $request)
    {
        $data = $request->validate(['variant_id' => 'required|integer', 'quantity' => 'required|integer|min:1|max:20']);
        $variant = ShopProductVariant::with('product')->findOrFail($data['variant_id']);
        abort_unless($variant->product?->is_published, 404);
        $cart = $request->session()->get('shop_cart', []);
        $quantity = ($cart[$variant->id] ?? 0) + (int) $data['quantity'];
        if ($quantity > $variant->stock || $quantity > 20) throw ValidationException::withMessages(['quantity' => __('Not enough stock available.')]);
        $cart[$variant->id] = $quantity; $request->session()->put('shop_cart', $cart);
        return redirect()->route('shop.cart');
    }
    public function remove(Request $request, int $variant)
    {
        $cart = $request->session()->get('shop_cart', []); unset($cart[$variant]); $request->session()->put('shop_cart', $cart);
        return redirect()->route('shop.cart');
    }
    public function checkout(Request $request, EasyKashCashApi $easyKash)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'email' => 'required|email|max:190', 'phone' => 'required|string|max:32', 'payment_method' => 'required|in:wallet,easykash', 'displayed_total' => 'required|integer|min:1', 'receipt' => 'required_if:payment_method,wallet|nullable|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $cart = $request->session()->get('shop_cart', []);
        if (! $cart || count($cart) > 50) throw ValidationException::withMessages(['cart' => __('Your cart is empty.')]);
        if ($data['payment_method'] === 'easykash' && ! $easyKash->configured()) throw ValidationException::withMessages(['payment_method' => __('Online payment is currently unavailable.')]);
        $receipt = $request->hasFile('receipt') ? $request->file('receipt')->store('shop-receipts', 'local') : null;
        if ($data['payment_method'] === 'wallet' && ! is_string($receipt)) throw ValidationException::withMessages(['receipt' => __('Could not save the transfer image. Please try again.')]);
        try {
            $order = DB::transaction(function () use ($cart, $data, $receipt) {
                $ids = array_keys($cart); sort($ids);
                $variants = ShopProductVariant::with('product')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $totalCents = 0; $items = [];
                foreach ($cart as $id => $quantity) {
                    $variant = $variants->get($id);
                    if (! $variant || ! $variant->product?->is_published || ! is_int($quantity) || $quantity < 1 || $quantity > 20 || $quantity > $variant->stock) throw ValidationException::withMessages(['cart' => __('A product is no longer available in that quantity.')]);
                    $cents = (int) round((float) $variant->product->price * 100); $totalCents += $cents * $quantity;
                    $items[] = ['variant_id' => $id, 'product_name' => $variant->product->name, 'size' => $variant->size, 'color' => $variant->color, 'quantity' => $quantity, 'unit_price' => $variant->product->price];
                    $variant->decrement('stock', $quantity);
                }
                if ($totalCents <= 0 || $totalCents > 100000000) throw ValidationException::withMessages(['cart' => __('Invalid order total.')]);
                if ($totalCents !== (int) $data['displayed_total']) throw ValidationException::withMessages(['cart' => __('The price has changed. Please review your cart before ordering.')]);
                $order = ShopOrder::create(['reference' => 'SHOP-'.strtoupper(Str::random(32)), 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'total' => number_format($totalCents / 100, 2, '.', ''), 'payment_method' => $data['payment_method'], 'receipt_path' => $receipt]);
                $order->items()->createMany($items);
                Payment::create(['shop_order_id' => $order->id, 'provider' => $data['payment_method'], 'amount' => $order->total, 'currency' => 'EGP', 'status' => 'pending', 'transaction_reference' => $order->reference]);
                return $order;
            });
        } catch (\Throwable $e) { if ($receipt) Storage::disk('local')->delete($receipt); throw $e; }
        $request->session()->forget('shop_cart');
        if ($data['payment_method'] === 'wallet') {
            try {
                if (config('mail.default') === 'log') throw new \RuntimeException('Email transport is not configured.');
                Mail::to('swalhiitsme@gmail.com')->send(new ShopWalletOrderNotification($order->load('items')));
            } catch (\Throwable $e) {
                Log::warning('Shop wallet order notification could not be sent.', ['order_id' => $order->id, 'exception' => $e::class]);
            }
        }
        if ($data['payment_method'] === 'easykash') {
            try {
                $result = $easyKash->createCheckoutLink(['amount' => (float) $order->total, 'currency' => 'EGP', 'name' => $order->name, 'email' => $order->email, 'mobile' => preg_replace('/\D+/', '', $order->phone), 'customerReference' => $order->reference], rtrim(config('app.url'), '/').'/shop/payment');
                return redirect()->away($result['redirectUrl']);
            } catch (\Throwable $e) {
                return redirect()->route('shop.status', $order->reference)->with('payment_error', true);
            }
        }
        return redirect()->route('shop.status', $order->reference);
    }
    public function paymentReturn(Request $request) { $reference = $request->query('customerReference'); return redirect()->route('shop.status', is_string($reference) ? $reference : 'missing'); }
    public function status(string $reference)
    {
        $order = ShopOrder::where('reference', $reference)->with('items')->firstOrFail();
        return response()->view('site.shop.status', ['order' => $order, 'pickup' => AppSetting::get('shop_pickup_location', '')])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
