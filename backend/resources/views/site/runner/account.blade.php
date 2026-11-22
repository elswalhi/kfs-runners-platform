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
                        {{ $onboarding ? __('Complete your runner profile before continuing.') : __('Manage your runner profile and view your event registrations.') }}
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
                                <input id="profile-first-name" name="first_name" type="text" dir="auto" minlength="2" maxlength="100" required
                                    value="{{ old('first_name', $runner->first_name) }}" autocomplete="given-name"
                                    @if ($errors->has('first_name')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-last-name">{{ __('Last name') }}</label>
                                <input id="profile-last-name" name="last_name" type="text" dir="auto" minlength="2" maxlength="100" required
                                    value="{{ old('last_name', $runner->last_name) }}" autocomplete="family-name"
                                    @if ($errors->has('last_name')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-phone">{{ __('Phone number') }}</label>
                                <input id="profile-phone" name="phone" type="tel" dir="ltr" maxlength="32" required
                                    value="{{ old('phone', $runner->phone) }}" autocomplete="tel"
                                    @if ($errors->has('phone')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-birth-date">{{ __('Date of birth') }}</label>
                                <input id="profile-birth-date" name="birth_date" type="date" dir="ltr" max="{{ today()->subDay()->toDateString() }}" required
                                    value="{{ old('birth_date', $runner->birth_date?->toDateString()) }}" autocomplete="bday"
                                    @if ($errors->has('birth_date')) aria-invalid="true" @endif>
                            </div>
                            <div class="runner-field">
                                <label for="profile-gender">{{ __('Gender') }}</label>
                                <select id="profile-gender" name="gender" required @if ($errors->has('gender')) aria-invalid="true" @endif>
                                    <option value="">{{ __('Choose gender') }}</option>
                                    <option value="male" @selected(old('gender', $runner->gender) === 'male')>{{ __('Male') }}</option>
                                    <option value="female" @selected(old('gender', $runner->gender) === 'female')>{{ __('Female') }}</option>
                                </select>
                            </div>
                            <div class="runner-field">
                                <label for="profile-id-number">{{ __('National ID / Passport') }}</label>
                                <input id="profile-id-number" name="id_number" type="text" dir="auto"
                                    autocomplete="off" minlength="4" maxlength="100" @required(! $hasSavedId)
                                    @if ($hasSavedId) placeholder="{{ __('ID number saved') }}" aria-describedby="profile-id-help" @endif
                                    @if ($errors->has('id_number')) aria-invalid="true" @endif>
                                @if ($hasSavedId)
                                    <small id="profile-id-help">{{ __('Enter a new number only if you want to replace the saved one.') }}</small>
                                @endif
                            </div>
                            <div class="runner-field">
                                <label for="profile-team">{{ __('Team name') }} ({{ __('Optional') }})</label>
                                <input id="profile-team" name="team_name" type="text" dir="auto" maxlength="120"
                                    value="{{ old('team_name', $runner->team_name) }}" autocomplete="organization"
                                    @if ($errors->has('team_name')) aria-invalid="true" @endif>
                            </div>
                        </div>

                        <div class="runner-actions">
                            <button class="runner-button" type="submit">{{ $onboarding && $returnUrl ? __('Save and continue') : __('Save runner profile') }}</button>
                            @if ($returnUrl && ! $onboarding)
                                <a class="runner-button runner-button--secondary" href="{{ $returnUrl }}">{{ __('Back to registration') }}</a>
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
