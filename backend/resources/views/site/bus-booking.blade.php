@extends('site.layout')
@section('title', __('Zayed Charity Race Bus') . ' · KFS Runners')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-runner.css') }}">
@endpush
@section('content')
<main class="runner-page">
    <div class="runner-shell">
        <section class="runner-card" style="max-width:620px;margin:auto">
            <p class="runner-event-context">{{ __('KFS Runners') }}</p>
            <h1 class="runner-title">{{ __('Zayed Charity Race Bus') }}</h1>
            <p class="runner-description">{{ __('Book your bus seat and complete payment through EasyKash.') }}</p>
            <p class="runner-description"><strong>{{ __('Price per seat') }}: {{ number_format($price, 2) }} EGP</strong></p>
            @if ($errors->any())
                <div class="runner-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form class="runner-form" method="POST" action="{{ route('bus.store') }}">
                @csrf
                <input type="hidden" name="displayed_price" value="{{ number_format($price, 2, '.', '') }}">
                <div class="runner-field">
                    <label for="bus-name">{{ __('Full name') }}</label>
                    <input id="bus-name" name="name" value="{{ old('name') }}" autocomplete="name" minlength="2" maxlength="150" required>
                </div>
                <div class="runner-field">
                    <label for="bus-email">{{ __('Email address') }}</label>
                    <input id="bus-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required>
                </div>
                <div class="runner-field">
                    <label for="bus-phone">{{ __('Phone number') }}</label>
                    <input id="bus-phone" name="phone" type="tel" dir="ltr" value="{{ old('phone') }}" autocomplete="tel" maxlength="32" placeholder="01012345678" required>
                    <small>{{ __('Use an Egyptian mobile number. Local and +20 formats are accepted.') }}</small>
                </div>
                <button class="runner-button" type="submit">{{ __('Continue to payment') }}</button>
            </form>
        </section>
    </div>
</main>
@endsection
