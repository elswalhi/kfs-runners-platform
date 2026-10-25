@extends('site.layout')

@section('title', 'Register for KFS Runners')

@section('content')
<main class="register-page">
    <section class="register-hero"><div class="register-hero-bg"></div><div class="wrap register-hero-inner">
        <div class="register-intro"><a href="{{ route('home') }}" class="back-link">← Back to home</a><span class="eyebrow-pill"><span class="pulse-dot"></span> KFS RUNNERS MARATHON</span><h1>YOUR RACE.<br><span>YOUR STORY.</span></h1><p>Choose your distance, pick your kit, and take the first step toward your next finish line.</p><div class="register-points"><span>♢ Secure registration</span><span>♙ Solo or team entry</span></div></div>
        <div class="register-summary-top"><span>YOUR SELECTED RACE</span><b id="summary-distance">{{ $races->firstWhere('slug', old('race', request('race'))) ? $races->firstWhere('slug', old('race', request('race')))->distance_label : ($races->first()?->distance_label ?? '—') }}</b><small id="summary-race">{{ $races->firstWhere('slug', old('race', request('race'))) ? $races->firstWhere('slug', old('race', request('race')))->name : ($races->first()?->name ?? 'Race category') }}</small><div class="summary-rule"></div><span>PACKAGE</span><b id="summary-kit">Full Kit</b><small id="summary-kit-subtitle">The complete race-day experience</small><div class="summary-price"><small>Ticket price preview</small><strong id="summary-price">—</strong></div></div>
    </div></section>

    <section class="registration-workspace"><div class="wrap registration-layout">
        <div class="registration-form-col">
            <div class="form-title-row"><div><span class="form-step">STEP 01 / 02</span><h2>RUNNER <span>DETAILS</span></h2><p>Tell us a little about the runner.</p></div><div class="secure-label">♙ Your information stays protected</div></div>

            @if ($errors->any()) <div class="form-alert alert-error" role="alert">{{ $errors->first() }}</div> @endif
            @if (session('payment_error')) <div class="form-alert alert-error" role="alert">{{ session('payment_error') }}@if(session('payment_reference')) Registration reference: <strong>{{ session('payment_reference') }}</strong>.@endif</div> @endif
            <div id="payment-return-message" class="form-alert alert-info" role="status" hidden></div>
            <div id="cash-voucher-panel" class="cash-voucher cash-voucher-pending" aria-live="polite" hidden>
                <span class="cash-voucher-kicker">EASYKASH PAYMENT</span><h3 id="payment-title">Checking payment status</h3><p id="payment-description">We are checking the signed EasyKash confirmation. Keep your registration reference.</p>
                <div id="voucher-box" class="cash-voucher-code" hidden><strong id="voucher-value"></strong><button type="button" id="copy-voucher">Copy</button></div>
                <div class="cash-voucher-meta"><span>Registration reference</span><b id="payment-reference"></b><span>Payment status</span><b id="payment-status">Confirming</b></div>
                <small class="cash-voucher-note">Payment is confirmed only by EasyKash’s signed server callback. Do not submit another registration while this payment is being checked.</small>
            </div>

            <form class="runner-form" method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" id="runner-form">
                @csrf
                <div class="form-section-heading"><span>♙</span><span>Personal information</span></div>
                <div class="form-grid two-col">
                    <label>First name <b>*</b><input required name="firstName" autocomplete="given-name" value="{{ old('firstName') }}" placeholder="Enter first name"></label>
                    <label>Last name <b>*</b><input required name="lastName" autocomplete="family-name" value="{{ old('lastName') }}" placeholder="Enter last name"></label>
                    <label>Email address <b>*</b><input required type="email" name="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com"></label>
                    <label>Phone number <b>*</b><input required type="tel" name="phone" autocomplete="tel" value="{{ old('phone') }}" placeholder="010 1234 5678 or +20 10 1234 5678"><small class="phone-input-hint">Use an Egyptian mobile number. Local and +20 formats are accepted.</small></label>
                    <label>Date of birth <b>*</b><input required type="date" name="birthDate" max="{{ now()->subDay()->toDateString() }}" value="{{ old('birthDate') }}"></label>
                    <label>Gender <b>*</b><span class="select-wrap"><select required name="gender"><option value="">Choose gender</option><option value="male" @selected(old('gender') === 'male')>Male</option><option value="female" @selected(old('gender') === 'female')>Female</option><option value="prefer_not_to_say" @selected(old('gender') === 'prefer_not_to_say')>Prefer not to say</option></select><span>⌄</span></span></label>
                    <label class="full-span">ID / Passport number <b>*</b><input required name="idNumber" value="{{ old('idNumber') }}" placeholder="Enter ID or passport number"></label>
                </div>

                <div class="form-section-heading section-heading-spaced"><span>◇</span><span>Choose your race</span></div>
                <input type="hidden" name="race" id="race-value" value="{{ old('race', request('race', $races->first()?->slug)) }}">
                <div class="race-select-grid" id="race-options">
                    @foreach ($races as $race)
                        @php($full = $race->kitPrices->firstWhere('kit_type', 'full'))
                        @php($basic = $race->kitPrices->firstWhere('kit_type', 'basic'))
                        <button type="button" class="race-select-card" data-race="{{ $race->slug }}" data-name="{{ $race->name }}" data-distance="{{ $race->distance_label }}" data-full="{{ $full?->price ?? '' }}" data-basic="{{ $basic?->price ?? '' }}">
                            <span class="race-select-check"><span class="radio-empty"></span></span><span class="select-distance">{{ $race->distance_label }}</span><strong>{{ $race->name }}</strong><small>{{ $race->category_type === 'family' ? 'Bring your family and run together.' : ($race->category_type === 'para' ? 'An inclusive race category.' : 'A challenge for every pace.') }}</small>
                        </button>
                    @endforeach
                </div>
                @if ($races->isEmpty()) <div class="form-alert alert-error">Registration is temporarily unavailable because no active race categories are configured.</div> @endif

                <div class="form-section-heading section-heading-spaced"><span>♧</span><span>Select your kit</span></div>
                <input type="hidden" name="kit" id="kit-value" value="{{ old('kit', 'full') }}">
                <div class="kit-select-grid" id="kit-options">
                    <button type="button" class="kit-select-card" data-kit="full"><span class="kit-check"><span class="radio-empty"></span></span><span class="kit-chip full">MOST COMPLETE</span><strong>Full Kit</strong><small>The complete race-day experience</small><ul><li>✓ Finisher medal</li><li>✓ Official KFS Runners T-shirt</li><li>✓ Runner bag</li><li>✓ Race support and photos</li></ul><span class="kit-price" data-price-for="full">—</span></button>
                    <button type="button" class="kit-select-card" data-kit="basic"><span class="kit-check"><span class="radio-empty"></span></span><span class="kit-chip basic">RACE ESSENTIALS</span><strong>Basic Kit</strong><small>The essentials for your race</small><ul><li>✓ Finisher medal</li><li>✓ Participation certificate</li><li>✓ Race support and photos</li></ul><span class="kit-price" data-price-for="basic">—</span></button>
                </div>

                <div class="shirt-size-panel" id="shirt-size-panel"><div class="shirt-size-copy"><span>♧</span><div><b>T-shirt size <span>*</span></b><small>Full Kit includes the official race T-shirt. Select your size.</small></div></div><div class="shirt-size-options">
                    @foreach (['S','M','L','XL','XXL'] as $size)<button type="button" data-size="{{ $size }}" class="shirt-size-choice">{{ $size }}</button>@endforeach
                </div><input type="hidden" name="shirtSize" id="shirt-size" value="{{ old('shirtSize') }}"><small class="field-hint" id="shirt-size-hint">Please select a size to continue.</small></div>

                <div class="form-section-heading section-heading-spaced"><span>⇧</span><span>Runner document</span><small>Optional</small></div>
                <label class="upload-drop"><input type="file" name="idFile" accept="image/jpeg,image/png,application/pdf"><span class="upload-icon">⇧</span><span><b id="file-name">Upload ID / passport (optional)</b><small>JPG, PNG or PDF · Max 2 MB</small></span></label>
                <label class="team-name-label">Team / running club <small>(optional)</small><input name="teamName" value="{{ old('teamName') }}" placeholder="e.g. Kafr El-Sheikh Runners"></label>

                <!-- Promo Code Box -->
                <div class="form-section-heading section-heading-spaced"><span>%</span><span>Promo code or voucher</span><small>Optional</small></div>
                <div class="promo-code-box" style="background:#f8faf9; border:1px solid #dce5df; border-radius:8px; padding:16px; margin-top:14px;">
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <input type="text" id="promo-code-input" placeholder="e.g. KFS50" maxlength="64" value="{{ old('promoCode') }}" style="flex:1; min-width:160px; height:42px; border:1px solid #c9d8d0; border-radius:6px; padding:0 12px; font-family:monospace; font-weight:700; text-transform:uppercase; font-size:13px; color:#064b3e; background:#fff;">
                        <button type="button" id="apply-promo-btn" style="height:42px; padding:0 18px; background:#064b3e; color:#ffffff; border:none; border-radius:6px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; transition:all 0.2s;">
                            Apply
                        </button>
                        <button type="button" id="remove-promo-btn" style="height:42px; padding:0 14px; background:#fff1f2; color:#be123c; border:1px solid #fecdd3; border-radius:6px; font-size:11px; font-weight:800; cursor:pointer;" hidden>
                            Remove
                        </button>
                    </div>
                    <div id="promo-feedback" style="margin-top:10px; font-size:11px; font-weight:600; line-height:1.4;" hidden></div>
                    <input type="hidden" name="promoCode" id="applied-promo-code" value="{{ old('promoCode') }}">
                </div>

                <label class="terms-check"><input type="checkbox" name="terms" value="1" required><span>I confirm that the details provided are correct and agree to the race terms and conditions. <b>*</b></span></label>
                <button class="btn btn-red form-submit" type="submit" id="submit-registration" @disabled($races->isEmpty())>Continue to payment <span>→</span></button>
                <p class="form-fineprint">♙ Payment is confirmed by EasyKash’s signed server callback.</p>
            </form>
        </div>

        <aside class="registration-order-col">
            <div class="order-card">
                <div class="order-card-heading">
                    <div>
                        <span class="form-step">YOUR ENTRY</span>
                        <h3>Registration <span>summary</span></h3>
                    </div>
                    <span class="order-lock">♙</span>
                </div>
                <div class="order-race-image">
                    <div class="order-race-photo"></div>
                    <div class="order-race-overlay">
                        <span>KFS RUNNERS</span>
                        <b id="order-distance">{{ $races->first()?->distance_label ?? '—' }}</b>
                        <small id="order-race">{{ $races->first()?->name ?? 'Race category' }}</small>
                    </div>
                </div>
                <div class="order-detail-row"><span>Race distance</span><b id="order-distance-row">{{ $races->first()?->distance_label ?? '—' }}</b></div>
                <div class="order-detail-row"><span>Race category</span><b id="order-race-row">{{ $races->first()?->name ?? '—' }}</b></div>
                <div class="order-detail-row"><span>Race kit</span><b id="order-kit-row">Full Kit</b></div>
                <div class="order-detail-row" id="order-shirt-row"><span>T-shirt size</span><b id="order-shirt">Not selected</b></div>
                <div class="order-divider"></div>
                <div class="order-price-line"><span>Entry fee</span><strong id="order-price">—</strong></div>
                <div class="order-price-line" id="order-discount-row" style="color:#047857;" hidden>
                    <span>Promo discount (<b id="order-promo-label"></b>)</span>
                    <strong id="order-discount" style="color:#047857;">—</strong>
                </div>
                <div class="order-price-total"><span>Total preview</span><strong id="order-total">—</strong></div>
                <p class="order-tax-note">Final price, availability, and payment status are confirmed by Laravel.</p>
                <div class="order-benefits"><span>♢ Secure registration</span><span>▭ EasyKash checkout</span></div>
            </div>
            <div class="need-help-card">
                <h4>Need help choosing?</h4>
                <p>Choose the distance that feels right for you. The best race is the one you show up for.</p>
                <a href="{{ route('home') }}#race-categories" class="text-link">Explore the event <span>→</span></a>
            </div>
        </aside>
    </div></section>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const races = [...document.querySelectorAll('.race-select-card')];
    const kits = [...document.querySelectorAll('.kit-select-card')];
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

    const price = value => new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(Number(value || 0));
    let selectedRace = races.find(item => item.dataset.race === raceInput.value) || races[0];
    let selectedKit = kits.find(item => item.dataset.kit === kitInput.value) || kits[0];
    const setText = (id, value) => { const node = document.getElementById(id); if (node) node.textContent = value; };

    function render() {
        if (!selectedRace || !selectedKit) return;
        races.forEach(item => item.classList.toggle('selected', item === selectedRace));
        kits.forEach(item => item.classList.toggle('selected', item === selectedKit));
        raceInput.value = selectedRace.dataset.race;
        kitInput.value = selectedKit.dataset.kit;
        const kit = selectedKit.dataset.kit;
        const baseAmount = Number(selectedRace.dataset[kit] || 0);
        const kitName = kit === 'full' ? 'Full Kit' : 'Basic Kit';

        let discountAmount = 0;
        let finalAmount = baseAmount;

        if (appliedPromo) {
            if (appliedPromo.discount_type === 'percentage') {
                discountAmount = Math.round((baseAmount * Number(appliedPromo.discount_value)) / 100 * 100) / 100;
            } else {
                discountAmount = Math.min(baseAmount, Number(appliedPromo.discount_value));
            }
            finalAmount = Math.max(0, baseAmount - discountAmount);

            orderDiscountRow.hidden = false;
            orderPromoLabel.textContent = appliedPromo.code;
            orderDiscount.textContent = '- ' + price(discountAmount);
        } else {
            orderDiscountRow.hidden = true;
        }

        setText('summary-distance', selectedRace.dataset.distance);
        setText('summary-race', selectedRace.dataset.name);
        setText('summary-kit', kitName);
        setText('summary-kit-subtitle', kit === 'full' ? 'The complete race-day experience' : 'The essentials for your race');
        setText('summary-price', finalAmount === 0 ? 'FREE' : price(finalAmount));
        setText('order-distance', selectedRace.dataset.distance);
        setText('order-distance-row', selectedRace.dataset.distance);
        setText('order-race', selectedRace.dataset.name);
        setText('order-race-row', selectedRace.dataset.name);
        setText('order-kit-row', kitName);
        setText('order-price', price(baseAmount));
        setText('order-total', finalAmount === 0 ? 'FREE (0 EGP)' : price(finalAmount));
        document.querySelectorAll('[data-price-for="full"]').forEach(el => el.textContent = price(selectedRace.dataset.full));
        document.querySelectorAll('[data-price-for="basic"]').forEach(el => el.textContent = price(selectedRace.dataset.basic));
        const full = kit === 'full';
        document.getElementById('shirt-size-panel').hidden = !full;
        document.getElementById('order-shirt-row').hidden = !full;
        setText('order-shirt', shirtInput.value || 'Not selected');
    }

    async function validatePromo(code) {
        if (!code || !code.trim()) return;
        const raceSlug = selectedRace ? selectedRace.dataset.race : raceInput.value;
        const kitType = selectedKit ? selectedKit.dataset.kit : kitInput.value;

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
                    race: raceSlug,
                    kit: kitType
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

    races.forEach(item => item.addEventListener('click', () => {
        selectedRace = item;
        render();
        if (appliedPromo) validatePromo(appliedPromo.code);
    }));
    kits.forEach(item => item.addEventListener('click', () => {
        selectedKit = item;
        render();
        if (appliedPromo) validatePromo(appliedPromo.code);
    }));

    document.querySelectorAll('.shirt-size-choice').forEach(button => button.addEventListener('click', () => {
        shirtInput.value = button.dataset.size;
        document.querySelectorAll('.shirt-size-choice').forEach(item => item.classList.toggle('size-active', item === button));
        document.getElementById('shirt-size-hint').hidden = true;
        setText('order-shirt', button.dataset.size);
    }));

    document.querySelector('.upload-drop input')?.addEventListener('change', event => setText('file-name', event.target.files?.[0]?.name || 'Upload ID / passport (optional)'));

    document.getElementById('runner-form')?.addEventListener('submit', event => {
        if (kitInput.value === 'full' && !shirtInput.value) {
            event.preventDefault();
            document.getElementById('shirt-size-hint').hidden = false;
            document.getElementById('shirt-size-panel').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    render();

    // Auto-validate old promo code if submitted with validation error
    if (appliedPromoHidden.value) {
        validatePromo(appliedPromoHidden.value);
    }

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
                    setText('payment-title', 'Payment confirmed'); setText('payment-description', 'EasyKash confirmed your payment. Your registration is complete.'); setText('payment-status', 'Paid');
                } else if (['failed', 'refunded'].includes(payment.status)) {
                    panel.classList.remove('cash-voucher-pending'); panel.classList.add('cash-voucher-failed');
                    setText('payment-title', 'Payment not completed'); setText('payment-description', 'Contact the race team with your reference if you need help.'); setText('payment-status', 'Not completed');
                } else {
                    setText('payment-title', payment.voucher ? 'Cash voucher ready' : 'Checking payment status');
                    setText('payment-description', payment.voucher ? 'Use this voucher at the payment provider selected in EasyKash.' : 'Your registration is saved. We are waiting for EasyKash’s signed server confirmation. Keep your reference.');
                    setText('payment-status', payment.voucher ? 'Waiting for payment' : 'Confirming');
                    if (payment.voucher) { document.getElementById('voucher-box').hidden = false; setText('voucher-value', payment.voucher); }
                }
            } catch (error) {
                setText('payment-title', 'Payment status is being checked');
                setText('payment-description', 'We could not load the latest status. Keep this reference and refresh this page shortly.');
            }
        };
        document.getElementById('copy-voucher').addEventListener('click', async () => { await navigator.clipboard.writeText(document.getElementById('voucher-value').textContent); document.getElementById('copy-voucher').textContent = 'Copied'; });
        check(); window.setInterval(check, 10000);
    }
})();
</script>
@endpush
