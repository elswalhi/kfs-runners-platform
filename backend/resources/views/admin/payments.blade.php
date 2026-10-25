@extends('admin.layout')

@section('title', 'Payments · KFS Runners Race Control')
@section('section', 'Payments')

@section('content')
<!-- Hero Header -->
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <div class="stitch-eyebrow">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            <span>FINANCIAL RECONCILIATION</span>
        </div>
        <h1 class="stitch-hero-title">Payment <span>Transactions</span></h1>
        <p class="stitch-hero-desc">Monitor payment attempts, gateway reference codes, and real-time status reconciliations.</p>
    </div>
</div>

<!-- Payment KPI Cards -->
<div class="stitch-kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="stitch-kpi-card tone-blue">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Total Transactions</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">{{ number_format($payments->total()) }}</div>
        <div class="stitch-kpi-footer">
            <span class="stitch-kpi-pill info">All checkouts</span>
            <span>Created by runners</span>
        </div>
    </div>

    <div class="stitch-kpi-card tone-emerald">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Settled Volume</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">EGP {{ number_format($paidTotal, 2) }}</div>
        <div class="stitch-kpi-footer">
            <span class="stitch-kpi-pill success">Verified</span>
            <span>Gateway confirmed amount</span>
        </div>
    </div>

    <div class="stitch-kpi-card tone-amber">
        <div class="stitch-kpi-header">
            <span class="stitch-kpi-title">Awaiting or Pending</span>
            <div class="stitch-kpi-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-value">{{ number_format($needsAttention) }}</div>
        <div class="stitch-kpi-footer">
            <span class="stitch-kpi-pill warning">Pending</span>
            <span>Awaiting customer payment</span>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="stitch-table-panel">
    <div class="stitch-table-header">
        <div class="stitch-panel-title-wrap">
            <h3>Gateway Transaction Logs</h3>
            <p>EasyKash checkouts and signed callback updates</p>
        </div>
        <div class="stitch-status-pill">
            <span class="stitch-status-dot"></span>
            <span>Live Gateway Sync</span>
        </div>
    </div>

    <div class="stitch-table-scroll">
        <table class="stitch-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Runner / Team</th>
                    <th>Provider</th>
                    <th>Gateway Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <strong style="font-size: 13px; color: var(--st-text-primary);">PAY-{{ $payment->id }}</strong>
                                <span class="stitch-code-badge" style="width: fit-content; font-size: 10px;">
                                    {{ $payment->team?->registration_code ?? $payment->registration?->registration_code }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--st-text-primary); font-size: 13px;">
                                {{ $payment->team?->name ? 'Team: '.$payment->team->name : trim(($payment->registration?->first_name ?? '').' '.($payment->registration?->last_name ?? '')) }}
                            </div>
                            <small style="color: var(--st-text-muted); font-size: 11px;">
                                {{ $payment->team_id ? 'Bulk Team Registration' : ($payment->registration?->email ?? '') }}
                            </small>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #047857; font-weight: 700; font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                {{ ucfirst($payment->provider) }}
                            </span>
                        </td>
                        <td>
                            <span class="stitch-code-badge">
                                {{ $payment->gateway_reference ?? $payment->transaction_reference ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <strong style="font-size: 14px; font-weight: 800; color: var(--st-text-primary);">
                                {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
                            </strong>
                        </td>
                        <td>
                            <span class="stitch-badge status-{{ $payment->status }}">
                                @if($payment->status === 'paid')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Paid</span>
                                @elseif($payment->status === 'pending')
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b;"></span>
                                    <span>Pending</span>
                                @else
                                    <span>{{ ucfirst($payment->status) }}</span>
                                @endif
                            </span>
                        </td>
                        <td style="color: var(--st-text-muted); font-size: 12px; white-space: nowrap;">
                            {{ $payment->created_at?->format('d M Y, h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--st-text-muted);">
                            No payment records recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="stitch-pagination-wrap">
        {{ $payments->links() }}
    </div>
</div>

<!-- Info Callout -->
<div class="stitch-callout">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg>
    <div>
        <h4>Automated Gateway Reconciliation</h4>
        <p>Payments are verified directly via EasyKash HMAC-SHA512 signed callbacks and real-time Inquire API status checks. Changing registration status does not affect the payment ledger.</p>
    </div>
</div>
@endsection
