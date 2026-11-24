<?php
namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopProductImage;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminShopController
{
    public function index() { return view('admin.shop', ['products' => ShopProduct::with(['images', 'variants'])->latest()->get(), 'pickup' => AppSetting::get('shop_pickup_location', '')]); }
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:5000', 'price' => 'required|numeric|gt:0|max:1000000|decimal:0,2']);
        ShopProduct::create($data + ['is_published' => false]);
        return back()->with('success', __('Product created.'));
    }
    public function update(Request $request, ShopProduct $product)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:5000', 'price' => 'required|numeric|gt:0|max:1000000|decimal:0,2', 'is_published' => 'nullable|boolean']);
        $product->update($data + ['is_published' => false]);
        return back()->with('success', __('Product saved.'));
    }
    public function variant(Request $request, ShopProduct $product)
    {
        $data = $request->validate(['size' => 'required|string|max:50', 'color' => 'required|string|max:80', 'stock' => 'required|integer|min:0|max:1000000']);
        $product->variants()->updateOrCreate(['size' => trim($data['size']), 'color' => trim($data['color'])], ['stock' => $data['stock']]);
        return back()->with('success', __('Variant saved.'));
    }
    public function image(Request $request, ShopProduct $product)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $path = $request->file('image')->store('shop-products', 'public');
        $product->images()->create(['path' => 'storage/'.$path, 'sort_order' => $product->images()->count()]);
        return back()->with('success', __('Image added.'));
    }
    public function deleteImage(ShopProduct $product, ShopProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);
        $path = $image->path;
        $image->delete();
        if (preg_match('/\Astorage\/shop-products\/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)\z/i', $path)) Storage::disk('public')->delete(substr($path, strlen('storage/')));
        return back()->with('success', __('Image removed.'));
    }
    public function settings(Request $request)
    {
        $data = $request->validate(['pickup' => 'nullable|string|max:1000']);
        AppSetting::set('shop_pickup_location', $data['pickup'] ?? '');
        return back()->with('success', __('Pickup location saved.'));
    }
    public function orders() { return view('admin.shop-orders', ['orders' => ShopOrder::with('items')->latest()->paginate(25)]); }
    public function receipt(ShopOrder $order)
    {
        abort_unless($order->receipt_path && Storage::disk('local')->exists($order->receipt_path), 404);
        return response()->file(Storage::disk('local')->path($order->receipt_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
    public function order(Request $request, ShopOrder $order)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['confirm_payment', 'ready', 'collected', 'cancel'])]]);
        DB::transaction(function () use ($order, $data) {
            $order = ShopOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($data['action'] === 'confirm_payment') {
                abort_unless($order->payment_method === 'wallet' && $order->status !== 'cancelled', 403);
                $order->update(['payment_status' => 'paid']);
                $order->payment()->update(['status' => 'paid', 'verified_at' => now()]);
            } elseif ($data['action'] === 'cancel') {
                abort_unless($order->status !== 'cancelled' && $order->payment_status !== 'paid' && $order->payment_method === 'wallet', 403);
                foreach ($order->items as $item) ShopProductVariant::whereKey($item->variant_id)->increment('stock', $item->quantity);
                $order->update(['status' => 'cancelled']);
            } else {
                abort_unless($order->payment_status === 'paid' && $order->status !== 'cancelled', 403);
                $order->update(['status' => $data['action'] === 'ready' ? 'ready' : 'collected']);
            }
        });
        return back()->with('success', __('Order updated.'));
    }
}
