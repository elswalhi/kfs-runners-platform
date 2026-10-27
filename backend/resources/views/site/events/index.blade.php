@extends('site.layout')

@section('title', __('KFS Runners Events'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-events.css') }}">
@endpush

@section('content')
<main class="kfs-events-page">
    <section class="kfs-events-intro">
        <div class="wrap">
            <p class="kfs-events-eyebrow"><span></span>{{ __('KFS RUNNERS · KAFR EL-SHEIKH') }}</p>
            <h1>{{ __('OUR EVENTS.') }}<br><span>{{ __('OUR STORIES.') }}</span></h1>
            <p>{{ __('Every finish line brings a new story. Explore the races, the people, and the moments that move our community.') }}</p>
        </div>
    </section>

    <section class="kfs-events-list" aria-labelledby="kfs-events-list-title">
        <div class="wrap">
            <div class="kfs-events-list-heading"><div><span>{{ __('MEMORIES IN MOTION') }}</span><h2 id="kfs-events-list-title">{{ __('Past events') }}</h2></div><p>{{ $events->count() }} {{ __('events') }}</p></div>
            @if ($events->isNotEmpty())
                <div class="kfs-events-grid">
                    @foreach ($events as $event)
                        <article class="kfs-events-card">
                            <a class="kfs-events-card-image" href="{{ route('events.show', $event->slug) }}" aria-label="{{ __('Read about') }} {{ $event->translated('title') }}">
                                <img src="{{ $event->coverPhoto?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $event->coverPhoto?->alt ?: $event->translated('title') }}" loading="lazy">
                                <span>{{ $event->event_year }}</span>
                            </a>
                            <div class="kfs-events-card-content">
                                <p class="kfs-events-card-location">⌖ {{ $event->translated('location') ?: __('Kafr El-Sheikh, Egypt') }}</p>
                                <h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->translated('title') }}</a></h3>
                                <p class="kfs-events-summary">{{ $event->translated('summary') }}</p>
                                <div class="kfs-events-card-footer"><span>▧ {{ $event->photos_count }} {{ __('photos') }}</span><a href="{{ route('events.show', $event->slug) }}">{{ __('View event') }} <span aria-hidden="true">→</span></a></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="kfs-events-empty"><h2>{{ __('New stories are on the way.') }}</h2><p>{{ __('Check back soon for KFS Runners events and photo galleries.') }}</p></div>
            @endif
        </div>
    </section>
</main>
@endsection
