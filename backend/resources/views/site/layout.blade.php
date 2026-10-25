<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#064b3e">
    <title>@yield('title', 'KFS Runners · Kafr El-Sheikh')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kfs-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-payment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-blade.css') }}">
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
<body>
<header class="site-header">
    <div class="header-inner wrap">
        <a href="{{ route('home') }}" class="brand-link" aria-label="KFS Runners home">
            <span class="brand"><span class="brand-mark" aria-hidden="true">♧</span><span class="brand-copy"><span class="brand-kfs">KFS <b>RUNNERS</b></span><span class="brand-location">{{ __('Kafr El-Sheikh, Egypt') }}</span></span></span>
        </a>
        <button class="mobile-menu-btn" type="button" aria-label="Open menu" aria-expanded="false" id="mobile-menu-toggle">☰</button>
        <nav class="main-nav" aria-label="Main navigation" id="main-nav">
            <a href="{{ route('home') }}#the-race">{{ __('The race') }}</a>
            <a href="{{ route('home') }}#race-categories">{{ __('Race categories') }}</a>
            <a href="{{ route('home') }}#whats-included">{{ __('What\'s included') }}</a>
            <a href="{{ route('home') }}#faq">{{ __('FAQ') }}</a>
            <a href="{{ route('register') }}" class="nav-cta">{{ __('Register now') }} <span aria-hidden="true">↗</span></a>
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="lang-switch-btn" title="Toggle language">
                <span>{{ app()->getLocale() === 'ar' ? '🇬🇧 English' : '🇪🇬 العربية' }}</span>
            </a>
        </nav>
    </div>
</header>

@yield('content')

<footer class="site-footer">
    <div class="wrap footer-main">
        <div class="footer-brand-col"><span class="brand brand-light"><span class="brand-mark" aria-hidden="true">♧</span><span class="brand-copy"><span class="brand-kfs">KFS <b>RUNNERS</b></span><span class="brand-location">KAFR EL-SHEIKH</span></span></span><p>{{ __('More than a race. A community that moves forward together.') }}</p></div>
        <div class="footer-links"><h4>{{ __('Explore') }}</h4><a href="{{ route('home') }}">{{ __('Home') }}</a><a href="{{ route('home') }}#the-race">{{ __('The race') }}</a><a href="{{ route('home') }}#race-categories">{{ __('Race categories') }}</a><a href="{{ route('register') }}">{{ __('Registration') }}</a></div>
        <div class="footer-links"><h4>{{ __('Get in touch') }}</h4><a href="mailto:elatikysports@gmail.com">✉ elatikysports@gmail.com</a><a href="tel:01508015156">☎ 01508015156</a><span>{{ __('Kafr El-Sheikh, Egypt') }}</span></div>
        <div class="footer-social-col"><h4>{{ __('Follow the movement') }}</h4><div class="social-row"><a href="https://www.facebook.com/" target="_blank" rel="noreferrer" aria-label="Facebook">f</a><a href="https://www.instagram.com/" target="_blank" rel="noreferrer" aria-label="Instagram">◎</a><a href="{{ route('register') }}" aria-label="Register">↗</a></div><p>{{ __('Find your next finish line.') }}</p></div>
    </div>
    <div class="footer-bottom"><div class="wrap footer-bottom-inner"><span>© {{ now()->year }} KFS Runners · El-Atiky Sports Services</span><span>Run · Challenge · Connect</span></div></div>
</footer>
<script>
    const menuButton = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('main-nav');
    menuButton?.addEventListener('click', () => {
        const open = menu.classList.toggle('nav-open');
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.textContent = open ? '×' : '☰';
    });
</script>
@stack('scripts')
</body>
</html>
