@extends('site.layout')
@section('title', __('Your order'))
@push('styles')<link rel="stylesheet" href="{{ asset('css/kfs-shop.css') }}">@endpush
@section('content')
<main class="shop-wrap"><section class="shop-panel"><h1>{{ __('Your order') }}</h1>@if(session('payment_error'))<div class="shop-errors">{{ __('We could not confirm a payment link was created. Contact the team with your order reference before ordering again.') }}</div>@endif<p>{{ __('Order reference') }}: <bdi>{{ $order->reference }}</bdi></p><p>{{ __('Payment status') }}: {{ __(ucfirst($order->payment_status)) }}</p>@foreach($order->items as $item)<div class="shop-line"><span>{{ $item->product_name }} · {{ $item->size }} · {{ $item->color }} × {{ $item->quantity }}</span><b>EGP {{ number_format($item->quantity * $item->unit_price, 2) }}</b></div>@endforeach<h2>EGP {{ number_format($order->total, 2) }}</h2><p class="shop-info">{{ __('Pickup only. No delivery.') }}<br>{{ $pickup ?: __('Pickup location will be announced later.') }}</p><p>{{ __('Keep your order reference for pickup.') }}</p></section></main>
@endsection
