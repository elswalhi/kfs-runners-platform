@extends('admin.layout')

@section('title', __('Race overview') . ' · KFS Runners Race Control')
@section('section', __('Overview'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-dashboard.css') }}">
@endpush

@section('content')
@php
    $maxTrendValue = max(1, (float) $trend->max('value'));
    $weeklyRevenue = (float) $trend->sum('value');
    $paidRate = $total > 0 ? (int) round(($paid / $total) * 100) : 0;
    $categoryTones = ['pine', 'red', 'sage', 'sand', 'slate'];
@endphp

<div class="kfs-dashboard">
    <header class="kfs-dashboard-heading">
        <div class="kfs-dashboard-heading-copy">
            <div class="kfs-dashboard-eyebrow"><span></span>{{ __('Race operations') }}</div>
            <h1>{{ __('Race overview') }}</h1>
            <p>{{ __('Monitor registrations, payments, and race-day readiness from one place.') }}</p>
        </div>
        <div class="kfs-dashboard-actions">
            <a class="kfs-dashboard-button secondary" href="{{ route('admin.export-overview') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                {{ __('Export CSV') }}
            </a>
            <a class="kfs-dashboard-button primary" href="{{ route('admin.registrations') }}">
                {{ __('View registrations') }}
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
    </header>

    <section class="kfs-race-control" aria-labelledby="kfs-race-control-title">
        <div class="kfs-race-control-summary">
            <div class="kfs-race-control-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3"/></svg>
            </div>
            <div class="kfs-race-control-copy">
                <div class="kfs-race-control-label">{{ __('Race day & countdown') }}</div>
                <h2 id="kfs-race-control-title">{{ \Carbon\Carbon::parse($raceDate ?? '2026-11-20')->translatedFormat('l, d F Y') }} <span>·</span> {{ $raceTime ?? '07:00' }}</h2>
                <span class="kfs-countdown-state {{ ($countdownEnabled ?? true) ? 'is-active' : 'is-hidden' }}">
                    <i></i>{{ ($countdownEnabled ?? true) ? __('Countdown active on website') : __('Countdown hidden on website') }}
                </span>
            </div>
        </div>
        <form class="kfs-race-control-form" method="POST" action="{{ route('admin.event-settings.update') }}">
            @csrf
            <label class="kfs-dashboard-field">
                <span>{{ __('Race date') }}</span>
                <input type="date" required name="race_date" value="{{ $raceDate ?? '2026-11-20' }}">
            </label>
            <label class="kfs-dashboard-field time-field">
                <span>{{ __('Start time') }}</span>
                <input type="time" required name="race_time" value="{{ $raceTime ?? '07:00' }}">
            </label>
            <label class="kfs-countdown-toggle">
                <input type="checkbox" name="countdown_enabled" value="1" {{ ($countdownEnabled ?? true) ? 'checked' : '' }}>
                <span class="kfs-toggle-track" aria-hidden="true"><i></i></span>
                <span>{{ __('Show website countdown') }}</span>
            </label>
            <div class="kfs-race-control-actions">
                <button class="kfs-dashboard-button primary" type="submit">{{ __('Update schedule') }}</button>
                <a href="{{ route('admin.event-settings') }}">{{ __('Full settings') }} <span aria-hidden="true">→</span></a>
            </div>
        </form>
    </section>

    <section class="kfs-dashboard-kpis" aria-label="{{ __('Race summary') }}">
        <article class="kfs-kpi-card">
            <div class="kfs-kpi-card-top">
                <span>{{ __('Total registrations') }}</span>
                <span class="kfs-kpi-icon green" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
            </div>
            <strong class="kfs-kpi-value">{{ number_format($total) }}</strong>
            <div class="kfs-kpi-foot"><span>{{ __('Across all race categories') }}</span><a href="{{ route('admin.registrations') }}">{{ __('View list') }} <b>→</b></a></div>
        </article>

        <article class="kfs-kpi-card">
            <div class="kfs-kpi-card-top">
                <span>{{ __('Paid entries') }}</span>
                <span class="kfs-kpi-icon green" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 7h18v13H3zM3 10h18M7 15h3M6 4h12"/></svg>
                </span>
            </div>
            <strong class="kfs-kpi-value">{{ number_format($paid) }}</strong>
            <div class="kfs-kpi-foot"><span class="kfs-kpi-status success"><i></i>{{ __('Payment confirmed') }}</span><span>{{ $paidRate }}% {{ __('of registrations') }}</span></div>
        </article>

        <article class="kfs-kpi-card">
            <div class="kfs-kpi-card-top">
                <span>{{ __('Awaiting payment') }}</span>
                <span class="kfs-kpi-icon amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
            </div>
            <strong class="kfs-kpi-value">{{ number_format($pending) }}</strong>
            <div class="kfs-kpi-foot"><span class="kfs-kpi-status pending"><i></i>{{ __('Pending') }}</span><span>{{ __('Waiting for gateway confirmation') }}</span></div>
        </article>

        <article class="kfs-kpi-card revenue-card">
            <div class="kfs-kpi-card-top">
                <span>{{ __('Confirmed revenue') }}</span>
                <span class="kfs-kpi-icon red" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
            </div>
            <strong class="kfs-kpi-value"><small>EGP</small> {{ number_format($revenue, 2) }}</strong>
            <div class="kfs-kpi-foot"><span class="kfs-kpi-status success"><i></i>{{ __('Paid registrations only') }}</span><a href="{{ route('admin.payments') }}">{{ __('Payments') }} <b>→</b></a></div>
        </article>
    </section>

    <section class="kfs-dashboard-analytics" aria-label="{{ __('Race analytics') }}">
        <article class="kfs-dashboard-panel kfs-revenue-panel">
            <div class="kfs-panel-heading">
                <div>
                    <span class="kfs-panel-eyebrow">{{ __('Last 7 days') }}</span>
                    <h2>{{ __('Revenue performance') }}</h2>
                    <p>{{ __('Confirmed payments by registration date') }}</p>
                </div>
                <div class="kfs-panel-total"><small>EGP</small><strong>{{ number_format($weeklyRevenue, 2) }}</strong></div>
            </div>
            <div class="kfs-revenue-chart" role="img" aria-label="{{ __('Confirmed revenue over the last 7 days') }}">
                @foreach($trend as $day)
                    @php($dayRevenue = (float) $day['value'])
                    @php($barHeight = $dayRevenue > 0 ? max(8, (int) round(($dayRevenue / $maxTrendValue) * 100)) : 4)
                    @php($isPeakDay = $dayRevenue > 0 && $dayRevenue >= (float) $trend->max('value'))
                    <div class="kfs-revenue-day" title="{{ $day['label'] }}: EGP {{ number_format($dayRevenue, 2) }}">
                        <div class="kfs-revenue-bar-track"><span class="{{ $isPeakDay ? 'is-peak' : '' }}" style="height: {{ $barHeight }}%"></span></div>
                        <span class="kfs-revenue-day-label">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="kfs-chart-legend"><span><i></i>{{ __('EasyKash confirmed payments') }}</span><span>{{ __('7-day total') }} · EGP {{ number_format($weeklyRevenue, 2) }}</span></div>
        </article>

        <article class="kfs-dashboard-panel kfs-category-panel">
            <div class="kfs-panel-heading">
                <div>
                    <span class="kfs-panel-eyebrow">{{ __('Participant distribution') }}</span>
                    <h2>{{ __('Race categories') }}</h2>
                    <p>{{ __('Registrations across each distance') }}</p>
                </div>
                <a class="kfs-panel-link" href="{{ route('admin.races') }}">{{ __('Manage') }} <span aria-hidden="true">→</span></a>
            </div>
            <div class="kfs-category-list">
                @forelse($raceBreakdown as $index => $race)
                    @php($racePercent = $total > 0 ? min(100, (int) round(($race->registrations_count / $total) * 100)) : 0)
                    <div class="kfs-category-row">
                        <div class="kfs-category-meta">
                            <span class="kfs-category-name"><i class="tone-{{ $categoryTones[$index % count($categoryTones)] }}"></i>{{ $race->name }} <small>{{ $race->distance_label }}</small></span>
                            <strong>{{ number_format($race->registrations_count) }} <small>· {{ $racePercent }}%</small></strong>
                        </div>
                        <div class="kfs-category-track"><span class="tone-{{ $categoryTones[$index % count($categoryTones)] }}" style="width: {{ $racePercent }}%"></span></div>
                    </div>
                @empty
                    <p class="kfs-empty-state">{{ __('No race categories have been added yet.') }}</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="kfs-dashboard-panel kfs-recent-panel" aria-labelledby="kfs-recent-title">
        <div class="kfs-panel-heading kfs-recent-heading">
            <div>
                <span class="kfs-panel-eyebrow">{{ __('Latest activity') }}</span>
                <h2 id="kfs-recent-title">{{ __('Recent registrations') }}</h2>
                <p>{{ __('Bib number, age group, and payment status at a glance') }}</p>
            </div>
            <a class="kfs-dashboard-button secondary" href="{{ route('admin.registrations') }}">{{ __('View all :count', ['count' => number_format($total)]) }} <span aria-hidden="true">→</span></a>
        </div>

        <div class="kfs-table-scroll" role="region" aria-label="{{ __('Recent runner registrations') }}" tabindex="0">
            <table class="kfs-dashboard-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Bib') }}</th>
                        <th scope="col">{{ __('Runner') }}</th>
                        <th scope="col">{{ __('Age & group') }}</th>
                        <th scope="col">{{ __('Distance') }}</th>
                        <th scope="col">{{ __('Registration reference') }}</th>
                        <th scope="col">{{ __('Kit') }}</th>
                        <th scope="col">{{ __('Amount') }}</th>
                        <th scope="col">{{ __('Payment status') }}</th>
                        <th scope="col">{{ __('Registered') }}</th>
                        <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $registration)
                        <tr>
                            <td><span class="kfs-bib-number">#{{ $registration->bib_number ?: '—' }}</span></td>
                            <td>
                                <div class="kfs-runner-cell">
                                    <span class="kfs-runner-avatar">{{ strtoupper(substr($registration->first_name, 0, 1) . substr($registration->last_name, 0, 1)) }}</span>
                                    <span class="kfs-runner-details"><strong>{{ $registration->first_name }} {{ $registration->last_name }}</strong><small>{{ $registration->email }}</small></span>
                                </div>
                            </td>
                            <td>
                                <strong class="kfs-runner-age">{{ $registration->age_on_race_day ?? '—' }}</strong>
                                <small class="kfs-age-group">{{ $registration->age_group_name ?? __('No age group') }}</small>
                            </td>
                            <td><span class="kfs-distance-chip">{{ $registration->race?->distance_label ?? '—' }}</span></td>
                            <td><a class="kfs-registration-code" href="{{ route('admin.registrations.show', $registration) }}">{{ $registration->registration_code }}</a></td>
                            <td><span class="kfs-kit-label">{{ ucfirst($registration->kit_type) }}@if($registration->shirt_size) <small>· {{ $registration->shirt_size }}</small>@endif</span></td>
                            <td><strong class="kfs-amount">EGP {{ number_format($registration->amount, 2) }}</strong></td>
                            <td><span class="kfs-payment-status status-{{ $registration->payment_status }}"><i></i>{{ __(ucfirst($registration->payment_status)) }}</span></td>
                            <td><time class="kfs-registered-at" datetime="{{ $registration->created_at?->toIso8601String() }}">{{ $registration->created_at?->format('d M Y, h:i A') }}</time></td>
                            <td><a class="kfs-row-action" href="{{ route('admin.registrations.show', $registration) }}">{{ __('Details') }} <span aria-hidden="true">→</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="kfs-table-empty">{{ __('No registrations have been recorded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="kfs-danger-zone" aria-labelledby="kfs-danger-title">
        <div class="kfs-danger-copy">
            <span class="kfs-danger-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 14h12l1-14M9 7V4h6v3"/></svg>
            </span>
            <div>
                <span class="kfs-panel-eyebrow">{{ __('Admin-only action') }}</span>
                <h2 id="kfs-danger-title">{{ __('Remove all orders') }}</h2>
                <p>{{ __('Permanently deletes registrations, teams, payment records, and uploaded runner documents. Real payments are not refunded. Races and settings remain.') }}</p>
                <small>{{ __('Current data: :runners runners, :teams teams, :payments payment records.', ['runners' => number_format($total), 'teams' => number_format($teamCount), 'payments' => number_format($paymentCount)]) }}</small>
            </div>
        </div>
        <form class="kfs-danger-form" method="POST" action="{{ route('admin.registrations.clear-all') }}" onsubmit="return confirm('{{ __('Permanently remove all orders and payment history?') }}')">
            @csrf
            <label class="kfs-dashboard-field">
                <span>{{ __('Type DELETE ALL to confirm') }}</span>
                <input name="confirmation_phrase" required pattern="DELETE ALL" autocomplete="off" placeholder="DELETE ALL">
            </label>
            <button class="kfs-dashboard-button danger" type="submit">{{ __('Remove all orders') }}</button>
        </form>
    </section>
</div>
@endsection
