@extends('admin.layout')

@section('title', __('Age Groups') . ' · KFS Runners Admin')
@section('section', __('Age Groups'))

@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">{{ __('RUNNER CLASSIFICATION') }}</span>
        <h1>{{ __('Age Groups') }}</h1>
        <p>{{ __('Set inclusive age ranges for the runner list and age-category filters. Age is calculated from date of birth on race day.') }}</p>
    </div>
</div>

@if(session('success'))
    <div style="padding:13px 16px;margin-bottom:18px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;color:#065f46;font-weight:700;">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div style="padding:13px 16px;margin-bottom:18px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b;">
        <strong>{{ __('Please fix the following:') }}</strong>
        <ul style="margin:6px 0 0;padding-inline-start:20px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="stitch-table-panel" style="margin-bottom:24px;">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">{{ __('Add an age group') }}</h2>
            <p class="stitch-panel-subtitle">{{ __('Both ends of the age range are included. Overlapping ranges are not allowed.') }}</p>
        </div>
    </div>
    <div style="padding:22px;">
        <form method="POST" action="{{ route('admin.age-groups.store') }}">
            @csrf
            <div class="stitch-form-grid">
                <div class="stitch-form-group">
                    <label>{{ __('Category name') }}</label>
                    <input required maxlength="100" name="name" value="{{ old('name') }}" class="stitch-input" placeholder="{{ __('e.g. Junior') }}">
                </div>
                <div class="stitch-form-group">
                    <label>{{ __('From age') }}</label>
                    <input required type="number" min="0" max="120" name="min_age" value="{{ old('min_age') }}" class="stitch-input" placeholder="e.g. 12">
                </div>
                <div class="stitch-form-group">
                    <label>{{ __('To age') }}</label>
                    <input required type="number" min="0" max="120" name="max_age" value="{{ old('max_age') }}" class="stitch-input" placeholder="e.g. 17">
                </div>
                <div class="stitch-form-group">
                    <label>{{ __('Display order') }}</label>
                    <input required type="number" min="0" max="10000" name="sort_order" value="{{ old('sort_order', $ageGroups->count() + 1) }}" class="stitch-input">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Add age group') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="stitch-table-panel">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">{{ __('Configured age groups') }}</h2>
            <p class="stitch-panel-subtitle">{{ trans_choice(':count age group|:count age groups', $ageGroups->count(), ['count' => $ageGroups->count()]) }}</p>
        </div>
    </div>
    <div class="stitch-table-container">
        <table class="stitch-table">
            <thead>
                <tr>
                    <th>{{ __('Category name') }}</th>
                    <th>{{ __('Age range') }}</th>
                    <th>{{ __('Order') }}</th>
                    <th style="text-align:right;">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ageGroups as $ageGroup)
                    <tr>
                        <td colspan="3">
                            <form method="POST" action="{{ route('admin.age-groups.update', $ageGroup) }}" style="display:grid;grid-template-columns:minmax(150px,1.5fr) minmax(85px,.6fr) minmax(85px,.6fr) minmax(80px,.5fr) auto;align-items:center;gap:10px;">
                                @csrf
                                @method('PUT')
                                <input required maxlength="100" name="name" value="{{ $ageGroup->name }}" class="stitch-input" aria-label="{{ __('Category name') }}">
                                <label style="display:flex;align-items:center;gap:6px;color:var(--st-text-muted);font-size:12px;">
                                    {{ __('From') }} <input required type="number" min="0" max="120" name="min_age" value="{{ $ageGroup->min_age }}" class="stitch-input" style="width:68px;">
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;color:var(--st-text-muted);font-size:12px;">
                                    {{ __('To') }} <input required type="number" min="0" max="120" name="max_age" value="{{ $ageGroup->max_age }}" class="stitch-input" style="width:68px;">
                                </label>
                                <input required type="number" min="0" max="10000" name="sort_order" value="{{ $ageGroup->sort_order }}" class="stitch-input" aria-label="{{ __('Display order') }}">
                                <button class="stitch-btn stitch-btn-secondary" type="submit">{{ __('Save') }}</button>
                            </form>
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.age-groups.destroy', $ageGroup) }}" onsubmit="return confirm(@js(__('Delete this age group?')))" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button class="stitch-btn stitch-btn-danger" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:36px;color:var(--st-text-muted);">
                            {{ __('No age groups yet. Add a range above to enable age-category filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
