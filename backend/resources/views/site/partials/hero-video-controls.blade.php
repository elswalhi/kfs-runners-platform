<div class="kfs-hero-video-controls" data-hero-video-controls
     data-play-label="{{ __('Play hero video') }}"
     data-pause-label="{{ __('Pause hero video') }}"
     data-unmute-label="{{ __('Turn video sound on') }}"
     data-mute-label="{{ __('Mute video') }}"
     aria-label="{{ __('Hero video controls') }}">
    <button type="button" data-video-play aria-label="{{ __('Pause hero video') }}" title="{{ __('Pause hero video') }}">
        <svg data-icon-pause viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14M16 5v14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        <svg data-icon-play viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m8 5 12 7-12 7z" fill="currentColor"/></svg>
    </button>
    <button type="button" data-video-mute aria-label="{{ __('Turn video sound on') }}" title="{{ __('Turn video sound on') }}" aria-pressed="false">
        <svg data-icon-muted viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10v4h4l5 4V6l-5 4H4Zm13-2 4 8m0-8-4 8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <svg data-icon-sound viewBox="0 0 24 24" aria-hidden="true" hidden><path d="M4 10v4h4l5 4V6l-5 4H4Zm13-1a5 5 0 0 1 0 6m2-9a9 9 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
</div>
