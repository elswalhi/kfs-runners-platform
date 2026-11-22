@extends('admin.layout')
@section('title', __('Zayed bus bookings') . ' · KFS Runners')
@section('section', __('Zayed bus bookings'))
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <h1 class="stitch-hero-title">{{ __('Zayed bus bookings') }}</h1>
        <p class="stitch-hero-desc">{{ __('Paid seats') }}: {{ $paidCount }}</p>
    </div>
    <a class="stitch-btn stitch-btn-secondary" href="{{ route('bus.create') }}" target="_blank" rel="noopener noreferrer">{{ __('Open booking page') }} ↗</a>
</div>
@if (session('success')) <div class="stitch-callout" role="status">{{ session('success') }}</div> @endif
@if ($errors->any()) <div class="stitch-callout" role="alert">{{ $errors->first() }}</div> @endif
<section class="stitch-table-panel" style="padding:24px;margin-bottom:24px">
    <h2 class="stitch-panel-title">{{ __('Bus ticket price') }}</h2>
    <p class="stitch-panel-subtitle">{{ __('The updated price applies to new bookings.') }}</p>
    <form method="POST" action="{{ route('admin.bus.price') }}" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-top:16px">
        @csrf @method('PUT')
        <label>{{ __('Price per seat (EGP)') }}<input class="stitch-input" type="number" name="price" value="{{ old('price', number_format($price, 2, '.', '')) }}" min="1.01" max="1000000" step="0.01" required></label>
        <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Save price') }}</button>
    </form>
</section>
<section class="stitch-table-panel">
    <div class="stitch-table-scroll">
        <table class="stitch-table">
            <thead><tr><th>{{ __('Booking reference') }}</th><th>{{ __('Full name') }}</th><th>{{ __('Email address') }}</th><th>{{ __('Phone number') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Payment status') }}</th><th>{{ __('Date') }}</th></tr></thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr><td><bdi>{{ $booking->reference }}</bdi></td><td>{{ $booking->name }}</td><td>{{ $booking->email }}</td><td><bdi>{{ $booking->phone }}</bdi></td><td>EGP {{ number_format($booking->amount, 2) }}</td><td>{{ __(ucfirst($booking->payment_status)) }}</td><td>{{ $booking->created_at->format('d M Y, H:i') }}</td></tr>
                @empty
                    <tr><td colspan="7">{{ __('No bus bookings yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="stitch-pagination-wrap">{{ $bookings->links() }}</div>
</section>
@endsection
