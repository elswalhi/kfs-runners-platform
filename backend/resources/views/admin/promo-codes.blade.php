@extends('admin.layout')
@section('title', 'Promo Codes · KFS Runners Admin')
@section('section', 'Promo Codes')
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">MARKETING & CAMPAIGNS</span>
        <h1>Promo Codes & Discounts</h1>
        <p>Create and manage discount codes for individual race categories or marathon-wide promotions.</p>
    </div>
</div>

<!-- 4 KPI Cards -->
<div class="stitch-kpi-grid">
    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">TOTAL PROMO CODES</span>
            <div class="stitch-kpi-icon icon-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20.59 13.41-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val">{{ $totalCodes }}</div>
        <div class="stitch-kpi-sub">Configured vouchers</div>
    </div>

    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">ACTIVE CAMPAIGNS</span>
            <div class="stitch-kpi-icon icon-green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val">{{ $activeCodes }}</div>
        <div class="stitch-kpi-sub">Currently redeemable</div>
    </div>

    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">TOTAL REDEMPTIONS</span>
            <div class="stitch-kpi-icon icon-amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val">{{ $totalRedemptions }}</div>
        <div class="stitch-kpi-sub">Runners redeemed</div>
    </div>

    <div class="stitch-kpi-card">
        <div class="stitch-kpi-top">
            <span class="stitch-kpi-label">TOTAL SAVINGS GRANTED</span>
            <div class="stitch-kpi-icon icon-emerald">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
        <div class="stitch-kpi-val" style="font-size: 20px;">EGP {{ number_format($totalDiscountGiven, 2) }}</div>
        <div class="stitch-kpi-sub">Total discount applied</div>
    </div>
</div>

<!-- Create Promo Code Panel -->
<div class="stitch-table-panel" style="margin-bottom: 28px;">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">Create a Promo Code</h2>
            <p class="stitch-panel-subtitle">Define voucher parameters, discount amount, applicability, and usage limits.</p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST" action="{{ route('admin.promo-codes.store') }}">
            @csrf
            <div class="stitch-form-grid">
                <div class="stitch-form-group">
                    <label>Voucher Code</label>
                    <input required maxlength="64" name="code" value="{{ old('code') }}" placeholder="e.g. KFS50" class="stitch-input" style="text-transform: uppercase; font-family: monospace; font-weight: 700;">
                </div>
                <div class="stitch-form-group">
                    <label>Discount Type</label>
                    <select name="discount_type" class="stitch-select" style="width:100%;">
                        <option value="fixed" @selected(old('discount_type') === 'fixed')>Fixed Amount (EGP)</option>
                        <option value="percentage" @selected(old('discount_type') === 'percentage')>Percentage (%)</option>
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label>Discount Value</label>
                    <input required type="number" min="0.01" step="0.01" name="discount_value" value="{{ old('discount_value') }}" placeholder="e.g. 50 or 20" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Applicable Race</label>
                    <select name="race_id" class="stitch-select" style="width:100%;">
                        <option value="">All Races & Distances</option>
                        @foreach($races as $race)
                            <option value="{{ $race->id }}" @selected(old('race_id') == $race->id)>{{ $race->name }} ({{ $race->distance_label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label>Max Redemptions <small style="color:var(--st-text-muted);">(Optional)</small></label>
                    <input type="number" min="1" max="1000000" name="max_uses" value="{{ old('max_uses') }}" placeholder="e.g. 100 (Blank for unlimited)" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Valid From <small style="color:var(--st-text-muted);">(Optional)</small></label>
                    <input type="date" name="valid_from" value="{{ old('valid_from') }}" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Valid Until <small style="color:var(--st-text-muted);">(Optional)</small></label>
                    <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="stitch-input">
                </div>
                <div class="stitch-form-group" style="justify-content: center; padding-top: 18px;">
                    <label class="stitch-checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active immediately
                    </label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button class="stitch-btn stitch-btn-primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Create Promo Code
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Promo Codes Table Panel -->
<div class="stitch-table-panel">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">Active & Historical Promo Codes</h2>
            <p class="stitch-panel-subtitle">{{ $promoCodes->count() }} vouchers registered in the database</p>
        </div>
    </div>
    <div class="stitch-table-container">
        <table class="stitch-table">
            <thead>
                <tr>
                    <th style="min-width: 160px;">Code</th>
                    <th style="min-width: 140px;">Discount</th>
                    <th style="min-width: 160px;">Applicable To</th>
                    <th style="min-width: 160px;">Usage & Limit</th>
                    <th style="min-width: 150px;">Validity Period</th>
                    <th style="min-width: 110px;">Status</th>
                    <th style="text-align: right; min-width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($promoCodes as $code)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--st-primary-subtle); color: var(--st-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20.59 13.41-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg>
                                </div>
                                <span class="stitch-code-badge" style="font-size: 13px; font-weight: 800; letter-spacing: 0.5px;">{{ $code->code }}</span>
                            </div>
                        </td>
                        <td>
                            @if($code->discount_type === 'percentage')
                                <span class="stitch-badge status-paid" style="font-size: 12px; font-weight: 800;">
                                    {{ (float) $code->discount_value }}% OFF
                                </span>
                            @else
                                <span class="stitch-badge status-paid" style="font-size: 12px; font-weight: 800;">
                                    {{ number_format($code->discount_value, 2) }} EGP OFF
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($code->race)
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="stitch-distance-chip">{{ $code->race->distance_label }}</span>
                                    <span style="font-size: 13px; font-weight: 600; color: var(--st-text-primary);">{{ $code->race->name }}</span>
                                </div>
                            @else
                                <span style="font-size: 13px; color: var(--st-text-secondary); font-weight: 600;">All Race Categories</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; color: var(--st-text-primary);">
                                    <span>{{ $code->times_used }} used</span>
                                    <span style="color: var(--st-text-muted);">{{ $code->max_uses ? $code->max_uses . ' max' : 'Unlimited' }}</span>
                                </div>
                                @if($code->max_uses)
                                    @php($pct = min(100, round(($code->times_used / $code->max_uses) * 100)))
                                    <div style="width: 100%; height: 6px; background: var(--st-surface-hover); border-radius: 3px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $pct }}%; background: {{ $pct >= 100 ? '#ef4444' : 'var(--st-primary)' }};"></div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 12px; color: var(--st-text-secondary);">
                                @if($code->valid_from || $code->valid_until)
                                    @if($code->valid_from) <div>From: {{ $code->valid_from->format('Y-m-d') }}</div> @endif
                                    @if($code->valid_until) <div>Until: {{ $code->valid_until->format('Y-m-d') }}</div> @endif
                                @else
                                    <span style="color: var(--st-text-muted);">Permanent / No expiry</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php($label = $code->status_label)
                            <span class="stitch-badge {{ $label === 'Active' ? 'status-active' : ($label === 'Exhausted' || $label === 'Expired' ? 'status-failed' : 'status-pending') }}">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                {{ $label }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: wrap;">
                                <button
                                    class="stitch-btn stitch-btn-secondary promo-edit-button"
                                    style="height: 30px; padding: 0 10px; font-size: 11px;"
                                    type="button"
                                    data-id="{{ $code->id }}"
                                    data-code="{{ $code->code }}"
                                    data-discount-type="{{ $code->discount_type }}"
                                    data-discount-value="{{ $code->discount_value }}"
                                    data-race-id="{{ $code->race_id }}"
                                    data-max-uses="{{ $code->max_uses }}"
                                    data-times-used="{{ $code->times_used }}"
                                    data-valid-from="{{ $code->valid_from?->format('Y-m-d') }}"
                                    data-valid-until="{{ $code->valid_until?->format('Y-m-d') }}"
                                    data-is-active="{{ $code->is_active ? '1' : '0' }}"
                                >Edit</button>
                                <form method="POST" action="{{ route('admin.promo-codes.toggle', $code) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="stitch-btn stitch-btn-secondary" style="height: 30px; padding: 0 10px; font-size: 11px;" type="submit">
                                        {{ $code->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.promo-codes.destroy', $code) }}" onsubmit="return confirm('Delete promo code {{ $code->code }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="stitch-btn stitch-btn-danger" style="height: 30px; padding: 0 10px; font-size: 11px;" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px; color: var(--st-text-muted);">
                            No promo codes created yet. Use the form above to add your first discount voucher!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="promo-edit-modal" style="display:none; position:fixed; inset:0; z-index:1000; align-items:center; justify-content:center; padding:20px; background:rgba(12,35,31,.58);">
    <section role="dialog" aria-modal="true" aria-labelledby="promo-edit-title" style="width:min(760px,100%); max-height:90vh; overflow:auto; background:var(--st-surface,#fff); border:1px solid var(--st-border); border-radius:16px; box-shadow:0 24px 80px rgba(0,0,0,.22);">
        <div class="stitch-panel-header" style="position:sticky; top:0; z-index:1; background:var(--st-surface,#fff);">
            <div>
                <h2 id="promo-edit-title" class="stitch-panel-title">Edit Promo Code</h2>
                <p class="stitch-panel-subtitle">Update the discount settings. Existing redemptions are preserved.</p>
            </div>
            <button type="button" id="promo-edit-close" class="stitch-btn stitch-btn-secondary" aria-label="Close edit dialog">Close</button>
        </div>
        <form id="promo-edit-form" method="POST" style="padding:24px;">
            @csrf
            @method('PATCH')
            <div class="stitch-form-grid">
                <div class="stitch-form-group">
                    <label for="edit-promo-code">Voucher Code</label>
                    <input id="edit-promo-code" required maxlength="64" name="code" class="stitch-input" style="text-transform:uppercase;font-family:monospace;font-weight:700;">
                </div>
                <div class="stitch-form-group">
                    <label for="edit-promo-type">Discount Type</label>
                    <select id="edit-promo-type" name="discount_type" class="stitch-select" style="width:100%;">
                        <option value="fixed">Fixed Amount (EGP)</option>
                        <option value="percentage">Percentage (%)</option>
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label for="edit-promo-value">Discount Value</label>
                    <input id="edit-promo-value" required type="number" min="0.01" step="0.01" max="100000" name="discount_value" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label for="edit-promo-race">Applicable Race</label>
                    <select id="edit-promo-race" name="race_id" class="stitch-select" style="width:100%;">
                        <option value="">All Races & Distances</option>
                        @foreach($races as $race)
                            <option value="{{ $race->id }}">{{ $race->name }} ({{ $race->distance_label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label for="edit-promo-max-uses">Max Redemptions <small style="color:var(--st-text-muted);">(Optional)</small></label>
                    <input id="edit-promo-max-uses" type="number" min="1" max="1000000" name="max_uses" class="stitch-input">
                    <small id="edit-promo-usage-note" style="color:var(--st-text-muted);"></small>
                </div>
                <div class="stitch-form-group">
                    <label for="edit-promo-valid-from">Valid From <small style="color:var(--st-text-muted);">(Optional)</small></label>
                    <input id="edit-promo-valid-from" type="date" name="valid_from" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label for="edit-promo-valid-until">Valid Until <small style="color:var(--st-text-muted);">(Optional)</small></label>
                    <input id="edit-promo-valid-until" type="date" name="valid_until" class="stitch-input">
                </div>
                <div class="stitch-form-group" style="justify-content:center;padding-top:18px;">
                    <label class="stitch-checkbox-label">
                        <input id="edit-promo-active" type="checkbox" name="is_active" value="1">
                        Active
                    </label>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;">
                <button type="button" id="promo-edit-cancel" class="stitch-btn stitch-btn-secondary">Cancel</button>
                <button type="submit" class="stitch-btn stitch-btn-primary">Save Changes</button>
            </div>
        </form>
    </section>
</div>

<script>
(() => {
    const modal = document.getElementById('promo-edit-modal');
    const form = document.getElementById('promo-edit-form');
    const updateUrl = @json(route('admin.promo-codes.update', ['promoCode' => '__PROMO_ID__']));
    const close = () => { modal.style.display = 'none'; document.body.style.overflow = ''; };

    document.querySelectorAll('.promo-edit-button').forEach((button) => {
        button.addEventListener('click', () => {
            const data = button.dataset;
            form.action = updateUrl.replace('__PROMO_ID__', encodeURIComponent(data.id));
            document.getElementById('edit-promo-code').value = data.code;
            document.getElementById('edit-promo-type').value = data.discountType;
            document.getElementById('edit-promo-value').value = data.discountValue;
            document.getElementById('edit-promo-race').value = data.raceId;
            document.getElementById('edit-promo-max-uses').value = data.maxUses;
            document.getElementById('edit-promo-max-uses').min = Math.max(1, Number(data.timesUsed || 0));
            document.getElementById('edit-promo-valid-from').value = data.validFrom;
            document.getElementById('edit-promo-valid-until').value = data.validUntil;
            document.getElementById('edit-promo-active').checked = data.isActive === '1';
            document.getElementById('edit-promo-usage-note').textContent = `${data.timesUsed || 0} redemption(s) already used; this count is preserved.`;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.getElementById('edit-promo-code').focus();
        });
    });

    document.getElementById('promo-edit-close').addEventListener('click', close);
    document.getElementById('promo-edit-cancel').addEventListener('click', close);
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.style.display !== 'none') close(); });
})();
</script>
@endsection
