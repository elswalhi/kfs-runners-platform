@extends('site.layout')

@section('title', 'KFS Runners · Your race. Your story.')

@section('content')
<main>
    <section class="hero-section">
        <div class="hero-photo" style="background-image:linear-gradient(90deg,rgba(3,35,30,.94),rgba(3,35,30,.72) 43%,rgba(3,35,30,.1)),url('https://events.elatiky.com/wp-content/uploads/2018/03/h1-slider-1-background-img.jpg')"></div><div class="hero-pattern"></div>
        <div class="wrap hero-content"><div class="hero-text">
            <span class="eyebrow-pill"><span class="pulse-dot"></span> KAFR EL-SHEIKH MARATHON</span>
            <h1>MORE THAN<br><span>A RACE.</span></h1><p class="hero-lede">Your next finish line starts here.</p>
            <p class="hero-copy">Run, challenge yourself, and become part of the KFS Runners family. There is a distance for every goal and every pace.</p>
            <div class="hero-actions"><a href="{{ route('register') }}" class="btn btn-red">Register for the race <span>↗</span></a><a href="#the-race" class="btn btn-outline-light">Explore the race <span>↓</span></a></div>
            <div class="hero-meta"><div><span>⌖</span><span><b>Kafr El-Sheikh</b><small>Egypt</small></span></div><div><span>♙</span><span><b>Every runner</b><small>Every pace, every story</small></span></div><div><span>♡</span><span><b>One community</b><small>One finish line</small></span></div></div>
        </div><div class="hero-stamp" aria-hidden="true"><span>RUN</span><span>YOUR</span><strong>STORY</strong><i>→</i></div></div><a href="#race-categories" class="hero-scroll"><span></span> Scroll to explore</a>
    </section>

    <section class="intro-strip" id="the-race"><div class="wrap intro-grid">
        <div class="intro-title"><div class="section-kicker"><span></span> THE KFS EXPERIENCE</div><h2>We run.<br><span>We become.</span></h2></div>
        <div class="intro-copy"><p class="large-copy">A race is more than the distance between the start and finish. It is every early morning, every step forward, and every person cheering beside you.</p><p>KFS Runners brings people together through movement, sport, and a shared belief that progress starts when you take the first step.</p><a href="{{ route('register') }}" class="text-link">Be part of the story <span>→</span></a></div>
        <div class="intro-badge"><span aria-hidden="true" style="font-size:36px">♧</span><span>START<br>TOGETHER</span><b>FINISH STRONG</b></div>
    </div></section>

    <section class="races-section section-pad" id="race-categories"><div class="wrap">
        <div class="section-heading centered"><div class="section-kicker"><span></span> FIND YOUR DISTANCE</div><h2>CHOOSE YOUR <span>CHALLENGE</span></h2><p>Different distances. One community. Find the race that fits you.</p></div>
        <div class="race-card-grid">
            @forelse ($races as $index => $race)
                @php($color = ['red', 'green', 'light', 'sand', 'sage'][$index % 5])
                <article class="race-card race-{{ $color }}"><div class="race-card-top"><span class="race-number">{{ sprintf('%02d', $index + 1) }}</span><span>↗</span></div><div class="race-distance">{{ $race->distance_label }}</div><h3>{{ $race->name }}</h3><p>{{ $race->category_type === 'family' ? 'Bring your family and run together.' : ($race->category_type === 'para' ? 'A race built around access and inclusion.' : 'A challenge for every pace and every goal.') }}</p><a href="{{ route('register', ['race' => $race->slug]) }}" class="race-card-link">Choose this race <span>→</span></a></article>
            @empty
                <div class="race-card race-light"><h3>Race categories are being prepared</h3><p>Please check back soon.</p></div>
            @endforelse
        </div>
        <div class="race-footnote"><span>♢ Inclusive race options</span><span>⌖ Kafr El-Sheikh, Egypt</span><span>♙ Run solo or with your team</span></div>
    </div></section>

    <section class="experience-section"><div class="experience-photo" style="background-image:linear-gradient(90deg,rgba(2,39,32,.96),rgba(2,39,32,.83) 50%,rgba(2,39,32,.3)),url('https://events.elatiky.com/wp-content/uploads/2018/03/h1-slider-2-background-img.jpg')"></div>
        <div class="wrap experience-content"><div class="experience-heading"><div class="section-kicker"><span></span> RACE DAY, YOUR WAY</div><h2>MAKE EVERY<br><span>STEP COUNT.</span></h2><p>Choose the race kit that works for you. Bring your energy; we'll take care of the race experience.</p><a href="{{ route('register') }}" class="btn btn-red">Pick your race kit <span>↗</span></a></div>
            <div class="kit-info-grid">
                <article class="kit-info-card kit-full"><span class="kit-tag">THE COMPLETE EXPERIENCE</span><div class="kit-card-heading"><div class="kit-symbol">♧</div><div><h3>Full Kit</h3><p>The complete race-day experience</p></div></div><ul><li>✓ Finisher medal</li><li>✓ Official KFS Runners T-shirt</li><li>✓ Runner bag</li><li>✓ Certificate of participation</li><li>✓ Race support and photographs</li></ul><a href="{{ route('register') }}" class="kit-link">Select Full Kit <span>→</span></a></article>
                <article class="kit-info-card kit-basic"><span class="kit-tag">THE ESSENTIALS</span><div class="kit-card-heading"><div class="kit-symbol">◇</div><div><h3>Basic Kit</h3><p>The essentials for your race</p></div></div><ul><li>✓ Finisher medal</li><li>✓ Certificate of participation</li><li>✓ Race support and photographs</li></ul><a href="{{ route('register') }}" class="kit-link">Select Basic Kit <span>→</span></a></article>
            </div>
        </div>
    </section>

    <section class="included-section section-pad" id="whats-included"><div class="wrap"><div class="section-heading centered"><div class="section-kicker"><span></span> READY FOR RACE DAY</div><h2>WHAT'S <span>INCLUDED?</span></h2><p>More than a bib number — a full experience built around every runner.</p></div><div class="included-grid">
        <div class="included-item"><span>◇</span><b>Race medal</b><small>Celebrate your finish</small></div><div class="included-item"><span>♧</span><b>Official T-shirt</b><small>Included with Full Kit</small></div><div class="included-item"><span>♢</span><b>Race support</b><small>Support along the way</small></div><div class="included-item"><span>◎</span><b>Race photos</b><small>Keep the memories</small></div><div class="included-item"><span>♿</span><b>Inclusive options</b><small>Family & Para Race</small></div>
    </div></div></section>

    <section class="community-section"><div class="wrap community-grid"><div><div class="section-kicker"><span></span> ONE COMMUNITY</div><h2>YOUR PEOPLE.<br><span>YOUR PACE.</span></h2></div><div class="community-copy"><p>Whether this is your first 2K or your next 21K, show up for yourself and discover what we can do together.</p><a href="{{ route('register') }}" class="btn btn-dark">Join KFS Runners <span>↗</span></a></div><div class="community-quote"><span>“</span><p>The finish line is a moment. The journey is what makes you a runner.</p><small>KFS RUNNERS · KAFR EL-SHEIKH</small></div></div></section>
    <section class="cta-band"><div class="wrap cta-band-inner"><div><span>YOUR NEXT FINISH LINE</span><h2>READY TO <b>RUN?</b></h2></div><a href="{{ route('register') }}" class="btn btn-white">Register now <span>↗</span></a></div></section>
</main>
@endsection
