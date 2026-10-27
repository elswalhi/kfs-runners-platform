@extends('site.layout')

@section('title', __('KFS Runners · Team and sports community'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-team-home.css') }}">
@endpush

@section('content')
<main class="team-home">
    <section class="team-home-hero" aria-labelledby="team-home-title">
        <div class="team-home-hero-image" aria-hidden="true"></div>
        <div class="wrap team-home-hero-content">
            <p class="team-home-eyebrow"><span></span>{{ __('KAFR EL-SHEIKH · PEOPLE · MOVEMENT') }}</p>
            <h1 id="team-home-title">{{ __('A TEAM.') }}<br>{{ __('A COMMUNITY.') }}<br><span>{{ __('A STRONGER YOU.') }}</span></h1>
            <p class="team-home-hero-copy">{{ __('Run together, train with purpose, and find your place in the KFS Runners family.') }}</p>
            <div class="team-home-hero-actions">
                <a class="team-home-button" href="#about-team">{{ __('Meet the team') }} <span aria-hidden="true">→</span></a>
                <a class="team-home-button team-home-button-outline" href="#our-sports">{{ __('Our sports') }} <span aria-hidden="true">→</span></a>
            </div>
            <p class="team-home-location"><span aria-hidden="true">⌖</span> {{ __('Kafr El-Sheikh, Egypt') }} <span class="team-home-location-dot">·</span> {{ __('Everyone has a place here') }}</p>
        </div>
    </section>

    <section class="team-home-about" id="about-team" aria-labelledby="about-team-title">
        <div class="wrap team-home-about-layout">
            <div class="team-home-about-copy">
                <p class="team-home-kicker"><span></span>{{ __('OUR STORY') }}</p>
                <h2 id="about-team-title">{{ __('ABOUT') }} <span>KFS RUNNERS</span></h2>
                <p>{{ __('KFS Runners is a community running team in Kafr El-Sheikh, working under El-Atiky Sports Services. We bring people together through sports, training, and a shared love of movement.') }}</p>
                <a class="team-home-text-link" href="#our-groups">{{ __('Find your group') }} <span aria-hidden="true">→</span></a>
            </div>
            <div class="team-home-about-photo" role="img" aria-label="{{ __('KFS Runners team training together') }}"></div>
        </div>
    </section>

    <section class="team-home-sports" id="our-sports" aria-labelledby="our-sports-title">
        <div class="wrap">
            <div class="team-home-section-heading">
                <div>
                    <p class="team-home-kicker"><span></span>{{ __('OUR SPORTS & SERVICES') }}</p>
                    <h2 id="our-sports-title">{{ __('EXPLORE OUR') }} <span>{{ __('SPORTS.') }}</span></h2>
                </div>
                <p>{{ __('More ways to move, connect, and grow. Choose the activity that fits you.') }}</p>
            </div>

            <div class="team-home-sport-grid">
                <article class="team-home-sport-card">
                    <a class="team-home-sport-photo team-home-sport-running" href="#contact" aria-label="{{ __('Ask us about running and fitness training') }}"></a>
                    <div class="team-home-sport-copy">
                        <div><p class="team-home-card-label">{{ __('TRAIN TOGETHER') }}</p><h3>{{ __('Running') }}</h3></div>
                        <p>{{ __('Running and fitness training for every level, with a supportive team beside you.') }}</p>
                        <a class="team-home-text-link" href="mailto:elatikysports@gmail.com?subject=Running%20and%20fitness%20training">{{ __('Learn more') }} <span aria-hidden="true">→</span></a>
                    </div>
                </article>

                <article class="team-home-sport-card">
                    <a class="team-home-sport-photo team-home-sport-skating" href="#contact" aria-label="{{ __('Ask us about skating') }}"></a>
                    <div class="team-home-sport-copy">
                        <div><p class="team-home-card-label">{{ __('LEARN & ROLL') }}</p><h3>{{ __('Skating') }}</h3></div>
                        <p>{{ __('Build confidence and skating skills with guidance from a welcoming community.') }}</p>
                        <a class="team-home-text-link" href="https://elatiky.com/production/skate-academy/" target="_blank" rel="noopener noreferrer">{{ __('Skate academy') }} <span aria-hidden="true">↗</span></a>
                    </div>
                </article>

                <article class="team-home-sport-card">
                    <a class="team-home-sport-photo team-home-sport-cycling" href="#contact" aria-label="{{ __('Ask us about cycling') }}"></a>
                    <div class="team-home-sport-copy">
                        <div><p class="team-home-card-label">{{ __('RIDE AS A TEAM') }}</p><h3>{{ __('Cycling') }}</h3></div>
                        <p>{{ __('Discover new routes and enjoy active time on two wheels with other riders.') }}</p>
                        <a class="team-home-text-link" href="mailto:elatikysports@gmail.com?subject=Cycling%20group">{{ __('Ask about cycling') }} <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            </div>

            <div class="team-home-more-links" aria-label="{{ __('More from KFS Runners') }}">
                <span>{{ __('MORE FROM KFS') }}</span>
                <a href="https://elatiky.com/shop/" target="_blank" rel="noopener noreferrer">{{ __('Store') }} <span aria-hidden="true">↗</span></a>
                <a href="https://elatiky.com/production/skate-academy/" target="_blank" rel="noopener noreferrer">{{ __('Skate academy') }} <span aria-hidden="true">↗</span></a>
                <a href="{{ route('event') }}">{{ __('Events') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section class="team-home-groups" id="our-groups" aria-labelledby="our-groups-title">
        <div class="wrap team-home-groups-layout">
            <div>
                <p class="team-home-kicker"><span></span>{{ __('OUR GROUPS') }}</p>
                <h2 id="our-groups-title">{{ __('FIND YOUR') }} <span>{{ __('GROUP.') }}</span></h2>
            </div>
            <p class="team-home-groups-intro">{{ __('Different sports, one community. Join the group that matches what you love.') }}</p>
            <div class="team-home-group-links">
                <a href="mailto:elatikysports@gmail.com?subject=Join%20the%20running%20team">{{ __('Running team') }} <span aria-hidden="true">→</span></a>
                <a href="mailto:elatikysports@gmail.com?subject=Join%20the%20skating%20group">{{ __('Skating group') }} <span aria-hidden="true">→</span></a>
                <a href="mailto:elatikysports@gmail.com?subject=Join%20the%20cycling%20group">{{ __('Cycling group') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    <section class="team-home-next" aria-label="{{ __('Explore the event and register') }}">
        <div class="wrap team-home-next-grid">
            <article class="team-home-next-card team-home-event-card">
                <div><p class="team-home-card-label">{{ __('UPCOMING EVENT') }}</p><h2>{{ __('KFS Runners Event') }}</h2><p>{{ __('Explore race details, distances, and what to expect on event day.') }}</p><a class="team-home-next-link" href="{{ route('event') }}">{{ __('Explore the event') }} <span aria-hidden="true">→</span></a></div>
            </article>
            <article class="team-home-next-card team-home-register-card">
                <div><p class="team-home-card-label">{{ __('MARATHON REGISTRATION') }}</p><h2>{{ __('Ready for your next challenge?') }}</h2><p>{{ __('Choose your distance and register for the KFS Runners marathon.') }}</p><a class="team-home-next-link" href="{{ route('register') }}">{{ __('Register now') }} <span aria-hidden="true">→</span></a></div>
            </article>
        </div>
    </section>

    <section class="team-home-contact" id="contact" aria-label="{{ __('Contact KFS Runners') }}">
        <div class="wrap team-home-contact-inner">
            <div><p class="team-home-kicker"><span></span>{{ __('COME MOVE WITH US') }}</p><h2>{{ __('Your team is waiting.') }}</h2></div>
            <a href="mailto:elatikysports@gmail.com" class="team-home-button">{{ __('Get in touch') }} <span aria-hidden="true">→</span></a>
        </div>
    </section>
</main>
@endsection
