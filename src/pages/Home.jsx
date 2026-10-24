import { ArrowDown, ArrowRight, ArrowUpRight, CalendarDays, CheckCircle2, HeartPulse, MapPin, Medal, ShieldCheck, Users, Shirt, Camera, Accessibility, Footprints } from 'lucide-react';
import { Link } from 'react-router-dom';
import { races, kitOptions, money } from '../data/races';

const HERO_IMAGE = 'https://events.elatiky.com/wp-content/uploads/2018/03/h1-slider-1-background-img.jpg';
const RUNNER_IMAGE = 'https://events.elatiky.com/wp-content/uploads/2018/03/h1-slider-2-background-img.jpg';

function SectionKicker({ children }) { return <div className="section-kicker"><span />{children}</div>; }

export default function Home() {
  return (
    <main>
      <section className="hero-section">
        <div className="hero-photo" style={{ backgroundImage: `linear-gradient(90deg, rgba(3,35,30,.94) 0%, rgba(3,35,30,.72) 43%, rgba(3,35,30,.1) 100%), url("${HERO_IMAGE}")` }} />
        <div className="hero-pattern" />
        <div className="wrap hero-content">
          <div className="hero-text">
            <span className="eyebrow-pill"><span className="pulse-dot" /> KAFR EL-SHEIKH MARATHON</span>
            <h1>MORE THAN<br /><span>A RACE.</span></h1>
            <p className="hero-lede">Your next finish line starts here.</p>
            <p className="hero-copy">Run, challenge yourself, and become part of the KFS Runners family. There is a distance for every goal and every pace.</p>
            <div className="hero-actions">
              <Link to="/register" className="btn btn-red">Register for the race <ArrowUpRight size={18} /></Link>
              <a href="#the-race" className="btn btn-outline-light">Explore the race <ArrowDown size={17} /></a>
            </div>
            <div className="hero-meta">
              <div><MapPin size={18} /><span><b>Kafr El-Sheikh</b><small>Egypt</small></span></div>
              <div><Users size={18} /><span><b>Every runner</b><small>Every pace, every story</small></span></div>
              <div><HeartPulse size={18} /><span><b>One community</b><small>One finish line</small></span></div>
            </div>
          </div>
          <div className="hero-stamp" aria-hidden="true"><span>RUN</span><span>YOUR</span><strong>STORY</strong><i>→</i></div>
        </div>
        <a href="#race-categories" className="hero-scroll"><span /> Scroll to explore</a>
      </section>

      <section className="intro-strip" id="the-race">
        <div className="wrap intro-grid">
          <div className="intro-title"><SectionKicker>THE KFS EXPERIENCE</SectionKicker><h2>We run.<br /><span>We become.</span></h2></div>
          <div className="intro-copy"><p className="large-copy">A race is more than the distance between the start and finish. It is every early morning, every step forward, and every person cheering beside you.</p><p>KFS Runners brings people together through movement, sport, and a shared belief that progress starts when you take the first step.</p><Link to="/register" className="text-link">Be part of the story <ArrowRight size={17} /></Link></div>
          <div className="intro-badge"><Footprints size={39} /><span>START<br />TOGETHER</span><b>FINISH STRONG</b></div>
        </div>
      </section>

      <section className="races-section section-pad" id="race-categories">
        <div className="wrap">
          <div className="section-heading centered"><SectionKicker>FIND YOUR DISTANCE</SectionKicker><h2>CHOOSE YOUR <span>CHALLENGE</span></h2><p>Different distances. One community. Find the race that fits you.</p></div>
          <div className="race-card-grid">
            {races.map((race, i) => (
              <article key={race.id} className={`race-card race-${race.color}`}>
                <div className="race-card-top"><span className="race-number">0{i + 1}</span><ArrowUpRight size={20} /></div>
                <div className="race-distance">{race.distance}</div>
                <h3>{race.title}</h3>
                <p>{race.description}</p>
                <Link to={`/register?race=${race.id}`} className="race-card-link">Choose this race <ArrowRight size={16} /></Link>
              </article>
            ))}
          </div>
          <div className="race-footnote"><span><ShieldCheck size={18} /> Inclusive race options</span><span><MapPin size={18} /> Kafr El-Sheikh, Egypt</span><span><Users size={18} /> Run solo or with your team</span></div>
        </div>
      </section>

      <section className="experience-section" id="whats-included">
        <div className="experience-photo" style={{ backgroundImage: `linear-gradient(90deg, rgba(2,39,32,.96) 0%, rgba(2,39,32,.83) 50%, rgba(2,39,32,.3) 100%), url("${RUNNER_IMAGE}")` }} />
        <div className="wrap experience-content">
          <div className="experience-heading"><SectionKicker>RACE DAY, YOUR WAY</SectionKicker><h2>MAKE EVERY<br /><span>STEP COUNT.</span></h2><p>Choose the race kit that works for you. Bring your energy; we'll take care of the race experience.</p><Link to="/register" className="btn btn-red">Pick your race kit <ArrowUpRight size={18} /></Link></div>
          <div className="kit-info-grid">
            {kitOptions.map((kit, i) => (
              <article className={`kit-info-card ${i === 0 ? 'kit-full' : 'kit-basic'}`} key={kit.id}>
                <span className="kit-tag">{i === 0 ? 'THE COMPLETE EXPERIENCE' : 'THE ESSENTIALS'}</span>
                <div className="kit-card-heading"><div className="kit-symbol">{i === 0 ? <Shirt size={28} /> : <Medal size={28} />}</div><div><h3>{kit.name}</h3><p>{kit.subtitle}</p></div></div>
                <ul>{kit.items.map(item => <li key={item}><CheckCircle2 size={16} />{item}</li>)}</ul>
                <Link to="/register" className="kit-link">Select {kit.name} <ArrowRight size={16} /></Link>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="included-section section-pad">
        <div className="wrap">
          <div className="section-heading centered"><SectionKicker>READY FOR RACE DAY</SectionKicker><h2>WHAT'S <span>INCLUDED?</span></h2><p>More than a bib number — a full experience built around every runner.</p></div>
          <div className="included-grid">
            <div className="included-item"><span><Medal /></span><b>Race medal</b><small>Celebrate your finish</small></div>
            <div className="included-item"><span><Shirt /></span><b>Official T-shirt</b><small>Included with Full Kit</small></div>
            <div className="included-item"><span><ShieldCheck /></span><b>Race support</b><small>Support along the way</small></div>
            <div className="included-item"><span><Camera /></span><b>Race photos</b><small>Keep the memories</small></div>
            <div className="included-item"><span><Accessibility /></span><b>Inclusive options</b><small>Family & Para Race</small></div>
          </div>
        </div>
      </section>

      <section className="community-section">
        <div className="wrap community-grid">
          <div><SectionKicker>ONE COMMUNITY</SectionKicker><h2>YOUR PEOPLE.<br /><span>YOUR PACE.</span></h2></div>
          <div className="community-copy"><p>Whether this is your first 2K or your next 21K, show up for yourself and discover what we can do together.</p><Link to="/register" className="btn btn-dark">Join KFS Runners <ArrowUpRight size={18} /></Link></div>
          <div className="community-quote"><span>“</span><p>The finish line is a moment. The journey is what makes you a runner.</p><small>KFS RUNNERS · KAFR EL-SHEIKH</small></div>
        </div>
      </section>

      <section className="cta-band"><div className="wrap cta-band-inner"><div><span>YOUR NEXT FINISH LINE</span><h2>READY TO <b>RUN?</b></h2></div><Link to="/register" className="btn btn-white">Register now <ArrowUpRight size={18} /></Link></div></section>
    </main>
  );
}
