<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KFS Runners Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&family=Manrope:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kfs-admin.css') }}"><link rel="stylesheet" href="{{ asset('css/kfs-blade.css') }}">
    @stack('styles')
</head>
<body>
<div class="admin-app">
    <aside class="admin-sidebar">
        <div class="admin-brand-row"><div class="admin-brand-symbol">K<span>↗</span></div><div class="admin-brand-word">KFS RUNNERS<small>RACE CONTROL</small></div></div>
        <div class="admin-workspace-label">WORKSPACE</div>
        <nav class="admin-nav">
            <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span>▦</span><span>Overview</span></a>
            <a class="admin-nav-link {{ request()->routeIs('admin.registrations*') ? 'active' : '' }}" href="{{ route('admin.registrations') }}"><span>♙</span><span>Registrations</span></a>
            <a class="admin-nav-link {{ request()->routeIs('admin.payments') ? 'active' : '' }}" href="{{ route('admin.payments') }}"><span>▭</span><span>Payments</span></a>
            <a class="admin-nav-link {{ request()->routeIs('admin.pricing') ? 'active' : '' }}" href="{{ route('admin.pricing') }}"><span>◇</span><span>Race & pricing</span></a>
            <a class="admin-nav-link {{ request()->routeIs('admin.races') ? 'active' : '' }}" href="{{ route('admin.races') }}"><span>⌁</span><span>Race management</span></a>
            <a class="admin-nav-link {{ request()->routeIs('admin.team-import') ? 'active' : '' }}" href="{{ route('admin.team-import') }}"><span>▤</span><span>Team Excel import</span></a>
        </nav>
        <div class="admin-sidebar-bottom"><div class="admin-help-card"><div class="admin-help-icon">♢</div><b>Race day ready?</b><p>Keep runner details and payment status up to date.</p><a href="mailto:events@elatiky.com">Get support →</a></div>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="admin-nav-link admin-logout" type="submit"><span>↪</span><span>Sign out</span></button></form>
            <div class="admin-user"><div class="admin-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><div><b>{{ auth()->user()->name }}</b><small>Event operations</small></div></div>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar"><div class="admin-breadcrumb">KFS Runners <span>/</span> <b>@yield('section', 'Operations')</b></div><div class="admin-top-actions"><div class="admin-top-date">{{ now()->format('d M Y') }}</div></div></header>
        <div class="admin-page-wrap">
            @if(session('success'))<div class="admin-inline-success">✓ {{ session('success') }}</div>@endif
            @if($errors->any())<div class="admin-inline-error">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
