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
                @if ($event->drive_url)
                    <a href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer" class="kfs-event-drive-aside-link">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                        <span>{{ __('Google Drive Album') }}</span> ↗
                    </a>
                @endif
            </aside>
        </div>
    </section>

    @php($galleryPhotos = $event->photos->reject(fn ($photo) => $photo->is_cover))
    @if ($galleryPhotos->isNotEmpty() || $event->drive_url)
        <section class="kfs-event-gallery" aria-labelledby="kfs-event-gallery-title">
            <div class="wrap">
                <div class="kfs-events-list-heading">
                    <div>
                        <span>{{ __('RACE DAY, REMEMBERED') }}</span>
                        <h2 id="kfs-event-gallery-title">{{ __('Photo gallery') }}</h2>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                        <p style="margin:0;">{{ $event->photos->count() }} {{ __('photos') }}</p>
                        @if ($event->drive_url)
                            <a href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer" class="kfs-event-drive-pill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                                <span>{{ __('Open on Google Drive') }}</span> ↗
                            </a>
                        @endif
                    </div>
                </div>
                @if ($galleryPhotos->isNotEmpty())
                    <div class="kfs-event-gallery-grid">
                        @foreach ($galleryPhotos as $photo)
                            <a href="{{ $photo->publicUrl() }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Open event photo') }}">
                                <img src="{{ $photo->publicUrl() }}" alt="{{ $photo->alt ?: $event->translated('title') }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @elseif ($event->drive_url)
                    <div class="kfs-events-empty" style="margin-top: 1rem;">
                        <h2>{{ __('View event photos on Google Drive') }}</h2>
                        <p>{{ __('All photos for this event are available in our public Google Drive album.') }}</p>
                        <a href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer" class="kfs-event-register-link" style="display:inline-flex; width:auto; margin:1rem auto 0; padding:10px 24px;">{{ __('Open Google Drive Album') }} ↗</a>
                    </div>
                @endif
            </div>
        </section>
    @endif
</main>
@endsection
