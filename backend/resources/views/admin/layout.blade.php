<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KFS Runners Race Control')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-stitch.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kfs-blade.css') }}">
    <style>
        [dir="rtl"] body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            direction: rtl;
            text-align: right;
        }
        [dir="rtl"] .stitch-sidebar {
            left: auto;
            right: 0;
            border-right: none;
            border-left: 1px solid var(--st-border);
        }
        [dir="rtl"] .stitch-main {
            margin-left: 0;
            margin-right: 260px;
        }
        @media (max-width: 860px) {
            [dir="rtl"] .stitch-sidebar {
                left: auto;
                right: 0;
                transform: translateX(100%);
            }
            [dir="rtl"] .stitch-sidebar.mobile-open {
                transform: translateX(0);
            }
            [dir="rtl"] .stitch-main {
                margin-right: 0;
            }
        }
        [dir="rtl"] .stitch-search-wrapper svg {
            left: auto;
            right: 14px;
        }
        [dir="rtl"] .stitch-nav-badge {
            margin-left: 0;
            margin-right: auto;
        }
        [dir="rtl"] .stitch-table th, [dir="rtl"] .stitch-table td {
            text-align: right;
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="stitch-app">
    <!-- Stitch Sidebar -->
    <aside class="stitch-sidebar" id="stitch-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="stitch-brand">
            <div class="stitch-brand-logo">K↗</div>
            <div class="stitch-brand-text">
                <span class="stitch-brand-title">KFS RUNNERS</span>
                <span class="stitch-brand-badge">{{ __('RACE CONTROL') }}</span>
            </div>
        </a>

        <div class="stitch-nav-group">
            <div class="stitch-nav-label">{{ __('Navigation') }}</div>
            
            <a class="stitch-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                <span>{{ __('Overview') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.registrations*') ? 'active' : '' }}" href="{{ route('admin.registrations') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>{{ __('Registrations') }}</span>
                <span class="stitch-nav-badge">{{ __('Runners') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.payments') ? 'active' : '' }}" href="{{ route('admin.payments') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                <span>{{ __('Payments') }}</span>
            </a>

            <div class="stitch-nav-label" style="margin-top: 10px;">{{ __('Race Configuration') }}</div>

            <a class="stitch-nav-item {{ request()->routeIs('admin.pricing') ? 'active' : '' }}" href="{{ route('admin.pricing') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                <span>{{ __('Race & Pricing') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.promo-codes*') ? 'active' : '' }}" href="{{ route('admin.promo-codes') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20.59 13.41-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <span>{{ __('Promo Codes') }}</span>
                <span class="stitch-nav-badge" style="background:#ecfdf5; color:#047857;">{{ __('Discounts') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.races') ? 'active' : '' }}" href="{{ route('admin.races') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                <span>{{ __('Race Categories') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.team-import') ? 'active' : '' }}" href="{{ route('admin.team-import') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg>
                <span>{{ __('Team Excel Import') }}</span>
            </a>

            <div class="stitch-nav-label" style="margin-top: 10px;">{{ __('Settings') }}</div>

            <a class="stitch-nav-item {{ request()->routeIs('admin.event-settings*') ? 'active' : '' }}" href="{{ route('admin.event-settings') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ __('Race Date & Countdown') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.site-media*') ? 'active' : '' }}" href="{{ route('admin.site-media') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <span>{{ __('Website Images') }}</span>
            </a>

            <a class="stitch-nav-item {{ request()->routeIs('admin.localization*') ? 'active' : '' }}" href="{{ route('admin.localization') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span>{{ __('Localization') }}</span>
                <span class="stitch-nav-badge" style="background:#eff6ff; color:#1d4ed8;">{{ strtoupper(app()->getLocale()) }}</span>
            </a>
        </div>

        <div class="stitch-sidebar-footer">
            <div class="stitch-user-card">
                <div class="stitch-user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="stitch-user-meta">
                    <span class="stitch-user-name">{{ auth()->user()->name }}</span>
                    <span class="stitch-user-role">{{ __('Race Administrator') }}</span>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0;">
                    @csrf
                    <button class="stitch-logout-btn" type="submit" title="{{ __('Sign out') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="stitch-main">
        <!-- Top Navigation Bar -->
        <header class="stitch-topbar">
            <div class="stitch-topbar-left">
                <button class="stitch-mobile-toggle" id="stitch-toggle" aria-label="Toggle Navigation">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
                <div class="stitch-breadcrumb">
                    <span>KFS Runners</span>
                    <span class="sep">/</span>
                    <strong>@yield('section', __('Race Control'))</strong>
                </div>
            </div>

            <div class="stitch-topbar-right">
                <!-- Language Switcher in Admin Topbar -->
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="stitch-status-pill" style="text-decoration:none; color:inherit; font-weight:700; gap:6px; cursor:pointer;" title="Change language">
                    <span>{{ app()->getLocale() === 'ar' ? '🇬🇧 English' : '🇪🇬 العربية' }}</span>
                </a>

                <div class="stitch-status-pill">
                    <span class="stitch-status-dot"></span>
                    <span>EasyKash Gateway Live</span>
                </div>

                <a href="{{ route('home') }}" target="_blank" class="stitch-btn-icon" title="View Public Website">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <div class="stitch-content">
            @if(session('success'))
                <div class="admin-inline-success" style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="admin-inline-error" style="margin-bottom: 20px;">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('stitch-toggle');
    const sidebar = document.getElementById('stitch-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });
    }
</script>
@stack('scripts')
</body>
</html>
