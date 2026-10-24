import { Footprints } from 'lucide-react';

export default function Brand({ light = false }) {
  return (
    <span className={`brand ${light ? 'brand-light' : ''}`} aria-label="KFS Runners">
      <span className="brand-mark"><Footprints size={22} strokeWidth={2.8} /></span>
      <span className="brand-copy">
        <span className="brand-kfs">KFS <b>RUNNERS</b></span>
        <span className="brand-location">KAFR EL-SHEIKH</span>
      </span>
    </span>
  );
}
