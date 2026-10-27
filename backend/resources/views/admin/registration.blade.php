@extends('admin.layout')

@section('title', $registration->registration_code.' · Runner Profile')
@section('section', 'Runner Profile')

@section('content')
<!-- Hero Header -->
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <div class="stitch-eyebrow">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <span>PARTICIPANT PROFILE</span>
        </div>
        <h1 class="stitch-hero-title">{{ $registration->first_name }} <span>{{ $registration->last_name }}</span></h1>
        <p class="stitch-hero-desc">Reference: <strong>{{ $registration->registration_code }}</strong> · Registered on {{ $registration->created_at?->format('d M Y, h:i A') }}</p>
    </div>
    <div class="stitch-hero-actions">
        <a href="{{ route('admin.registrations') }}" class="stitch-btn stitch-btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            <span>Back to Registrations</span>
        </a>
    </div>
</div>

<div class="stitch-detail-grid">
    <!-- Left Column: Details -->
    <div class="stitch-panel">
        <div class="stitch-panel-header">
            <div class="stitch-panel-title-wrap">
                <h3>Runner Information</h3>
                <p>Personal identification and contact details</p>
            </div>
            <span class="stitch-badge status-{{ $registration->payment_status }}">
                {{ ucfirst($registration->payment_status) }}
            </span>
        </div>

        <div class="stitch-detail-fields">
            <div class="stitch-detail-item">
                <span class="label">Full Name</span>
                <strong class="val">{{ $registration->first_name }} {{ $registration->last_name }}</strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Email Address</span>
                <strong class="val">{{ $registration->email }}</strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Phone Number</span>
                <strong class="val">{{ $registration->phone }}</strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Gender</span>
                <strong class="val">{{ str($registration->gender)->replace('_',' ')->title() }}</strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Date of Birth</span>
                <strong class="val">{{ $registration->birth_date?->format('d M Y') ?? '—' }}</strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">National ID / Passport</span>
                <strong class="val" style="font-family: 'JetBrains Mono', monospace;">{{ $maskedId }}</strong>
                @if ($idNumberUnavailable)
                    <small style="display:block; margin-top:5px; color:#b45309; font-size:11px; line-height:1.5;">This ID could not be decrypted. Confirm the server APP_KEY is the same one used when this registration was saved.</small>
                @endif
            </div>

            <div class="stitch-detail-item">
                <span class="label">Race Category</span>
                <strong class="val" style="color: var(--st-primary);">
                    {{ $registration->race?->distance_label }} · {{ $registration->race?->name }}
                </strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Package & Kit</span>
                <strong class="val">
                    {{ ucfirst($registration->kit_type) }}
                    @if($registration->shirt_size)
                        (Size: {{ $registration->shirt_size }})
                    @endif
                </strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Club or Team</span>
                <strong class="val">{{ $registration->team?->name ?? $registration->team_name ?? 'Individual' }}</strong>
            </div>

            <div class="stitch-detail-item">
                <span class="label">Ticket Price</span>
                <strong class="val" style="color: var(--st-primary); font-size: 16px;">
                    EGP {{ number_format($registration->amount, 2) }}
                    @if($registration->promo_code)
                        <small style="display:block; font-size:11px; color:#047857; font-weight:600; margin-top:3px;">
                            Promo code: {{ $registration->promo_code }} (-EGP {{ number_format($registration->discount_amount, 2) }})
                        </small>
                    @endif
                </strong>
            </div>
        </div>

        @if($registration->document_path)
            <div style="margin-bottom: 20px;">
                <a href="{{ route('admin.registrations.document', $registration) }}" class="stitch-btn stitch-btn-secondary" style="width: 100%;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    <span>Download Runner Document (PDF/ID)</span>
                </a>
            </div>
        @endif

        <!-- Status Form -->
        <form method="POST" action="{{ route('admin.registrations.update', $registration) }}" style="display: flex; gap: 12px; align-items: flex-end;">
            @csrf
            @method('PATCH')
            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--st-text-muted);">
                    Operational Status
                </label>
                <select name="status" class="stitch-select" style="width: 100%;">
                    @foreach(['pending' => 'Pending Review', 'confirmed' => 'Confirmed Entry', 'cancelled' => 'Cancelled'] as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected($registration->status === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="stitch-btn stitch-btn-primary" style="height: 42px;">
                <span>Update Status</span>
            </button>
        </form>
    </div>

    <!-- Right Column: QR Code & Payments -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Official Runner QR & Digital Bib Pass Card -->
        <div class="stitch-panel" style="text-align: center;">
            <div class="stitch-panel-header" style="justify-content: center; border-bottom: 1px dashed var(--st-border);">
                <div style="text-align: center;">
                    <span class="stitch-code-badge" style="font-size: 13px; font-weight: 800; padding: 4px 10px; background: #fee2e2; color: #991b1b;">
                        BIB # {{ $registration->registration_code }}
                    </span>
                    <h3 style="margin-top: 6px;">{{ __('Runner QR & Kit Pickup Code') }}</h3>
                    <p style="font-size: 12px; color: var(--st-text-muted);">{{ __('Scannable at race expo & kit collection booth') }}</p>
                </div>
            </div>

            <div style="padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="background: #ffffff; padding: 12px; border-radius: 12px; border: 2px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: inline-block;">
                    <img src="{{ $qrDataUri }}" alt="Runner QR Code" width="220" height="220" style="display: block; border-radius: 6px;">
                </div>

                <!-- Summary Chips -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin-top: 14px;">
                    <span style="font-size: 11px; font-weight: 800; background: #ecfdf5; color: #047857; padding: 4px 10px; border-radius: 100px;">
                        🏁 {{ $registration->race?->distance_label }}
                    </span>
                    <span style="font-size: 11px; font-weight: 800; background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 100px;">
                        📦 {{ ucfirst($registration->kit_type) }}
                    </span>
                    @if(str_contains(strtolower($registration->kit_type), 'full') && $registration->shirt_size)
                    <span style="font-size: 11px; font-weight: 800; background: #fef2f2; color: #991b1b; padding: 4px 10px; border-radius: 100px;">
                        👕 T-Shirt: {{ strtoupper($registration->shirt_size) }}
                    </span>
                    @endif
                </div>

                <div style="margin-top: 18px; width: 100%; display: flex; gap: 8px;">
                    <a href="{{ $qrDataUri }}" download="runner-qr-{{ $registration->registration_code }}.png" class="stitch-btn stitch-btn-secondary" style="flex: 1; font-size: 12px; justify-content: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        <span>{{ __('Download QR') }}</span>
                    </a>
                    <button type="button" onclick="window.print()" class="stitch-btn stitch-btn-primary" style="flex: 1; font-size: 12px; justify-content: center; gap: 6px; background: #b81420; border-color: #8e0e17;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        <span>{{ __('Print Badge') }}</span>
                    </button>
                </div>

                <!-- Decoded QR Text preview -->
                <div style="margin-top: 14px; width: 100%; text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px;">
                    <span style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px;">{{ __('QR Scanned Content Preview:') }}</span>
                    <pre style="font-family: monospace; font-size: 11px; color: #1e293b; margin: 0; white-space: pre-wrap; line-height: 1.4;">{{ $qrPayload }}</pre>
                </div>
            </div>
        </div>

        <!-- Payments Ledger -->
        <div class="stitch-panel">
        <div class="stitch-panel-header">
            <div class="stitch-panel-title-wrap">
                <h3>Payment Activity</h3>
                <p>Transactions associated with this registration</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            @forelse($payments as $payment)
                <div style="padding: 14px 16px; border-radius: var(--st-radius-md); border: 1px solid var(--st-border); background: var(--st-surface-subtle); display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span class="stitch-code-badge">PAY-{{ $payment->id }}</span>
                        <span class="stitch-badge status-{{ $payment->status }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <span style="font-size: 12px; color: var(--st-text-muted);">Amount</span>
                        <strong style="font-size: 15px; color: var(--st-text-primary);">
                            {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
                        </strong>
                    </div>
                    @if($payment->gateway_reference)
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--st-text-muted);">
                            <span>Gateway Ref:</span>
                            <span style="font-family: 'JetBrains Mono', monospace;">{{ $payment->gateway_reference }}</span>
                        </div>
                    @endif
                </div>
            @empty
                <p style="text-align: center; color: var(--st-text-muted); font-size: 13px; padding: 24px 0;">No payment attempts found.</p>
            @endforelse
        </div>

        <div class="stitch-callout" style="margin-top: 20px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg>
            <div>
                <h4>Security Notice</h4>
                <p>Payment verification is handled securely via EasyKash. Official receipts are tied to the gateway transaction code.</p>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
