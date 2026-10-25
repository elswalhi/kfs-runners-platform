<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#064b3e">
    <title>@yield('title', 'KFS Runners · Kafr El-Sheikh')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kfs-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-payment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-blade.css') }}">
    @stack('styles')
</head>
<body>
<header class="site-header">
    <div class="header-inner wrap">
        <a href="{{ route('home') }}" class="brand-link" aria-label="KFS Runners home">
            <span class="brand"><span class="brand-mark" aria-hidden="true">♧</span><span class="brand-copy"><span class="brand-kfs">KFS <b>RUNNERS</b></span><span class="brand-location">KAFR EL-SHEIKH</span></span></span>
        </a>
        <button class="mobile-menu-btn" type="button" aria-label="Open menu" aria-expanded="false" id="mobile-menu-toggle">☰</button>
        <nav class="main-nav" aria-label="Main navigation" id="main-nav">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('home') }}#the-race">The race</a>
            <a href="{{ route('home') }}#race-categories">Race categories</a>
            <a href="{{ route('home') }}#whats-included">What's included</a>
            <a href="{{ route('register') }}" class="nav-cta">Register now <span aria-hidden="true">↗</span></a>
        </nav>
    </div>
</header>

@yield('content')

<footer class="site-footer">
    <div class="wrap footer-main">
        <div class="footer-brand-col"><span class="brand brand-light"><span class="brand-mark" aria-hidden="true">♧</span><span class="brand-copy"><span class="brand-kfs">KFS <b>RUNNERS</b></span><span class="brand-location">KAFR EL-SHEIKH</span></span></span><p>More than a race. A community that moves forward together.</p></div>
        <div class="footer-links"><h4>Explore</h4><a href="{{ route('home') }}">Home</a><a href="{{ route('home') }}#the-race">The race</a><a href="{{ route('home') }}#race-categories">Race categories</a><a href="{{ route('register') }}">Registration</a></div>
        <div class="footer-links"><h4>Get in touch</h4><a href="mailto:elatikysports@gmail.com">✉ elatikysports@gmail.com</a><a href="tel:01508015156">☎ 01508015156</a><span>Kafr El-Sheikh, Egypt</span></div>
        <div class="footer-social-col"><h4>Follow the movement</h4><div class="social-row"><a href="https://www.facebook.com/" target="_blank" rel="noreferrer" aria-label="Facebook">f</a><a href="https://www.instagram.com/" target="_blank" rel="noreferrer" aria-label="Instagram">◎</a><a href="{{ route('register') }}" aria-label="Register">↗</a></div><p>Find your next finish line.</p></div>
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
