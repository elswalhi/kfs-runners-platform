@php
    $themeReq = request('theme');
    if ($themeReq && in_array($themeReq, ['teal_gold', 'original', 'new'], true)) {
        session(['site_theme' => ($themeReq === 'new' ? 'teal_gold' : $themeReq)]);
    }
    $siteTheme = session('site_theme', \App\Models\AppSetting::get('site_theme', 'original'));
    $isTealGold = ($siteTheme === 'teal_gold');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="{{ $isTealGold ? 'teal-gold' : 'original' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $isTealGold ? '#0D2B3A' : '#064b3e' }}">
    <script>
        (function() {
            try {
                var saved = localStorage.getItem('kfs_color_mode');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var mode = saved ? saved : (prefersDark ? 'dark' : 'light');
                if (mode === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-color-mode', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-color-mode', 'light');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title', 'KFS Runners · Kafr El-Sheikh')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kfs-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-payment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-blade.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-interactive.css') }}?v=20261027-mobile-1">
    <link rel="stylesheet" href="{{ asset('css/kfs-hero-video.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-theme-palette.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-dark-mode.css') }}?v=20261028-1">
    <style>
        [dir="rtl"] body {
            font-family: 'Cairo', 'Inter', sans-serif;
            direction: rtl;
            text-align: right;
        }
        [dir="rtl"] .main-nav {
            gap: 20px;
        }
        [dir="rtl"] .brand-mark {
            transform: rotate(18deg);
        }
        [dir="rtl"] .order-detail-row b {
            text-align: left;
        }
        [dir="rtl"] .home-assurances span i {
            margin-right: 0;
            margin-left: 6px;
        }
        .lang-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            padding: 8px 14px;
            border: 1px solid #dce5df;
            border-radius: 100px;
            background: #ffffff;
            color: #064b3e;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .lang-switch-btn:hover {
            border-color: #064b3e;
            background: #f0fdf4;
        }
    </style>
    @stack('styles')
</head>
<body class="{{ $isTealGold ? 'theme-teal-gold' : 'theme-original' }}">
<header class="site-header">
    <div class="header-inner wrap">
        <a href="{{ route('home') }}" class="brand-link" aria-label="KFS Runners home">
            <img class="brand-logo brand-logo-header" src="{{ asset('images/kfs-runners-logo.png') }}" alt="KFS Runners · Kafr El-Sheikh">
        </a>
        <nav class="main-nav" aria-label="Main navigation" id="main-nav">
            <a href="{{ route('home') }}">{{ __('Home') }}</a>
            <a href="{{ route('home') }}#our-sports">{{ __('Our sports') }}</a>
            <a href="{{ route('home') }}#our-groups">{{ __('Our groups') }}</a>
            <a href="{{ route('events.index') }}">{{ __('Events') }}</a>
            <a href="{{ route('register') }}" class="nav-cta">{{ __('Register now') }} <span aria-hidden="true">↗</span></a>
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="lang-switch-btn" title="Toggle language">
                <span>{{ app()->getLocale() === 'ar' ? '🇬🇧 English' : '🇪🇬 العربية' }}</span>
            </a>
            <button type="button" class="theme-toggle-btn nav-inline-toggle" id="theme-toggle-mobile-menu" aria-label="{{ __('Toggle dark mode') }}">
                <span class="theme-toggle-icon-wrap" aria-hidden="true">🌙</span>
                <span class="theme-toggle-label">{{ __('Dark mode') }}</span>
            </button>
        </nav>
        <div class="header-controls">
            <button type="button" class="theme-toggle-btn header-toggle" id="theme-toggle-btn" aria-label="{{ __('Toggle dark mode') }}" title="{{ __('Toggle dark mode') }}">
                <span class="theme-icon-sun" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </span>
                <span class="theme-icon-moon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </span>
            </button>
            <button class="mobile-menu-btn" type="button" aria-label="Open menu" aria-expanded="false" id="mobile-menu-toggle">☰</button>
        </div>
    </div>
</header>

@yield('content')

<footer class="site-footer">
    <div class="wrap footer-main">
        <div class="footer-brand-col"><img class="brand-logo brand-logo-footer" src="{{ asset('images/kfs-runners-logo.png') }}" alt="KFS Runners · Kafr El-Sheikh"><p>{{ __('More than a race. A community that moves forward together.') }}</p></div>
        <div class="footer-links"><h4>{{ __('Explore') }}</h4><a href="{{ route('home') }}">{{ __('Home') }}</a><a href="{{ route('home') }}#our-sports">{{ __('Our sports') }}</a><a href="{{ route('home') }}#our-groups">{{ __('Our groups') }}</a><a href="{{ route('events.index') }}">{{ __('Events') }}</a><a href="{{ route('event') }}">{{ __('Current marathon') }}</a><a href="{{ route('register') }}">{{ __('Marathon registration') }}</a></div>
        <div class="footer-links"><h4>{{ __('Get in touch') }}</h4><a href="mailto:elatikysports@gmail.com">✉ elatikysports@gmail.com</a><a href="tel:01508015156">☎ 01508015156</a><span>{{ __('Kafr El-Sheikh, Egypt') }}</span></div>
        <div class="footer-social-col"><h4>{{ __('Follow the movement') }}</h4><div class="social-row"><a href="https://www.facebook.com/KFSRUNNER/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">f</a><a href="https://www.instagram.com/" target="_blank" rel="noreferrer" aria-label="Instagram">◎</a><a href="{{ route('register') }}" aria-label="Register">↗</a></div><p>{{ __('Find your next finish line.') }}</p></div>
    </div>
    <div class="footer-bottom"><div class="wrap footer-bottom-inner"><span>© {{ now()->year }} KFS Runners · El-Atiky Sports Services</span><span>Run · Challenge · Connect</span></div></div>
</footer>
<div id="kfs-toast-container"></div>
<script>
    const menuButton = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('main-nav');
    menuButton?.addEventListener('click', () => {
        const open = menu.classList.toggle('nav-open');
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.textContent = open ? '×' : '☰';
    });
</script>
<script src="{{ asset('js/kfs-interactive.js') }}"></script>
<script src="{{ asset('js/kfs-hero-video.js') }}"></script>
<script>
    (function() {
        const doc = document.documentElement;

        function getTheme() {
            return doc.getAttribute('data-color-mode') === 'dark' ? 'dark' : 'light';
        }

        function applyTheme(mode) {
            const isDark = (mode === 'dark');
            if (isDark) {
                doc.classList.add('dark');
                doc.setAttribute('data-color-mode', 'dark');
            } else {
                doc.classList.remove('dark');
                doc.setAttribute('data-color-mode', 'light');
            }

            try {
                localStorage.setItem('kfs_color_mode', mode);
            } catch (e) {}

            const metaTheme = document.querySelector('meta[name="theme-color"]');
            if (metaTheme) {
                metaTheme.setAttribute('content', isDark ? '#0b1210' : '#064b3e');
            }

            const isAr = doc.getAttribute('lang') === 'ar';

            // Update mobile inline toggle
            document.querySelectorAll('.nav-inline-toggle').forEach(btn => {
                const label = btn.querySelector('.theme-toggle-label');
                const icon = btn.querySelector('.theme-toggle-icon-wrap');
                if (label) {
                    label.textContent = isDark 
                        ? (isAr ? 'الوضع النهاري' : 'Light Mode') 
                        : (isAr ? 'الوضع الليلي' : 'Dark Mode');
                }
                if (icon) {
                    icon.textContent = isDark ? '☀️' : '🌙';
                }
            });

            // Update header toggle title & aria-label
            document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
                btn.setAttribute('aria-pressed', String(isDark));
                const titleText = isDark 
                    ? (isAr ? 'تفعيل الوضع النهاري' : 'Switch to Light Mode') 
                    : (isAr ? 'تفعيل الوضع الليلي' : 'Switch to Dark Mode');
                btn.setAttribute('title', titleText);
                btn.setAttribute('aria-label', titleText);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(getTheme());

            document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    applyTheme(getTheme() === 'dark' ? 'light' : 'dark');
                });
            });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
