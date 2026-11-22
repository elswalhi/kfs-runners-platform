@extends('site.layout')

@section('title', __('Choose how to register') . ' · KFS Runners')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-runner.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-runner-registration.css') }}">
@endpush

@section('content')
<main class="runner-page">
    <div class="runner-shell">
        <section class="runner-card runner-entry-card" aria-labelledby="runner-entry-title">
            @if ($eventName)
                <p class="runner-event-context">{{ $eventName }}</p>
            @endif
            <h1 class="runner-title" id="runner-entry-title">{{ __('Choose how to register') }}</h1>
            <p class="runner-description">{{ __('Use an email code to sign in and reuse your runner details, or register as a guest.') }}</p>
            <div class="runner-entry-actions">
                <a class="runner-button" href="{{ $signInUrl }}">{{ __('Sign in or create an account') }}</a>
                <a class="runner-button runner-button--secondary" href="{{ $guestUrl }}">{{ __('Continue as guest') }}</a>
            </div>
            <a class="runner-text-link" href="{{ route('events.index') }}">{{ __('Back to all events') }}</a>
        </section>
    </div>
</main>
@endsection
