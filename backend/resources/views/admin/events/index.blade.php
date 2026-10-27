@extends('admin.layout')

@section('title', __('Events') . ' · KFS Runners Admin')
@section('section', __('Events'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-events.css') }}">
@endpush

@section('content')
<div class="stitch-hero event-admin-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">{{ __('EVENT CONTENT') }}</span>
        <h1>{{ __('Manage Events') }}</h1>
        <p>{{ __('Create event pages with dates, details, and photo galleries.') }}</p>
    </div>
    <a href="{{ route('admin.events.create') }}" class="stitch-btn stitch-btn-primary">＋ {{ __('Add event') }}</a>
</div>

@if (session('success')) <div class="event-admin-flash success" role="status">{{ session('success') }}</div> @endif

<div class="event-admin-grid">
    @forelse ($events as $event)
        <article class="event-admin-card">
            <div class="event-admin-cover">
                <img src="{{ $event->coverPhoto?->adminUrl() ?? asset('images/kfs-runners-hero.png') }}" alt="{{ $event->translated('title') }}" loading="lazy">
                <span class="event-admin-year">{{ $event->event_year }}</span>
                <span class="event-admin-status {{ $event->is_published ? 'published' : 'draft' }}">{{ $event->is_published ? __('Published') : __('Draft') }}</span>
            </div>
            <div class="event-admin-card-body">
                <h2>{{ $event->translated('title') }}</h2>
                <p>{{ $event->translated('summary') ?: __('Add a short description for this event.') }}</p>
                <div class="event-admin-meta"><span>📍 {{ $event->translated('location') ?: __('Location not set') }}</span><span>▧ {{ $event->photos_count }} {{ __('photos') }}</span></div>
                <div class="event-admin-actions">
                    <a class="stitch-btn stitch-btn-primary" href="{{ route('admin.events.edit', $event) }}">{{ __('Edit details & photos') }}</a>
                    @if ($event->is_published)
                        <a class="event-admin-view" href="{{ route('events.show', $event->slug) }}" target="_blank" rel="noopener noreferrer">{{ __('View page') }} ↗</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('{{ __('Delete this event and its uploaded photos?') }}')">
                    @csrf
                    @method('DELETE')
                    <button class="event-admin-delete" type="submit">{{ __('Delete event') }}</button>
                </form>
            </div>
        </article>
    @empty
        <div class="event-admin-empty"><h2>{{ __('No events yet') }}</h2><p>{{ __('Create the first event to publish its details and photos.') }}</p><a href="{{ route('admin.events.create') }}" class="stitch-btn stitch-btn-primary">{{ __('Add event') }}</a></div>
    @endforelse
</div>
@endsection
