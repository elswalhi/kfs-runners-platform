@extends('admin.layout')

@section('title', ($isCreating ? __('Add event') : __('Edit event')) . ' · KFS Runners Admin')
@section('section', __('Events'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-events.css') }}">
@endpush

@section('content')
<div class="event-admin-form-heading">
    <div><span class="stitch-eyebrow">{{ __('EVENT CONTENT') }}</span><h1>{{ $isCreating ? __('Create an event') : __('Edit event') }}</h1><p>{{ __('Add the event information in English and Arabic, then manage its photos below.') }}</p></div>
    <a href="{{ route('admin.events.index') }}" class="event-admin-back">← {{ __('All events') }}</a>
</div>

@if (session('success')) <div class="event-admin-flash success" role="status">{{ session('success') }}</div> @endif
@if ($errors->any())
    <div class="event-admin-flash error" role="alert"><strong>{{ __('Please review the event information.') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form class="event-admin-form" method="POST" action="{{ $isCreating ? route('admin.events.store') : route('admin.events.update', $event) }}" enctype="multipart/form-data">
    @csrf
    @unless ($isCreating) @method('PUT') @endunless
    <section class="event-admin-panel">
        <header><h2>{{ __('Event information') }}</h2><p>{{ __('These details appear on the public event page.') }}</p></header>
        <div class="event-admin-fields">
            <label><span>{{ __('Event title (English)') }} <b>*</b></span><input type="text" name="title" value="{{ old('title', $event->title) }}" maxlength="180" required data-event-title></label>
            <label><span>{{ __('Event title (Arabic)') }}</span><input type="text" name="title_ar" value="{{ old('title_ar', $event->title_ar) }}" maxlength="180" dir="rtl"></label>
            <label><span>{{ __('Page slug') }} <b>*</b></span><input type="text" name="slug" value="{{ old('slug', $event->slug) }}" maxlength="200" required data-event-slug><small>{{ __('Used in the public event page URL.') }}</small></label>
            <label><span>{{ __('Event year') }} <b>*</b></span><input type="number" name="event_year" min="1900" max="2200" value="{{ old('event_year', $event->event_year ?: now()->year) }}" required></label>
            <label><span>{{ __('Location (English)') }}</span><input type="text" name="location" value="{{ old('location', $event->location) }}" maxlength="180"></label>
            <label><span>{{ __('Location (Arabic)') }}</span><input type="text" name="location_ar" value="{{ old('location_ar', $event->location_ar) }}" maxlength="180" dir="rtl"></label>
            <label class="wide"><span>{{ __('Short description (English)') }}</span><input type="text" name="summary" value="{{ old('summary', $event->summary) }}" maxlength="280"></label>
            <label class="wide" dir="rtl"><span>{{ __('Short description (Arabic)') }}</span><input type="text" name="summary_ar" value="{{ old('summary_ar', $event->summary_ar) }}" maxlength="280"></label>
            <label class="wide"><span>{{ __('Event details (English)') }}</span><textarea name="description" rows="7">{{ old('description', $event->description) }}</textarea></label>
            <label class="wide" dir="rtl"><span>{{ __('Event details (Arabic)') }}</span><textarea name="description_ar" rows="7" dir="rtl">{{ old('description_ar', $event->description_ar) }}</textarea></label>
            <label class="wide"><span>{{ __('Registration or information link (optional)') }}</span><input type="url" name="registration_url" value="{{ old('registration_url', $event->registration_url) }}" placeholder="https://"></label>
            <label class="wide"><span>{{ __('Original event page (optional)') }}</span><input type="url" name="source_url" value="{{ old('source_url', $event->source_url) }}" placeholder="https://"></label>
            <label class="wide">
                <span>{{ __('Google Drive Folder URL') }} @if ($event->drive_url) <a href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer" style="margin-inline-start: 8px; font-weight: normal; color: #3b82f6; text-decoration: underline;">{{ __('Open Drive folder') }} ↗</a> @endif</span>
                <input type="url" name="drive_url" value="{{ old('drive_url', $event->drive_url) }}" placeholder="https://drive.google.com/drive/folders/...">
                <small>{{ __('Link a public Google Drive folder containing event photos.') }}</small>
            </label>
        </div>
        @if ($isCreating)
            <label class="event-admin-upload"><span>{{ __('Event photos') }}</span><input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple><small>{{ __('Select up to 20 JPG, PNG, or WebP images. The first image becomes the cover. Max 5 MB each.') }}</small></label>
        @endif
        <label class="event-admin-publish"><input type="checkbox" name="is_published" value="1" {{ old('is_published', $event->is_published) ? 'checked' : '' }}><span><b>{{ __('Publish this event') }}</b><small>{{ __('Published events appear on the public Events page.') }}</small></span></label>
        <div class="event-admin-form-actions"><button class="stitch-btn stitch-btn-primary" type="submit">{{ $isCreating ? __('Create event') : __('Save event') }}</button><a href="{{ route('admin.events.index') }}">{{ __('Cancel') }}</a></div>
    </section>
</form>

@unless ($isCreating)
    <section class="event-admin-panel event-admin-gallery-panel">
        <header><h2>{{ __('Event photos') }}</h2><p>{{ __('Sync photos from Google Drive, upload images directly, choose the cover image, or remove images.') }}</p></header>

        <div class="event-admin-drive-sync-card" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 10px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="color: #60a5fa;"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                        {{ __('Google Drive Photo Sync') }}
                    </h3>
                    <p style="margin: 0.25rem 0 0; color: #a1a1aa; font-size: 0.85rem;">{{ __('Automatically import all photos from your public Google Drive folder.') }}</p>
                </div>
                @if ($event->drive_url)
                    <a href="{{ $event->drive_url }}" target="_blank" rel="noopener noreferrer" class="stitch-btn stitch-btn-secondary" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                        {{ __('Open Drive folder') }} ↗
                    </a>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.events.drive.sync', $event) }}">
                @csrf
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                    <input type="url" name="drive_url" value="{{ old('drive_url', $event->drive_url) }}" placeholder="https://drive.google.com/drive/folders/..." style="flex: 1; min-width: 260px;" required>
                    <button class="stitch-btn stitch-btn-primary" type="submit" style="white-space: nowrap;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-inline-end: 4px;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        {{ __('Sync photos now') }}
                    </button>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem; color: #94a3b8;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="replace_existing" value="1">
                        <span>{{ __('Replace previously synced Drive photos') }}</span>
                    </label>
                </div>
            </form>
        </div>

        <div style="margin: 1.5rem 0 1rem; border-top: 1px dashed rgba(255, 255, 255, 0.1); padding-top: 1.25rem;">
            <p style="margin: 0 0 0.75rem; font-weight: 700; font-size: 0.95rem; color: #cbd5e1;">{{ __('Or upload images directly:') }}</p>
            <form class="event-admin-photo-upload" method="POST" action="{{ route('admin.events.photos.store', $event) }}" enctype="multipart/form-data">
                @csrf
                <label class="event-admin-upload"><span>{{ __('Add photos to gallery') }}</span><input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required><small>{{ __('Select up to 20 JPG, PNG, or WebP images. Max 5 MB each.') }}</small></label>
                <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Upload photos') }}</button>
            </form>
        </div>
        @if ($event->photos->isNotEmpty())
            <div class="event-admin-photo-grid">
                @foreach ($event->photos as $photo)
                    <article class="event-admin-photo">
                        <div class="event-admin-photo-image"><img src="{{ $photo->adminUrl() }}" alt="{{ $photo->alt ?: $event->translated('title') }}" loading="lazy">@if ($photo->is_cover)<span>{{ __('Cover') }}</span>@endif</div>
                        <div class="event-admin-photo-actions">
                            @unless ($photo->is_cover)
                                <form method="POST" action="{{ route('admin.events.photos.cover', ['event' => $event, 'photo' => $photo]) }}">@csrf @method('PATCH')<button type="submit">{{ __('Set as cover') }}</button></form>
                            @endunless
                            <form method="POST" action="{{ route('admin.events.photos.destroy', ['event' => $event, 'photo' => $photo]) }}" onsubmit="return confirm('{{ __('Remove this photo?') }}')">@csrf @method('DELETE')<button class="danger" type="submit">{{ __('Remove photo') }}</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="event-admin-empty-photo">{{ __('No photos have been added yet.') }}</div>
        @endif
    </section>
@endunless

@push('scripts')
<script>
(() => {
    const title = document.querySelector('[data-event-title]');
    const slug = document.querySelector('[data-event-slug]');
    if (!title || !slug || slug.value) return;
    title.addEventListener('input', () => {
        if (!slug.dataset.edited) slug.value = title.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
    slug.addEventListener('input', () => slug.dataset.edited = '1');
})();
</script>
@endpush
@endsection
