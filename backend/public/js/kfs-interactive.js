/**
 * KFS Runners - Interactive UI Engine (Powered by ui-skills standards)
 * Microinteractions, Stepper, Live Validation, Toasts, Countdown
 */

(() => {
    'use strict';

    // 1. Toast Notification System
    window.kfsToast = (message, type = 'success', duration = 3500) => {
        let container = document.getElementById('kfs-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'kfs-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `kfs-toast kfs-toast-${type}`;

        const iconMap = {
            success: '✓',
            error: '✕',
            info: 'ℹ'
        };

        toast.innerHTML = `
            <span class="kfs-toast-icon">${iconMap[type] || '•'}</span>
            <span class="kfs-toast-msg">${message}</span>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });

        // Animate out & remove
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // 2. Clipboard Helper with Visual Feedback
    window.kfsCopy = async (text, button) => {
        try {
            await navigator.clipboard.writeText(text);
            if (button) {
                const originalText = button.textContent;
                button.classList.add('copied');
                button.textContent = '✓ ' + (document.documentElement.lang === 'ar' ? 'تم النسخ' : 'Copied');
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.textContent = originalText;
                }, 2000);
            }
            window.kfsToast(document.documentElement.lang === 'ar' ? 'تم النسخ للحافظة بنجاح!' : 'Copied to clipboard!', 'success');
        } catch (e) {
            window.kfsToast('Failed to copy', 'error');
        }
    };

    // 3. Live Egyptian Phone & Form Validation
    const initFormValidation = () => {
        const phoneInput = document.querySelector('input[name="phone"]');
        const emailInput = document.querySelector('input[name="email"]');

        if (phoneInput) {
            const formatEgyptianPhone = (val) => {
                let clean = val.replace(/[^\d+]/g, '');
                if (clean.startsWith('+20')) {
                    // +20 10 1234 5678
                    const rest = clean.slice(3);
                    if (rest.length <= 2) return `+20 ${rest}`;
                    if (rest.length <= 6) return `+20 ${rest.slice(0, 2)} ${rest.slice(2)}`;
                    return `+20 ${rest.slice(0, 2)} ${rest.slice(2, 6)} ${rest.slice(6, 10)}`;
                } else {
                    // 010 1234 5678
                    clean = clean.replace(/[^\d]/g, '');
                    if (clean.length <= 3) return clean;
                    if (clean.length <= 7) return `${clean.slice(0, 3)} ${clean.slice(3)}`;
                    return `${clean.slice(0, 3)} ${clean.slice(3, 7)} ${clean.slice(7, 11)}`;
                }
            };

            const validatePhone = () => {
                const raw = phoneInput.value.replace(/\s+/g, '');
                const isValid = /^(010|011|012|015)\d{8}$/.test(raw) || /^\+20(10|11|12|15)\d{8}$/.test(raw);
                const wrap = phoneInput.closest('.kfs-field-wrap');
                const hint = document.getElementById('phone-validation-hint');

                if (raw.length === 0) {
                    phoneInput.classList.remove('is-valid', 'is-invalid');
                    if (wrap) wrap.classList.remove('is-valid', 'is-invalid');
                    if (hint) { hint.className = 'kfs-field-hint'; hint.textContent = hint.dataset.default || ''; }
                    return false;
                }

                if (isValid) {
                    phoneInput.classList.remove('is-invalid');
                    phoneInput.classList.add('is-valid');
                    if (wrap) { wrap.classList.remove('is-invalid'); wrap.classList.add('is-valid'); }
                    if (hint) {
                        hint.className = 'kfs-field-hint is-valid';
                        hint.textContent = '✓ ' + (document.documentElement.lang === 'ar' ? 'رقم هاتف مصري معتمد' : 'Valid Egyptian mobile number');
                    }
                    return true;
                } else {
                    phoneInput.classList.remove('is-valid');
                    phoneInput.classList.add('is-invalid');
                    if (wrap) { wrap.classList.remove('is-valid'); wrap.classList.add('is-invalid'); }
                    if (hint) {
                        hint.className = 'kfs-field-hint is-invalid';
                        hint.textContent = '✕ ' + (document.documentElement.lang === 'ar' ? 'يجب أن يتكون من 11 رقماً ويبدأ بـ 010، 011، 012، أو 015' : 'Must be 11 digits starting with 010, 011, 012, or 015');
                    }
                    return false;
                }
            };

            phoneInput.addEventListener('input', (e) => {
                const curPos = phoneInput.selectionStart;
                phoneInput.value = formatEgyptianPhone(phoneInput.value);
                validatePhone();
            });

            phoneInput.addEventListener('blur', validatePhone);
        }

        if (emailInput) {
            const validateEmail = () => {
                const val = emailInput.value.trim();
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
                const wrap = emailInput.closest('.kfs-field-wrap');

                if (val.length === 0) {
                    emailInput.classList.remove('is-valid', 'is-invalid');
                    if (wrap) wrap.classList.remove('is-valid', 'is-invalid');
                    return false;
                }

                if (isValid) {
                    emailInput.classList.remove('is-invalid');
                    emailInput.classList.add('is-valid');
                    if (wrap) { wrap.classList.remove('is-invalid'); wrap.classList.add('is-valid'); }
                    return true;
                } else {
                    emailInput.classList.remove('is-valid');
                    emailInput.classList.add('is-invalid');
                    if (wrap) { wrap.classList.remove('is-valid'); wrap.classList.add('is-invalid'); }
                    return false;
                }
            };

            emailInput.addEventListener('input', validateEmail);
            emailInput.addEventListener('blur', validateEmail);
        }
    };

    // 4. Interactive Countdown Timer (Homepage)
    const initCountdown = () => {
        const countdownEl = document.getElementById('kfs-race-countdown');
        if (!countdownEl) return;

        // Target Date: Either data-target or Nov 20, 2026
        const targetStr = countdownEl.dataset.target || '2026-11-20T07:00:00+02:00';
        const targetTime = new Date(targetStr).getTime();

        const daysEl = document.getElementById('countdown-days');
        const hoursEl = document.getElementById('countdown-hours');
        const minsEl = document.getElementById('countdown-mins');
        const secsEl = document.getElementById('countdown-secs');

        const updateTimer = () => {
            const now = Date.now();
            const diff = Math.max(0, targetTime - now);

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((diff / 1000 / 60) % 60);
            const seconds = Math.floor((diff / 1000) % 60);

            if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
            if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
            if (minsEl) minsEl.textContent = String(minutes).padStart(2, '0');
            if (secsEl) secsEl.textContent = String(seconds).padStart(2, '0');
        };

        updateTimer();
        setInterval(updateTimer, 1000);
    };

    // 5. Homepage Category Filter Pills
    const initCategoryFilters = () => {
        const filterBtns = document.querySelectorAll('.kfs-filter-pill');
        const cards = document.querySelectorAll('.home-distance-card');

        if (!filterBtns.length || !cards.length) return;

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.dataset.filter;

                cards.forEach(card => {
                    const kicker = card.querySelector('.home-card-kicker')?.textContent.toLowerCase() || '';
                    const title = card.querySelector('h3')?.textContent.toLowerCase() || '';

                    let matches = false;
                    if (filter === 'all') {
                        matches = true;
                    } else if (filter === '21k' && (kicker.includes('21') || title.includes('21'))) {
                        matches = true;
                    } else if (filter === '10k' && (kicker.includes('10') || title.includes('10'))) {
                        matches = true;
                    } else if (filter === '5k' && (kicker.includes('5') || title.includes('5'))) {
                        matches = true;
                    } else if (filter === 'family' && (kicker.includes('family') || kicker.includes('para') || title.includes('عائل') || title.includes('همم'))) {
                        matches = true;
                    }

                    if (matches) {
                        card.style.display = '';
                        card.style.animation = 'kfsStepFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    };

    // Run on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        initFormValidation();
        initCountdown();
        initCategoryFilters();
    });
})();
