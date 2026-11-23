@extends('admin.layout')
@section('title', __('Bus booking details') . ' · KFS Runners')
@section('section', __('Bus booking details'))
@push('styles')
<style>
    .bus-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 24px; }
    .bus-detail-card { padding: 24px; }
    .bus-detail-list { display: grid; gap: 16px; margin: 20px 0; }
    .bus-detail-list dt { color: #698079; font-size: 13px; }
    .bus-detail-list dd { margin: 4px 0 0; font-weight: 700; overflow-wrap: anywhere; }
    .bus-detail-image { display: block; width: 100%; max-height: 70vh; object-fit: contain; background: #f2f6f3; border-radius: 8px; }
    .bus-detail-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
</style>
@endpush
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <h1 class="stitch-hero-title">{{ __('Bus booking details') }}</h1>
        <p class="stitch-hero-desc"><bdi>{{ $booking->booking_number }}</bdi></p>
    </div>
    <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.bus.index', $booking->trashed() ? ['tab' => 'deleted'] : []) }}">{{ __('Back to bus bookings') }}</a>
</div>
<div class="bus-detail-grid">
    <section class="stitch-table-panel bus-detail-card">
        <h2 class="stitch-panel-title">{{ __('Booking details') }}</h2>
        @if ($booking->trashed())<p>{{ __('This booking has been deleted. Restore it before changing its payment status.') }}</p>@endif
        <dl class="bus-detail-list">
            <div><dt>{{ __('Full name') }}</dt><dd>{{ $booking->name }}</dd></div>
            <div><dt>{{ __('Booking reference') }}</dt><dd><bdi>{{ $booking->booking_number }}</bdi></dd></div>
            <div><dt>{{ __('Phone number') }}</dt><dd><bdi>{{ $booking->phone }}</bdi></dd></div>
            <div><dt>{{ __('Amount') }}</dt><dd>EGP {{ number_format($booking->amount, 2) }}</dd></div>
            <div><dt>{{ __('Payment status') }}</dt><dd>{{ __(ucfirst($booking->payment_status)) }}</dd></div>
            <div><dt>{{ __('Date') }}</dt><dd>{{ $booking->created_at->format('d M Y, H:i') }}</dd></div>
        </dl>
        @if (! $booking->trashed() && $booking->payment?->provider === 'wallet' && in_array($booking->payment_status, ['pending', 'paid'], true))
            <p>{{ __('Check your wallet and the transfer image before confirming payment.') }}</p>
            <form method="POST" action="{{ route('admin.bus.payment', $booking->id) }}" onsubmit="return confirm(@js(__('Update this booking payment status?')))">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $booking->payment_status === 'paid' ? 'pending' : 'paid' }}">
                <button class="stitch-btn stitch-btn-primary" type="submit">{{ $booking->payment_status === 'paid' ? __('Mark pending') : __('Confirm payment') }}</button>
            </form>
        @endif
    </section>
    <section class="stitch-table-panel bus-detail-card">
        <h2 class="stitch-panel-title">{{ __('Transfer receipt image') }}</h2>
        @if ($booking->receipt_path)
            <a href="{{ route('admin.bus.receipt.preview', $booking->id) }}" target="_blank" rel="noopener noreferrer">
                <img class="bus-detail-image" src="{{ route('admin.bus.receipt.preview', $booking->id) }}" alt="{{ __('Transfer receipt image') }} {{ $booking->booking_number }}">
            </a>
            <div class="bus-detail-actions"><a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.bus.receipt', $booking->id) }}">{{ __('Download transfer image') }}</a></div>
        @else
            <p>{{ __('No transfer image available.') }}</p>
        @endif
    </section>
</div>
@endsection
