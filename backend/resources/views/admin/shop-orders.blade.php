@extends('admin.layout')
@section('title', __('Store orders'))
@section('section', __('Store orders'))
@push('styles')
<style>
    .shop-orders .stitch-table th { font-size: 12px; }
    .shop-orders .stitch-table td { font-size: 14px; }
    .shop-order-filters, .shop-manual-form { display: flex; flex-wrap: wrap; gap: 12px; padding: 20px; align-items: end; }
    .shop-order-filters label, .shop-manual-form label { display: grid; gap: 6px; flex: 1 1 150px; font-size: 13px; font-weight: 700; }
    .shop-order-filters label.shop-order-search { flex: 2 1 230px; }
    .shop-order-filters .stitch-input, .shop-order-filters .stitch-select, .shop-manual-form .stitch-input, .shop-manual-form .stitch-select { width: 100%; min-width: 0; font-size: 14px; }
    .shop-order-tabs { display: flex; gap: 8px; padding: 18px 20px 0; flex-wrap: wrap; }
    .shop-order-tabs a[aria-current="page"] { color: #fff; background: #064b3e; }
    .shop-order-actions { display: flex; gap: 7px; flex-wrap: wrap; min-width: 180px; }
    .shop-order-actions .stitch-btn { font-size: 13px; }
    .shop-receipt-preview { display: inline-flex; padding: 3px; border: 1px solid #cfddd5; border-radius: 8px; background: #fff; cursor: zoom-in; }
    .shop-receipt-preview img { display: block; width: 64px; height: 64px; object-fit: cover; border-radius: 5px; }
    .shop-receipt-dialog { max-width: min(940px, 95vw); max-height: 92vh; padding: 18px; border: 0; border-radius: 12px; background: #fff; color: #123d34; }
    .shop-receipt-dialog::backdrop { background: rgba(0, 20, 17, .8); }
    .shop-receipt-dialog header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 12px; }
    .shop-receipt-dialog img { display: block; max-width: 100%; max-height: 75vh; width: auto; height: auto; margin: auto; object-fit: contain; }
</style>
@endpush
@section('content')
<div class="shop-orders">
    <div class="stitch-hero">
        <div><h1 class="stitch-hero-title">{{ __('Store orders') }}</h1></div>
        <div><a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.sales') }}">{{ __('Store sales') }}</a> <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.index') }}">{{ __('Store') }}</a> <a class="stitch-btn stitch-btn-secondary" href="{{ route('shop.index') }}" target="_blank" rel="noopener noreferrer">{{ __('Open store') }} ↗</a></div>
    </div>
    <div class="stitch-kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:24px">
        <div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Total order value') }}</div><div class="stitch-kpi-value">EGP {{ number_format($totalAmount, 2) }}</div></div>
        <div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Confirmed payments') }}</div><div class="stitch-kpi-value">EGP {{ number_format($paidAmount, 2) }}</div></div>
        <div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Awaiting payment review') }}</div><div class="stitch-kpi-value">EGP {{ number_format($pendingAmount, 2) }}</div></div>
    </div>
    <details class="stitch-table-panel" style="padding:24px;margin-bottom:24px" @if($errors->manualOrder->any()) open @endif>
        <summary class="stitch-panel-title" style="cursor:pointer">+ {{ __('Add store order manually') }}</summary>
        <p class="stitch-panel-subtitle">{{ __('Only the name is required. Manual orders are pending by default.') }}</p>
        @if($errors->manualOrder->any())<div role="alert">@foreach($errors->manualOrder->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form class="shop-manual-form" style="padding:16px 0 0" method="POST" action="{{ route('admin.shop.orders.store') }}">
            @csrf
            <label>{{ __('Full name') }} *<input class="stitch-input" name="name" maxlength="150" value="{{ old('name') }}" required></label>
            <label>{{ __('Phone number') }}<input class="stitch-input" name="phone" maxlength="32" value="{{ old('phone') }}"></label>
            <label>{{ __('Email address') }}<input class="stitch-input" type="email" name="email" maxlength="190" value="{{ old('email') }}"></label>
            <label>{{ __('Amount') }} (EGP)<input class="stitch-input" type="number" name="amount" min="0" max="1000000" step="0.01" value="{{ old('amount') }}"></label>
            <label>{{ __('Product option') }}<select class="stitch-select" name="variant_id"><option value="">{{ __('None') }}</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected(old('variant_id') == $variant->id)>{{ $variant->product->name }} · {{ $variant->size }} · {{ $variant->color }} ({{ __('Stock') }}: {{ $variant->stock }})</option>@endforeach</select></label>
            <label>{{ __('Quantity') }}<input class="stitch-input" type="number" name="quantity" min="1" max="100" value="{{ old('quantity', 1) }}"></label>
            <label>{{ __('Payment status') }}<select class="stitch-select" name="payment_status"><option value="pending" @selected(old('payment_status', 'pending') === 'pending')>{{ __('Pending') }}</option><option value="paid" @selected(old('payment_status') === 'paid')>{{ __('Confirmed') }}</option></select></label>
            <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Add order') }}</button>
        </form>
    </details>
    <section class="stitch-table-panel">
        <nav class="shop-order-tabs" aria-label="{{ __('Store orders') }}">
            <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.orders') }}" @if(! $deleted) aria-current="page" @endif>{{ __('Active orders') }}</a>
            <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.orders', ['tab' => 'deleted']) }}" @if($deleted) aria-current="page" @endif>{{ __('Deleted orders') }} ({{ $deletedCount }})</a>
        </nav>
        <form class="shop-order-filters" method="GET" action="{{ route('admin.shop.orders') }}">
            @if($deleted)<input type="hidden" name="tab" value="deleted">@endif
            <label class="shop-order-search">{{ __('Search') }}<input class="stitch-input" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Name, order number, email or phone') }}"></label>
            <label>{{ __('Payment status') }}<select class="stitch-select" name="status"><option value="">{{ __('All payment statuses') }}</option>@foreach(['paid' => 'Confirmed', 'pending' => 'Pending'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>@endforeach</select></label>
            <label>{{ __('Size') }}<select class="stitch-select" name="size"><option value="">{{ __('All sizes') }}</option>@foreach($sizes as $size)<option value="{{ $size }}" @selected(request('size') === $size)>{{ $size }}</option>@endforeach</select></label>
            <label>{{ __('From date') }}<input class="stitch-input" type="date" name="from" value="{{ request('from') }}"></label>
            <label>{{ __('To date') }}<input class="stitch-input" type="date" name="to" value="{{ request('to') }}"></label>
            <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Filter') }}</button>
            <button class="stitch-btn stitch-btn-secondary" type="submit" formaction="{{ route('admin.shop.orders.export') }}">{{ __('Export CSV') }}</button>
            <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.orders', $deleted ? ['tab' => 'deleted'] : []) }}">{{ __('Reset') }}</a>
        </form>
        <div class="stitch-table-scroll"><table class="stitch-table">
            <thead><tr><th>{{ __('Order') }}</th><th>{{ __('Full name') }}</th><th>{{ __('Email address') }}</th><th>{{ __('Phone number') }}</th><th>{{ __('Products') }}</th><th>{{ __('Size') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Payment status') }}</th><th>{{ __('Order status') }}</th><th>{{ __('Date') }}</th><th>{{ __('Transfer receipt image') }}</th><th>{{ __('Action') }}</th></tr></thead>
            <tbody>@forelse($orders as $order)
                <tr>
                    <td><a href="{{ route('admin.shop.orders.show', $order->id) }}">#{{ $order->id }}</a></td><td>{{ $order->name }}</td><td>{{ $order->email ?: '—' }}</td><td><bdi>{{ $order->phone ?: '—' }}</bdi></td>
                    <td>@forelse($order->items as $item)<div>{{ $item->product_name }} · {{ $item->color }} × {{ $item->quantity }}</div>@empty — @endforelse</td>
                    <td>@forelse($order->items as $item)<div>{{ $item->size }}</div>@empty — @endforelse</td>
                    <td>EGP {{ number_format($order->total, 2) }}</td><td>{{ __(ucfirst($order->payment_status)) }}<br><small>{{ __($order->payment_method === 'wallet' ? 'Wallet transfer' : ($order->payment_method === 'manual' ? 'Manual booking' : 'EasyKash')) }}</small></td><td>{{ __(ucfirst($order->status)) }}</td><td>{{ $order->created_at->format('d M Y, H:i') }}</td>
                    <td>@if($order->receipt_path)<button type="button" class="shop-receipt-preview" data-receipt-src="{{ route('admin.shop.orders.receipt', $order->id) }}" data-receipt-number="#{{ $order->id }}" aria-label="{{ __('Enlarge transfer image') }} #{{ $order->id }}"><img src="{{ route('admin.shop.orders.receipt', $order->id) }}" alt="{{ __('Transfer receipt image') }} #{{ $order->id }}" loading="lazy"></button>@else — @endif</td>
                    <td><div class="shop-order-actions">
                        @if($order->receipt_path)<a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.orders.receipt.download', $order->id) }}">{{ __('Download transfer image') }}</a>@endif
                        @if($deleted)
                            <form method="POST" action="{{ route('admin.shop.orders.restore', $order->id) }}">@csrf @method('PATCH')<button class="stitch-btn stitch-btn-secondary">{{ __('Restore') }}</button></form>
                        @else
                            @if(in_array($order->payment_method, ['wallet', 'manual'], true) && in_array($order->payment_status, ['pending', 'paid'], true) && $order->status !== 'cancelled')
                                <form method="POST" action="{{ route('admin.shop.orders.update', $order->id) }}" onsubmit="return confirm(@js(__('Update this booking payment status?')))">@csrf @method('PATCH')<button class="stitch-btn stitch-btn-secondary" name="action" value="{{ $order->payment_status === 'paid' ? 'mark_pending' : 'confirm_payment' }}">{{ $order->payment_status === 'paid' ? __('Mark pending') : __('Confirm payment') }}</button></form>
                            @endif
                            @if($order->payment_method === 'wallet' && $order->payment_status === 'pending' && $order->status !== 'cancelled')<form method="POST" action="{{ route('admin.shop.orders.update', $order->id) }}" onsubmit="return confirm(@js(__('Cancel this order?')))">@csrf @method('PATCH')<button class="stitch-btn stitch-btn-secondary" name="action" value="cancel">{{ __('Cancel') }}</button></form>@endif
                            @if($order->payment_status === 'paid' && $order->status !== 'cancelled')<form method="POST" action="{{ route('admin.shop.orders.update', $order->id) }}">@csrf @method('PATCH')<button class="stitch-btn stitch-btn-secondary" name="action" value="ready">{{ __('Ready for pickup') }}</button> <button class="stitch-btn stitch-btn-secondary" name="action" value="collected">{{ __('Collected') }}</button></form>@endif
                            <form method="POST" action="{{ route('admin.shop.orders.destroy', $order->id) }}" onsubmit="return confirm(@js(__('Move this order to deleted?')))">@csrf @method('DELETE')<button class="stitch-btn stitch-btn-secondary">{{ __('Delete') }}</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty<tr><td colspan="12">{{ __('No store orders found.') }}</td></tr>@endforelse</tbody>
        </table></div>
        <div class="stitch-pagination-wrap">{{ $orders->links() }}</div>
    </section>
    <dialog id="shop-receipt-dialog" class="shop-receipt-dialog" aria-labelledby="shop-receipt-title"><header><strong id="shop-receipt-title">{{ __('Transfer receipt image') }}</strong><button type="button" class="stitch-btn stitch-btn-secondary" id="shop-receipt-close">{{ __('Close') }}</button></header><img id="shop-receipt-image" alt=""></dialog>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('shop-receipt-dialog'), image = document.getElementById('shop-receipt-image'), title = document.getElementById('shop-receipt-title');
    const label = @js(__('Transfer receipt image'));
    document.querySelectorAll('.shop-receipt-preview').forEach(button => button.addEventListener('click', () => { image.src = button.dataset.receiptSrc; image.alt = label + ' ' + button.dataset.receiptNumber; title.textContent = label + ' · ' + button.dataset.receiptNumber; dialog.showModal(); }));
    document.getElementById('shop-receipt-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => image.removeAttribute('src'));
})();
</script>
@endpush
