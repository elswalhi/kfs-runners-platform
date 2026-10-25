@extends('admin.layout')
@section('title', 'Race pricing · KFS Runners Admin')
@section('section', 'Race & pricing')
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">EVENT CONFIGURATION</span>
        <h1>Race & Pricing</h1>
        <p>Maintain race-kit pricing. Laravel recalculates all registration totals safely on the server.</p>
    </div>
</div>

<div class="stitch-callout" style="margin-bottom: 24px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <div>
        <b>Pricing is sensitive configuration</b>
        <p>Prices must be greater than 1 EGP for EasyKash. An active race needs an active Full and Basic price. Prices linked to existing registrations can be deactivated, but not deleted.</p>
    </div>
</div>

<div class="stitch-table-panel" style="margin-bottom: 28px;">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">Add a Kit Price</h2>
            <p class="stitch-panel-subtitle">Add a missing pricing tier or new option to a race category.</p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST" action="{{ route('admin.pricing.store') }}">
            @csrf
            <div class="stitch-form-grid">
                <div class="stitch-form-group">
                    <label>Race</label>
                    <select name="race_id" class="stitch-select" style="width:100%;" required>
                        @foreach($races as $race)
                            <option value="{{ $race->id }}">{{ $race->name }} ({{ $race->distance_label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label>Kit Type</label>
                    <select name="kit_type" class="stitch-select" style="width:100%;">
                        <option value="full">Full Kit (T-Shirt + Race Essentials)</option>
                        <option value="basic">Basic Kit (Race Essentials only)</option>
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label>Price</label>
                    <input type="number" min="1.01" step="0.01" name="price" class="stitch-input" placeholder="e.g. 350.00" required>
                </div>
                <div class="stitch-form-group">
                    <label>Currency</label>
                    <input name="currency" value="EGP" maxlength="3" class="stitch-input" required>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button class="stitch-btn stitch-btn-primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Add Price Option
                </button>
            </div>
        </form>
    </div>
</div>

<div class="stitch-pricing-grid">
    @forelse($races as $race)
        <div class="stitch-pricing-card">
            <div class="stitch-pricing-header">
                <div class="stitch-pricing-race-info">
                    <div class="stitch-pricing-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    </div>
                    <div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <h3 style="font-size:18px; font-weight:800; color:var(--st-text-primary); margin:0;">{{ $race->name }}</h3>
                            <span class="stitch-distance-chip">{{ $race->distance_label }}</span>
                        </div>
                        <p style="font-size:12px; color:var(--st-text-muted); margin:4px 0 0;">{{ ucfirst($race->category_type) }} race · {{ $race->kitPrices->count() }} active price tier(s)</p>
                    </div>
                </div>
                <span class="stitch-badge {{ $race->is_active ? 'status-active' : 'status-inactive' }}">
                    <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                    {{ $race->is_active ? 'Active Race' : 'Inactive Race' }}
                </span>
            </div>

            <div class="stitch-pricing-options">
                @forelse($race->kitPrices as $price)
                    <div class="stitch-price-item">
                        <div class="stitch-price-item-top">
                            <span class="stitch-price-dot {{ $price->kit_type }}"></span>
                            <div style="flex:1;">
                                <div style="font-size:14px; font-weight:700; color:var(--st-text-primary);">{{ ucfirst($price->kit_type) }} Kit</div>
                                <div style="font-size:11px; color:var(--st-text-muted); margin-top:2px;">
                                    {{ $price->kit_type === 'full' ? 'Includes commemorative race T-shirt + medal' : 'Race bib and timing essentials' }}
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.pricing.update', $price) }}" style="display:flex; flex-direction:column; gap:12px;">
                            @csrf
                            @method('PUT')
                            <div class="stitch-price-inputs">
                                <div class="stitch-input-currency-wrap">
                                    <span>{{ $price->currency }}</span>
                                    <input type="number" min="1.01" step="0.01" name="price" value="{{ $price->price }}" required>
                                </div>
                                <label class="stitch-checkbox-label">
                                    <input type="checkbox" name="is_active" value="1" @checked($price->is_active)>
                                    Active
                                </label>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed var(--st-border); margin-top:4px; padding-top:10px;">
                                <button class="stitch-btn stitch-btn-secondary" style="font-size:12px; height:32px; padding:0 12px;" type="submit">
                                    Save
                                </button>
                        </form>
                                <form method="POST" action="{{ route('admin.pricing.destroy', $price) }}" onsubmit="return confirm('Delete this kit price?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="stitch-btn stitch-btn-danger" style="font-size:12px; height:32px; padding:0 10px;" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </div>
                    </div>
                @empty
                    <div style="padding:20px; text-align:center; color:var(--st-text-muted); font-size:13px; grid-column: 1 / -1;">
                        No prices configured for this race.
                    </div>
                @endforelse
            </div>
        </div>
    @empty
        <div class="stitch-table-panel" style="padding:48px; text-align:center; color:var(--st-text-muted);">
            No races have been created yet.
        </div>
    @endforelse
</div>
@endsection
