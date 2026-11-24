@extends('site.layout')
@section('title', __('KFS Runners Store'))
@push('styles')<link rel="stylesheet" href="{{ asset('css/kfs-shop.css') }}">@endpush
@section('content')
<main class="shop-wrap"><div class="shop-head"><div><p>{{ __('KFS RUNNERS · THE STORE') }}</p><h1>{{ __('Wear your team.') }}</h1><p class="shop-note">{{ __('Pickup only. No delivery.') }}</p></div><a class="shop-btn secondary" href="{{ route('shop.cart') }}">{{ __('View cart') }} →</a></div>
<div class="shop-grid">@forelse($products as $product)<article class="shop-card"><a href="{{ route('shop.show', $product) }}">@if($product->images->first())<img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}">@endif</a><div class="shop-card-body"><h2>{{ $product->name }}</h2><p>EGP {{ number_format($product->price, 2) }}</p><a class="shop-btn" href="{{ route('shop.show', $product) }}">{{ __('View product') }} →</a></div></article>@empty<p>{{ __('Products are coming soon.') }}</p>@endforelse</div></main>
@endsection
