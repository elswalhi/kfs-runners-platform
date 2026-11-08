@extends('site.layout')

@section('title', __('KFS Runners Events'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-events.css') }}?v=20261103-events-retouch-2">
@endpush

@section('content')
@php
    $featuredEvent = $events->first();
    $archiveEvents = $events->skip(1);
@endphp

<main class="kfs-events-page">
    <section class="kfs-events-hero">
        <div class="wrap kfs-events-hero-grid">
            <div class="kfs-events-hero-copy">
                <p class="kfs-events-eyebrow"><span></span>{{ __('KFS RUNNERS · EVENT ARCHIVE') }}</p>
                <h1>{{ __('Every race leaves a story.') }}</h1>
                <p class="kfs-events-hero-description">{{ __('Explore moments from KFS Runners events in Kafr El-Sheikh.') }}</p>
                <div class="kfs-events-hero-actions">
                    <a class="kfs-events-button light" href="#past-events">{{ __('Browse events') }} <span aria-hidden="true">↓</span></a>
                    <a class="kfs-events-text-link" href="{{ route('event') }}">{{ __('Current marathon') }} <span aria-hidden="true">↗</span></a>
                </div>
            </div>
            <div class="kfs-events-hero-media">
                <img src="{{ $featuredEvent?->coverPhoto?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $featuredEvent?->coverPhoto?->alt ?: __('KFS Runners event') }}" fetchpriority="high">
                <span class="kfs-events-hero-image-label"><i></i>{{ __('Stories from the finish line') }}</span>
            </div>
        </div>
    </section>

    @if ($upcomingEvents->isNotEmpty())
        <section class="kfs-events-list kfs-upcoming-events" aria-labelledby="kfs-upcoming-events-title">
            <div class="wrap">
                <div class="kfs-events-list-heading">
                    <div>
                        <p class="kfs-events-kicker">{{ __('CURRENT EVENTS') }}</p>
                        <h2 id="kfs-upcoming-events-title">{{ __('Upcoming events') }}</h2>
                        <p class="kfs-events-list-subtitle">{{ __('Choose an event to see its race distances and registration options.') }}</p>
                    </div>
                    <p class="kfs-events-result-count"><strong>{{ $upcomingEvents->count() }}</strong> {{ __('events') }}</p>
                </div>
                <div class="kfs-events-grid">
                    @foreach ($upcomingEvents as $event)
                        <article class="kfs-events-card">
                            <a class="kfs-events-card-image" href="{{ route('events.show', $event->slug) }}" aria-label="{{ __('View event') }}: {{ $event->translated('title') }}">
                                <img src="{{ $event->coverPhoto?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $event->coverPhoto?->alt ?: $event->translated('title') }}" loading="lazy">
                                @if ($event->event_year)<span class="kfs-events-year-badge">{{ $event->event_year }}</span>@endif
                            </a>
                            <div class="kfs-events-card-content">
                                @if ($event->translated('location'))<p class="kfs-events-card-location"><i aria-hidden="true">⌖</i>{{ $event->translated('location') }}</p>@endif
                                <h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->translated('title') }}</a></h3>
                                @if ($event->translated('summary'))<p class="kfs-events-summary">{{ $event->translated('summary') }}</p>@endif
                                <div class="kfs-events-card-footer">
                                    <span class="kfs-photo-count">{{ $event->starts_at?->format('d M Y') ?? $event->event_year }}</span>
                                    <a href="{{ $event->registration_enabled ? route('events.register', $event->slug) : route('events.show', $event->slug) }}">{{ $event->registration_enabled ? __('Register now') : __('View event') }} <span aria-hidden="true">→</span></a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featuredEvent)
        <section class="kfs-featured-event wrap">
            <a class="kfs-featured-event-image" href="{{ route('events.show', $featuredEvent->slug) }}" aria-label="{{ __('View event') }}: {{ $featuredEvent->translated('title') }}">
                <img src="{{ $featuredEvent->coverPhoto?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $featuredEvent->coverPhoto?->alt ?: $featuredEvent->translated('title') }}" loading="lazy">
                <span class="kfs-featured-label">{{ __('Latest event story') }}</span>
            </a>
            <div class="kfs-featured-event-copy">
                <div class="kfs-event-meta-line">
                    @if ($featuredEvent->event_year)<span class="kfs-event-year">{{ $featuredEvent->event_year }}</span>@endif
                    @if ($featuredEvent->translated('location'))<span class="kfs-event-location"><i aria-hidden="true">⌖</i>{{ $featuredEvent->translated('location') }}</span>@endif
                </div>
                <h2><a href="{{ route('events.show', $featuredEvent->slug) }}">{{ $featuredEvent->translated('title') }}</a></h2>
                @if ($featuredEvent->translated('summary'))<p>{{ $featuredEvent->translated('summary') }}</p>@endif
                <div class="kfs-featured-event-footer">
                    <span class="kfs-photo-count"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-2h6l2 2h3v12H4z"/><circle cx="12" cy="13" r="3"/></svg>{{ $featuredEvent->photos_count }} {{ __('photos') }}</span>
                    <a class="kfs-events-button primary" href="{{ route('events.show', $featuredEvent->slug) }}">{{ __('View event story') }} <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </section>
    @endif

    <section class="kfs-events-list" id="past-events" aria-labelledby="kfs-events-list-title">
        <div class="wrap">
            <div class="kfs-events-list-heading">
                <div>
                    <p class="kfs-events-kicker">{{ __('MEMORIES IN MOTION') }}</p>
                    <h2 id="kfs-events-list-title">{{ __('Past events') }}</h2>
                    <p class="kfs-events-list-subtitle">{{ __('Browse event stories and photos from the KFS Runners community.') }}</p>
                </div>
                <p class="kfs-events-result-count"><strong data-event-count>{{ $events->count() }}</strong> {{ __('events') }}</p>
            </div>

            @if ($events->isNotEmpty())
                @if ($archiveEvents->isNotEmpty())
                    <div class="kfs-events-grid">
                        @foreach ($archiveEvents as $event)
                            <article class="kfs-events-card">
                                <a class="kfs-events-card-image" href="{{ route('events.show', $event->slug) }}" aria-label="{{ __('View event') }}: {{ $event->translated('title') }}">
                                    <img src="{{ $event->coverPhoto?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $event->coverPhoto?->alt ?: $event->translated('title') }}" loading="lazy">
                                    @if ($event->event_year)<span class="kfs-events-year-badge">{{ $event->event_year }}</span>@endif
                                </a>
                                <div class="kfs-events-card-content">
                                    @if ($event->translated('location'))<p class="kfs-events-card-location"><i aria-hidden="true">⌖</i>{{ $event->translated('location') }}</p>@endif
                                    <h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->translated('title') }}</a></h3>
                                    @if ($event->translated('summary'))<p class="kfs-events-summary">{{ $event->translated('summary') }}</p>@endif
                                    <div class="kfs-events-card-footer">
                                        <span class="kfs-photo-count"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-2h6l2 2h3v12H4z"/><circle cx="12" cy="13" r="3"/></svg>{{ $event->photos_count }} {{ __('photos') }}</span>
                                        <a href="{{ route('events.show', $event->slug) }}">{{ __('View event') }} <span aria-hidden="true">→</span></a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="kfs-events-empty">
                        <h3>{{ __('More event stories will appear here.') }}</h3>
                    </div>
                @endif
            @else
                <div class="kfs-events-empty">
                    <span class="kfs-events-empty-icon" aria-hidden="true">↗</span>
                    <h3>{{ __('New stories are on the way.') }}</h3>
                    <p>{{ __('Check back soon for KFS Runners events and photo galleries.') }}</p>
                    <a href="{{ route('home') }}">{{ __('Back to home') }} <span aria-hidden="true">→</span></a>
                </div>
            @endif
        </div>
    </section>
</main>
@endsection
