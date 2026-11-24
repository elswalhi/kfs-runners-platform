<?php
namespace App\Http\Controllers\Web;

use App\Models\AppSetting;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopProductImage;
use App\Models\ShopOrder;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    private function filters(Request $request): array
    {
        return $request->validate(['tab' => 'nullable|in:active,deleted', 'search' => 'nullable|string|max:190', 'status' => 'nullable|in:pending,paid,failed,refunded', 'size' => 'nullable|string|max:50', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
    }
    private function filteredOrders(array $filters): Builder
    {
        $query = ($filters['tab'] ?? 'active') === 'deleted' ? ShopOrder::onlyTrashed() : ShopOrder::query();
        if ($term = trim($filters['search'] ?? '')) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('reference', 'like', "%{$term}%");
                if (preg_match('/\A#?0*([0-9]+)\z/', $term, $m)) $q->orWhere('id', $m[1]);
            });
        }
        if (! empty($filters['status'])) $query->where('payment_status', $filters['status']);
        if (filled($filters['size'] ?? null)) $query->whereHas('items', fn ($items) => $items->where('size', $filters['size']));
        if (! empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);
        return $query;
    }
    public function orders(Request $request)
    {
        $filters = $this->filters($request);
        return view('admin.shop-orders', [
            'orders' => $this->filteredOrders($filters)->with('items')->latest()->paginate(25)->withQueryString(),
            'deleted' => ($filters['tab'] ?? 'active') === 'deleted', 'deletedCount' => ShopOrder::onlyTrashed()->count(),
            'totalAmount' => ShopOrder::sum('total'), 'paidAmount' => ShopOrder::where('payment_status', 'paid')->sum('total'),
            'pendingAmount' => ShopOrder::where('payment_status', 'pending')->where('status', '!=', 'cancelled')->sum('total'),
            'variants' => ShopProductVariant::with('product')->get(),
            'sizes' => \App\Models\ShopOrderItem::query()->select('size')->distinct()->pluck('size')->sortBy(function ($size) {
                $order = array_flip(['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']);
                return [$order[strtoupper(trim($size))] ?? count($order), strtoupper(trim($size))];
            })->values(),
        ]);
    }
    public function export(Request $request)
    {
        $orders = $this->filteredOrders($this->filters($request))->with('items')->latest()->cursor();
        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Order', 'Name', 'Email', 'Phone', 'Products', 'Sizes', 'Amount EGP', 'Payment status', 'Order status', 'Date']);
            foreach ($orders as $o) {
                $items = $o->items->map(fn ($i) => "{$i->product_name} ({$i->size}, {$i->color}) x{$i->quantity}")->implode('; ');
                $row = [$o->id, $o->name, $o->email, $o->phone, $items, $o->items->pluck('size')->implode('; '), $o->total, $o->payment_status, $o->status, $o->created_at?->format('Y-m-d H:i:s')];
                fputcsv($out, array_map(static fn ($v) => is_string($v) && preg_match('/\A[\s\x00-\x1F]*[=+\-@]/u', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, 'store-orders.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
    public function storeOrder(Request $request)
    {
        $data = $request->validateWithBag('manualOrder', ['name' => 'required|string|max:150|regex:/\p{L}/u', 'phone' => 'nullable|string|max:32', 'email' => 'nullable|email|max:190', 'amount' => 'nullable|numeric|min:0|max:1000000|decimal:0,2', 'payment_status' => 'nullable|in:pending,paid', 'variant_id' => 'nullable|integer|exists:shop_product_variants,id', 'quantity' => 'nullable|integer|min:1|max:100']);
        DB::transaction(function () use ($data) {
            $variant = ! empty($data['variant_id']) ? ShopProductVariant::with('product')->lockForUpdate()->findOrFail($data['variant_id']) : null;
            $quantity = (int) ($data['quantity'] ?? 1);
            if ($variant && $variant->stock < $quantity) throw \Illuminate\Validation\ValidationException::withMessages(['variant_id' => __('Not enough stock available.')])->errorBag('manualOrder');
            $amount = $data['amount'] ?? ($variant ? $variant->product->price * $quantity : 0);
            $status = $data['payment_status'] ?? 'pending';
            $order = ShopOrder::create(['reference' => 'SHOP-'.strtoupper(Str::random(32)), 'name' => trim($data['name']), 'phone' => $data['phone'] ?? '', 'email' => $data['email'] ?? '', 'total' => $amount, 'payment_method' => 'manual', 'payment_status' => $status]);
            if ($variant) {
                $variant->decrement('stock', $quantity);
                $order->items()->create(['variant_id' => $variant->id, 'product_name' => $variant->product->name, 'size' => $variant->size, 'color' => $variant->color, 'quantity' => $quantity, 'unit_price' => $variant->product->price]);
            }
            Payment::create(['shop_order_id' => $order->id, 'provider' => 'manual', 'amount' => $amount, 'currency' => 'EGP', 'status' => $status, 'verified_at' => $status === 'paid' ? now() : null, 'transaction_reference' => $order->reference]);
        });
        return redirect()->route('admin.shop.orders')->with('success', __('Order added.'));
    }
    public function destroy(ShopOrder $order) { $order->delete(); return back()->with('success', __('Order moved to deleted.')); }
    public function restore(int $order) { ShopOrder::onlyTrashed()->findOrFail($order)->restore(); return back()->with('success', __('Order restored.')); }
    public function sales(Request $request)
    {
        $data = $request->validate(['size' => 'nullable|string|max:50']);
        $sales = ShopOrder::where('payment_status', 'paid');
        if (filled($data['size'] ?? null)) $sales->whereHas('items', fn ($items) => $items->where('size', $data['size']));
        $sizes = \App\Models\ShopOrderItem::whereHas('order', fn ($order) => $order->where('payment_status', 'paid'))->select('size')->distinct()->pluck('size')->sortBy(function ($size) {
            $order = array_flip(['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']);
            return [$order[strtoupper(trim($size))] ?? count($order), strtoupper(trim($size))];
        })->values();
        return view('admin.shop-sales', ['total' => (clone $sales)->sum('total'), 'count' => (clone $sales)->count(), 'pendingWalletCount' => ShopOrder::where('payment_method', 'wallet')->where('payment_status', 'pending')->where('status', '!=', 'cancelled')->count(), 'orders' => $sales->with('items')->latest()->paginate(25)->withQueryString(), 'sizes' => $sizes]);
    }
    public function receipt(int $order)
    {
        $order = ShopOrder::withTrashed()->findOrFail($order);
        abort_unless($order->receipt_path && Storage::disk('local')->exists($order->receipt_path), 404);
        return response()->file(Storage::disk('local')->path($order->receipt_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
    public function downloadReceipt(int $order)
    {
        $order = ShopOrder::withTrashed()->findOrFail($order);
        abort_unless($order->receipt_path && Storage::disk('local')->exists($order->receipt_path), 404);
        return Storage::disk('local')->download($order->receipt_path, 'order-'.$order->id.'-receipt.'.pathinfo($order->receipt_path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
    public function order(Request $request, ShopOrder $order)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['confirm_payment', 'mark_pending', 'ready', 'collected', 'cancel'])]]);
        DB::transaction(function () use ($order, $data) {
            $order = ShopOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (in_array($data['action'], ['confirm_payment', 'mark_pending'], true)) {
                abort_unless(in_array($order->payment_method, ['wallet', 'manual'], true) && $order->status !== 'cancelled', 403);
                $status = $data['action'] === 'confirm_payment' ? 'paid' : 'pending';
                $order->update(['payment_status' => $status]);
                $order->payment()->update(['status' => $status, 'verified_at' => $status === 'paid' ? now() : null]);
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
