@extends('admin.layout')

@section('title', __('Events') . ' · KFS Runners Admin')
@section('section', __('Events'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-events.css') }}?v=20261108-multievent-1">
@endpush

@section('content')
<div class="stitch-hero event-admin-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">{{ __('EVENT MANAGEMENT') }}</span>
        <h1>{{ __('Events') }}</h1>
        <p>{{ __('Manage multiple races and configure each event independently.') }}</p>
    </div>
    <a href="{{ route('admin.events.create') }}" class="stitch-btn stitch-btn-primary">＋ {{ __('Create event') }}</a>
</div>

<div class="event-admin-stats">
    <article><span>{{ __('Current events') }}</span><strong>{{ $currentEvents->count() }}</strong><small>{{ __('Events managed separately') }}</small></article>
    <article><span>{{ __('Registrations') }}</span><strong>{{ number_format($currentRegistrationCount) }}</strong><small>{{ __('Across current events') }}</small></article>
    <article><span>{{ __('Next event') }}</span><strong>{{ $nextEvent?->starts_at?->diffForHumans() ?? __('Not scheduled') }}</strong><small>{{ $nextEvent?->translated('title') ?? __('Add a race date in event settings') }}</small></article>
</div>

<div class="event-admin-workspace">
    <section class="event-admin-panel event-admin-current-list">
        <header class="event-admin-panel-heading"><div><h2>{{ __('Current events') }}</h2><p>{{ __('Each event keeps its own race distances, prices, registration, and QR settings.') }}</p></div><span class="event-admin-count-pill">{{ $currentEvents->count() }} {{ __('events') }}</span></header>
        @forelse ($currentEvents as $event)
            <a class="event-admin-current-item {{ $selectedEvent?->id === $event->id ? 'selected' : '' }}" href="{{ route('admin.events.index', ['event' => $event->id]) }}">
                <img src="{{ $event->coverPhoto?->adminUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="" loading="lazy">
                <div class="event-admin-current-copy">
                    <div class="event-admin-current-title"><strong>{{ $event->translated('title') }}</strong><span class="event-admin-status {{ $event->registration_enabled ? 'published' : 'draft' }}">{{ $event->registration_enabled ? __('Registration open') : __('Registration closed') }}</span></div>
                    <small>{{ $event->starts_at?->format('D, d M Y · H:i') ?? __('Date not set') }} <span>·</span> {{ $event->translated('location') ?: __('Location not set') }}</small>
                    <div class="event-admin-current-metrics"><span>{{ $event->registrations_count }} {{ __('registrations') }}</span><span>{{ $event->races_count }} {{ __('distances') }}</span></div>
                </div>
                <span class="event-admin-current-arrow" aria-hidden="true">›</span>
            </a>
        @empty
            <div class="event-admin-empty"><h2>{{ __('No current events') }}</h2><p>{{ __('Create an event, add its race distances, and enable registration when it is ready.') }}</p><a href="{{ route('admin.events.create') }}" class="stitch-btn stitch-btn-primary">{{ __('Create event') }}</a></div>
        @endforelse
    </section>

    <section class="event-admin-panel event-admin-selected-panel">
        @if ($selectedEvent)
            <div class="event-admin-selected-heading">
                <img src="{{ $selectedEvent->coverPhoto?->adminUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="" loading="lazy">
                <div><span class="stitch-eyebrow">{{ __('EVENT-SPECIFIC SETTINGS') }}</span><h2>{{ $selectedEvent->translated('title') }}</h2><p>{{ $selectedEvent->starts_at?->format('D, d M Y · H:i') ?? __('Date not set') }} · {{ $selectedEvent->translated('location') ?: __('Location not set') }}</p></div>
            </div>
            <div class="event-admin-isolation-note"><span aria-hidden="true">ⓘ</span><span>{{ __('Only applies to this event. Settings for other events stay unchanged.') }}</span></div>
            <div class="event-admin-selected-tabs"><span class="active">{{ __('Race & pricing') }}</span><span>{{ __('Registration') }}</span><span>{{ __('Payments') }}</span><span>{{ __('Race numbers & QR') }}</span></div>
            <div class="event-admin-distance-heading"><div><h3>{{ __('Race distances & pricing') }}</h3><p>{{ __('Active distances and kit prices for this event.') }}</p></div><a href="{{ route('admin.events.configure', $selectedEvent) }}#race-pricing">{{ __('Manage') }} ↗</a></div>
            @forelse ($selectedEvent->races as $race)
                <div class="event-admin-distance-row">
                    <div><strong>{{ $race->distance_label }}</strong><small>{{ $race->name }}</small></div>
                    <div class="event-admin-price-summary"><span>{{ __('Full') }} <b>{{ $race->kitPrices->firstWhere('kit_type', 'full')?->currency ?? 'EGP' }} {{ number_format((float) ($race->kitPrices->firstWhere('kit_type', 'full')?->price ?? 0), 0) }}</b></span><span>{{ __('Basic') }} <b>{{ $race->kitPrices->firstWhere('kit_type', 'basic')?->currency ?? 'EGP' }} {{ number_format((float) ($race->kitPrices->firstWhere('kit_type', 'basic')?->price ?? 0), 0) }}</b></span></div>
                    <span class="event-admin-race-state {{ $race->is_active ? 'on' : 'off' }}">{{ $race->is_active ? __('Active') : __('Paused') }}</span>
                </div>
            @empty
                <div class="event-admin-empty-distance">{{ __('No distances yet. Add the first one in event configuration.') }}</div>
            @endforelse
            <div class="event-admin-selected-actions"><a href="{{ route('admin.events.configure', $selectedEvent) }}" class="stitch-btn stitch-btn-primary">{{ __('Configure this event') }} <span aria-hidden="true">→</span></a><a href="{{ route('admin.events.edit', $selectedEvent) }}" class="event-admin-view">{{ __('Edit event page & photos') }}</a></div>
        @else
            <div class="event-admin-empty"><h2>{{ __('Create your first event') }}</h2><p>{{ __('The event settings panel will appear here after you add an event.') }}</p></div>
        @endif
    </section>
</div>

<section class="event-admin-archive">
    <div class="event-admin-archive-heading"><div><span class="stitch-eyebrow">{{ __('EVENT CONTENT') }}</span><h2>{{ __('Past event stories') }}</h2><p>{{ __('Historical event pages and photo galleries.') }}</p></div></div>
    <div class="event-admin-grid">
        @forelse ($events->where('is_current', false) as $event)
            <article class="event-admin-card">
                <div class="event-admin-cover"><img src="{{ $event->coverPhoto?->adminUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $event->translated('title') }}" loading="lazy"><span class="event-admin-year">{{ $event->event_year }}</span><span class="event-admin-status {{ $event->is_published ? 'published' : 'draft' }}">{{ $event->is_published ? __('Published') : __('Draft') }}</span></div>
                <div class="event-admin-card-body"><h2>{{ $event->translated('title') }}</h2><p>{{ $event->translated('summary') ?: __('Add a short description for this event.') }}</p><div class="event-admin-meta"><span>📍 {{ $event->translated('location') ?: __('Location not set') }}</span><span>▧ {{ $event->photos_count }} {{ __('photos') }}</span></div><div class="event-admin-actions"><a class="stitch-btn stitch-btn-primary" href="{{ route('admin.events.edit', $event) }}">{{ __('Edit details & photos') }}</a>@if ($event->is_published)<a class="event-admin-view" href="{{ route('events.show', $event->slug) }}" target="_blank" rel="noopener noreferrer">{{ __('View page') }} ↗</a>@endif</div><form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('{{ __('Delete this event and its uploaded photos?') }}')">@csrf @method('DELETE')<button class="event-admin-delete" type="submit">{{ __('Delete event') }}</button></form></div>
            </article>
        @empty
            <div class="event-admin-empty"><p>{{ __('No past events have been added yet.') }}</p></div>
        @endforelse
    </div>
</section>
@endsection
