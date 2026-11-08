@extends('admin.layout')

@section('title', __('Configure event') . ' · ' . $event->translated('title'))
@section('section', __('Events'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-events.css') }}?v=20261108-multievent-1">
@endpush

@section('content')
<div class="event-admin-form-heading event-config-heading">
    <div><span class="stitch-eyebrow">{{ __('EVENT-SPECIFIC SETTINGS') }}</span><h1>{{ $event->translated('title') }}</h1><p>{{ __('Configure races, pricing, registration, payments, and QR details for this event only.') }}</p></div>
    <div class="event-config-heading-actions"><a href="{{ route('admin.events.index', ['event' => $event->id]) }}" class="event-admin-back">← {{ __('All events') }}</a><a href="{{ route('admin.events.edit', $event) }}" class="stitch-btn stitch-btn-secondary">{{ __('Edit details & photos') }}</a></div>
</div>

<div class="event-config-banner"><strong>{{ __('Only applies to this event') }}</strong><span>{{ __('Race settings and prices are isolated. Other event configurations remain unchanged.') }}</span></div>

<div class="event-config-layout">
    <nav class="event-config-tabs" role="tablist" aria-label="{{ __('Event configuration') }}">
        <button type="button" role="tab" aria-selected="true" class="active" data-event-tab="overview">{{ __('Overview') }}</button>
        <button type="button" role="tab" aria-selected="false" data-event-tab="race-pricing">{{ __('Race & pricing') }}</button>
        <button type="button" role="tab" aria-selected="false" data-event-tab="registration">{{ __('Registration') }}</button>
        <button type="button" role="tab" aria-selected="false" data-event-tab="payments">{{ __('Payments') }}</button>
        <button type="button" role="tab" aria-selected="false" data-event-tab="qr">{{ __('Race numbers & QR') }}</button>
        <button type="button" role="tab" aria-selected="false" data-event-tab="emails">{{ __('Emails') }}</button>
    </nav>

    <section class="event-config-panel" data-event-panel="overview" role="tabpanel">
        <header><div><h2>{{ __('Event overview') }}</h2><p>{{ __('Set the name, race day, and location shown to runners.') }}</p></div><span class="event-admin-status {{ $event->is_published ? 'published' : 'draft' }}">{{ $event->is_published ? __('Published') : __('Draft') }}</span></header>
        <form method="POST" action="{{ route('admin.events.settings.update', $event) }}">
            @csrf @method('PUT')
            <div class="event-config-form-grid">
                <label><span>{{ __('Event title (English)') }}</span><input name="title" value="{{ old('title', $event->title) }}" required maxlength="180"></label>
                <label dir="rtl"><span>{{ __('Event title (Arabic)') }}</span><input name="title_ar" value="{{ old('title_ar', $event->title_ar) }}" maxlength="180"></label>
                <label><span>{{ __('Race date and start time') }}</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}"></label>
                <label><span>{{ __('Event year') }}</span><input type="number" name="event_year" min="1900" max="2200" value="{{ old('event_year', $event->event_year) }}" required></label>
                <label><span>{{ __('Location (English)') }}</span><input name="location" value="{{ old('location', $event->location) }}" maxlength="180"></label>
                <label dir="rtl"><span>{{ __('Location (Arabic)') }}</span><input name="location_ar" value="{{ old('location_ar', $event->location_ar) }}" maxlength="180"></label>
                <label class="wide"><span>{{ __('Short event description') }}</span><textarea name="summary" rows="2" maxlength="280">{{ old('summary', $event->summary) }}</textarea></label>
                <label class="wide" dir="rtl"><span>{{ __('Short event description (Arabic)') }}</span><textarea name="summary_ar" rows="2" maxlength="280" dir="rtl">{{ old('summary_ar', $event->summary_ar) }}</textarea></label>
            </div>
            <div class="event-config-actions"><button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Save event overview') }}</button><a href="{{ route('events.show', $event->slug) }}" target="_blank" rel="noopener noreferrer">{{ __('Preview public event page') }} ↗</a></div>
        </form>
    </section>

    <section class="event-config-panel" data-event-panel="race-pricing" role="tabpanel" hidden id="race-pricing">
        <header><div><h2>{{ __('Race distances & pricing') }}</h2><p>{{ __('Each distance and price belongs only to :event.', ['event' => $event->translated('title')]) }}</p></div><span class="event-admin-count-pill">{{ $event->races->count() }} {{ __('distances') }}</span></header>
        <div class="event-config-race-list">
            @forelse ($event->races as $race)
                <article class="event-config-race-card">
                    <div class="event-config-race-card-title"><div><strong>{{ $race->distance_label }} · {{ $race->name }}</strong><small>{{ $race->registrations_count }} {{ __('registrations') }} · {{ __('Bib numbers start at') }} {{ $race->bib_number_start }}</small></div><span class="event-admin-race-state {{ $race->is_active ? 'on' : 'off' }}">{{ $race->is_active ? __('Active') : __('Paused') }}</span></div>
                    <form method="POST" action="{{ route('admin.events.races.update', ['event' => $event, 'race' => $race]) }}" class="event-config-race-form">
                        @csrf @method('PUT')
                        <label><span>{{ __('Race name') }}</span><input name="name" value="{{ $race->name }}" required></label>
                        <label><span>{{ __('Race slug') }}</span><input name="slug" value="{{ $race->slug }}" required></label>
                        <label><span>{{ __('Distance label') }}</span><input name="distance_label" value="{{ $race->distance_label }}" required></label>
                        <label><span>{{ __('Category') }}</span><select name="category_type"><option value="individual" @selected($race->category_type === 'individual')>{{ __('Individual') }}</option><option value="family" @selected($race->category_type === 'family')>{{ __('Family') }}</option><option value="para" @selected($race->category_type === 'para')>{{ __('Para race') }}</option></select></label>
                        <label><span>{{ __('Bib number start') }}</span><input type="number" name="bib_number_start" min="1" value="{{ $race->bib_number_start }}" required></label>
                        <label><span>{{ __('Sort order') }}</span><input type="number" name="sort_order" min="0" value="{{ $race->sort_order }}" required></label>
                        <label><span>{{ __('Full kit price (EGP)') }}</span><input type="number" name="full_price" min="1.01" step="0.01" value="{{ $race->kitPrices->firstWhere('kit_type', 'full')?->price }}" required></label>
                        <label><span>{{ __('Basic kit price (EGP)') }}</span><input type="number" name="basic_price" min="1.01" step="0.01" value="{{ $race->kitPrices->firstWhere('kit_type', 'basic')?->price }}" required></label>
                        <div class="event-config-race-actions"><label class="event-config-inline-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($race->is_active)> {{ __('Available for registration') }}</label><button class="stitch-btn stitch-btn-secondary" type="submit">{{ __('Save distance') }}</button></div>
                    </form>
                    @if ($race->registrations_count === 0)
                        <form method="POST" action="{{ route('admin.events.races.destroy', ['event' => $event, 'race' => $race]) }}" onsubmit="return confirm('{{ __('Remove this distance from the event?') }}')">@csrf @method('DELETE')<button class="event-admin-delete" type="submit">{{ __('Remove distance') }}</button></form>
                    @endif
                </article>
            @empty
                <div class="event-admin-empty-distance">{{ __('No race distances have been added to this event yet.') }}</div>
            @endforelse
        </div>
        <details class="event-config-add-race" @if ($errors->any()) open @endif>
            <summary>＋ {{ __('Add a distance to this event') }}</summary>
            <form method="POST" action="{{ route('admin.events.races.store', $event) }}" class="event-config-race-form">
                @csrf
                <label><span>{{ __('Race name') }}</span><input name="name" placeholder="Half Marathon" required></label>
                    <label><span>{{ __('Race slug') }}</span><input name="slug" placeholder="half-marathon" required></label>
                <label><span>{{ __('Distance label') }}</span><input name="distance_label" placeholder="21 KM" required></label>
                <label><span>{{ __('Category') }}</span><select name="category_type"><option value="individual">{{ __('Individual') }}</option><option value="family">{{ __('Family') }}</option><option value="para">{{ __('Para race') }}</option></select></label>
                <label><span>{{ __('Bib number start') }}</span><input type="number" name="bib_number_start" min="1" placeholder="2121" required></label>
                <label><span>{{ __('Sort order') }}</span><input type="number" name="sort_order" min="0" value="0"></label>
                <label><span>{{ __('Full kit price (EGP)') }}</span><input type="number" name="full_price" min="1.01" step="0.01" placeholder="650" required></label>
                <label><span>{{ __('Basic kit price (EGP)') }}</span><input type="number" name="basic_price" min="1.01" step="0.01" placeholder="450" required></label>
                <label class="event-config-inline-check"><input type="checkbox" name="is_active" value="1" checked> {{ __('Available for registration') }}</label>
                <div class="event-config-actions"><button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Add distance') }}</button></div>
            </form>
        </details>
    </section>

    <section class="event-config-panel" data-event-panel="registration" role="tabpanel" hidden>
        <header><div><h2>{{ __('Registration controls') }}</h2><p>{{ __('Open or close signups for this event without affecting other events.') }}</p></div></header>
        <form method="POST" action="{{ route('admin.events.settings.update', $event) }}" class="event-config-toggle-form">
            @csrf @method('PUT')
            @foreach ([['is_current', __('Show as a current event'), $event->is_current], ['registration_enabled', __('Accept new registrations'), $event->registration_enabled], ['countdown_enabled', __('Show countdown on the public event page'), $event->countdown_enabled], ['is_published', __('Publish the public event page'), $event->is_published], ['is_default', __('Use as the default marathon page'), $event->is_default]] as [$name, $label, $checked])
                <label class="event-config-toggle"><span><strong>{{ $label }}</strong><small>{{ $name === 'is_default' ? __('Used by the main Marathon link and /register.') : __('This setting is saved only for this event.') }}</small></span><span class="event-config-switch"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked($checked)><i></i></span></label>
            @endforeach
            <div class="event-config-actions"><button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Save registration settings') }}</button></div>
        </form>
    </section>

    <section class="event-config-panel" data-event-panel="payments" role="tabpanel" hidden>
        <header><div><h2>{{ __('Payment settings') }}</h2><p>{{ __('Control payment availability for this event.') }}</p></div></header>
        <div class="event-config-payment-callout"><span aria-hidden="true">◉</span><div><strong>{{ __('EasyKash Cash') }}</strong><p>{{ __('Gateway credentials are shared by the website. Payments are attached to this event through its race distances.') }}</p></div></div>
        <form method="POST" action="{{ route('admin.events.settings.update', $event) }}" class="event-config-toggle-form">
            @csrf @method('PUT')
            <label class="event-config-toggle"><span><strong>{{ __('Accept EasyKash payments') }}</strong><small>{{ __('When disabled, paid registration attempts for this event are rejected.') }}</small></span><span class="event-config-switch"><input type="hidden" name="payments_enabled" value="0"><input type="checkbox" name="payments_enabled" value="1" @checked($event->payments_enabled)><i></i></span></label>
            <div class="event-config-actions"><button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Save payment settings') }}</button></div>
        </form>
    </section>

    <section class="event-config-panel" data-event-panel="qr" role="tabpanel" hidden>
        <header><div><h2>{{ __('Race numbers & QR') }}</h2><p>{{ __('Set each distance’s bib sequence and choose the fields encoded in this event’s runner QR.') }}</p></div></header>
        <div class="event-config-bib-summary"><strong>{{ $event->races->count() }}</strong><span>{{ __('distance-specific number sequences') }}</span></div>
        <form method="POST" action="{{ route('admin.events.settings.update', $event) }}" class="event-config-qr-form">
            @csrf @method('PUT')
            <p>{{ __('QR code fields') }}</p>
            <div class="event-config-qr-grid">
                @foreach ($qrFields as $key => $label)
                    <label><input type="checkbox" name="qr_fields[]" value="{{ $key }}" @checked(in_array($key, $selectedQrFields, true))><span>{{ $label }}</span></label>
                @endforeach
            </div>
            <small>{{ __('The runner QR is generated after payment and uses this event’s selected fields.') }}</small>
            <div class="event-config-actions"><button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Save QR settings') }}</button></div>
        </form>
    </section>

    <section class="event-config-panel" data-event-panel="emails" role="tabpanel" hidden>
        <header><div><h2>{{ __('Runner emails') }}</h2><p>{{ __('Email content uses this event’s details and race information.') }}</p></div></header>
        <div class="event-config-email-preview"><span class="stitch-eyebrow">{{ __('PAYMENT CONFIRMATION') }}</span><h3>{{ __('Your KFS Runners entry is confirmed') }}</h3><p>{{ __('After EasyKash confirms payment, the runner receives their event, race number, kit details, and QR code by email.') }}</p><div><span>{{ __('Event') }}</span><strong>{{ $event->translated('title') }}</strong></div><div><span>{{ __('Active race distances') }}</span><strong>{{ $event->races->where('is_active', true)->count() }}</strong></div><a href="{{ route('admin.localization') }}">{{ __('Manage shared email translations') }} →</a></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const tabs = [...document.querySelectorAll('[data-event-tab]')];
    const panels = [...document.querySelectorAll('[data-event-panel]')];
    const activate = (name) => {
        tabs.forEach(tab => {
            const active = tab.dataset.eventTab === name;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(panel => panel.hidden = panel.dataset.eventPanel !== name);
        if (history.replaceState) history.replaceState(null, '', `#${name}`);
    };
    tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.eventTab)));
    const requestedPanel = window.location.hash.replace('#', '');
    if (tabs.some(tab => tab.dataset.eventTab === requestedPanel)) activate(requestedPanel);
})();
</script>
@endpush
