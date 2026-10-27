(() => {
    const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    document.querySelectorAll('[data-hero-section]').forEach((hero) => {
        const video = hero.querySelector('[data-hero-video]');
        const controls = hero.querySelector('[data-hero-video-controls]');
        if (!video || !controls) return;

        const playButton = controls.querySelector('[data-video-play]');
        const muteButton = controls.querySelector('[data-video-mute]');
        const labels = {
            play: controls.dataset.playLabel || 'Play hero video',
            pause: controls.dataset.pauseLabel || 'Pause hero video',
            unmute: controls.dataset.unmuteLabel || 'Turn video sound on',
            mute: controls.dataset.muteLabel || 'Mute video',
        };
        const setPlayState = () => {
            const paused = video.paused;
            playButton?.setAttribute('aria-label', paused ? labels.play : labels.pause);
            playButton?.setAttribute('title', paused ? labels.play : labels.pause);
            controls.querySelector('[data-icon-play]')?.toggleAttribute('hidden', !paused);
            controls.querySelector('[data-icon-pause]')?.toggleAttribute('hidden', paused);
        };
        const setMuteState = () => {
            const muted = video.muted;
            muteButton?.setAttribute('aria-label', muted ? labels.unmute : labels.mute);
            muteButton?.setAttribute('title', muted ? labels.unmute : labels.mute);
            muteButton?.setAttribute('aria-pressed', String(!muted));
            controls.querySelector('[data-icon-muted]')?.toggleAttribute('hidden', !muted);
            controls.querySelector('[data-icon-sound]')?.toggleAttribute('hidden', muted);
        };

        playButton?.addEventListener('click', () => {
            if (video.paused) {
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        });
        muteButton?.addEventListener('click', () => {
            video.muted = !video.muted;
            setMuteState();
            if (video.paused && !video.muted) video.play().catch(() => {});
        });

        video.addEventListener('play', setPlayState);
        video.addEventListener('pause', setPlayState);
        video.addEventListener('volumechange', setMuteState);
        video.addEventListener('error', () => {
            video.classList.add('is-unavailable');
            controls.hidden = true;
        });

        video.muted = true;
        setPlayState();
        setMuteState();
        if (reducedMotion) {
            video.removeAttribute('autoplay');
            video.pause();
        } else {
            video.play().catch(() => {});
        }
    });
})();
