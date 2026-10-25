@extends('admin.layout')
@section('title', 'Race management · KFS Runners Admin')
@section('section', 'Race management')
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">EVENT CONFIGURATION</span>
        <h1>Race Management</h1>
        <p>Create, configure, activate, and manage race categories available on the public registration portal.</p>
    </div>
</div>

<div class="stitch-table-panel" style="margin-bottom: 28px;">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">Create a New Race</h2>
            <p class="stitch-panel-subtitle">New races are initialized with their default Full and Basic kit prices.</p>
        </div>
    </div>
    <div style="padding: 24px;">
        <form method="POST" action="{{ route('admin.races.store') }}">
            @csrf
            <div class="stitch-form-grid">
                <div class="stitch-form-group">
                    <label>Race Name</label>
                    <input required maxlength="120" name="name" value="{{ old('name') }}" placeholder="e.g. Half Marathon" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>URL Slug</label>
                    <input required maxlength="120" pattern="[A-Za-z0-9_-]+" name="slug" value="{{ old('slug') }}" placeholder="e.g. 21k" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Distance Label</label>
                    <input required maxlength="40" name="distance_label" value="{{ old('distance_label') }}" placeholder="e.g. 21 KM" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Category Type</label>
                    <select name="category_type" class="stitch-select" style="width:100%;">
                        <option value="individual">Individual</option>
                        <option value="family">Family</option>
                        <option value="para">Para</option>
                    </select>
                </div>
                <div class="stitch-form-group">
                    <label>Display Order</label>
                    <input required type="number" min="0" max="10000" name="sort_order" value="{{ old('sort_order', $races->count() + 1) }}" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Full Kit Price (EGP)</label>
                    <input required type="number" min="1.01" step="0.01" name="full_price" value="{{ old('full_price') }}" placeholder="e.g. 450" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Basic Kit Price (EGP)</label>
                    <input required type="number" min="1.01" step="0.01" name="basic_price" value="{{ old('basic_price') }}" placeholder="e.g. 250" class="stitch-input">
                </div>
                <div class="stitch-form-group" style="justify-content: center; padding-top: 18px;">
                    <label class="stitch-checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active on Registration
                    </label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; pt: 8px;">
                <button class="stitch-btn stitch-btn-primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                    Create Race + Prices
                </button>
            </div>
        </form>
    </div>
</div>

<div class="stitch-table-panel">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">Configured Race Categories</h2>
            <p class="stitch-panel-subtitle">{{ $races->count() }} race distances currently registered</p>
        </div>
    </div>
    <div class="stitch-table-container">
        <table class="stitch-table">
            <thead>
                <tr>
                    <th style="min-width: 200px;">Race & Distance</th>
                    <th style="min-width: 120px;">Category</th>
                    <th style="min-width: 180px;">Kit Prices</th>
                    <th style="min-width: 100px;">Entries</th>
                    <th style="min-width: 100px;">Status</th>
                    <th style="min-width: 280px;">Quick Edit & Update</th>
                    <th style="text-align: right; width: 80px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($races as $race)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--st-text-primary); font-size: 14px;">{{ $race->name }}</div>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                                <span class="stitch-distance-chip">{{ $race->distance_label }}</span>
                                <span class="stitch-code-badge" style="font-size: 10px;">/{{ $race->slug }}</span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600; text-transform: capitalize; color: var(--st-text-secondary); font-size: 13px;">
                                {{ $race->category_type }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 3px; font-size: 12px;">
                                @forelse($race->kitPrices as $price)
                                    <div>
                                        <b style="color: var(--st-text-primary);">{{ ucfirst($price->kit_type) }}:</b>
                                        <span style="color: var(--st-text-secondary);">{{ $price->currency }} {{ number_format($price->price, 2) }}</span>
                                    </div>
                                @empty
                                    <span style="color: var(--st-text-muted);">No prices configured</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <span class="stitch-code-badge" style="font-size: 12px; font-weight: 700;">
                                {{ $race->registrations_count }} runners
                            </span>
                        </td>
                        <td>
                            <span class="stitch-badge {{ $race->is_active ? 'status-active' : 'status-inactive' }}">
                                <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                                {{ $race->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.races.update', $race) }}" style="display: flex; flex-direction: column; gap: 8px;">
                                @csrf
                                @method('PUT')
                                <div style="display: flex; gap: 6px;">
                                    <input required name="name" value="{{ $race->name }}" class="stitch-input" style="height: 32px; font-size: 12px; padding: 0 8px;" placeholder="Name">
                                    <input required name="slug" value="{{ $race->slug }}" class="stitch-input" style="height: 32px; font-size: 12px; padding: 0 8px; width: 70px;" placeholder="Slug">
                                    <input required name="distance_label" value="{{ $race->distance_label }}" class="stitch-input" style="height: 32px; font-size: 12px; padding: 0 8px; width: 70px;" placeholder="Dist">
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                    <select name="category_type" class="stitch-select" style="height: 30px; font-size: 11px; padding: 0 6px; min-width: 90px;">
                                        @foreach(['individual','family','para'] as $type)
                                            <option value="{{ $type }}" @selected($race->category_type === $type)>{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="0" name="sort_order" value="{{ $race->sort_order }}" class="stitch-input" style="height: 30px; width: 45px; font-size: 11px; padding: 0 4px;" title="Sort order">
                                    <label class="stitch-checkbox-label" style="font-size: 11px;">
                                        <input type="checkbox" name="is_active" value="1" @checked($race->is_active)>
                                        Live
                                    </label>
                                    <button class="stitch-btn stitch-btn-secondary" style="height: 30px; padding: 0 10px; font-size: 11px;" type="submit">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('admin.races.destroy', $race) }}" onsubmit="return confirm('Delete {{ addslashes($race->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="stitch-btn stitch-btn-danger" style="height: 30px; padding: 0 10px; font-size: 11px;" type="submit">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--st-text-muted);">
                            No race categories have been configured yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
