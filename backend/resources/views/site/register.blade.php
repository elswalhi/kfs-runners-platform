@extends('site.layout')

@section('title', __('Register for KFS Runners'))

@section('content')
<main class="register-page">
    <section class="register-hero"><div class="register-hero-bg"></div><div class="wrap register-hero-inner">
        <div class="register-intro"><a href="{{ route('home') }}" class="back-link">← {{ __('Back to home') }}</a><span class="eyebrow-pill"><span class="pulse-dot"></span> {{ __('KFS RUNNERS · KAFR EL-SHEIKH') }}</span><h1>{{ __('YOUR RACE.') }}<br><span>{{ __('YOUR STORY.') }}</span></h1><p>{{ __('Choose your distance, pick your kit, and take the first step toward your next finish line.') }}</p><div class="register-points"><span>♢ {{ __('Secure registration') }}</span><span>♙ {{ __('Solo or team entry') }}</span></div></div>
        <div class="register-summary-top"><span>{{ __('YOUR SELECTED RACE') }}</span><b id="summary-distance">{{ $races->firstWhere('slug', old('race', request('race'))) ? $races->firstWhere('slug', old('race', request('race')))->distance_label : ($races->first()?->distance_label ?? '—') }}</b><small id="summary-race">{{ $races->firstWhere('slug', old('race', request('race'))) ? $races->firstWhere('slug', old('race', request('race')))->name : ($races->first()?->name ?? __('Race category')) }}</small><div class="summary-rule"></div><span>{{ __('PACKAGE') }}</span><b id="summary-kit">{{ __('Full Kit') }}</b><small id="summary-kit-subtitle">{{ __('The complete race-day experience') }}</small><div class="summary-price"><small>{{ __('Ticket price preview') }}</small><strong id="summary-price">—</strong></div></div>
    </div></section>

    <section class="registration-workspace"><div class="wrap registration-layout">
        <div class="registration-form-col">
            <!-- Modern Stepper Progress Bar (ui-skills baseline) -->
            <div class="kfs-stepper-container" id="kfs-stepper">
                <div class="kfs-stepper-progress-track">
                    <div class="kfs-stepper-progress-bar" id="stepper-progress-bar" style="width: 33.33%;"></div>
                </div>
                <div class="kfs-stepper-list" role="tablist">
                    <button type="button" class="kfs-step-item active" data-step-target="1" id="step-btn-1">
                        <span class="kfs-step-badge">1</span>
                        <span class="kfs-step-title">
                            <small>{{ __('STEP 01') }}</small>
                            <strong>{{ __('Step 1: Distance & Kit') }}</strong>
                        </span>
                    </button>
                    <button type="button" class="kfs-step-item" data-step-target="2" id="step-btn-2">
                        <span class="kfs-step-badge">2</span>
                        <span class="kfs-step-title">
                            <small>{{ __('STEP 02') }}</small>
                            <strong>{{ __('Step 2: Runner Details') }}</strong>
                        </span>
                    </button>
                    <button type="button" class="kfs-step-item" data-step-target="3" id="step-btn-3">
                        <span class="kfs-step-badge">3</span>
                        <span class="kfs-step-title">
                            <small>{{ __('STEP 03') }}</small>
                            <strong>{{ __('Step 3: Review & Pay') }}</strong>
                        </span>
                    </button>
                </div>
            </div>

            @if ($errors->any()) <div class="form-alert alert-error" role="alert">{{ $errors->first() }}</div> @endif
            @if (session('payment_error')) <div class="form-alert alert-error" role="alert">{{ session('payment_error') }}@if(session('payment_reference')) Registration reference: <strong>{{ session('payment_reference') }}</strong>.@endif</div> @endif
            <div id="payment-return-message" class="form-alert alert-info" role="status" hidden></div>
            <div id="cash-voucher-panel" class="cash-voucher cash-voucher-pending" aria-live="polite" hidden>
                <span class="cash-voucher-kicker">{{ __('EasyKash payment') }}</span><h3 id="payment-title">{{ __('Checking payment status') }}</h3><p id="payment-description">We are checking the signed EasyKash confirmation. Keep your registration reference.</p>
                <div id="voucher-box" class="cash-voucher-code" hidden><strong id="voucher-value"></strong><button type="button" id="copy-voucher" class="kfs-copy-btn">Copy</button></div>
                <div class="cash-voucher-meta"><span>Registration reference</span><b id="payment-reference"></b><span>Payment status</span><b id="payment-status">Confirming</b></div>
                <small class="cash-voucher-note">Payment is confirmed only by EasyKash’s signed server callback. Do not submit another registration while this payment is being checked.</small>
            </div>

            <form class="runner-form" method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" id="runner-form">
                @csrf

                <!-- ================= STEP 1: DISTANCE & KIT (MULTI-TICKET CART) ================= -->
                <div class="kfs-step-view active" data-step-view="1">
                    <div class="form-title-row">
                        <div>
                            <span class="form-step">{{ __('STEP 01 / 03') }}</span>
                            <h2>{{ __('CHOOSE YOUR') }} <span>{{ __('CHALLENGE') }}</span></h2>
                            <p>{{ __('Pick distance and package for yourself, or register a group of runners together.') }}</p>
                        </div>
                        <div class="secure-label">♙ {{ __('Select distance & kit') }}</div>
                    </div>

                    <!-- Dynamic Tickets Container -->
                    <div id="tickets-container">
                        <!-- Primary Ticket (Ticket #1) -->
                        <div class="kfs-ticket-card" id="ticket-card-1">
                            <div class="kfs-ticket-header">
                                <span class="kfs-ticket-badge">🎟 {{ __('Runner') }} #1 ({{ __('Main contact & buyer') }})</span>
                                <span class="kfs-ticket-price" id="ticket-price-1" style="font-weight:900; color:#064b3e;">—</span>
                            </div>

                            <div class="form-section-heading" style="margin-top:0;"><span>◇</span><span>{{ __('Choose your race') }}</span></div>
                            <input type="hidden" name="race" id="race-value" value="{{ old('race', request('race', $races->first()?->slug)) }}">
                            <div class="race-select-grid" id="race-options">
                                @foreach ($races as $race)
                                    @php
                                        $full = $race->kitPrices->firstWhere('kit_type', 'full');
                                        $basic = $race->kitPrices->firstWhere('kit_type', 'basic');
                                    @endphp
                                    <button type="button" class="race-select-card" data-race="{{ $race->slug }}" data-name="{{ $race->name }}" data-distance="{{ $race->distance_label }}" data-full="{{ $full?->price ?? '' }}" data-basic="{{ $basic?->price ?? '' }}">
                                        <span class="race-select-check"><span class="radio-empty"></span></span><span class="select-distance">{{ $race->distance_label }}</span><strong>{{ $race->name }}</strong><small>{{ $race->category_type === 'family' ? __('Bring your family and enjoy the race together.') : ($race->category_type === 'para' ? __('An inclusive race experience made for every runner.') : __('A challenge for every pace and every goal.')) }}</small>
                                    </button>
                                @endforeach
                            </div>

                            <div class="form-section-heading section-heading-spaced"><span>♧</span><span>{{ __('Select your kit') }}</span></div>
                            <input type="hidden" name="kit" id="kit-value" value="{{ old('kit', request('kit', 'full')) }}">
                            <div class="kit-select-grid" id="kit-options">
                                <button type="button" class="kit-select-card" data-kit="full"><span class="kit-check"><span class="radio-empty"></span></span><span class="kit-chip full">MOST COMPLETE</span><strong>{{ __('Full Kit') }}</strong><small>{{ __('The complete race-day experience') }}</small><ul><li>✓ {{ __('Finisher medal') }}</li><li>✓ {{ __('Official KFS Runners T-shirt') }}</li><li>✓ {{ __('Runner bag') }}</li><li>✓ {{ __('Race support and photographs') }}</li></ul><span class="kit-price" data-price-for="full">—</span></button>
                                <button type="button" class="kit-select-card" data-kit="basic"><span class="kit-check"><span class="radio-empty"></span></span><span class="kit-chip basic">RACE ESSENTIALS</span><strong>{{ __('Basic Kit') }}</strong><small>{{ __('The essentials for your race') }}</small><ul><li>✓ {{ __('Finisher medal') }}</li><li>✓ {{ __('Certificate of participation') }}</li><li>✓ {{ __('Race support and photographs') }}</li></ul><span class="kit-price" data-price-for="basic">—</span></button>
                            </div>

                            <div class="shirt-size-panel" id="shirt-size-panel"><div class="shirt-size-copy"><span>♧</span><div><b>{{ __('T-shirt size') }} <span>*</span></b><small>{{ __('Full Kit includes the official race T-shirt. Select your size.') }}</small></div></div><div class="shirt-size-options">
                                @foreach (['S','M','L','XL','XXL'] as $size)<button type="button" data-size="{{ $size }}" class="shirt-size-choice">{{ $size }}</button>@endforeach
                            </div><input type="hidden" name="shirtSize" id="shirt-size" value="{{ old('shirtSize') }}"><small class="field-hint" id="shirt-size-hint">{{ __('Please select a size to continue.') }}</small></div>
                        </div>

                        <!-- Extra Tickets Inserted Here Dynamically -->
                        <div id="extra-tickets-list"></div>
                    </div>

                    <!-- Add Another Runner Button (EasyKash Multi-ticket feature) -->
                    <button type="button" class="kfs-add-ticket-btn" id="add-runner-ticket-btn">
                        <span>＋</span> <span>{{ __('Add another runner') }}</span>
                    </button>

                    <div class="kfs-step-nav-bar" style="justify-content: flex-end;">
                        <button type="button" class="kfs-btn-next" id="step-1-next-btn">
                            <span>{{ __('Next: Runner details') }}</span> <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- ================= STEP 2: RUNNER DETAILS ================= -->
                <div class="kfs-step-view" data-step-view="2">
                    <div class="form-title-row">
                        <div>
                            <span class="form-step">{{ __('STEP 02 / 03') }}</span>
                            <h2>{{ __('RUNNER') }} <span>{{ __('DETAILS') }}</span></h2>
                            <p>{{ __('Enter runner details. Runner #1 is the primary contact who receives tickets and completes payment.') }}</p>
                        </div>
                        <div class="secure-label">♙ {{ __('Your information stays protected') }}</div>
                    </div>

                    <!-- Attendees / Runners Details Container -->
                    <div id="attendees-container">
                        <!-- Runner 1 Attendee Card (Main Contact & Buyer) -->
                        <div class="kfs-attendee-section" id="attendee-card-1">
                            <div class="kfs-attendee-header">
                                <b style="font-size:12px; color:#064b3e;">{{ __('Runner') }} #1 ({{ __('Main contact & buyer') }} · <span id="attendee-race-label-1">—</span>)</b>
                                <small style="color:#137333; font-weight:700; background:#e6f4ea; padding:3px 8px; border-radius:4px;">{{ __('Receives payment confirmation & e-tickets') }}</small>
                            </div>
                            <div class="form-grid two-col">
                                <label>{{ __('First name') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <input required name="firstName" id="input-firstName" autocomplete="given-name" value="{{ old('firstName', old('buyerFirstName')) }}" placeholder="{{ __('First name') }}">
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                </label>
                                <label>{{ __('Last name') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <input required name="lastName" id="input-lastName" autocomplete="family-name" value="{{ old('lastName', old('buyerLastName')) }}" placeholder="{{ __('Last name') }}">
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                </label>
                                <label>{{ __('Email address') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <input required type="email" name="email" id="input-email" autocomplete="email" value="{{ old('email', old('buyerEmail')) }}" placeholder="you@example.com">
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                </label>
                                <label>{{ __('Phone number') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <input required type="tel" name="phone" id="input-phone" autocomplete="tel" value="{{ old('phone', old('buyerPhone')) }}" placeholder="010 1234 5678">
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                    <small class="kfs-field-hint" id="phone-validation-hint" data-default="{{ __('Use an Egyptian mobile number. Local and +20 formats are accepted.') }}">{{ __('Use an Egyptian mobile number. Local and +20 formats are accepted.') }}</small>
                                </label>
                                <label>{{ __('Date of birth') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <input required type="date" name="birthDate" id="input-birthDate" max="{{ now()->subDay()->toDateString() }}" value="{{ old('birthDate') }}">
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                </label>
                                <label>{{ __('Gender') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <span class="select-wrap">
                                            <select required name="gender" id="input-gender">
                                                <option value="">{{ __('Choose gender') }}</option>
                                                <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                                                <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                                            </select>
                                            <span>⌄</span>
                                        </span>
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                </label>
                                <label class="full-span">{{ __('ID / Passport number') }} <b>*</b>
                                    <div class="kfs-field-wrap">
                                        <input required name="idNumber" id="input-idNumber" value="{{ old('idNumber') }}" placeholder="{{ __('ID / Passport number') }}">
                                        <span class="kfs-validation-badge">✓</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Extra Attendees Injected Dynamically for Runners #2+ -->
                        <div id="extra-attendees-list"></div>
                    </div>

                    <div class="form-section-heading section-heading-spaced"><span>⇧</span><span>{{ __('Runner document') }}</span><small>{{ __('Optional') }}</small></div>
                    <label class="upload-drop"><input type="file" name="idFile" accept="image/jpeg,image/png,application/pdf"><span class="upload-icon">⇧</span><span><b id="file-name">{{ __('Upload ID / passport (optional)') }}</b><small>{{ __('JPG, PNG or PDF · Max 2 MB') }}</small></span></label>

                    <div class="team-select-group" style="margin-top:20px;">
                        <label class="team-name-label" style="display:block; font-weight:700; font-size:12px; color:#064b3e; margin-bottom:8px;">
                            <span>🏃 {{ __('Your Team - الفريق') }}</span>
                            <small style="color:#778b82; font-weight:normal;">({{ __('Optional') }})</small>
                            <div class="kfs-field-wrap" style="margin-top:6px;">
                                <span class="select-wrap" style="width:100%;">
                                    <select name="teamName" id="team-name-select" style="width:100%; height:46px; border:1px solid #c9d8d0; border-radius:8px; padding:0 14px; font-size:13px; color:#113a30; background:#ffffff;">
                                        <option value="">{{ __('Individual / No team') }} ({{ __('Optional') }})</option>
                                        @foreach (config('teams.list') as $teamOption)
                                            <option value="{{ $teamOption }}" @selected(old('teamName') === $teamOption)>{{ $teamOption }}</option>
                                        @endforeach
                                    </select>
                                    <span>⌄</span>
                                </span>
                            </div>
                        </label>
                        <div id="other-team-wrap" style="margin-top:8px; display:none;">
                            <input type="text" id="other-team-input" placeholder="{{ __('Type your team / running club name') }}" style="width:100%; height:44px; border:1px solid #c9d8d0; border-radius:8px; padding:0 14px; font-size:13px; color:#113a30; background:#ffffff;">
                        </div>
                    </div>

                    <div class="kfs-step-nav-bar">
                        <button type="button" class="kfs-btn-back" id="step-2-back-btn">
                            <span>←</span> <span>{{ __('Back') }}</span>
                        </button>
                        <button type="button" class="kfs-btn-next" id="step-2-next-btn">
                            <span>{{ __('Next: Review & pay') }}</span> <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- ================= STEP 3: REVIEW & PAYMENT (SINGLE CHECKOUT) ================= -->
                <div class="kfs-step-view" data-step-view="3">
                    <div class="form-title-row">
                        <div>
                            <span class="form-step">{{ __('STEP 03 / 03') }}</span>
                            <h2>{{ __('REVIEW &') }} <span>{{ __('PAYMENT') }}</span></h2>
                            <p>{{ __('Verify all runners in this order, apply discounts, and complete single checkout via EasyKash.') }}</p>
                        </div>
                        <div class="secure-label">♙ {{ __('Secure checkout with EasyKash') }}</div>
                    </div>

                    <!-- Primary Contact Preview -->
                    <div style="background:#f4f8f5; border:1px solid #dce8e1; border-radius:10px; padding:16px 20px; margin-top:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:10px; font-weight:800; letter-spacing:1px; color:#064b3e; text-transform:uppercase;">{{ __('Main contact & buyer') }}</span>
                            <button type="button" id="edit-buyer-btn" style="background:none; border:none; color:#ed1b2f; font-size:11px; font-weight:800; cursor:pointer;">✎ {{ __('Edit') }}</button>
                        </div>
                        <div style="display:flex; gap:20px; flex-wrap:wrap; font-size:11px; color:#113a30;">
                            <div><span style="color:#778b82;">{{ __('Name') }}:</span> <b id="review-buyer-name">—</b></div>
                            <div><span style="color:#778b82;">{{ __('Email address') }}:</span> <b id="review-buyer-email">—</b></div>
                            <div><span style="color:#778b82;">{{ __('Phone number') }}:</span> <b id="review-buyer-phone">—</b></div>
                        </div>
                    </div>

                    <!-- Itemized Runners Order List -->
                    <div class="form-section-heading section-heading-spaced"><span>🎟</span><span>{{ __('Order summary') }}</span></div>
                    <div class="kfs-order-items-list" id="review-runners-list"></div>

                    <!-- Promo Code Box -->
                    <div class="form-section-heading section-heading-spaced"><span>%</span><span>{{ __('Promo code or voucher') }}</span><small>{{ __('Optional') }}</small></div>
                    <div class="promo-code-box" style="background:#f8faf9; border:1px solid #dce5df; border-radius:8px; padding:16px; margin-top:10px;">
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <input type="text" id="promo-code-input" placeholder="e.g. KFS50" maxlength="64" value="{{ old('promoCode') }}" style="flex:1; min-width:160px; height:42px; border:1px solid #c9d8d0; border-radius:6px; padding:0 12px; font-family:monospace; font-weight:700; text-transform:uppercase; font-size:13px; color:#064b3e; background:#fff;">
                            <button type="button" id="apply-promo-btn" style="height:42px; padding:0 18px; background:#064b3e; color:#ffffff; border:none; border-radius:6px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; transition:all 0.2s;">
                                {{ __('Apply') }}
                            </button>
                            <button type="button" id="remove-promo-btn" style="height:42px; padding:0 14px; background:#fff1f2; color:#be123c; border:1px solid #fecdd3; border-radius:6px; font-size:11px; font-weight:800; cursor:pointer;" hidden>
                                {{ __('Remove') }}
                            </button>
                        </div>
                        <div id="promo-feedback" style="margin-top:10px; font-size:11px; font-weight:600; line-height:1.4;" hidden></div>
                        <input type="hidden" name="promoCode" id="applied-promo-code" value="{{ old('promoCode') }}">
                    </div>

                    <label class="terms-check"><input type="checkbox" name="terms" id="input-terms" value="1" required><span>{{ __('I confirm that the details provided are correct and agree to the race terms and conditions.') }} <b>*</b></span></label>
                    <button class="btn btn-red form-submit" type="submit" id="submit-registration" @disabled($races->isEmpty())>
                        <span id="submit-btn-text">{{ __('Proceed to pay for all runners') }}</span> <span>→</span>
                    </button>
                    <p class="form-fineprint">♙ Payment is confirmed by EasyKash’s signed server callback.</p>

                    <div class="kfs-step-nav-bar">
                        <button type="button" class="kfs-btn-back" id="step-3-back-btn">
                            <span>←</span> <span>{{ __('Back to details') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <aside class="registration-order-col">
            <div class="order-card">
                <div class="order-card-heading">
                    <div>
                        <span class="form-step">{{ __('YOUR ENTRY') }}</span>
                        <h3>{{ __('Registration') }} <span>{{ __('summary') }}</span></h3>
                    </div>
                    <span class="order-lock">♙</span>
                </div>
                <div class="order-race-image">
                    <div class="order-race-photo"></div>
                    <div class="order-race-overlay">
                        <span>KFS RUNNERS</span>
                        <b id="order-distance">{{ $races->first()?->distance_label ?? '—' }}</b>
                        <small id="order-race">{{ $races->first()?->name ?? __('Race category') }}</small>
                    </div>
                </div>
                <div class="order-detail-row"><span>{{ __('Total tickets') }}</span><b id="order-tickets-count">1 Ticket</b></div>
                <div class="order-detail-row"><span>{{ __('Race distance') }}</span><b id="order-distance-row">{{ $races->first()?->distance_label ?? '—' }}</b></div>
                <div class="order-detail-row"><span>{{ __('Race kit') }}</span><b id="order-kit-row">{{ __('Full Kit') }}</b></div>
                <div class="order-detail-row" id="order-shirt-row"><span>{{ __('T-shirt size') }}</span><b id="order-shirt">Not selected</b></div>
                <div class="order-divider"></div>
                <div class="order-price-line"><span>{{ __('Entry fee') }}</span><strong id="order-price">—</strong></div>
                <div class="order-price-line" id="order-discount-row" style="color:#047857;" hidden>
                    <span>{{ __('Promo discount') }} (<b id="order-promo-label"></b>)</span>
                    <strong id="order-discount" style="color:#047857;">—</strong>
                </div>
                <div class="order-price-total"><span>{{ __('Total preview') }}</span><strong id="order-total">—</strong></div>
                <p class="order-tax-note">{{ __('Final price, availability, and payment status are confirmed by Laravel.') }}</p>
                <div class="order-benefits"><span>♢ {{ __('Secure registration') }}</span><span>▭ EasyKash checkout</span></div>
            </div>
            <div class="need-help-card">
                <h4>Need help choosing?</h4>
                <p>Choose the distance that feels right for you. The best race is the one you show up for.</p>
                <a href="{{ route('home') }}#race-categories" class="text-link">Explore the event <span>→</span></a>
            </div>
        </aside>
    </div></section>

    <!-- Mobile Floating Sticky Checkout Dock -->
    <div class="kfs-mobile-checkout-dock" id="mobile-checkout-dock">
        <div class="kfs-dock-meta">
            <span class="kfs-dock-label" id="dock-race-label">1 Ticket · 10K Full Kit</span>
            <b class="kfs-dock-price" id="dock-price-value">—</b>
        </div>
        <button type="button" class="kfs-dock-btn" id="dock-action-btn">
            <span id="dock-action-text">{{ __('Next: Runner details') }}</span> <span>→</span>
        </button>
    </div>
</main>
@endsection

@push('scripts')
@php
    $racesPayload = $races->map(function ($r) {
        return [
            'id' => $r->id,
            'slug' => $r->slug,
            'name' => $r->name,
            'distance' => $r->distance_label,
            'category_type' => $r->category_type,
            'full_price' => (float) ($r->kitPrices->firstWhere('kit_type', 'full')?->price ?? 0),
            'basic_price' => (float) ($r->kitPrices->firstWhere('kit_type', 'basic')?->price ?? 0),
        ];
    })->values();
@endphp
<script>
(() => {
    const allRaces = @json($racesPayload);

    // Multi-ticket Cart State (EasyKash model)
    let tickets = [
        {
            id: 1,
            race: '{{ old('race', request('race', $races->first()?->slug)) }}',
            kit: '{{ old('kit', request('kit', 'full')) }}',
            shirtSize: '{{ old('shirtSize') }}'
        }
    ];

    const raceInput = document.getElementById('race-value');
    const kitInput = document.getElementById('kit-value');
    const shirtInput = document.getElementById('shirt-size');
    const promoInput = document.getElementById('promo-code-input');
    const appliedPromoHidden = document.getElementById('applied-promo-code');
    const applyPromoBtn = document.getElementById('apply-promo-btn');
    const removePromoBtn = document.getElementById('remove-promo-btn');
    const promoFeedback = document.getElementById('promo-feedback');
    const orderDiscountRow = document.getElementById('order-discount-row');
    const orderPromoLabel = document.getElementById('order-promo-label');
    const orderDiscount = document.getElementById('order-discount');

    let appliedPromo = null;
    let currentStep = {{ $errors->any() ? 2 : 1 }};

    const price = value => new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(Number(value || 0));
    const setText = (id, value) => { const node = document.getElementById(id); if (node) node.textContent = value; };

    function getTicketPrice(t) {
        const race = allRaces.find(r => r.slug === t.race) || allRaces[0];
        if (!race) return 0;
        return t.kit === 'full' ? race.full_price : race.basic_price;
    }

    function renderTickets() {
        const extraTicketsContainer = document.getElementById('extra-tickets-list');
        const extraAttendeesContainer = document.getElementById('extra-attendees-list');

        if (extraTicketsContainer) {
            extraTicketsContainer.innerHTML = '';
            tickets.slice(1).forEach((t, idx) => {
                const tNum = idx + 2;
                const race = allRaces.find(r => r.slug === t.race) || allRaces[0];
                const card = document.createElement('div');
                card.className = 'kfs-ticket-card';
                card.id = `ticket-card-${tNum}`;

                card.innerHTML = `
                    <div class="kfs-ticket-header">
                        <span class="kfs-ticket-badge">🎟 {{ __('Runner') }} #${tNum}</span>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <b style="font-weight:900; color:#064b3e;">${price(getTicketPrice(t))}</b>
                            <button type="button" class="kfs-ticket-remove-btn" data-remove-ticket="${t.id}">✕ {{ __('Remove ticket') }}</button>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:12px;">
                        <label style="font-size:10px; font-weight:800; color:#294b43; display:flex; flex-direction:column; gap:5px;">
                            {{ __('Choose your race') }} *
                            <select class="kfs-ticket-race-select" data-ticket-id="${t.id}" style="height:38px; border:1px solid #dce6e0; border-radius:6px; padding:0 8px; font-size:11px; background:#fff;">
                                ${allRaces.map(r => `<option value="${r.slug}" ${r.slug === t.race ? 'selected' : ''}>${r.distance} - ${r.name}</option>`).join('')}
                            </select>
                        </label>
                        <label style="font-size:10px; font-weight:800; color:#294b43; display:flex; flex-direction:column; gap:5px;">
                            {{ __('Select your kit') }} *
                            <select class="kfs-ticket-kit-select" data-ticket-id="${t.id}" style="height:38px; border:1px solid #dce6e0; border-radius:6px; padding:0 8px; font-size:11px; background:#fff;">
                                <option value="full" ${t.kit === 'full' ? 'selected' : ''}>{{ __('Full Kit') }} (${price(race?.full_price)})</option>
                                <option value="basic" ${t.kit === 'basic' ? 'selected' : ''}>{{ __('Basic Kit') }} (${price(race?.basic_price)})</option>
                            </select>
                        </label>
                        ${t.kit === 'full' ? `
                            <label style="font-size:10px; font-weight:800; color:#294b43; display:flex; flex-direction:column; gap:5px;">
                                {{ __('T-shirt size') }} *
                                <select class="kfs-ticket-size-select" data-ticket-id="${t.id}" style="height:38px; border:1px solid #dce6e0; border-radius:6px; padding:0 8px; font-size:11px; background:#fff;">
                                    <option value="">{{ __('Select size') }}</option>
                                    ${['S','M','L','XL','XXL'].map(s => `<option value="${s}" ${s === t.shirtSize ? 'selected' : ''}>${s}</option>`).join('')}
                                </select>
                            </label>
                        ` : ''}
                    </div>
                `;
                extraTicketsContainer.appendChild(card);
            });
        }

        // Render attendee cards for runners #2+ in Step 2
        if (extraAttendeesContainer) {
            extraAttendeesContainer.innerHTML = '';
            tickets.slice(1).forEach((t, idx) => {
                const tNum = idx + 2;
                const race = allRaces.find(r => r.slug === t.race) || allRaces[0];
                const section = document.createElement('div');
                section.className = 'kfs-attendee-section';
                section.id = `attendee-card-${tNum}`;

                section.innerHTML = `
                    <div class="kfs-attendee-header">
                        <b style="font-size:12px; color:#064b3e;">{{ __('Runner') }} #${tNum} (${race.distance} · ${t.kit === 'full' ? '{{ __('Full Kit') }}' : '{{ __('Basic Kit') }}'})</b>
                        <small style="color:#6b8278;">{{ __('Ticket') }} #${tNum}</small>
                    </div>
                    <div class="form-grid two-col">
                        <label>{{ __('First name') }} <b>*</b>
                            <div class="kfs-field-wrap">
                                <input required name="runners[${idx + 1}][firstName]" id="input-firstName-${tNum}" autocomplete="given-name" placeholder="{{ __('First name') }}">
                                <span class="kfs-validation-badge">✓</span>
                            </div>
                        </label>
                        <label>{{ __('Last name') }} <b>*</b>
                            <div class="kfs-field-wrap">
                                <input required name="runners[${idx + 1}][lastName]" id="input-lastName-${tNum}" autocomplete="family-name" placeholder="{{ __('Last name') }}">
                                <span class="kfs-validation-badge">✓</span>
                            </div>
                        </label>
                        <label>{{ __('Date of birth') }} <b>*</b>
                            <div class="kfs-field-wrap">
                                <input required type="date" name="runners[${idx + 1}][birthDate]" id="input-birthDate-${tNum}" max="{{ now()->subDay()->toDateString() }}">
                                <span class="kfs-validation-badge">✓</span>
                            </div>
                        </label>
                        <label>{{ __('Gender') }} <b>*</b>
                            <div class="kfs-field-wrap">
                                <span class="select-wrap">
                                    <select required name="runners[${idx + 1}][gender]" id="input-gender-${tNum}">
                                        <option value="">{{ __('Choose gender') }}</option>
                                        <option value="male">{{ __('Male') }}</option>
                                        <option value="female">{{ __('Female') }}</option>
                                    </select>
                                    <span>⌄</span>
                                </span>
                                <span class="kfs-validation-badge">✓</span>
                            </div>
                        </label>
                        <label class="full-span">{{ __('ID / Passport number') }} <b>*</b>
                            <div class="kfs-field-wrap">
                                <input required name="runners[${idx + 1}][idNumber]" id="input-idNumber-${tNum}" placeholder="{{ __('ID / Passport number') }}">
                                <span class="kfs-validation-badge">✓</span>
                            </div>
                        </label>
                    </div>
                    <input type="hidden" name="runners[${idx + 1}][race]" value="${t.race}">
                    <input type="hidden" name="runners[${idx + 1}][kit]" value="${t.kit}">
                    <input type="hidden" name="runners[${idx + 1}][shirtSize]" value="${t.shirtSize || ''}">
                `;
                extraAttendeesContainer.appendChild(section);
            });
        }

        // Attach listeners for dynamic selects
        document.querySelectorAll('.kfs-ticket-race-select').forEach(sel => {
            sel.addEventListener('change', e => {
                const tid = Number(sel.dataset.ticketId);
                const t = tickets.find(item => item.id === tid);
                if (t) {
                    t.race = sel.value;
                    renderTickets();
                    render();
                }
            });
        });

        document.querySelectorAll('.kfs-ticket-kit-select').forEach(sel => {
            sel.addEventListener('change', e => {
                const tid = Number(sel.dataset.ticketId);
                const t = tickets.find(item => item.id === tid);
                if (t) {
                    t.kit = sel.value;
                    renderTickets();
                    render();
                }
            });
        });

        document.querySelectorAll('.kfs-ticket-size-select').forEach(sel => {
            sel.addEventListener('change', e => {
                const tid = Number(sel.dataset.ticketId);
                const t = tickets.find(item => item.id === tid);
                if (t) {
                    t.shirtSize = sel.value;
                    render();
                }
            });
        });

        document.querySelectorAll('[data-remove-ticket]').forEach(btn => {
            btn.addEventListener('click', e => {
                const tid = Number(btn.dataset.removeTicket);
                tickets = tickets.filter(item => item.id !== tid);
                renderTickets();
                render();
                window.kfsToast?.('{{ __('Remove ticket') }} ✓', 'info');
            });
        });
    }

    function render() {
        const ticket1 = tickets[0];
        const race1 = allRaces.find(r => r.slug === ticket1.race) || allRaces[0];
        const kitName1 = ticket1.kit === 'full' ? '{{ __('Full Kit') }}' : '{{ __('Basic Kit') }}';

        // Synchronize hidden inputs for ticket 1
        raceInput.value = ticket1.race;
        kitInput.value = ticket1.kit;
        shirtInput.value = ticket1.shirtSize;

        // Synchronize card 1 selection classes
        document.querySelectorAll('#race-options .race-select-card').forEach(item => {
            item.classList.toggle('selected', item.dataset.race === ticket1.race);
        });
        document.querySelectorAll('#kit-options .kit-select-card').forEach(item => {
            item.classList.toggle('selected', item.dataset.kit === ticket1.kit);
        });
        document.querySelectorAll('#shirt-size-panel .shirt-size-choice').forEach(btn => {
            btn.classList.toggle('size-active', btn.dataset.size === ticket1.shirtSize);
        });

        const full1 = ticket1.kit === 'full';
        document.getElementById('shirt-size-panel').hidden = !full1;
        document.getElementById('order-shirt-row').hidden = !full1;
        setText('order-shirt', ticket1.shirtSize || 'Not selected');
        setText('attendee-race-label-1', `${race1.distance} · ${kitName1}`);
        setText('ticket-price-1', price(getTicketPrice(ticket1)));

        // Calculate combined order total
        let baseTotal = 0;
        tickets.forEach(t => baseTotal += getTicketPrice(t));

        let discountAmount = 0;
        let finalTotal = baseTotal;

        if (appliedPromo) {
            if (appliedPromo.discount_type === 'percentage') {
                discountAmount = Math.round((baseTotal * Number(appliedPromo.discount_value)) / 100 * 100) / 100;
            } else {
                discountAmount = Math.min(baseTotal, Number(appliedPromo.discount_value));
            }
            finalTotal = Math.max(0, baseTotal - discountAmount);

            orderDiscountRow.hidden = false;
            orderPromoLabel.textContent = appliedPromo.code;
            orderDiscount.textContent = '- ' + price(discountAmount);
        } else {
            orderDiscountRow.hidden = true;
        }

        const formattedFinal = finalTotal === 0 ? '{{ __('FREE') }}' : price(finalTotal);
        const totalText = finalTotal === 0 ? '{{ __('FREE') }} (0 EGP)' : price(finalTotal);

        setText('summary-distance', race1.distance);
        setText('summary-race', race1.name);
        setText('summary-kit', kitName1);
        setText('summary-kit-subtitle', full1 ? '{{ __('The complete race-day experience') }}' : '{{ __('The essentials for your race') }}');
        setText('summary-price', formattedFinal);
        setText('order-distance', race1.distance);
        setText('order-distance-row', race1.distance);
        setText('order-race', race1.name);
        setText('order-race-row', race1.name);
        setText('order-kit-row', kitName1);
        setText('order-price', price(baseTotal));
        setText('order-total', totalText);
        setText('order-tickets-count', `${tickets.length} ${tickets.length === 1 ? 'Ticket' : 'Tickets'}`);

        // Update mobile dock
        setText('dock-race-label', `${tickets.length} ${tickets.length === 1 ? 'Ticket' : 'Tickets'} · ${race1.distance}`);
        setText('dock-price-value', formattedFinal);

        document.querySelectorAll('[data-price-for="full"]').forEach(el => el.textContent = price(race1.full_price));
        document.querySelectorAll('[data-price-for="basic"]').forEach(el => el.textContent = price(race1.basic_price));

        // Submit button text
        setText('submit-btn-text', finalTotal === 0 ? '{{ __('Complete free registration') }}' : `{{ __('Proceed to pay for all runners') }} (${formattedFinal})`);

        // Render Review itemized list in Step 3
        const reviewList = document.getElementById('review-runners-list');
        if (reviewList) {
            reviewList.innerHTML = '';
            tickets.forEach((t, i) => {
                const r = allRaces.find(item => item.slug === t.race) || allRaces[0];
                const kName = t.kit === 'full' ? '{{ __('Full Kit') }}' : '{{ __('Basic Kit') }}';
                const row = document.createElement('div');
                row.className = 'kfs-order-item-row';
                row.innerHTML = `
                    <div>
                        <strong>{{ __('Runner') }} #${i + 1}:</strong> <span id="review-runner-name-${i + 1}">—</span>
                        <div style="font-size:10px; color:#688075; margin-top:2px;">${r.name} (${r.distance}) · ${kName} ${t.shirtSize ? `· Size ${t.shirtSize}` : ''}</div>
                    </div>
                    <b>${price(getTicketPrice(t))}</b>
                `;
                reviewList.appendChild(row);
            });
        }
    }

    // Add another ticket button click
    document.getElementById('add-runner-ticket-btn')?.addEventListener('click', () => {
        if (tickets.length >= 10) {
            window.kfsToast?.('Maximum 10 tickets per order', 'info');
            return;
        }
        const newId = Date.now();
        tickets.push({
            id: newId,
            race: allRaces[0]?.slug,
            kit: 'full',
            shirtSize: ''
        });
        renderTickets();
        render();
        window.kfsToast?.('{{ __('Add another runner') }} ✓', 'success');
    });

    // Stepper validations
    function validateStep1() {
        for (let i = 0; i < tickets.length; i++) {
            const t = tickets[i];
            if (!t.race) {
                window.kfsToast?.(`Please select a race for Runner #${i + 1}`, 'error');
                return false;
            }
            if (t.kit === 'full' && !t.shirtSize) {
                window.kfsToast?.(`Please select T-shirt size for Runner #${i + 1}`, 'error');
                if (i === 0) {
                    document.getElementById('shirt-size-panel')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
        }
        return true;
    }

    function validateStep2() {
        let firstInvalid = null;

        // Validate each runner's details (Runner #1 is primary buyer & contact)
        for (let i = 0; i < tickets.length; i++) {
            const pfx = i === 0 ? '' : `-${i + 1}`;
            const runnerFields = [
                { id: `input-firstName${pfx}`, valid: el => el && el.value.trim().length >= 2 },
                { id: `input-lastName${pfx}`, valid: el => el && el.value.trim().length >= 2 },
            ];

            // Runner #1 also has email and phone
            if (i === 0) {
                runnerFields.push(
                    { id: 'input-email', valid: el => el && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim()) },
                    { id: 'input-phone', valid: el => {
                        if (!el) return false;
                        const raw = el.value.replace(/\s+/g, '');
                        return /^(010|011|012|015)\d{8}$/.test(raw) || /^\+20(10|11|12|15)\d{8}$/.test(raw);
                    }}
                );
            }

            runnerFields.push(
                { id: `input-birthDate${pfx}`, valid: el => {
                    if (!el || !el.value) return false;
                    const d = new Date(el.value);
                    return !isNaN(d.getTime()) && d < new Date();
                }},
                { id: `input-gender${pfx}`, valid: el => el && !!el.value },
                { id: `input-idNumber${pfx}`, valid: el => el && el.value.trim().length >= 4 }
            );

            for (const item of runnerFields) {
                const el = document.getElementById(item.id);
                if (!el) continue;
                const ok = item.valid(el);
                window.kfsSetFieldState?.(el, ok, true);
                if (!ok && !firstInvalid) firstInvalid = el;
            }
        }

        if (firstInvalid) {
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.kfsToast?.('{{ __('Please check required fields') }}', 'error');
            return false;
        }

        // Update Review preview from Runner #1
        setText('review-buyer-name', `${document.getElementById('input-firstName')?.value || ''} ${document.getElementById('input-lastName')?.value || ''}`);
        setText('review-buyer-email', document.getElementById('input-email')?.value || '');
        setText('review-buyer-phone', document.getElementById('input-phone')?.value || '');

        tickets.forEach((t, i) => {
            const pfx = i === 0 ? '' : `-${i + 1}`;
            const fn = document.getElementById(`input-firstName${pfx}`)?.value || '';
            const ln = document.getElementById(`input-lastName${pfx}`)?.value || '';
            setText(`review-runner-name-${i + 1}`, `${fn} ${ln}`);
        });

        return true;
    }

    function setStep(step) {
        if (step > currentStep) {
            if (currentStep === 1 && !validateStep1()) return;
            if (currentStep === 2 && !validateStep2()) return;
        }

        currentStep = step;

        const progressEl = document.getElementById('stepper-progress-bar');
        if (progressEl) {
            progressEl.style.width = step === 1 ? '33.33%' : (step === 2 ? '66.66%' : '100%');
        }

        for (let i = 1; i <= 3; i++) {
            const btn = document.getElementById(`step-btn-${i}`);
            if (!btn) continue;
            btn.classList.toggle('active', i === step);
            btn.classList.toggle('completed', i < step);
            const badge = btn.querySelector('.kfs-step-badge');
            if (badge) badge.textContent = i < step ? '✓' : String(i);
        }

        document.querySelectorAll('.kfs-step-view').forEach(view => {
            view.classList.toggle('active', Number(view.dataset.stepView) === step);
        });

        const dockText = document.getElementById('dock-action-text');
        if (dockText) {
            if (step === 1) dockText.textContent = '{{ __('Next: Runner details') }}';
            else if (step === 2) dockText.textContent = '{{ __('Next: Review & pay') }}';
            else dockText.textContent = '{{ __('Proceed to pay for all runners') }}';
        }

        const formEl = document.getElementById('runner-form');
        if (formEl) formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Step navigations
    document.querySelectorAll('.kfs-step-item').forEach(btn => {
        btn.addEventListener('click', () => setStep(Number(btn.dataset.stepTarget)));
    });

    document.getElementById('step-1-next-btn')?.addEventListener('click', () => setStep(2));
    document.getElementById('step-2-back-btn')?.addEventListener('click', () => setStep(1));
    document.getElementById('step-2-next-btn')?.addEventListener('click', () => setStep(3));
    document.getElementById('step-3-back-btn')?.addEventListener('click', () => setStep(2));
    document.getElementById('edit-buyer-btn')?.addEventListener('click', () => setStep(2));

    document.getElementById('dock-action-btn')?.addEventListener('click', () => {
        if (currentStep === 1) setStep(2);
        else if (currentStep === 2) setStep(3);
        else document.getElementById('runner-form')?.requestSubmit();
    });

    // Card 1 selectors
    document.querySelectorAll('#race-options .race-select-card').forEach(item => {
        item.addEventListener('click', () => {
            tickets[0].race = item.dataset.race;
            render();
            if (appliedPromo) validatePromo(appliedPromo.code);
        });
    });

    document.querySelectorAll('#kit-options .kit-select-card').forEach(item => {
        item.addEventListener('click', () => {
            tickets[0].kit = item.dataset.kit;
            render();
            if (appliedPromo) validatePromo(appliedPromo.code);
        });
    });

    document.querySelectorAll('#shirt-size-panel .shirt-size-choice').forEach(btn => {
        btn.addEventListener('click', () => {
            tickets[0].shirtSize = btn.dataset.size;
            shirtInput.value = btn.dataset.size;
            document.querySelectorAll('#shirt-size-panel .shirt-size-choice').forEach(item => item.classList.toggle('size-active', item === btn));
            document.getElementById('shirt-size-hint').hidden = true;
            render();
        });
    });

    // Promo code validation
    async function validatePromo(code) {
        if (!code || !code.trim()) return;
        let baseTotal = 0;
        tickets.forEach(t => baseTotal += getTicketPrice(t));

        applyPromoBtn.disabled = true;
        applyPromoBtn.textContent = 'Checking…';
        promoFeedback.hidden = false;
        promoFeedback.style.color = '#0284c7';
        promoFeedback.textContent = 'Validating code…';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('{{ route('promo-codes.validate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({
                    code: code.trim(),
                    total: baseTotal,
                    race: tickets[0].race,
                    kit: tickets[0].kit
                })
            });

            const data = await res.json();
            if (!res.ok || !data.valid) {
                throw new Error(data.message || 'Invalid promo code');
            }

            appliedPromo = data;
            appliedPromoHidden.value = data.code;
            promoInput.value = data.code;
            promoInput.readOnly = true;
            applyPromoBtn.hidden = true;
            removePromoBtn.hidden = false;
            promoFeedback.style.color = '#047857';
            promoFeedback.textContent = '✓ ' + data.message;
            window.kfsToast?.(data.message, 'success');
            render();
        } catch (err) {
            appliedPromo = null;
            appliedPromoHidden.value = '';
            promoInput.readOnly = false;
            applyPromoBtn.hidden = false;
            applyPromoBtn.disabled = false;
            applyPromoBtn.textContent = 'Apply';
            removePromoBtn.hidden = true;
            promoFeedback.style.color = '#dc2626';
            promoFeedback.textContent = '✕ ' + err.message;
            window.kfsToast?.(err.message, 'error');
            render();
        } finally {
            applyPromoBtn.disabled = false;
            applyPromoBtn.textContent = 'Apply';
        }
    }

    function removePromo() {
        appliedPromo = null;
        appliedPromoHidden.value = '';
        promoInput.value = '';
        promoInput.readOnly = false;
        promoFeedback.hidden = true;
        promoFeedback.textContent = '';
        applyPromoBtn.hidden = false;
        applyPromoBtn.disabled = false;
        applyPromoBtn.textContent = 'Apply';
        removePromoBtn.hidden = true;
        window.kfsToast?.('Promo code removed', 'info');
        render();
    }

    applyPromoBtn?.addEventListener('click', () => validatePromo(promoInput.value));
    promoInput?.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            validatePromo(promoInput.value);
        }
    });
    removePromoBtn?.addEventListener('click', removePromo);

    // Team select handling
    const teamSelect = document.getElementById('team-name-select');
    const otherTeamWrap = document.getElementById('other-team-wrap');
    const otherTeamInput = document.getElementById('other-team-input');

    teamSelect?.addEventListener('change', () => {
        if (teamSelect.value === 'Other') {
            if (otherTeamWrap) otherTeamWrap.style.display = 'block';
            otherTeamInput?.focus();
        } else {
            if (otherTeamWrap) otherTeamWrap.style.display = 'none';
        }
    });

    if (teamSelect?.value === 'Other' && otherTeamWrap) {
        otherTeamWrap.style.display = 'block';
    }

    // Form submit check
    document.getElementById('runner-form')?.addEventListener('submit', event => {
        if (!validateStep1()) {
            event.preventDefault();
            setStep(1);
            return;
        }
        if (!validateStep2()) {
            event.preventDefault();
            setStep(2);
            return;
        }

        // Custom team override if Other selected
        if (teamSelect?.value === 'Other' && otherTeamInput?.value.trim()) {
            let customTeamHidden = document.getElementById('hidden-custom-team-input');
            if (!customTeamHidden) {
                customTeamHidden = document.createElement('input');
                customTeamHidden.type = 'hidden';
                customTeamHidden.id = 'hidden-custom-team-input';
                customTeamHidden.name = 'teamName';
                document.getElementById('runner-form').appendChild(customTeamHidden);
            }
            customTeamHidden.value = otherTeamInput.value.trim();
            teamSelect.disabled = true;
        }

        // If multiple tickets, ensure runner[0] & buyer inputs are populated
        if (tickets.length > 1) {
            let hiddenContainer = document.getElementById('hidden-runner-0-inputs');
            if (!hiddenContainer) {
                hiddenContainer = document.createElement('div');
                hiddenContainer.id = 'hidden-runner-0-inputs';
                document.getElementById('runner-form').appendChild(hiddenContainer);
            }
            hiddenContainer.innerHTML = `
                <input type="hidden" name="runners[0][firstName]" value="${document.getElementById('input-firstName')?.value || ''}">
                <input type="hidden" name="runners[0][lastName]" value="${document.getElementById('input-lastName')?.value || ''}">
                <input type="hidden" name="runners[0][email]" value="${document.getElementById('input-email')?.value || ''}">
                <input type="hidden" name="runners[0][phone]" value="${document.getElementById('input-phone')?.value || ''}">
                <input type="hidden" name="runners[0][birthDate]" value="${document.getElementById('input-birthDate')?.value || ''}">
                <input type="hidden" name="runners[0][gender]" value="${document.getElementById('input-gender')?.value || ''}">
                <input type="hidden" name="runners[0][idNumber]" value="${document.getElementById('input-idNumber')?.value || ''}">
                <input type="hidden" name="runners[0][race]" value="${tickets[0].race}">
                <input type="hidden" name="runners[0][kit]" value="${tickets[0].kit}">
                <input type="hidden" name="runners[0][shirtSize]" value="${tickets[0].shirtSize || ''}">
                <input type="hidden" name="buyerFirstName" value="${document.getElementById('input-firstName')?.value || ''}">
                <input type="hidden" name="buyerLastName" value="${document.getElementById('input-lastName')?.value || ''}">
                <input type="hidden" name="buyerEmail" value="${document.getElementById('input-email')?.value || ''}">
                <input type="hidden" name="buyerPhone" value="${document.getElementById('input-phone')?.value || ''}">
            `;
        }
    });

    renderTickets();
    render();
    setStep(currentStep);

    // Check return reference
    const params = new URLSearchParams(window.location.search);
    const reference = params.get('customerReference') || params.get('reference') || @json($returnReference ?? session('payment_reference'));
    if (reference) {
        const form = document.getElementById('runner-form');
        form.hidden = true;
        const panel = document.getElementById('cash-voucher-panel');
        const banner = document.getElementById('payment-return-message');
        panel.hidden = false; banner.hidden = false;
        banner.textContent = 'You are back from EasyKash. We are checking payment confirmation securely.';
        setText('payment-reference', reference);

        const check = async () => {
            try {
                const response = await fetch(`/api/registrations/${encodeURIComponent(reference)}/payment-status`, { headers: { Accept: 'application/json' } });
                const body = await response.json();
                if (!response.ok) throw new Error(body.message || 'Unable to check payment.');
                const payment = body.data;

                if (payment.status === 'paid') {
                    panel.classList.remove('cash-voucher-pending'); panel.classList.add('cash-voucher-paid');
                    setText('payment-title', 'Payment confirmed');
                    setText('payment-description', payment.team ? `EasyKash confirmed payment for ${payment.team.name} (${payment.team.member_count} runners). Your group registration is complete.` : 'EasyKash confirmed your payment. Your registration is complete.');
                    setText('payment-status', 'Paid');
                } else if (['failed', 'refunded'].includes(payment.status)) {
                    panel.classList.remove('cash-voucher-pending'); panel.classList.add('cash-voucher-failed');
                    setText('payment-title', 'Payment not completed');
                    setText('payment-description', 'Contact the race team with your reference if you need help.');
                    setText('payment-status', 'Not completed');
                } else {
                    setText('payment-title', payment.voucher ? 'Cash voucher ready' : 'Checking payment status');
                    setText('payment-description', payment.voucher ? 'Use this voucher at the payment provider selected in EasyKash.' : 'Your registration is saved. We are waiting for EasyKash’s signed server confirmation. Keep your reference.');
                    setText('payment-status', payment.voucher ? 'Waiting for payment' : 'Confirming');
                    if (payment.voucher) {
                        document.getElementById('voucher-box').hidden = false;
                        setText('voucher-value', payment.voucher);
                    }
                }
            } catch (error) {
                setText('payment-title', 'Payment status is being checked');
                setText('payment-description', 'We could not load the latest status. Keep this reference and refresh this page shortly.');
            }
        };

        document.getElementById('copy-voucher')?.addEventListener('click', function() {
            window.kfsCopy(document.getElementById('voucher-value').textContent, this);
        });
        check(); window.setInterval(check, 10000);
    }
})();
</script>
@endpush
