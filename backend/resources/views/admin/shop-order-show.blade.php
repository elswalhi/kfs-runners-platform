@extends('admin.layout')
@section('title', __('Store order details') . ' · KFS Runners')
@section('section', __('Store order details'))
@push('styles')
<style>
    .shop-order-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 24px; }
    .shop-order-detail-card { padding: 24px; }
    .shop-order-detail-list { display: grid; gap: 16px; margin: 20px 0; }
    .shop-order-detail-list dt { color: #698079; font-size: 13px; }
    .shop-order-detail-list dd { margin: 4px 0 0; font-weight: 700; overflow-wrap: anywhere; }
    .shop-order-detail-image { display: block; width: 100%; max-height: 70vh; object-fit: contain; background: #f2f6f3; border-radius: 8px; }
</style>
@endpush
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-info"><h1 class="stitch-hero-title">{{ __('Store order details') }}</h1><p class="stitch-hero-desc">{{ __('Order') }} #{{ $order->id }}</p></div>
    <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.orders', $order->trashed() ? ['tab' => 'deleted'] : []) }}">{{ __('Store orders') }}</a>
</div>
<div class="shop-order-detail-grid">
    <section class="stitch-table-panel shop-order-detail-card">
        <h2 class="stitch-panel-title">{{ __('Order details') }}</h2>
        @if ($order->trashed())<p>{{ __('This order has been deleted. Restore it before changing its payment status.') }}</p>@endif
        <dl class="shop-order-detail-list">
            <div><dt>{{ __('Full name') }}</dt><dd>{{ $order->name }}</dd></div>
            <div><dt>{{ __('Phone number') }}</dt><dd><bdi>{{ $order->phone ?: '—' }}</bdi></dd></div>
            <div><dt>{{ __('Email address') }}</dt><dd>{{ $order->email ?: '—' }}</dd></div>
            <div><dt>{{ __('Products') }}</dt><dd>@foreach($order->items as $item)<div>{{ $item->product_name }} · {{ $item->size }} · {{ $item->color }} × {{ $item->quantity }}</div>@endforeach</dd></div>
            <div><dt>{{ __('Amount') }}</dt><dd>EGP {{ number_format($order->total, 2) }}</dd></div>
            <div><dt>{{ __('Payment method') }}</dt><dd>{{ $order->payment_method }}</dd></div>
            <div><dt>{{ __('Payment status') }}</dt><dd>{{ __(ucfirst($order->payment_status)) }}</dd></div>
            <div><dt>{{ __('Order status') }}</dt><dd>{{ __(ucfirst($order->status)) }}</dd></div>
            <div><dt>{{ __('Date') }}</dt><dd>{{ $order->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>
        @if (! $order->trashed() && in_array($order->payment_method, ['wallet', 'manual'], true) && in_array($order->payment_status, ['pending', 'paid'], true) && $order->status !== 'cancelled')
            @if($order->payment_method === 'wallet' && $order->payment_status === 'pending')<p>{{ __('Check your wallet and the transfer image before confirming payment.') }}</p>@endif
            <form method="POST" action="{{ route('admin.shop.orders.update', $order->id) }}" onsubmit="return confirm(@js(__('Update this booking payment status?'))) ">
                @csrf @method('PATCH')<button class="stitch-btn stitch-btn-primary" name="action" value="{{ $order->payment_status === 'paid' ? 'mark_pending' : 'confirm_payment' }}" type="submit">{{ $order->payment_status === 'paid' ? __('Mark pending') : __('Confirm payment') }}</button>
            </form>
        @endif
    </section>
    <section class="stitch-table-panel shop-order-detail-card">
        <h2 class="stitch-panel-title">{{ __('Transfer receipt image') }}</h2>
        @if ($order->receipt_path)
            <a href="{{ route('admin.shop.orders.receipt', $order->id) }}" target="_blank" rel="noopener noreferrer"><img class="shop-order-detail-image" src="{{ route('admin.shop.orders.receipt', $order->id) }}" alt="{{ __('Transfer receipt image') }}"></a>
        @else
            <p>{{ __('No transfer image available.') }}</p>
        @endif
    </section>
</div>
@endsection
