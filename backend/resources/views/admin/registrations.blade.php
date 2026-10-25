@extends('admin.layout')

@section('title', 'Registrations · KFS Runners Race Control')
@section('section', 'Registrations')

@section('content')
<!-- Hero Header -->
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <div class="stitch-eyebrow">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>PARTICIPANT MANAGEMENT</span>
        </div>
        <h1 class="stitch-hero-title">Race <span>Registrations</span></h1>
        <p class="stitch-hero-desc">Search runners, filter payment statuses, download documents, and manage event rosters.</p>
    </div>
    <div class="stitch-hero-actions">
        <a href="{{ route('admin.registrations.export', request()->query()) }}" class="stitch-btn stitch-btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('admin.team-import') }}" class="stitch-btn stitch-btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
            <span>Import Team Excel</span>
        </a>
    </div>
</div>

<!-- Main Table Container -->
<div class="stitch-table-panel">
    <!-- Filter Toolbar -->
    <form class="stitch-filter-bar" method="GET" action="{{ route('admin.registrations') }}">
        <div class="stitch-search-wrapper">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
            <input type="search" class="stitch-input" name="search" value="{{ request('search') }}" placeholder="Search by name, email, phone or reference code...">
        </div>

        <select name="payment_status" class="stitch-select">
            <option value="all">All payment statuses</option>
            @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $val => $label)
                <option value="{{ $val }}" @selected(request('payment_status') === $val)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="race_id" class="stitch-select">
            <option value="">All race distances</option>
            @foreach($races as $race)
                <option value="{{ $race->id }}" @selected((string) request('race_id') === (string) $race->id)>{{ $race->distance_label }} · {{ $race->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="stitch-btn stitch-btn-secondary" style="height: 42px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter</span>
        </button>

        @if(request()->hasAny(['search', 'payment_status', 'race_id']))
            <a href="{{ route('admin.registrations') }}" class="stitch-btn stitch-btn-secondary" style="height: 42px; color: #ef4444;" title="Reset filters">
                <span>Reset</span>
            </a>
        @endif
    </form>

    <!-- Table -->
    <div class="stitch-table-scroll">
        <table class="stitch-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Runner</th>
                    <th>Distance & Category</th>
                    <th>Package & Kit</th>
                    <th>Club / Team</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $item)
                    <tr>
                        <td>
                            <a href="{{ route('admin.registrations.show', $item) }}" class="stitch-code-badge">
                                {{ $item->registration_code }}
                            </a>
                        </td>
                        <td>
                            <div class="stitch-runner-cell">
                                <div class="stitch-runner-avatar">
                                    {{ strtoupper(substr($item->first_name, 0, 1) . substr($item->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="stitch-runner-name">{{ $item->first_name }} {{ $item->last_name }}</div>
                                    <div class="stitch-runner-sub">{{ $item->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <span class="stitch-distance-chip" style="width: fit-content;">
                                    {{ $item->race?->distance_label ?? '—' }}
                                </span>
                                <small style="color: var(--st-text-muted); font-size: 11px;">{{ $item->race?->name }}</small>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700; font-size: 13px; color: var(--st-text-primary);">
                                {{ ucfirst($item->kit_type) }}
                                @if($item->shirt_size)
                                    <span style="display: inline-block; padding: 1px 6px; background: #f1f5f9; border-radius: 4px; font-size: 10px; color: #475569; margin-left: 4px;">{{ $item->shirt_size }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($item->team?->name || $item->team_name)
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600; font-size: 12px; color: #0284c7;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span>{{ $item->team?->name ?? $item->team_name }}</span>
                                </span>
                            @else
                                <span style="color: var(--st-text-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="stitch-badge status-{{ $item->payment_status }}">
                                @if($item->payment_status === 'paid')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Paid</span>
                                @elseif($item->payment_status === 'pending')
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b;"></span>
                                    <span>Pending</span>
                                @else
                                    <span>{{ ucfirst($item->payment_status) }}</span>
                                @endif
                            </span>
                        </td>
                        <td style="color: var(--st-text-muted); font-size: 12px; white-space: nowrap;">
                            {{ $item->created_at?->format('d M Y') }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.registrations.show', $item) }}" class="stitch-btn stitch-btn-secondary" style="padding: 6px 14px; font-size: 11px;">
                                <span>Details</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 48px 20px; color: var(--st-text-muted);">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/></svg>
                                <span style="font-size: 14px; font-weight: 700; color: #475569;">No registrations found</span>
                                <small>Try adjusting your search criteria or filters.</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="stitch-pagination-wrap">
        {{ $registrations->links() }}
    </div>
</div>
@endsection
