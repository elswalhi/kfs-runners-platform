@extends('site.layout')

@section('title', $event->translated('title') . ' · KFS Runners')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-events.css') }}?v=20261103-events-retouch-3">
@endpush

@section('content')
@php
    $cover = $event->coverPhoto;
    $galleryPhotos = $event->photos->reject(fn ($photo) => $photo->is_cover)->values();

    if ($galleryPhotos->isEmpty() && $cover) {
        $galleryPhotos = collect([$cover]);
    }
@endphp

<main class="kfs-events-page kfs-event-detail-page" data-event-detail>
    <div class="wrap kfs-event-breadcrumbs">
        <a class="kfs-event-back" href="{{ route('events.index') }}"><span aria-hidden="true">←</span>{{ __('All events') }}</a>
        <nav aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}">{{ __('Home') }}</a><span aria-hidden="true">/</span>
            <a href="{{ route('events.index') }}">{{ __('Events') }}</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $event->translated('title') }}</span>
        </nav>
    </div>

    <section class="kfs-event-detail-hero">
        <img class="kfs-event-cover" src="{{ $cover?->publicUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $cover?->alt ?: $event->translated('title') }}" fetchpriority="high">
        <div class="kfs-event-detail-shade" aria-hidden="true"></div>
        <div class="wrap kfs-event-detail-hero-content">
            <div class="kfs-event-hero-meta">
                @if ($event->event_year)<span>{{ $event->event_year }}</span>@endif
                @if ($event->translated('location'))<span><i aria-hidden="true">⌖</i>{{ $event->translated('location') }}</span>@endif
            </div>
            <h1>{{ $event->translated('title') }}</h1>
            @if ($event->translated('summary'))<p class="kfs-event-detail-summary">{{ $event->translated('summary') }}</p>@endif
        </div>
    </section>

    <section class="kfs-event-detail-content">
        <div class="wrap kfs-event-detail-layout">
            <article class="kfs-event-story">
                <p class="kfs-event-detail-kicker"><span></span>{{ __('EVENT STORY') }}</p>
                <h2>{{ $event->translated('title') }}</h2>
                @if ($event->translated('summary'))<p class="kfs-event-lead">{{ $event->translated('summary') }}</p>@endif
                @if ($event->translated('description'))
                    <div class="kfs-event-description">{{ $event->translated('description') }}</div>
                @elseif (! $event->translated('summary'))
                    <p class="kfs-event-story-empty">{{ __('Details for this event will be added soon.') }}</p>
                @endif
                <a class="kfs-event-return-link" href="{{ route('events.index') }}"><span aria-hidden="true">←</span>{{ __('Back to all events') }}</a>
            </article>

            <aside class="kfs-event-detail-aside" aria-labelledby="kfs-event-information-title">
                <div class="kfs-event-aside-heading">
                    <span class="kfs-event-aside-icon" aria-hidden="true">i</span>
                    <h2 id="kfs-event-information-title">{{ __('Event information') }}</h2>
                </div>
                @if ($event->event_year)
                    <div class="kfs-event-info-row"><span>{{ __('Year') }}</span><strong>{{ $event->event_year }}</strong></div>
                @endif
                @if ($event->translated('location'))
                    <div class="kfs-event-info-row"><span>{{ __('Location') }}</span><strong>{{ $event->translated('location') }}</strong></div>
                @endif

            </aside>
        </div>
    </section>

    <section class="kfs-event-gallery" aria-labelledby="kfs-event-gallery-title">
        <div class="wrap">
            <div class="kfs-event-gallery-heading">
                <div>
                    <p class="kfs-event-detail-kicker"><span></span>{{ __('MEMORIES IN MOTION') }}</p>
                    <h2 id="kfs-event-gallery-title">{{ __('Photo gallery') }}</h2>
                    <p>{{ __('Moments from this KFS Runners event.') }}</p>
                </div>
                @if ($galleryPhotos->isNotEmpty())
                    <span class="kfs-event-gallery-count"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-2h6l2 2h3v12H4z"/><circle cx="12" cy="13" r="3"/></svg>{{ $galleryPhotos->count() }} {{ __('photos') }}</span>
                @endif
            </div>

            @if ($galleryPhotos->isNotEmpty())
                <div class="kfs-event-gallery-grid">
                    @foreach ($galleryPhotos as $photo)
                        <a class="kfs-event-gallery-item" href="{{ $photo->publicUrl() }}" data-event-photo data-photo-src="{{ $photo->publicUrl() }}" data-photo-alt="{{ $photo->alt ?: $event->translated('title') }}" aria-label="{{ __('Open event photo') }}: {{ $photo->alt ?: $event->translated('title') }}" @if ($loop->first) data-gallery-first @endif>
                            <img src="{{ $photo->publicUrl() }}" alt="{{ $photo->alt ?: $event->translated('title') }}" loading="lazy">
                            <span aria-hidden="true">↗</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="kfs-event-gallery-empty">
                    <span class="kfs-event-gallery-empty-icon" aria-hidden="true">▧</span>
                    <div><h3>{{ __('Photos are not available yet.') }}</h3><p>{{ __('Please check back later for this event gallery.') }}</p></div>
                    @if ($event->drive_url)<a class="kfs-events-button primary" href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer">{{ __('Open photo album') }} <span aria-hidden="true">↗</span></a>@endif
                </div>
            @endif

            @if ($galleryPhotos->isNotEmpty() && $event->drive_url)
                <div class="kfs-event-drive-callout">
                    <p>{{ __('Want to view the full album?') }}</p>
                    <a href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer">{{ __('Open Google Drive album') }} <span aria-hidden="true">↗</span></a>
                </div>
            @endif
        </div>
    </section>

    @if ($galleryPhotos->isNotEmpty())
        <dialog class="kfs-event-lightbox" aria-label="{{ __('Photo viewer') }}" data-event-lightbox>
            <button class="kfs-lightbox-close" type="button" data-lightbox-close aria-label="{{ __('Close photo viewer') }}">×</button>
            <button class="kfs-lightbox-nav previous" type="button" data-lightbox-previous aria-label="{{ __('Previous photo') }}">←</button>
            <figure><img data-lightbox-image src="" alt=""><figcaption data-lightbox-caption></figcaption></figure>
            <button class="kfs-lightbox-nav next" type="button" data-lightbox-next aria-label="{{ __('Next photo') }}">→</button>
            <p class="kfs-lightbox-count" data-lightbox-count aria-live="polite"></p>
        </dialog>
    @endif
</main>
@endsection

@push('scripts')
@if ($galleryPhotos->isNotEmpty())
<script>
    (() => {
        const page = document.querySelector('[data-event-detail]');
        const dialog = page?.querySelector('[data-event-lightbox]');
        if (!page || !dialog || typeof dialog.showModal !== 'function') return;

        const photos = [...page.querySelectorAll('[data-event-photo]')];
        const image = dialog.querySelector('[data-lightbox-image]');
        const caption = dialog.querySelector('[data-lightbox-caption]');
        const counter = dialog.querySelector('[data-lightbox-count]');
        let activeIndex = 0;

        const showPhoto = (index) => {
            activeIndex = (index + photos.length) % photos.length;
            const photo = photos[activeIndex];
            image.src = photo.dataset.photoSrc;
            image.alt = photo.dataset.photoAlt || '';
            caption.textContent = photo.dataset.photoAlt || '';
            counter.textContent = `${activeIndex + 1} / ${photos.length}`;
        };

        photos.forEach((photo, index) => photo.addEventListener('click', (event) => {
            event.preventDefault();
            showPhoto(index);
            dialog.showModal();
        }));

        dialog.querySelector('[data-lightbox-close]')?.addEventListener('click', () => dialog.close());
        dialog.querySelector('[data-lightbox-previous]')?.addEventListener('click', () => showPhoto(activeIndex - 1));
        dialog.querySelector('[data-lightbox-next]')?.addEventListener('click', () => showPhoto(activeIndex + 1));
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') showPhoto(activeIndex - 1);
            if (event.key === 'ArrowRight') showPhoto(activeIndex + 1);
        });
    })();
</script>
@endif
@endpush
