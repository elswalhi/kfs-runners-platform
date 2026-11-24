@extends('admin.layout')
@section('title', __('Store sales'))
@section('section', __('Store sales'))
@section('content')
<div class="stitch-hero"><div><h1 class="stitch-hero-title">{{ __('Store sales') }}</h1><p>{{ __('Paid orders only') }}</p></div><a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.orders') }}">{{ __('Store orders') }}</a></div>
<div class="stitch-table-panel" style="padding:18px;margin-bottom:20px">{{ __('Wallet transfers awaiting review') }}: <strong>{{ $pendingWalletCount }}</strong> · <a href="{{ route('admin.shop.orders') }}">{{ __('Review store orders') }} →</a></div>
<div class="stitch-kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:24px"><div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Paid orders') }}</div><div class="stitch-kpi-value">{{ $count }}</div></div><div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Confirmed sales') }}</div><div class="stitch-kpi-value">EGP {{ number_format($total, 2) }}</div></div></div>
<section class="stitch-table-panel">
    <form method="GET" action="{{ route('admin.shop.sales') }}" style="display:flex;align-items:end;gap:12px;flex-wrap:wrap;padding:20px">
        <label style="display:grid;gap:6px">{{ __('Size') }}<select class="stitch-select" name="size"><option value="">{{ __('All sizes') }}</option>@foreach($sizes as $size)<option value="{{ $size }}" @selected(request('size') === $size)>{{ $size }}</option>@endforeach</select></label>
        <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Filter') }}</button>
        <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.shop.sales') }}">{{ __('Reset') }}</a>
    </form>
    <div class="stitch-table-scroll"><table class="stitch-table"><thead><tr><th>{{ __('Order') }}</th><th>{{ __('Full name') }}</th><th>{{ __('Phone number') }}</th><th>{{ __('Email address') }}</th><th>{{ __('Products') }}</th><th>{{ __('Size') }}</th><th>{{ __('Payment method') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Order status') }}</th><th>{{ __('Date') }}</th></tr></thead><tbody>@forelse($orders as $order)<tr><td>#{{ $order->id }}</td><td>{{ $order->name }}</td><td><bdi>{{ $order->phone }}</bdi></td><td>{{ $order->email }}</td><td>@forelse($order->items as $item)<div>{{ $item->product_name }} · {{ $item->color }} × {{ $item->quantity }}</div>@empty — @endforelse</td><td>@forelse($order->items as $item)<div>{{ $item->size }}</div>@empty — @endforelse</td><td>{{ $order->payment_method }}</td><td>EGP {{ number_format($order->total, 2) }}</td><td>{{ $order->status }}</td><td>{{ $order->created_at->format('d M Y, H:i') }}</td></tr>@empty<tr><td colspan="10">{{ __('No paid orders yet.') }}</td></tr>@endforelse</tbody></table></div><div class="stitch-pagination-wrap">{{ $orders->links() }}</div></section>
@endsection
