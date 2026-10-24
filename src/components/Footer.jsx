import { Link } from 'react-router-dom';
import { ArrowUpRight, Instagram, Facebook, Mail, Phone, MapPin } from 'lucide-react';
import Brand from './Brand';

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="wrap footer-main">
        <div className="footer-brand-col">
          <Brand light />
          <p>More than a race. A community that moves forward together.</p>
        </div>
        <div className="footer-links">
          <h4>Explore</h4>
          <Link to="/">Home</Link>
          <a href="/#the-race">The race</a>
          <a href="/#race-categories">Race categories</a>
          <Link to="/register">Registration</Link>
        </div>
        <div className="footer-links">
          <h4>Get in touch</h4>
          <a href="mailto:elatikysports@gmail.com"><Mail size={15} /> elatikysports@gmail.com</a>
          <a href="tel:01508015156"><Phone size={15} /> 01508015156</a>
          <span><MapPin size={15} /> Kafr El-Sheikh, Egypt</span>
        </div>
        <div className="footer-social-col">
          <h4>Follow the movement</h4>
          <div className="social-row">
            <a href="https://www.facebook.com/" target="_blank" rel="noreferrer" aria-label="Facebook"><Facebook size={18} /></a>
            <a href="https://www.instagram.com/" target="_blank" rel="noreferrer" aria-label="Instagram"><Instagram size={18} /></a>
            <Link to="/register" aria-label="Register"><ArrowUpRight size={18} /></Link>
          </div>
          <p>Find your next finish line.</p>
        </div>
      </div>
      <div className="footer-bottom">
        <div className="wrap footer-bottom-inner"><span>© {new Date().getFullYear()} KFS Runners · El-Atiky Sports Services</span><span>Run · Challenge · Connect</span></div>
      </div>
    </footer>
  );
}
