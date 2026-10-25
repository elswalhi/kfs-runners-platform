@extends('admin.layout')
@section('title', 'Localization & Languages · KFS Runners Admin')
@section('section', __('Localization'))
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">SYSTEM & I18N</span>
        <h1>{{ __('Localization') }} & {{ __('Language') }}</h1>
        <p>Manage bilingual platform settings (Arabic 🇪🇬 & English 🇬🇧), default visitor language, and live phrase translations.</p>
    </div>
</div>

<!-- 4 KPI Cards -->
<div class="stitch-kpi-grid">
    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">DEFAULT LANGUAGE</span>
            <div class="stitch-kpi-icon icon-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val" style="font-size: 22px;">{{ $defaultLocale === 'ar' ? 'العربية (AR)' : 'English (EN)' }}</div>
        <div class="stitch-kpi-sub">Default for new visitors</div>
    </div>

    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">SUPPORTED LOCALES</span>
            <div class="stitch-kpi-icon icon-green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val">2 Languages</div>
        <div class="stitch-kpi-sub">Arabic (RTL) & English (LTR)</div>
    </div>

    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">ACTIVE DIRECTION</span>
            <div class="stitch-kpi-icon icon-amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val" style="font-size: 20px;">{{ app()->getLocale() === 'ar' ? 'RTL (يمين إلى يسار)' : 'LTR (Left-to-Right)' }}</div>
        <div class="stitch-kpi-sub">Current admin layout</div>
    </div>

    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">TRANSLATED STRINGS</span>
            <div class="stitch-kpi-icon icon-emerald">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val">{{ $totalStrings }} phrases</div>
        <div class="stitch-kpi-sub">100% dictionary synced</div>
    </div>
</div>

<!-- General Settings Panel -->
<div class="stitch-table-panel" style="margin-bottom: 28px;">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">Platform Localization Settings</h2>
            <p class="stitch-panel-subtitle">Configure which language loads by default and toggle language switchers across the platform.</p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST" action="{{ route('admin.localization.update') }}">
            @csrf
            <div class="stitch-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <div class="stitch-form-group">
                    <label>Default Website Language (اللغة الافتراضية للزوار)</label>
                    <select name="default_locale" class="stitch-select" style="width: 100%;">
                        <option value="en" @selected($defaultLocale === 'en')>🇬🇧 English (Default)</option>
                        <option value="ar" @selected($defaultLocale === 'ar')>🇪🇬 العربية (الافتراضية)</option>
                    </select>
                </div>

                <div class="stitch-form-group">
                    <label>Arabic Web Font (خط اللغة العربية)</label>
                    <select name="arabic_font" class="stitch-select" style="width: 100%;">
                        <option value="Cairo" @selected($arabicFont === 'Cairo')>Cairo (كايرو - عصري ومميز)</option>
                        <option value="Alexandria" @selected($arabicFont === 'Alexandria')>Alexandria (الإسكندرية)</option>
                        <option value="Tajawal" @selected($arabicFont === 'Tajawal')>Tajawal (تجوال)</option>
                    </select>
                </div>

                <div class="stitch-form-group" style="justify-content: center; padding-top: 14px;">
                    <label class="stitch-checkbox-label">
                        <input type="checkbox" name="show_public_switcher" value="1" @checked($showPublicSwitcher)>
                        Show Language Switcher in Public Header
                    </label>
                </div>

                <div class="stitch-form-group" style="justify-content: center; padding-top: 14px;">
                    <label class="stitch-checkbox-label">
                        <input type="checkbox" name="show_admin_switcher" value="1" @checked($showAdminSwitcher)>
                        Show Language Switcher in Admin Header
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; pt: 8px;">
                <button class="stitch-btn stitch-btn-primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                    Save Localization Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Live Phrase & Translation Editor Panel -->
<div class="stitch-table-panel">
    <div class="stitch-panel-header" style="flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 class="stitch-panel-title">Translation Dictionary & Phrase Editor</h2>
            <p class="stitch-panel-subtitle">Edit any phrase in English or Arabic directly. Changes take effect across public and admin pages.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="text" id="phrase-filter" class="stitch-input" placeholder="Search phrases / ابحث عن نص..." style="height: 38px; min-width: 240px; font-size: 12px; padding: 0 12px;">
        </div>
    </div>

    <form method="POST" action="{{ route('admin.localization.translations') }}" id="translations-form">
        @csrf
        <div class="stitch-table-container">
            <table class="stitch-table" id="translations-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Phrase Key</th>
                        <th style="width: 35%;">English (EN)</th>
                        <th style="width: 40%; text-align: right;">Arabic Translation (العربية - AR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($translations as $index => $item)
                        <tr class="translation-row">
                            <td style="font-family: monospace; font-size: 11px; color: var(--st-text-muted); word-break: break-all;">
                                {{ $item['key'] }}
                            </td>
                            <td>
                                <input type="text" name="translations[{{ $item['key'] }}][en]" value="{{ $item['en'] }}" class="stitch-input" style="height: 36px; font-size: 12px; padding: 0 10px;">
                            </td>
                            <td>
                                <input type="text" name="translations[{{ $item['key'] }}][ar]" value="{{ $item['ar'] }}" class="stitch-input" style="height: 36px; font-size: 12px; padding: 0 10px; text-align: right; font-family: 'Cairo', sans-serif;" dir="rtl">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding: 20px 24px; background: var(--st-surface-subtle); border-top: 1px solid var(--st-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <span style="font-size: 12px; color: var(--st-text-muted);">
                Saving will update <code>lang/ar.json</code> and <code>lang/en.json</code> instantly.
            </span>
            <button class="stitch-btn stitch-btn-primary" type="submit" style="height: 42px; padding: 0 24px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save All Translations
            </button>
        </div>
    </form>
</div>

<script>
    const filterInput = document.getElementById('phrase-filter');
    const rows = document.querySelectorAll('.translation-row');

    filterInput?.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const inputs = Array.from(row.querySelectorAll('input')).map(i => i.value.toLowerCase()).join(' ');
            row.hidden = !(text.includes(query) || inputs.includes(query));
        });
    });
</script>
@endsection
