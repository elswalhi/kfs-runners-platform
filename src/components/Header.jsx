import { useState } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { Menu, X, ArrowUpRight } from 'lucide-react';
import Brand from './Brand';

export default function Header() {
  const [open, setOpen] = useState(false);
  const location = useLocation();
  const close = () => setOpen(false);
  const links = [
    { to: '/', label: 'Home' },
    { to: '/#the-race', label: 'The Race' },
    { to: '/#race-categories', label: 'Race Categories' },
    { to: '/#whats-included', label: "What's Included" },
  ];

  return (
    <header className="site-header">
      <div className="header-inner wrap">
        <Link to="/" className="brand-link" onClick={close}><Brand /></Link>
        <button className="mobile-menu-btn" aria-label={open ? 'Close menu' : 'Open menu'} onClick={() => setOpen(!open)}>
          {open ? <X size={23} /> : <Menu size={23} />}
        </button>
        <nav className={`main-nav ${open ? 'nav-open' : ''}`} aria-label="Main navigation">
          {links.map(link => (
            <a key={link.label} href={link.to} onClick={close} className={location.pathname === '/' && link.to === '/' ? 'active' : ''}>{link.label}</a>
          ))}
          <NavLink to="/register" className="nav-cta" onClick={close}>Register now <ArrowUpRight size={15} /></NavLink>
        </nav>
      </div>
    </header>
  );
}
