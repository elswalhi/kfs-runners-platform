@extends('admin.layout')

@section('title', 'Overview · KFS Runners Race Control')
@section('section', 'Overview')

@section('content')
<!-- Hero Header -->
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <div class="stitch-eyebrow">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span>RACE OPERATIONS DASHBOARD</span>
        </div>
        <h1 class="stitch-hero-title">Welcome back, <span>{{ auth()->user()->name }}</span></h1>
        <p class="stitch-hero-desc">Live overview of marathon participants, payments reconciliation, and category performance.</p>
    </div>
    <div class="stitch-hero-actions">
        <a href="{{ route('admin.export-overview') }}" class="stitch-btn stitch-btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('admin.registrations') }}" class="stitch-btn stitch-btn-primary">
            <span>View Registrations</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </div>
</div>

<!-- 4 Modern Stitch KPI Cards -->
<div class="stitch-kpi-grid">
    <!-- Card 1: Total Runners -->
    <div class="stitch-kpi-card tone-blue">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Total Registrations</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">{{ number_format($total) }}</div>
        <div class="stitch-kpi-footer">
            <span class="stitch-kpi-pill info">
                <span>All distances</span>
            </span>
            <span>Individual & teams</span>
        </div>
    </div>

    <!-- Card 2: Confirmed Revenue -->
    <div class="stitch-kpi-card tone-emerald">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Confirmed Revenue</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">EGP {{ number_format($revenue, 2) }}</div>
        <div class="stitch-kpi-footer">
            <span class="stitch-kpi-pill success">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Settled</span>
            </span>
            <span>EasyKash verified</span>
        </div>
    </div>

    <!-- Card 3: Paid Runners -->
    <div class="stitch-kpi-card tone-emerald">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Paid Entries</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">{{ number_format($paid) }}</div>
        <div class="stitch-kpi-footer">
            @php($conversion = $total > 0 ? round(($paid / $total) * 100) : 0)
            <span class="stitch-kpi-pill success">{{ $conversion }}% rate</span>
            <span>Ready for bib pickup</span>
        </div>
    </div>

    <!-- Card 4: Pending Payments -->
    <div class="stitch-kpi-card tone-amber">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Awaiting Payment</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">{{ number_format($pending) }}</div>
        <div class="stitch-kpi-footer">
            <span class="stitch-kpi-pill warning">In checkout</span>
            <span>Awaiting gateway confirmation</span>
        </div>
    </div>
</div>

<!-- 2-Column Analytics: Payment Trend & Race Breakdown -->
<div class="stitch-grid-2col">
    <!-- Payment Trend Bar Chart -->
    <div class="stitch-panel">
        <div class="stitch-panel-header">
            <div class="stitch-panel-title-wrap">
                <h3>Revenue Performance</h3>
                <p>Confirmed payments trend over the last 7 days</p>
            </div>
            <span class="stitch-kpi-pill success">
                7-day total: EGP {{ number_format($trend->sum('value'), 2) }}
            </span>
        </div>

        <div class="stitch-chart-wrap">
            @php($maxTrend = max(1, $trend->max('value')))
            <div class="stitch-chart-bars">
                @foreach($trend as $day)
                    @php($percent = max(6, (int) round(($day['value'] / $maxTrend) * 100)))
                    @php($isPeak = $day['value'] > 0 && $day['value'] === $trend->max('value'))
                    <div class="stitch-chart-col">
                        <div class="stitch-bar-container">
                            <div class="stitch-bar" 
                                 style="height: {{ $percent }}%; {{ $isPeak ? 'background: linear-gradient(180deg, #34d399 0%, #047857 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);' : '' }}" 
                                 title="{{ $day['label'] }}: EGP {{ number_format($day['value'], 2) }}">
                            </div>
                        </div>
                        <span class="stitch-bar-label">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="stitch-chart-summary">
                <span>Peak day volume: <strong>EGP {{ number_format($trend->max('value'), 2) }}</strong></span>
                <span style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 2px; background: #10b981;"></span>
                    <span>EasyKash Verified</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Race Category Breakdown -->
    <div class="stitch-panel">
        <div class="stitch-panel-header">
            <div class="stitch-panel-title-wrap">
                <h3>Race Categories</h3>
                <p>Participant distribution by distance</p>
            </div>
            <a class="stitch-panel-link" href="{{ route('admin.races') }}">
                <span>Manage</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>

        <div class="stitch-category-list">
            @php($categoryColors = ['cat-21k', 'cat-10k', 'cat-5k', 'cat-family', 'cat-para'])
            @forelse($raceBreakdown as $index => $race)
                @php($racePercent = $total > 0 ? round(($race->registrations_count / $total) * 100) : 0)
                @php($colorClass = $categoryColors[$index % count($categoryColors)])
                <div class="stitch-category-item">
                    <div class="stitch-category-meta">
                        <div class="stitch-category-name">
                            <span class="stitch-category-dot {{ $colorClass }}"></span>
                            <span>{{ $race->name }} ({{ $race->distance_label }})</span>
                        </div>
                        <span class="stitch-category-count">
                            <strong>{{ $race->registrations_count }}</strong> ({{ $racePercent }}%)
                        </span>
                    </div>
                    <div class="stitch-progress-track">
                        <div class="stitch-progress-bar {{ $colorClass }}" style="width: {{ max(4, $racePercent) }}%;"></div>
                    </div>
                </div>
            @empty
                <p style="color: var(--st-text-muted); font-size: 13px; text-align: center; padding: 24px 0;">No race categories created yet.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Recent Registrations Table -->
<div class="stitch-table-panel">
    <div class="stitch-table-header">
        <div class="stitch-panel-title-wrap">
            <h3>Recent Registrations</h3>
            <p>Latest participant entries and payment states</p>
        </div>
        <a class="stitch-panel-link" href="{{ route('admin.registrations') }}">
            <span>View All ({{ $total }})</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
    </div>

    <div class="stitch-table-scroll">
        <table class="stitch-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Runner</th>
                    <th>Distance</th>
                    <th>Kit</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>Registered At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $registration)
                    <tr>
                        <td>
                            <a href="{{ route('admin.registrations.show', $registration) }}" class="stitch-code-badge">
                                {{ $registration->registration_code }}
                            </a>
                        </td>
                        <td>
                            <div class="stitch-runner-cell">
                                <div class="stitch-runner-avatar">
                                    {{ strtoupper(substr($registration->first_name, 0, 1) . substr($registration->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="stitch-runner-name">{{ $registration->first_name }} {{ $registration->last_name }}</div>
                                    <div class="stitch-runner-sub">{{ $registration->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="stitch-distance-chip">
                                {{ $registration->race?->distance_label ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 600; font-size: 12px; color: var(--st-text-secondary);">
                                {{ ucfirst($registration->kit_type) }}
                                @if($registration->shirt_size)
                                    <small style="color: var(--st-text-muted);">({{ $registration->shirt_size }})</small>
                                @endif
                            </span>
                        </td>
                        <td>
                            <strong style="font-weight: 800; color: var(--st-text-primary);">
                                EGP {{ number_format($registration->amount, 2) }}
                            </strong>
                        </td>
                        <td>
                            <span class="stitch-badge status-{{ $registration->payment_status }}">
                                @if($registration->payment_status === 'paid')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Paid</span>
                                @elseif($registration->payment_status === 'pending')
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b;"></span>
                                    <span>Pending</span>
                                @else
                                    <span>{{ ucfirst($registration->payment_status) }}</span>
                                @endif
                            </span>
                        </td>
                        <td style="color: var(--st-text-muted); font-size: 12px; white-space: nowrap;">
                            {{ $registration->created_at?->format('d M Y, h:i A') }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.registrations.show', $registration) }}" class="stitch-btn stitch-btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px; color: var(--st-text-muted);">
                            No registrations recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
