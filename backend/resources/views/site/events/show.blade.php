@extends('site.layout')

@section('title', $event->translated('title') . ' · KFS Runners')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-events.css') }}">
@endpush

@section('content')
<main class="kfs-events-page kfs-event-detail-page">
    @php($cover = $event->coverPhoto)
    <section class="kfs-event-detail-hero">
        <img src="{{ $cover?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $cover?->alt ?: $event->translated('title') }}">
        <div class="kfs-event-detail-shade"></div>
        <div class="wrap kfs-event-detail-hero-content">
            <a class="kfs-event-back" href="{{ route('events.index') }}">← {{ __('All events') }}</a>
            <p class="kfs-events-eyebrow"><span></span>{{ __('KFS RUNNERS · EVENT ARCHIVE') }} · {{ $event->event_year }}</p>
            <h1>{{ $event->translated('title') }}</h1>
            @if ($event->translated('location')) <p class="kfs-event-detail-location">⌖ {{ $event->translated('location') }}</p> @endif
        </div>
    </section>

    <section class="kfs-event-detail-content">
        <div class="wrap kfs-event-detail-layout">
            <article class="kfs-event-story">
                <p class="kfs-event-detail-kicker">{{ __('THE STORY') }}</p>
                @if ($event->translated('summary')) <p class="kfs-event-lead">{{ $event->translated('summary') }}</p> @endif
                @if ($event->translated('description')) <div class="kfs-event-description">{{ $event->translated('description') }}</div> @endif
                @if ($event->source_url)
                    <a class="kfs-event-source-link" href="{{ $event->source_url }}" target="_blank" rel="noopener noreferrer">{{ __('View the original event page') }} ↗</a>
                @endif
            </article>
            <aside class="kfs-event-detail-aside">
                <span>{{ __('EVENT YEAR') }}</span><strong>{{ $event->event_year }}</strong>
                @if ($event->translated('location')) <span>{{ __('LOCATION') }}</span><strong>{{ $event->translated('location') }}</strong> @endif
                @if ($event->registration_url)<a href="{{ $event->registration_url }}" target="_blank" rel="noopener noreferrer" class="kfs-event-register-link">{{ __('More event information') }} ↗</a>@endif
            </aside>
        </div>
    </section>

    @php($galleryPhotos = $event->photos->reject(fn ($photo) => $photo->is_cover))
    @if ($galleryPhotos->isNotEmpty())
        <section class="kfs-event-gallery" aria-labelledby="kfs-event-gallery-title">
            <div class="wrap">
                <div class="kfs-events-list-heading"><div><span>{{ __('RACE DAY, REMEMBERED') }}</span><h2 id="kfs-event-gallery-title">{{ __('Photo gallery') }}</h2></div><p>{{ $event->photos->count() }} {{ __('photos') }}</p></div>
                <div class="kfs-event-gallery-grid">
                    @foreach ($galleryPhotos as $photo)
                        <a href="{{ $photo->publicUrl() }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open event photo') }}">
                            <img src="{{ $photo->publicUrl() }}" alt="{{ $photo->alt ?: $event->translated('title') }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
