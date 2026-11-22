@extends('site.layout')

@section('title', __('Runner account') . ' · KFS Runners')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-runner.css') }}">
@endpush

@section('content')
    <main class="runner-page">
        <div class="runner-shell runner-account">
            <header class="runner-account-header">
                <div>
                    @if ($event)
                        <p class="runner-event-context">{{ $event->translated('title') }}</p>
                    @endif
                    <h1 class="runner-title">{{ $onboarding ? __('Set up your runner profile') : __('Runner account') }}</h1>
                    <p class="runner-description">
                        {{ $onboarding ? __('Save the details you want to reuse when registering. You can do this later.') : __('Manage your runner profile and view your event registrations.') }}
                    </p>
                </div>
                <form action="{{ route('runner.logout') }}" method="POST">
                    @csrf
                    @include('site.runner.flow-fields')
                    <button class="runner-button runner-button--secondary" type="submit">{{ __('Sign out') }}</button>
                </form>
            </header>

            @include('site.runner.messages')

            <div class="runner-account-grid{{ $onboarding ? ' runner-account-grid--onboarding' : '' }}">
                <section class="runner-card runner-profile" aria-labelledby="runner-profile-title">
                    <h2 class="runner-heading" id="runner-profile-title">{{ __('Runner details') }}</h2>
                    <p class="runner-verified">{{ __('Verified email') }} <bdi>{{ $runner->email }}</bdi></p>

                    <form class="runner-form" action="{{ route('runner.profile.update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        @include('site.runner.flow-fields')

                        <div class="runner-fields">
                            <div class="runner-field">
                                <label for="profile-first-name">{{ __('First name') }}</label>
                                <input id="profile-first-name" name="first_name" type="text" dir="auto" maxlength="100"
                                    value="{{ old('first_name', $runner->first_name) }}" autocomplete="given-name"
                                    @if ($errors->has('first_name')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-last-name">{{ __('Last name') }}</label>
                                <input id="profile-last-name" name="last_name" type="text" dir="auto" maxlength="100"
                                    value="{{ old('last_name', $runner->last_name) }}" autocomplete="family-name"
                                    @if ($errors->has('last_name')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-phone">{{ __('Phone number') }}</label>
                                <input id="profile-phone" name="phone" type="tel" dir="ltr" maxlength="32"
                                    value="{{ old('phone', $runner->phone) }}" autocomplete="tel"
                                    @if ($errors->has('phone')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-birth-date">{{ __('Date of birth') }}</label>
                                <input id="profile-birth-date" name="birth_date" type="date" dir="ltr" max="{{ today()->subDay()->toDateString() }}"
                                    value="{{ old('birth_date', $runner->birth_date?->toDateString()) }}" autocomplete="bday"
                                    @if ($errors->has('birth_date')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-gender">{{ __('Gender') }}</label>
                                <select id="profile-gender" name="gender" @if ($errors->has('gender')) aria-invalid="true" @endif>
                                    <option value="">{{ __('Choose gender') }}</option>
                                    <option value="male" @selected(old('gender', $runner->gender) === 'male')>{{ __('Male') }}</option>
                                    <option value="female" @selected(old('gender', $runner->gender) === 'female')>{{ __('Female') }}</option>
                                </select>
                            </div>
                            <div class="runner-field">
                                <label for="profile-team">{{ __('Team name') }}</label>
                                <input id="profile-team" name="team_name" type="text" dir="auto" maxlength="120"
                                    value="{{ old('team_name', $runner->team_name) }}" autocomplete="organization"
                                    @if ($errors->has('team_name')) aria-invalid="true" @endif>
                            </div>
                        </div>

                        <div class="runner-actions">
                            <button class="runner-button" type="submit">{{ $onboarding && $returnUrl ? __('Save and continue') : __('Save runner profile') }}</button>
                            @if ($returnUrl)
                                <a class="runner-button runner-button--secondary" href="{{ $returnUrl }}">
                                    {{ $onboarding ? __('Skip for now') : __('Back to registration') }}
                                </a>
                            @elseif ($onboarding)
                                <a class="runner-button runner-button--secondary" href="{{ route('runner.account') }}">{{ __('Skip for now') }}</a>
                            @endif
                        </div>
                    </form>
                </section>

                @unless ($onboarding)
                    <section class="runner-card runner-registrations" aria-labelledby="runner-registrations-title">
                        <h2 class="runner-heading" id="runner-registrations-title">{{ __('My registrations') }}</h2>

                        @if ($registrations->isEmpty())
                            <p class="runner-description">{{ __('Registrations made while signed in will appear here.') }}</p>
                            <a class="runner-text-link" href="{{ route('events.index') }}">{{ __('Browse events') }}</a>
                        @else
                            <ul class="runner-registration-list">
                                @foreach ($registrations as $registration)
                                    @php
                                        $registrationEvent = $registration->race?->event;
                                        $kitLabel = $registration->kit_type === 'full' ? __('Full Kit') : __('Basic Kit');
                                        $paymentLabel = match ($registration->payment_status) {
                                            'paid' => __('Paid'),
                                            'failed' => __('Failed'),
                                            'refunded' => __('Refunded'),
                                            'pending' => __('Pending'),
                                            default => __(ucfirst($registration->payment_status)),
                                        };
                                    @endphp
                                    <li class="runner-registration">
                                        <div class="runner-registration-top">
                                            <div>
                                                <h3>{{ $registrationEvent?->translated('title') ?? $registration->race?->name ?? __('Event registration') }}</h3>
                                                <p>
                                                    @if ($registration->race?->distance_label)
                                                        {{ $registration->race->distance_label }} ·
                                                    @endif
                                                    {{ $kitLabel }}
                                                </p>
                                            </div>
                                            <span class="runner-payment-state{{ $registration->payment_status === 'paid' ? ' runner-payment-state--paid' : '' }}">{{ $paymentLabel }}</span>
                                        </div>
                                        <dl class="runner-registration-details">
                                            <div>
                                                <dt>{{ __('Runner name') }}</dt>
                                                <dd><bdi>{{ $registration->first_name }} {{ $registration->last_name }}</bdi></dd>
                                            </div>
                                            <div>
                                                <dt>{{ __('Registration reference') }}</dt>
                                                <dd><bdi>{{ $registration->registration_code }}</bdi></dd>
                                            </div>
                                            @if ($registration->payment_status === 'paid' && $registration->bib_number)
                                                <div>
                                                    <dt>{{ __('Race number') }}</dt>
                                                    <dd><bdi>{{ $registration->bib_number }}</bdi></dd>
                                                </div>
                                            @endif
                                        </dl>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($registrations->hasPages())
                                <nav class="runner-actions runner-pagination" aria-label="{{ __('Registration pages') }}">
                                    @if ($registrations->previousPageUrl())
                                        <a class="runner-text-link" rel="prev" href="{{ $registrations->previousPageUrl() }}">{{ __('Previous page') }}</a>
                                    @endif
                                    @if ($registrations->nextPageUrl())
                                        <a class="runner-text-link" rel="next" href="{{ $registrations->nextPageUrl() }}">{{ __('Next page') }}</a>
                                    @endif
                                </nav>
                            @endif
                        @endif
                    </section>
                @endunless
            </div>
        </div>
    </main>
@endsection
