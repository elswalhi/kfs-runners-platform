@extends('site.layout')

@section('title', __('Runner account') . ' · KFS Runners')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-runner.css') }}">
@endpush

@section('content')
    @php
        $signInUrl = route('runner.login', array_filter([
            'event' => $eventSlug,
            'race' => $raceSlug,
            'kit' => $kit,
            'return' => $returnToRegistration ? 'registration' : null,
        ]));
    @endphp

    <main class="runner-page runner-page--auth">
        <div class="runner-shell runner-auth">
            <section class="runner-card runner-auth-card" aria-labelledby="runner-auth-title">
                @if ($event)
                    <p class="runner-event-context">{{ $event->translated('title') }}</p>
                @endif

                @if ($challengeEmail)
                    <h1 id="runner-auth-title" class="runner-title">{{ __('Check your email') }}</h1>
                    <p id="runner-code-help" class="runner-description">
                        {{ __('Enter the 6-digit sign-in code for') }} <bdi>{{ $challengeEmail }}</bdi>.
                        {{ __('The code expires after 10 minutes.') }}
                    </p>
                @else
                    <h1 id="runner-auth-title" class="runner-title">{{ __('Sign in or create an account') }}</h1>
                    <p class="runner-description">{{ __('Enter your email and we’ll send you a code to sign in.') }}</p>
                @endif

                @include('site.runner.messages')

                @if ($challengeEmail)
                    <form class="runner-form" action="{{ route('runner.code.verify') }}" method="POST">
                        @csrf
                        @include('site.runner.flow-fields')
                        <div class="runner-field">
                            <label for="runner-code">{{ __('Sign-in code') }}</label>
                            <input id="runner-code" class="runner-code" name="code" type="text" dir="ltr"
                                inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9٠-٩۰-۹]{6}"
                                aria-describedby="runner-code-help" @if ($errors->has('code')) aria-invalid="true" @endif required>
                        </div>
                        <button class="runner-button" type="submit">{{ __('Verify and continue') }}</button>
                    </form>

                    <div class="runner-auth-links">
                        <a class="runner-text-link" href="{{ $signInUrl }}">{{ __('Use a different email') }}</a>
                        <form action="{{ route('runner.code.send') }}" method="POST">
                            @csrf
                            @include('site.runner.flow-fields')
                            <button class="runner-text-link" type="submit" @if ($resendAt) data-resend-seconds="{{ max(0, $resendAt->timestamp - now()->timestamp) }}" data-resend-wait="{{ __('Resend in :seconds s', ['seconds' => '{seconds}']) }}" @endif>
                                {{ __('Resend code') }}
                            </button>
                        </form>
                    </div>
                @else
                    <form class="runner-form" action="{{ route('runner.code.send') }}" method="POST">
                        @csrf
                        @include('site.runner.flow-fields')
                        <div class="runner-field">
                            <label for="runner-email">{{ __('Email address') }}</label>
                            <input id="runner-email" name="email" type="email" dir="ltr" value="{{ old('email') }}"
                                autocomplete="email" maxlength="190" spellcheck="false"
                                @if ($errors->has('email')) aria-invalid="true" @endif required>
                        </div>
                        <button class="runner-button" type="submit">{{ __('Send sign-in code') }}</button>
                    </form>
                @endif

                @if ($returnUrl)
                    <div class="runner-auth-guest">
                        <a class="runner-button runner-button--secondary" href="{{ $returnUrl }}">{{ __('Continue as guest') }}</a>
                    </div>
                @endif
            </section>
            <div class="runner-auth-photo" aria-hidden="true"></div>
        </div>
    </main>
@endsection

@if ($challengeEmail && $resendAt)
    @push('scripts')
        <script>
            (() => {
                const button = document.querySelector('[data-resend-seconds]');
                if (!button) return;
                const label = button.textContent.trim();
                const readyAt = performance.now() + Number(button.dataset.resendSeconds) * 1000;
                function updateResend() {
                    const seconds = Math.ceil((readyAt - performance.now()) / 1000);
                    button.disabled = seconds > 0;
                    button.textContent = seconds > 0
                        ? button.dataset.resendWait.replace('{seconds}', seconds)
                        : label;
                    if (seconds > 0) window.setTimeout(updateResend, 1000);
                }
                updateResend();
            })();
        </script>
    @endpush
@endif
