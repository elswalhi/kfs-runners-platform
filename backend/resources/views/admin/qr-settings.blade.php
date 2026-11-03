@extends('admin.layout')

@section('title', __('Runner QR Settings') . ' · KFS Runners Admin')
@section('section', __('Runner QR Settings'))

@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">{{ __('RUNNER CHECK-IN') }}</span>
        <h1>{{ __('Runner QR Settings') }}</h1>
        <p>{{ __('Choose which registration details are included when a runner QR code is generated.') }}</p>
    </div>
</div>

@if (session('success'))
    <div style="margin-bottom:20px;padding:14px 18px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;color:#065f46;font-weight:700;">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div style="margin-bottom:20px;padding:14px 18px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b;">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="stitch-table-panel" style="max-width:860px;">
    <div class="stitch-panel-header">
        <div>
            <h2 class="stitch-panel-title">{{ __('QR code fields') }}</h2>
            <p class="stitch-panel-subtitle">{{ __('Selected fields appear in the QR scan text in the order shown below.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.qr-settings.update') }}" style="padding:22px;">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;">
            @foreach ($qrFields as $key => $label)
                <label style="display:flex;align-items:center;gap:11px;min-height:54px;padding:12px 14px;border:1px solid #dce7e1;border-radius:9px;background:#fff;cursor:pointer;">
                    <input type="checkbox" name="qr_fields[]" value="{{ $key }}" @checked(in_array($key, old('qr_fields', $selectedQrFields), true)) style="width:17px;height:17px;accent-color:#08775b;">
                    <span style="color:#173e34;font-size:13px;font-weight:700;">{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-top:20px;padding:14px 16px;border-radius:9px;background:#f3f7f4;color:#63766e;font-size:12px;line-height:1.6;">
            <span>{{ __('A runner number and QR are generated only after payment is confirmed. At least one QR field must remain selected.') }}</span>
            <button type="submit" class="stitch-btn stitch-btn-primary" style="padding:11px 22px;background:#064b3e;border-color:#052f29;">
                {{ __('Save QR Settings') }}
            </button>
        </div>
    </form>
</div>
@endsection
