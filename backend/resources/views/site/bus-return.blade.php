@extends('site.layout')
@section('title', __('Bus booking') . ' · KFS Runners')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-runner.css') }}">
@endpush
@section('content')
<main class="runner-page">
    <div class="runner-shell">
        <section class="runner-card" style="max-width:620px;margin:auto" aria-live="polite">
            <h1 class="runner-title">{{ __('Bus booking') }}</h1>
            @if ($booking)
                <p class="runner-description">{{ __('Booking reference') }}: <bdi>{{ $booking->reference }}</bdi></p>
                <p class="runner-description">{{ __('Amount') }}: {{ number_format($booking->amount, 2) }} EGP</p>
                <p class="runner-description">{{ __('Payment status') }}: <strong id="bus-payment-status">{{ __($booking->payment_status === 'paid' ? 'Paid' : ($booking->payment_status === 'failed' ? 'Failed' : 'Pending')) }}</strong></p>
                @if ($voucher)
                    <p class="runner-description">{{ __('Payment code') }}: <strong dir="ltr">{{ $voucher }}</strong></p>
                    <p class="runner-description">{{ __('Use this code with your selected cash payment provider.') }}</p>
                @endif
                @if (session('checkout_uncertain'))
                    <p class="runner-description">{{ __('We could not confirm that a payment link was created. Contact the team with your booking reference before booking again.') }}</p>
                @else
                    <p class="runner-description">{{ __('Keep your booking reference. Payment is confirmed when EasyKash sends confirmation.') }}</p>
                @endif
            @else
                <p class="runner-description">{{ __('Booking reference not found. Please contact the team if you have already paid.') }}</p>
            @endif
            <a class="runner-text-link" href="{{ route('home') }}">{{ __('Back to home') }}</a>
        </section>
    </div>
</main>
@if ($booking && $booking->payment_status === 'pending')
    @push('scripts')
    <script>
        (() => {
            const status = document.getElementById('bus-payment-status');
            const statusUrl = @js(route('bus.status', $booking->reference, false));
            const labels = {paid: @js(__('Paid')), failed: @js(__('Failed')), pending: @js(__('Pending')), refunded: @js(__('Refunded'))};
            async function updateStatus() {
                try {
                    const response = await fetch(statusUrl, {cache: 'no-store', headers: {Accept: 'application/json'}});
                    if (!response.ok) return;
                    const result = await response.json();
                    status.textContent = labels[result.status] || labels.pending;
                    if (result.status !== 'pending') clearInterval(timer);
                } catch (_) {}
            }
            const timer = setInterval(updateStatus, 10000);
            updateStatus();
        })();
    </script>
    @endpush
@endif
@endsection
