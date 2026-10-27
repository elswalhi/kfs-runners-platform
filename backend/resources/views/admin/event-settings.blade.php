@extends('admin.layout')
@section('title', __('Race Date & Countdown') . ' · KFS Runners Admin')
@section('section', __('Event Settings'))
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">{{ __('EVENT CONFIGURATION') }}</span>
        <h1>{{ __('Race Date & Countdown Timer') }}</h1>
        <p>{{ __('Configure the official marathon date and start time, and show or hide the live countdown on the marathon event page.') }}</p>
    </div>
</div>

@if(session('success'))
<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:16px 20px; border-radius:12px; margin-bottom:24px; display:flex; align-items:center; gap:12px; font-weight:600;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

<div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:24px; margin-bottom:32px; align-items:start;">
    <!-- Form Panel -->
    <div class="stitch-table-panel">
        <div class="stitch-panel-header">
            <div>
                <h2 class="stitch-panel-title">{{ __('Official Race Schedule') }}</h2>
                <p class="stitch-panel-subtitle">{{ __('Set the target day and time for the countdown clock on the marathon event page.') }}</p>
            </div>
        </div>
        <div style="padding: 24px;">
            <form method="POST" action="{{ route('admin.event-settings.update') }}">
                @csrf
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div class="stitch-form-group">
                        <label style="font-weight:700; color:#1e293b; margin-bottom:8px; display:block;">
                            📅 {{ __('Race Date') }}
                        </label>
                        <input type="date" required name="race_date" id="input-race-date" value="{{ old('race_date', $raceDate) }}" class="stitch-input" style="font-size:15px; padding:10px 14px;">
                        <small style="color:#64748b; font-size:12px; margin-top:4px;">{{ __('The date the marathon takes place.') }}</small>
                    </div>

                    <div class="stitch-form-group">
                        <label style="font-weight:700; color:#1e293b; margin-bottom:8px; display:block;">
                            ⏰ {{ __('Start Time') }}
                        </label>
                        <input type="time" required name="race_time" id="input-race-time" value="{{ old('race_time', $raceTime) }}" class="stitch-input" style="font-size:15px; padding:10px 14px;">
                        <small style="color:#64748b; font-size:12px; margin-top:4px;">{{ __('Expected starting gun time (e.g. 07:00 AM).') }}</small>
                    </div>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:24px;">
                    <label class="stitch-checkbox-label" style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                        <input type="checkbox" name="countdown_enabled" id="input-countdown-enabled" value="1" {{ $countdownEnabled ? 'checked' : '' }} style="width:18px; height:18px; accent-color:#b81420;">
                        <div>
                            <span style="font-weight:700; color:#0f172a; display:block;">{{ __('Show Countdown Timer on Homepage') }}</span>
                            <span style="color:#64748b; font-size:12px;">{{ __('When unchecked, the countdown is hidden from the marathon event page.') }}</span>
                        </div>
                    </label>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button class="stitch-btn stitch-btn-primary" type="submit" style="padding:12px 28px; font-weight:700; font-size:15px; background:#b81420; border-color:#8e0e17;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>{{ __('Save Race Date & Countdown') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Preview Card -->
    <div class="stitch-table-panel">
        <div class="stitch-panel-header">
            <div>
                <h2 class="stitch-panel-title">👁️ {{ __('Live Homepage Preview') }}</h2>
                <p class="stitch-panel-subtitle">{{ __('How the countdown appears to visitors right now.') }}</p>
            </div>
        </div>
        <div style="padding: 24px; background: #0f172a; border-bottom-left-radius:12px; border-bottom-right-radius:12px; color:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:220px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; text-transform:uppercase; font-size:11px; letter-spacing:0.1em; color:#ef4444; font-weight:800;">
                <span style="width:8px; height:8px; border-radius:50%; background:#ef4444; display:inline-block; box-shadow:0 0 8px #ef4444;"></span>
                <span>{{ __('RACE DAY COUNTDOWN') }}</span>
            </div>
            
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="background:#1e293b; border:1px solid #334155; border-radius:8px; padding:10px 12px; text-align:center; min-width:58px;">
                    <span id="preview-days" style="display:block; font-size:24px; font-weight:900; font-family:monospace; line-height:1;">--</span>
                    <span style="display:block; font-size:10px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">{{ __('DAYS') }}</span>
                </div>
                <span style="color:#ef4444; font-weight:900; font-size:18px;">:</span>
                <div style="background:#1e293b; border:1px solid #334155; border-radius:8px; padding:10px 12px; text-align:center; min-width:58px;">
                    <span id="preview-hours" style="display:block; font-size:24px; font-weight:900; font-family:monospace; line-height:1;">--</span>
                    <span style="display:block; font-size:10px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">{{ __('HOURS') }}</span>
                </div>
                <span style="color:#ef4444; font-weight:900; font-size:18px;">:</span>
                <div style="background:#1e293b; border:1px solid #334155; border-radius:8px; padding:10px 12px; text-align:center; min-width:58px;">
                    <span id="preview-mins" style="display:block; font-size:24px; font-weight:900; font-family:monospace; line-height:1;">--</span>
                    <span style="display:block; font-size:10px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">{{ __('MINS') }}</span>
                </div>
                <span style="color:#ef4444; font-weight:900; font-size:18px;">:</span>
                <div style="background:#1e293b; border:1px solid #334155; border-radius:8px; padding:10px 12px; text-align:center; min-width:58px;">
                    <span id="preview-secs" style="display:block; font-size:24px; font-weight:900; font-family:monospace; line-height:1; color:#ef4444;">--</span>
                    <span style="display:block; font-size:10px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">{{ __('SECS') }}</span>
                </div>
            </div>

            <div style="margin-top:18px; font-size:12px; color:#94a3b8; text-align:center;">
                {{ __('Configured for:') }} <strong id="preview-target-text" style="color:#f1f5f9;">{{ $raceDate }} {{ $raceTime }}</strong>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('input-race-date');
    const timeInput = document.getElementById('input-race-time');
    const previewDays = document.getElementById('preview-days');
    const previewHours = document.getElementById('preview-hours');
    const previewMins = document.getElementById('preview-mins');
    const previewSecs = document.getElementById('preview-secs');
    const previewText = document.getElementById('preview-target-text');

    const updatePreview = () => {
        const dVal = dateInput.value || '{{ $raceDate }}';
        const tVal = timeInput.value || '{{ $raceTime }}';
        previewText.textContent = `${dVal} at ${tVal}`;

        const targetTime = new Date(`${dVal}T${tVal}:00`).getTime();
        const now = Date.now();
        const diff = Math.max(0, targetTime - now);

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
        const mins = Math.floor((diff / 1000 / 60) % 60);
        const secs = Math.floor((diff / 1000) % 60);

        if (previewDays) previewDays.textContent = String(days).padStart(2, '0');
        if (previewHours) previewHours.textContent = String(hours).padStart(2, '0');
        if (previewMins) previewMins.textContent = String(mins).padStart(2, '0');
        if (previewSecs) previewSecs.textContent = String(secs).padStart(2, '0');
    };

    dateInput.addEventListener('input', updatePreview);
    timeInput.addEventListener('input', updatePreview);
    setInterval(updatePreview, 1000);
    updatePreview();
});
</script>
@endsection
