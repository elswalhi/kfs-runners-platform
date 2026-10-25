@extends('site.layout')

@section('title', 'KFS Runners · Your race. Your story.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-home.css') }}">
@endpush

@section('content')
<main class="home-redesign">
    <section class="home-hero" id="the-race" aria-labelledby="home-title">
        <div class="home-hero-photo" aria-hidden="true"></div>
        <div class="wrap home-hero-layout">
            <div class="home-hero-copy">
                <p class="home-eyebrow"><span></span> KFS RUNNERS · KAFR EL-SHEIKH</p>
                <h1 id="home-title">YOUR RACE.<br><span>YOUR STORY.</span></h1>
                <p class="home-lede">Choose your distance, pick your kit, and take the first step toward your next finish line.</p>
                <a href="{{ route('register') }}" class="home-primary-button">Register for the race <span aria-hidden="true">→</span></a>
                <div class="home-assurances">
                    <span><i aria-hidden="true">✓</i> Secure registration</span>
                    <span><i aria-hidden="true">↗</i> Solo or team entry</span>
                </div>
            </div>

            <aside class="home-race-card" aria-label="Race overview">
                @php($featuredPrice = $featuredRace?->kitPrices->firstWhere('kit_type', 'full') ?? $featuredRace?->kitPrices->sortBy('price')->first())
                @if ($featuredRace)
                    <p class="home-card-kicker">FEATURED DISTANCE</p>
                    <h2 id="home-featured-distance">{{ $featuredRace->distance_label }}</h2>
                    <p class="home-featured-name" id="home-featured-name">{{ $featuredRace->name }}</p>
                @else
                    <p class="home-card-kicker">KFS RUNNERS</p>
                    <h2 id="home-featured-distance">Race day</h2>
                    <p class="home-featured-name" id="home-featured-name">Race registration is being prepared.</p>
                @endif

                <div class="home-card-divider"></div>
                <div class="home-distance-slider-heading">
                    <p class="home-card-kicker">DISTANCE OPTIONS</p>
                    <div class="home-slider-controls" aria-label="Slide through race distances">
                        <button type="button" data-distance-slide="-1" aria-label="Previous distances">←</button>
                        <button type="button" data-distance-slide="1" aria-label="Next distances">→</button>
                    </div>
                </div>
                <div class="home-distance-options" id="home-distance-slider" role="group" aria-label="Choose a race distance">
                    @forelse ($races as $race)
                        @php($racePrice = $race->kitPrices->firstWhere('kit_type', 'full') ?? $race->kitPrices->sortBy('price')->first())
                        <a href="{{ route('register', ['race' => $race->slug]) }}" data-distance-option data-distance="{{ $race->distance_label }}" data-race-name="{{ $race->name }}" data-price="{{ $racePrice ? number_format((float) $racePrice->price, 0, '.', '') : '' }}" data-currency="{{ $racePrice?->currency ?? 'EGP' }}" data-kit="{{ $racePrice?->kit_type === 'basic' ? 'Basic Kit' : 'Full Kit' }}" aria-label="Register for {{ $race->name }}">{{ $race->distance_label }}</a>
                    @empty
                        <span>Coming soon</span>
                    @endforelse
                </div>

                <div class="home-card-divider"></div>
                <div class="home-feature-kit">
                    <div>
                        <p class="home-card-kicker">RACE PACKAGE</p>
                        <strong>Full Kit</strong>
                        <span>The complete race-day experience</span>
                    </div>
                    <span class="home-kit-mark" aria-hidden="true">✳</span>
                </div>
                <div class="home-entry-price">
                    <span id="home-entry-label">{{ ($featuredPrice?->kit_type ?? 'full') === 'basic' ? 'Basic Kit entry' : 'Full Kit entry' }}</span>
                    @if ($featuredPrice)
                        <strong id="home-entry-value">{{ $featuredPrice->currency }} {{ number_format((float) $featuredPrice->price, 0) }}</strong>
                    @else
                        <strong id="home-entry-value">See race options</strong>
                    @endif
                </div>
            </aside>
        </div>
        <a class="home-scroll-link" href="#race-categories"><span aria-hidden="true">↓</span> Find your distance</a>
    </section>

    <section class="home-distances" id="race-categories" aria-labelledby="distances-title">
        <div class="wrap">
            <div class="home-section-heading">
                <div>
                    <p class="home-section-kicker"><span></span> KFS RUNNERS · KAFR EL-SHEIKH</p>
                    <h2 id="distances-title">FIND YOUR <span>DISTANCE.</span></h2>
                </div>
                <p>Whether you are taking your first steps or chasing a new personal best, there is a race for you. Same great atmosphere. A stronger you.</p>
            </div>

            <div class="home-distance-grid">
                @forelse ($races as $index => $race)
                    @php($raceStartingPrice = $race->kitPrices->sortBy('price')->first())
                    <a href="{{ route('register', ['race' => $race->slug]) }}" class="home-distance-card" style="--card-position: {{ [22, 50, 78][$index % 3] }}%;">
                        <div class="home-distance-photo" aria-hidden="true">
                            <span>{{ sprintf('%02d', $index + 1) }}</span>
                            <span class="home-photo-arrow" aria-hidden="true">↗</span>
                        </div>
                        <div class="home-distance-copy">
                            <p class="home-card-kicker">{{ $race->distance_label }}</p>
                            <h3>{{ $race->name }}</h3>
                            <p class="home-distance-description">
                                @if ($race->category_type === 'family')
                                    Bring your family and enjoy the race together.
                                @elseif ($race->category_type === 'para')
                                    An inclusive race experience made for every runner.
                                @else
                                    A challenge for every pace and every goal.
                                @endif
                            </p>
                            <div class="home-distance-card-footer">
                                <span>
                                    @if ($raceStartingPrice)
                                        From {{ $raceStartingPrice->currency }} {{ number_format((float) $raceStartingPrice->price, 0) }}
                                    @else
                                        View race details
                                    @endif
                                </span>
                                <b>Choose race <span aria-hidden="true">→</span></b>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="home-empty-races">Race categories are being prepared. Please check back soon.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="home-kits" aria-labelledby="kits-title">
        <div class="wrap home-kits-layout">
            <div class="home-kits-intro">
                <p class="home-section-kicker"><span></span> RACE DAY, YOUR WAY</p>
                <h2 id="kits-title">PICK YOUR<br><span>RACE KIT.</span></h2>
                <p>Bring your energy. Choose the race package that feels right for you.</p>
                <a href="{{ route('register') }}" class="home-text-link">Explore registration <span aria-hidden="true">→</span></a>
            </div>
            <div class="home-kit-grid">
                <a href="{{ route('register', ['kit' => 'full']) }}" class="home-kit-card home-kit-full">
                    <span class="home-kit-label">THE COMPLETE EXPERIENCE</span>
                    <div class="home-kit-card-heading"><span aria-hidden="true">✳</span><div><h3>Full Kit</h3><p>Everything you need for race day</p></div></div>
                    <ul><li>Finisher medal</li><li>Official KFS Runners T-shirt</li><li>Runner bag</li><li>Certificate of participation</li><li>Race support and photographs</li></ul>
                    <span class="home-kit-action">Choose Full Kit <span aria-hidden="true">→</span></span>
                </a>
                <a href="{{ route('register', ['kit' => 'basic']) }}" class="home-kit-card">
                    <span class="home-kit-label">THE ESSENTIALS</span>
                    <div class="home-kit-card-heading"><span aria-hidden="true">◇</span><div><h3>Basic Kit</h3><p>The essentials for your race</p></div></div>
                    <ul><li>Finisher medal</li><li>Certificate of participation</li><li>Race support and photographs</li></ul>
                    <span class="home-kit-action">Choose Basic Kit <span aria-hidden="true">→</span></span>
                </a>
            </div>
        </div>
    </section>

    <section class="home-included" id="whats-included" aria-labelledby="included-title">
        <div class="wrap">
            <div class="home-included-heading">
                <p class="home-section-kicker"><span></span> READY FOR RACE DAY</p>
                <h2 id="included-title">MORE THAN A <span>BIB NUMBER.</span></h2>
            </div>
            <div class="home-included-grid">
                <div><span aria-hidden="true">◇</span><strong>Finisher medal</strong><small>Celebrate your finish</small></div>
                <div><span aria-hidden="true">✳</span><strong>Official race T-shirt</strong><small>Included with Full Kit</small></div>
                <div><span aria-hidden="true">＋</span><strong>Race support</strong><small>Support along the route</small></div>
                <div><span aria-hidden="true">◎</span><strong>Race photos</strong><small>Keep the memories</small></div>
                <div><span aria-hidden="true">♿</span><strong>Inclusive options</strong><small>Family and Para Race</small></div>
            </div>
        </div>
    </section>

    <section class="home-faq" id="faq" aria-labelledby="faq-title">
        <div class="wrap home-faq-layout">
            <div>
                <p class="home-section-kicker"><span></span> GOOD TO KNOW</p>
                <h2 id="faq-title">READY, SET,<br><span>ANY QUESTIONS?</span></h2>
                <p class="home-faq-intro">A few quick answers before you join the starting line.</p>
                <a href="mailto:elatikysports@gmail.com" class="home-text-link">Contact the race team <span aria-hidden="true">→</span></a>
            </div>
            <div class="home-faq-list">
                <details><summary>How do I register?</summary><p>Choose a race distance and kit, complete the runner details, then continue to EasyKash to pay.</p></details>
                <details><summary>Can I register with my team?</summary><p>Yes. Select the team option during registration and enter your team details.</p></details>
                <details><summary>When is my payment confirmed?</summary><p>Your registration is confirmed after EasyKash sends the signed payment confirmation. Keep your registration reference until the status updates.</p></details>
            </div>
        </div>
    </section>

    <section class="home-final-cta">
        <div class="wrap home-final-cta-inner">
            <div><p>YOUR NEXT FINISH LINE STARTS HERE</p><h2>READY TO <span>RUN?</span></h2></div>
            <a href="{{ route('register') }}" class="home-primary-button home-primary-button-light">Register now <span aria-hidden="true">→</span></a>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const slider = document.getElementById('home-distance-slider');
    if (!slider) return;

    const options = [...slider.querySelectorAll('[data-distance-option]')];
    const featuredDistance = document.getElementById('home-featured-distance');
    const featuredName = document.getElementById('home-featured-name');
    const entryValue = document.getElementById('home-entry-value');
    const entryLabel = document.getElementById('home-entry-label');
    const previous = document.querySelector('[data-distance-slide="-1"]');
    const next = document.querySelector('[data-distance-slide="1"]');
    const defaultOption = options[0];

    const preview = option => {
        if (!option) return;
        options.forEach(item => item.classList.toggle('is-preview', item === option));
        if (featuredDistance) featuredDistance.textContent = option.dataset.distance;
        if (featuredName) featuredName.textContent = option.dataset.raceName;
        if (entryLabel) entryLabel.textContent = `${option.dataset.kit} entry`;
        if (entryValue) {
            entryValue.textContent = option.dataset.price
                ? `${option.dataset.currency} ${new Intl.NumberFormat('en-EG', { maximumFractionDigits: 0 }).format(Number(option.dataset.price))}`
                : 'See race options';
        }
    };

    const updateControls = () => {
        const canSlide = slider.scrollWidth > slider.clientWidth + 2;
        if (previous) {
            previous.hidden = !canSlide;
            previous.disabled = slider.scrollLeft <= 2;
        }
        if (next) {
            next.hidden = !canSlide;
            next.disabled = slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 2;
        }
    };

    options.forEach(option => {
        option.addEventListener('mouseenter', () => preview(option));
        option.addEventListener('focus', () => preview(option));
    });

    slider.addEventListener('mouseleave', () => preview(defaultOption));
    slider.addEventListener('focusout', event => {
        if (!slider.contains(event.relatedTarget)) preview(defaultOption);
    });
    slider.addEventListener('scroll', updateControls, { passive: true });
    window.addEventListener('resize', updateControls);

    document.querySelectorAll('[data-distance-slide]').forEach(button => {
        button.addEventListener('click', () => {
            const direction = Number(button.dataset.distanceSlide);
            slider.scrollBy({ left: direction * Math.max(150, slider.clientWidth * .75), behavior: 'smooth' });
        });
    });

    preview(defaultOption);
    updateControls();
})();
</script>
@endpush
