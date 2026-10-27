@extends('site.layout')

@section('title', __('KFS Runners · Event'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-event.css') }}">
@endpush

@section('content')
<main class="kfs-event-page">
    @php
        $eventHeroImage = \App\Support\SiteMedia::url('event', 'hero', asset('images/kfs-runners-hero.png'));
        $eventHeroVideo = \App\Support\SiteMedia::videoUrl('event');
    @endphp
    <section class="kfs-event-hero" data-hero-section>
        <div class="kfs-event-hero-image" style="--event-hero-image: url('{{ $eventHeroImage }}')" aria-hidden="true"></div>
        @if ($eventHeroVideo)
            <video class="kfs-hero-video" data-hero-video autoplay muted loop playsinline preload="metadata" poster="{{ $eventHeroImage }}" aria-hidden="true" tabindex="-1">
                <source src="{{ $eventHeroVideo }}" type="{{ \App\Support\SiteMedia::videoMimeType(\App\Support\SiteMedia::videoPath('event')) }}">
            </video>
            <div class="kfs-hero-video-shade" aria-hidden="true"></div>
            @include('site.partials.hero-video-controls')
        @endif
        <div class="wrap kfs-event-hero-content">
            <p class="kfs-event-eyebrow"><span></span>{{ __('KFS RUNNERS · KAFR EL-SHEIKH') }}</p>
            <h1>{{ __('ONE CITY.') }}<br><span>{{ __('ONE STARTING LINE.') }}</span></h1>
            <p>{{ __('A community event for every pace, every age, and every story. Find your distance and be part of race day.') }}</p>
            <a class="kfs-event-button" href="{{ route('register') }}">{{ __('Choose your race') }} <span aria-hidden="true">→</span></a>
            @if ($countdownEnabled)
                <div class="kfs-countdown-card kfs-event-countdown" id="kfs-race-countdown" data-target="{{ $raceDateTime }}" aria-label="{{ __('Countdown to race day') }}">
                    <div class="kfs-countdown-header"><span aria-hidden="true">●</span>{{ __('RACE DAY COUNTDOWN') }}</div>
                    <div class="kfs-countdown-grid">
                        <div class="kfs-countdown-unit"><span class="kfs-countdown-number" id="countdown-days">--</span><span class="kfs-countdown-label">{{ __('DAYS') }}</span></div>
                        <span class="kfs-countdown-sep">:</span>
                        <div class="kfs-countdown-unit"><span class="kfs-countdown-number" id="countdown-hours">--</span><span class="kfs-countdown-label">{{ __('HOURS') }}</span></div>
                        <span class="kfs-countdown-sep">:</span>
                        <div class="kfs-countdown-unit"><span class="kfs-countdown-number" id="countdown-mins">--</span><span class="kfs-countdown-label">{{ __('MINS') }}</span></div>
                        <span class="kfs-countdown-sep">:</span>
                        <div class="kfs-countdown-unit"><span class="kfs-countdown-number" id="countdown-secs">--</span><span class="kfs-countdown-label">{{ __('SECS') }}</span></div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="kfs-event-facts" aria-label="{{ __('Event details') }}">
        <div class="wrap kfs-event-facts-grid">
            <div><span>{{ __('RACE DAY') }}</span><strong>{{ $raceDateFormatted }}</strong></div>
            <div><span>{{ __('START TIME') }}</span><strong>{{ $raceTime }}</strong></div>
            <div><span>{{ __('LOCATION') }}</span><strong>{{ __('Kafr El-Sheikh, Egypt') }}</strong></div>
        </div>
    </section>

    <section class="kfs-event-races" aria-labelledby="event-races-title">
        <div class="wrap">
            <div class="kfs-event-section-heading">
                <p class="kfs-event-kicker"><span></span>{{ __('PICK YOUR CHALLENGE') }}</p>
                <h2 id="event-races-title">{{ __('A DISTANCE FOR') }} <span>{{ __('EVERY RUNNER.') }}</span></h2>
                <p>{{ __('From a family-friendly start to a longer personal challenge, choose the race that fits you.') }}</p>
            </div>
            <div class="kfs-event-race-grid">
                @forelse ($races as $race)
                    @php($fullPrice = $race->kitPrices->firstWhere('kit_type', 'full'))
                    @php($basicPrice = $race->kitPrices->firstWhere('kit_type', 'basic'))
                    <article class="kfs-event-race-card">
                        <div class="kfs-event-race-photo" style="--event-race-photo: url('{{ \App\Support\SiteMedia::url('event', 'race-card-' . $race->slug, \App\Support\SiteMedia::url('event', 'race-default', asset('images/kfs-runners-hero.png'))) }}')"><span>{{ $race->distance_label }}</span></div>
                        <div class="kfs-event-race-copy">
                            <h3>{{ $race->name }}</h3>
                            <p>
                                @if ($race->category_type === 'family')
                                    {{ __('A relaxed distance to enjoy with family.') }}
                                @elseif ($race->category_type === 'para')
                                    {{ __('An inclusive race option for participants with different abilities.') }}
                                @else
                                    {{ __('A supported race for runners building toward their next goal.') }}
                                @endif
                            </p>
                            <div class="kfs-event-price-list">
                                @if ($fullPrice)<span>{{ __('Full Kit') }} <b>{{ $fullPrice->currency }} {{ number_format((float) $fullPrice->price, 0) }}</b></span>@endif
                                @if ($basicPrice)<span>{{ __('Basic Kit') }} <b>{{ $basicPrice->currency }} {{ number_format((float) $basicPrice->price, 0) }}</b></span>@endif
                            </div>
                            <a class="kfs-event-race-link" href="{{ route('register', ['race' => $race->slug]) }}" aria-label="{{ __('Register for the :race marathon', ['race' => $race->name]) }}">{{ __('Register for this race') }} <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                @empty
                    <div class="kfs-event-empty">{{ __('Race categories will be announced soon.') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="kfs-event-kits" aria-labelledby="event-kits-title">
        <div class="wrap kfs-event-kits-layout">
            <div>
                <p class="kfs-event-kicker"><span></span>{{ __('RACE DAY, YOUR WAY') }}</p>
                <h2 id="event-kits-title">{{ __('CHOOSE YOUR') }}<br><span>{{ __('RACE KIT.') }}</span></h2>
                <p>{{ __('Both packages include race support. Pick the experience that works for you.') }}</p>
            </div>
            <div class="kfs-event-kit-grid">
                <a href="{{ route('register', ['kit' => 'full']) }}" aria-label="{{ __('Register with the Full Kit') }}"><span class="kfs-event-kit-label">{{ __('THE COMPLETE EXPERIENCE') }}</span><h3>{{ __('Full Kit') }}</h3><ul><li>{{ __('Finisher medal') }}</li><li>{{ __('Official KFS Runners T-shirt') }}</li><li>{{ __('Runner bag') }}</li><li>{{ __('Participation certificate') }}</li><li>{{ __('Race support and photographs') }}</li></ul><span class="kfs-event-kit-cta">{{ __('Choose Full Kit') }} <span aria-hidden="true">→</span></span></a>
                <a href="{{ route('register', ['kit' => 'basic']) }}" aria-label="{{ __('Register with the Basic Kit') }}"><span class="kfs-event-kit-label">{{ __('THE ESSENTIALS') }}</span><h3>{{ __('Basic Kit') }}</h3><ul><li>{{ __('Finisher medal') }}</li><li>{{ __('Participation certificate') }}</li><li>{{ __('Race support and photographs') }}</li></ul><span class="kfs-event-kit-cta">{{ __('Choose Basic Kit') }} <span aria-hidden="true">→</span></span></a>
            </div>
        </div>
    </section>

    <section class="kfs-event-cta">
        <div class="wrap kfs-event-cta-inner"><div><p>{{ __('YOUR NEXT FINISH LINE') }}</p><h2>{{ __('READY TO RUN?') }}</h2></div><a class="kfs-event-button kfs-event-button-light" href="{{ route('register') }}">{{ __('Marathon registration') }} <span aria-hidden="true">→</span></a></div>
    </section>
</main>
@endsection
