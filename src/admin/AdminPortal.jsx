import { useEffect, useMemo, useState } from 'react';
import { Link, Navigate, NavLink, Outlet, Route, Routes, useLocation, useNavigate, useParams } from 'react-router-dom';
import {
  Activity, ArrowLeft, ArrowRight, ArrowUpRight, BadgeCheck, Bell, CalendarDays,
  Check, ChevronDown, CircleDollarSign, CreditCard, Download, Eye, FileSpreadsheet,
  FileText, Filter, Gauge, LayoutDashboard, LogOut, Menu, Save, Search, ShieldCheck,
  Ticket, TrendingUp, Users, X, Zap,
} from 'lucide-react';
import { adminLogin, adminLogout, downloadRegistrationDocument, getDashboardSummary, getPayments, getRaceKitPrices, getRegistrationDetails, getRegistrations, hasAdminSession, isDemoMode, updateRaceKitPrice, updateRegistrationStatus } from './api';
import { PricingManagementPage, RaceManagementPage, TeamImportPage } from './ManagementPages';
import './admin.css';

const currency = value => new Intl.NumberFormat('en-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 0 }).format(Number(value || 0));
const dateText = value => value ? new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const titleCase = value => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

function downloadCsv(filename, rows) {
  if (!rows?.length) return;
  const headers = [...new Set(rows.flatMap(row => Object.keys(row)))];
  const escape = value => {
    const text = value === null || value === undefined ? '' : String(value);
    return /[",\n\r]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
  };
  const csv = [headers.map(escape).join(','), ...rows.map(row => headers.map(key => escape(row[key])).join(','))].join('\r\n');
  const blob = new Blob(['\ufeff', csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url; anchor.download = filename; anchor.click();
  URL.revokeObjectURL(url);
}

function AdminGate() {
  const location = useLocation();
  if (!hasAdminSession()) return <Navigate to="/admin/login" replace state={{ from: location }} />;
  return <AdminShell />;
}

function AdminShell() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const navigate = useNavigate();
  const navItems = [
    { to: '/admin', label: 'Overview', icon: LayoutDashboard, end: true },
    { to: '/admin/registrations', label: 'Registrations', icon: Users, badge: 'Runners' },
    { to: '/admin/payments', label: 'Payments', icon: CreditCard },
    { to: '/admin/pricing', label: 'Race & pricing', icon: Ticket },
    { to: '/admin/races', label: 'Race management', icon: Activity },
    { to: '/admin/team-import', label: 'Team Excel import', icon: FileSpreadsheet },
  ];
  async function logout() { try { await adminLogout(); } finally { navigate('/admin/login', { replace: true }); } }
  return <div className="admin-app">
    <aside className={`admin-sidebar ${mobileOpen ? 'admin-sidebar-open' : ''}`}>
      <div className="admin-brand-row"><div className="admin-brand-symbol">K<span>↗</span></div><div className="admin-brand-word">KFS RUNNERS<small>RACE CONTROL</small></div><button className="admin-mobile-close" onClick={() => setMobileOpen(false)} aria-label="Close menu"><X size={18}/></button></div>
      <div className="admin-workspace-label">WORKSPACE</div>
      <nav className="admin-nav">
        {navItems.map(({ to, label, icon: Icon, end }) => <NavLink key={to} to={to} end={end} className={({ isActive }) => `admin-nav-link ${isActive ? 'active' : ''}`} onClick={() => setMobileOpen(false)}><Icon size={18}/><span>{label}</span>{label === 'Registrations' && <span className="admin-nav-dot"/>}</NavLink>)}
      </nav>
      <div className="admin-sidebar-bottom">
        <div className="admin-help-card"><div className="admin-help-icon"><ShieldCheck size={19}/></div><b>Race day ready?</b><p>Keep runner details and payment status up to date.</p><a href="mailto:events@elatiky.com">Get support <ArrowRight size={13}/></a></div>
        <button className="admin-nav-link admin-logout" onClick={logout}><LogOut size={18}/><span>Sign out</span></button>
        <div className="admin-user"><div className="admin-avatar">KA</div><div><b>KFS Administrator</b><small>Event operations</small></div><ChevronDown size={15}/></div>
      </div>
    </aside>
    {mobileOpen && <button className="admin-mobile-scrim" onClick={() => setMobileOpen(false)} aria-label="Close navigation"/>}
    <div className="admin-main">
      <header className="admin-topbar"><button className="admin-mobile-menu" onClick={() => setMobileOpen(true)} aria-label="Open menu"><Menu size={20}/></button><div className="admin-breadcrumb">KFS Runners <span>/</span> <b>Operations</b></div><div className="admin-top-actions">{isDemoMode && <span className="admin-demo-badge"><span/> DEMO DATA</span>}<button className="admin-icon-button" aria-label="Notifications"><Bell size={18}/><i/></button><div className="admin-top-date"><CalendarDays size={15}/> {new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</div></div></header>
      {isDemoMode && <div className="admin-demo-notice"><Activity size={15}/> Demo mode: this dashboard is using sample data. Configure <code>VITE_API_BASE_URL</code> and the Laravel API to load live records.</div>}
      <div className="admin-page-wrap"><Outlet/></div>
    </div>
  </div>;
}

function AdminLogin() {
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  async function submit(event, demo = false) {
    event?.preventDefault(); setBusy(true); setError('');
    try {
      await adminLogin(email, password, demo);
      navigate(location.state?.from?.pathname || '/admin', { replace: true });
    } catch (err) { setError(err.message || 'Unable to sign in.'); }
    finally { setBusy(false); }
  }
  if (hasAdminSession()) return <Navigate to="/admin" replace/>;
  return <div className="admin-login-page"><div className="admin-login-left"><Link to="/" className="admin-back-public">← Public website</Link><div className="admin-login-content"><div className="admin-login-brand"><div className="admin-brand-symbol">K<span>↗</span></div><div className="admin-brand-word">KFS RUNNERS<small>RACE CONTROL</small></div></div><span className="admin-eyebrow"><span/> EVENT OPERATIONS PLATFORM</span><h1>Every runner.<br/><em>Every detail.</em></h1><p>One place to manage registrations, race categories, kits and payments.</p><div className="admin-login-features"><span><Check size={15}/> Runner management</span><span><Check size={15}/> Payment visibility</span><span><Check size={15}/> Race-day readiness</span></div></div><div className="admin-login-foot">KFS RUNNERS · KAFR EL-SHEIKH</div></div><div className="admin-login-right"><form className="admin-login-form" onSubmit={e => submit(e, false)}><div className="admin-login-icon"><ShieldCheck size={24}/></div><div className="admin-eyebrow">WELCOME BACK</div><h2>Admin sign in</h2><p>Use your event administrator account to continue.</p><label>Email address<input type="email" autoComplete="username" placeholder="admin@yourdomain.com" value={email} onChange={e => setEmail(e.target.value)} required/></label><label>Password<input type="password" autoComplete="current-password" placeholder="Enter your password" value={password} onChange={e => setPassword(e.target.value)} required/></label>{error && <div className="admin-form-error" role="alert">{error}</div>}<button className="admin-primary-button admin-login-submit" disabled={busy}>{busy ? 'Signing in…' : 'Sign in securely'} <ArrowRight size={16}/></button>{isDemoMode && <><div className="admin-login-divider"><span/>FOR UI PREVIEW<span/></div><button type="button" className="admin-secondary-button" disabled={busy} onClick={e => submit(e, true)}>Open demo dashboard <ArrowUpRight size={15}/></button><small className="admin-demo-caption">Demo only — no real login or live data is used.</small></>}</form><div className="admin-login-bottom">Secure admin workspace <span>·</span> KFS Runners</div></div></div>;
}

function PageHeading({ eyebrow, title, accent, description, action }) {
  return <div className="admin-page-heading"><div><div className="admin-eyebrow">{eyebrow}</div><h1>{title} <span>{accent}</span></h1><p>{description}</p></div>{action && <div className="admin-heading-action">{action}</div>}</div>;
}

function MetricCard({ label, value, change, note, icon: Icon, tone = 'green', trend = 'up' }) {
  return <div className={`admin-metric-card tone-${tone}`}><div className="admin-metric-top"><span>{label}</span><span className="admin-metric-icon"><Icon size={18}/></span></div><div className="admin-metric-value">{value}</div><div className="admin-metric-foot"><span className={`admin-trend ${trend}`}><ArrowUpRight size={13}/>{change}</span><span>{note}</span></div></div>;
}

function StatusPill({ value }) {
  const normalized = String(value || 'pending').toLowerCase();
  return <span className={`admin-status status-${normalized}`}>{titleCase(normalized)}</span>;
}

function Dashboard() {
  const [summary, setSummary] = useState(null);
  const [error, setError] = useState('');
  useEffect(() => { getDashboardSummary().then(r => setSummary(r.data || r)).catch(e => setError(e.message)); }, []);
  if (error) return <div className="admin-load-error"><b>Could not load dashboard.</b><p>{error}</p></div>;
  if (!summary) return <div className="admin-loading"><span className="admin-spinner"/> Loading dashboard metrics…</div>;
  const maxTrend = Math.max(...(summary.revenue_trend || []).map(p => p.value), 1);
  const total = (summary.race_breakdown || []).reduce((s, r) => s + Number(r.count), 0) || 1;
  return <>
    <PageHeading eyebrow="OVERVIEW / LIVE OPERATIONS" title="Good evening," accent="team." description="Here’s what’s happening across KFS Runners registrations and payments." action={<button className="admin-secondary-button" onClick={() => downloadCsv('kfs-dashboard-overview.csv', [{ metric: 'Total registrations', value: summary?.total_registrations }, { metric: 'Paid registrations', value: summary?.paid_registrations }, { metric: 'Pending payments', value: summary?.pending_payments }, { metric: 'Failed payments', value: summary?.failed_payments }, { metric: 'Confirmed revenue EGP', value: summary?.total_revenue }])}><Download size={15}/> Export overview</button>}/>
    <div className="admin-date-callout"><div className="admin-date-icon"><Zap size={18}/></div><div><b>Race operations at a glance</b><span>Monitor registrations, payment confirmations and category demand from one workspace.</span></div><div className="admin-date-right"><span className="admin-live-dot"/> OPERATIONAL VIEW</div></div>
    <div className="admin-metrics-grid">
      <MetricCard label="Total registrations" value={Number(summary.total_registrations || 0).toLocaleString()} change="All records" note="from Laravel database" icon={Users} tone="green"/>
      <MetricCard label="Paid registrations" value={Number(summary.paid_registrations || 0).toLocaleString()} change={`${summary.conversion_rate || 0}%`} note="payment conversion" icon={BadgeCheck} tone="blue"/>
      <MetricCard label="Confirmed revenue" value={currency(summary.total_revenue)} change="Verified only" note="paid registrations only" icon={CircleDollarSign} tone="red"/>
      <MetricCard label="Pending payments" value={Number(summary.pending_payments || 0).toLocaleString()} change={`${summary.failed_payments || 0} failed`} note="need review" icon={CreditCard} tone="sand" trend="down"/>
    </div>
    <div className="admin-dashboard-grid">
      <section className="admin-panel admin-revenue-panel"><div className="admin-panel-heading"><div><h3>Revenue activity</h3><p>Daily confirmed revenue from the connected API</p></div><button className="admin-more-button">Last 7 days <ChevronDown size={14}/></button></div><div className="admin-chart-total"><strong>{currency(summary.total_revenue)}</strong><span><TrendingUp size={14}/> Revenue received</span></div><div className="admin-bar-chart">{(summary.revenue_trend || []).map((point, index) => <div className="admin-chart-column" key={point.label}><div className="admin-chart-bar-wrap"><div className={`admin-chart-bar ${index === (summary.revenue_trend || []).length - 2 ? 'highlight' : ''}`} style={{ height: `${Math.max(8, point.value / maxTrend * 100)}%` }} title={`${currency(point.value)} confirmed revenue`}/></div><span>{point.label}</span></div>)}</div><div className="admin-chart-legend"><span><i/> Confirmed revenue (EGP)</span><small>Review gateway reconciliation for accounting</small></div></section>
      <section className="admin-panel admin-race-panel"><div className="admin-panel-heading"><div><h3>Registrations by race</h3><p>Demand across categories</p></div><Ticket size={18} className="admin-panel-heading-icon"/></div><div className="admin-race-total"><strong>{Number(summary.total_registrations || 0).toLocaleString()}</strong><span>all participants</span></div><div className="admin-race-bars">{(summary.race_breakdown || []).map((r, i) => <div className="admin-race-bar-row" key={r.label}><div><span>{r.label}</span><b>{r.count}</b></div><div className="admin-progress-track"><i style={{ width: `${Math.max(2, r.count / total * 100)}%` }} className={`race-color-${i}`}/></div></div>)}</div></section>
    </div>
    <section className="admin-panel admin-recent-panel"><div className="admin-panel-heading"><div><h3>Recent registrations</h3><p>The latest runner records in the system</p></div><Link className="admin-panel-link" to="/admin/registrations">View all registrations <ArrowRight size={14}/></Link></div><RegistrationTable rows={(summary.recent_registrations || []).map(r => ({ ...r, runner_name: `${r.first_name || ''} ${r.last_name || ''}`.trim() }))} compact/></section>
  </>;
}

function RegistrationTable({ rows, compact = false }) {
  if (!rows?.length) return <div className="admin-empty-state"><Users size={24}/><b>No registrations found</b><span>New runner registrations will appear here.</span></div>;
  return <div className="admin-table-scroll"><table className={`admin-table ${compact ? 'compact' : ''}`}><thead><tr><th>Runner</th><th>Race / kit</th><th>Registration</th><th>Payment</th><th>Amount</th><th>Date</th>{!compact && <th/>}</tr></thead><tbody>{rows.map(r => <tr key={r.id}><td><div className="admin-runner-cell"><span className="admin-runner-avatar">{(r.first_name || 'R')[0]}{(r.last_name || 'N')[0]}</span><span><b>{r.runner_name || `${r.first_name || ''} ${r.last_name || ''}`.trim()}</b><small>{r.email}</small></span></div></td><td><div className="admin-race-cell"><b>{r.race?.distance_label || r.race?.name || r.race_name || '—'}</b><small>{titleCase(r.kit_type)} Kit{r.shirt_size ? ` · ${r.shirt_size}` : ''}</small></div></td><td><StatusPill value={r.status}/></td><td><StatusPill value={r.payment_status}/></td><td className="admin-amount-cell">{currency(r.amount)}</td><td className="admin-date-cell">{dateText(r.created_at)}</td>{!compact && <td><Link className="admin-table-icon" title="View runner details" aria-label="View runner details" to={`/admin/registrations/${r.id}`}><Eye size={16}/></Link></td>}</tr>)}</tbody></table></div>;
}

function RegistrationsPage() {
  const [rows, setRows] = useState([]); const [search, setSearch] = useState(''); const [paymentStatus, setPaymentStatus] = useState('all'); const [loading, setLoading] = useState(true); const [error, setError] = useState('');
  useEffect(() => { setLoading(true); getRegistrations({ search, payment_status: paymentStatus }).then(r => setRows((r.data || r).map ? (r.data || r) : [])).catch(e => setError(e.message)).finally(() => setLoading(false)); }, [search, paymentStatus]);
  return <><PageHeading eyebrow="RUNNER DATABASE" title="Registrations" accent="management" description="Search runner records, review race selections and monitor payment status." action={<button className="admin-secondary-button" onClick={() => downloadCsv('kfs-registrations.csv', rows.map(r => ({ reference: r.reference || `KFS-${r.id}`, first_name: r.first_name, last_name: r.last_name, email: r.email, phone: r.phone, race: r.race?.distance_label || r.race?.name || r.race_name, kit: r.kit_type, shirt_size: r.shirt_size, amount_egp: r.amount, registration_status: r.status, payment_status: r.payment_status, created_at: r.created_at })))}><Download size={15}/> Export list</button>}/><section className="admin-panel admin-list-panel"><div className="admin-list-toolbar"><div className="admin-search-box"><Search size={16}/><input placeholder="Search name, email or reference…" value={search} onChange={e => setSearch(e.target.value)}/></div><label className="admin-filter-select"><Filter size={15}/><select value={paymentStatus} onChange={e => setPaymentStatus(e.target.value)}><option value="all">All payments</option><option value="paid">Paid</option><option value="pending">Pending</option><option value="failed">Failed</option></select></label></div>{error && <div className="admin-inline-error">{error}</div>}{loading ? <div className="admin-loading"><span className="admin-spinner"/> Loading registrations…</div> : <RegistrationTable rows={rows.map(r => ({ ...r, runner_name: `${r.first_name || ''} ${r.last_name || ''}`.trim() }))}/>}<div className="admin-table-footer"><span>Showing <b>{rows.length}</b> registration records</span><span>Records are filtered by the connected API when configured.</span></div></section></>;
}

function RegistrationDetailsPage() {
  const { registrationId } = useParams();
  const [registration, setRegistration] = useState(null);
  const [draftStatus, setDraftStatus] = useState('pending');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [downloading, setDownloading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  useEffect(() => {
    let active = true;
    getRegistrationDetails(registrationId).then(result => {
      if (!active) return;
      const data = result.data || result;
      setRegistration(data); setDraftStatus(data.status || 'pending');
    }).catch(e => { if (active) setError(e.message); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [registrationId]);
  async function saveStatus() {
    setSaving(true); setError(''); setSuccess('');
    try {
      const result = await updateRegistrationStatus(registrationId, draftStatus);
      setRegistration(previous => ({ ...previous, ...(result.data || {}), status: draftStatus }));
      setSuccess('Registration status updated.');
    } catch (e) { setError(e.message); }
    finally { setSaving(false); }
  }
  async function downloadDocument() {
    setDownloading(true); setError('');
    try { await downloadRegistrationDocument(registrationId, registration.reference); }
    catch (e) { setError(e.message); }
    finally { setDownloading(false); }
  }
  if (loading) return <div className="admin-loading"><span className="admin-spinner"/> Loading runner details…</div>;
  if (!registration) return <><PageHeading eyebrow="RUNNER DATABASE" title="Registration" accent="details" description="The registration details could not be loaded." action={<Link className="admin-secondary-button" to="/admin/registrations"><ArrowLeft size={15}/> Back to registrations</Link>}/>{error && <div className="admin-inline-error">{error}</div>}</>;
  const fullName = `${registration.first_name || ''} ${registration.last_name || ''}`.trim();
  return <>
    <PageHeading eyebrow="RUNNER DATABASE / DETAILS" title={fullName || 'Runner'} accent="profile" description={`Registration reference ${registration.reference || '—'}. Personal identity details are masked in the dashboard.`} action={<Link className="admin-secondary-button" to="/admin/registrations"><ArrowLeft size={15}/> Back to registrations</Link>}/>
    {error && <div className="admin-inline-error">{error}</div>}{success && <div className="admin-inline-success"><Check size={16}/>{success}</div>}
    <div className="admin-detail-grid">
      <section className="admin-panel admin-detail-panel"><div className="admin-panel-heading"><div><h3>Runner information</h3><p>Contact and race-day details</p></div><StatusPill value={registration.payment_status}/></div><div className="admin-detail-fields">
        <DetailField label="First name" value={registration.first_name}/><DetailField label="Last name" value={registration.last_name}/>
        <DetailField label="Email address" value={registration.email}/><DetailField label="Mobile number" value={registration.phone}/>
        <DetailField label="Date of birth" value={registration.birth_date ? dateText(registration.birth_date) : '—'}/><DetailField label="Gender" value={titleCase(registration.gender)}/>
        <DetailField label="ID number" value={registration.id_number_masked || 'Not provided'}/><DetailField label="Team / club" value={registration.team?.name || 'Individual registration'}/>
        <DetailField label="Race" value={registration.race?.distance_label ? `${registration.race.distance_label} · ${registration.race.name}` : registration.race?.name}/><DetailField label="Package" value={`${titleCase(registration.kit_type)} Kit${registration.shirt_size ? ` · Shirt ${registration.shirt_size}` : ''}`}/>
        <DetailField label="Entry fee" value={currency(registration.amount)}/><DetailField label="Submitted" value={dateText(registration.created_at)}/>
      </div>{registration.document_available && <button className="admin-secondary-button" onClick={downloadDocument} disabled={downloading}><FileText size={15}/>{downloading ? 'Preparing document…' : 'Download submitted document'}</button>}</section>
      <section className="admin-panel admin-detail-panel"><div className="admin-panel-heading"><div><h3>Registration status</h3><p>Operational status can be updated by an administrator.</p></div><StatusPill value={registration.status}/></div><div className="admin-detail-status-form"><label className="admin-field-label">Registration status<select value={draftStatus} onChange={e => setDraftStatus(e.target.value)}><option value="pending">Pending</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option></select></label><button className="admin-primary-button" onClick={saveStatus} disabled={saving || draftStatus === registration.status}>{saving ? 'Saving…' : 'Save status'} <Save size={15}/></button></div><div className="admin-info-callout"><ShieldCheck size={17}/><div><b>Payment status is gateway controlled</b><p>{titleCase(registration.payment_status)} payments can only change after EasyKash’s signed server callback. Updating registration status does not change payment status.</p></div></div></section>
      <section className="admin-panel admin-detail-panel admin-detail-payments"><div className="admin-panel-heading"><div><h3>Payment history</h3><p>Verified transaction records for this runner</p></div><CreditCard size={18} className="admin-panel-heading-icon"/></div>{registration.payments?.length ? <div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Provider</th><th>Payment scope</th><th>Transaction reference</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>{registration.payments.map(payment => <tr key={payment.id}><td>{titleCase(payment.provider)}</td><td>{payment.payment_scope || 'Individual checkout'}</td><td className="admin-mono">{payment.transaction_reference || '—'}</td><td className="admin-amount-cell">{currency(payment.amount)}</td><td><StatusPill value={payment.status}/></td><td className="admin-date-cell">{dateText(payment.created_at)}</td></tr>)}</tbody></table></div> : <div className="admin-empty-state"><CreditCard size={23}/><b>No payment attempts found</b></div>}</section>
    </div>
  </>;
}

function DetailField({ label, value }) {
  return <div className="admin-detail-field"><span>{label}</span><b>{value || '—'}</b></div>;
}

function PaymentsPage() {
  const [payments, setPayments] = useState([]); const [loading, setLoading] = useState(true); const [error, setError] = useState('');
  useEffect(() => { getPayments().then(r => setPayments(r.data || r)).catch(e => setError(e.message)).finally(() => setLoading(false)); }, []);
  const totalPaid = payments.filter(p => p.status === 'paid' || p.status === 'succeeded').reduce((s,p) => s + Number(p.amount || 0),0);
  return <><PageHeading eyebrow="FINANCIAL OPERATIONS" title="Payments" accent="& transactions" description="Track payment attempts and provider references. Confirmations must come from a verified EasyCash callback."/><div className="admin-metrics-grid admin-payment-metrics"><MetricCard label="Payment records" value={payments.length} change="All attempts" note="loaded records" icon={CreditCard}/><MetricCard label="Paid amount" value={currency(totalPaid)} change="Verified statuses" note="paid records only" icon={CircleDollarSign} tone="blue"/><MetricCard label="Needs attention" value={payments.filter(p => ['pending','failed'].includes(p.status)).length} change="Pending / failed" note="review required" icon={Activity} tone="sand"/></div><section className="admin-panel admin-list-panel"><div className="admin-panel-heading"><div><h3>Payment transactions</h3><p>EasyCash and other configured providers</p></div><span className="admin-provider-label"><ShieldCheck size={14}/> Gateway status tracking</span></div>{error && <div className="admin-inline-error">{error}</div>}{loading ? <div className="admin-loading"><span className="admin-spinner"/> Loading payments…</div> : <div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Transaction</th><th>Runner</th><th>Provider</th><th>Gateway reference</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>{payments.map(p => <tr key={p.id}><td><b>{p.reference || `PAY-${p.id}`}</b><small className="admin-cell-sub">{p.registration_reference}</small></td><td>{p.runner || '—'}</td><td><span className="admin-provider-pill">{p.provider || 'EasyCash'}</span></td><td className="admin-mono">{p.transaction_reference || '—'}</td><td className="admin-amount-cell">{currency(p.amount)}</td><td><StatusPill value={p.status}/></td><td className="admin-date-cell">{dateText(p.created_at)}</td></tr>)}</tbody></table></div>}</section><div className="admin-info-callout"><ShieldCheck size={17}/><div><b>Payment integrity</b><p>Never mark a payment as paid based only on a browser redirect. The backend should verify the provider callback or perform a server-to-server status check.</p></div></div></>;
}

function PricingPage() {
  const [prices, setPrices] = useState([]); const [draft, setDraft] = useState({}); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(''); const [error, setError] = useState(''); const [success, setSuccess] = useState('');
  useEffect(() => { getRaceKitPrices().then(r => { const data = r.data || r; setPrices(data); setDraft(Object.fromEntries(data.map(p => [p.id, String(p.price)]))); }).catch(e => setError(e.message)).finally(() => setLoading(false)); }, []);
  const byRace = useMemo(() => prices.reduce((acc,p) => { const key = p.race_id; if (!acc[key]) acc[key] = { race_id: p.race_id, race_name: p.race_name, distance_label: p.distance_label, rows: [] }; acc[key].rows.push(p); return acc; }, {}), [prices]);
  async function save(item) { setSaving(String(item.id)); setError(''); setSuccess(''); const price = Number(draft[item.id]); if (!Number.isFinite(price) || price < 0) { setError('Enter a valid, non-negative price.'); setSaving(''); return; } try { const result = await updateRaceKitPrice(item.id, price, item.is_active); setPrices(current => current.map(p => String(p.id) === String(item.id) ? { ...p, ...(result.data || {}), price } : p)); setSuccess(`${item.race_name} · ${titleCase(item.kit_type)} price saved.`); } catch (e) { setError(e.message); } finally { setSaving(''); } }
  return <><PageHeading eyebrow="EVENT CONFIGURATION" title="Races &" accent="pricing" description="Maintain race-kit pricing. Laravel recalculates all totals on the server when the live API is connected."/><div className="admin-info-callout pricing-callout"><Ticket size={17}/><div><b>Pricing is sensitive configuration</b><p>Prices must be greater than 1 EGP for EasyKash checkout. Changes made in demo mode are stored only in this browser. In live mode, changes are saved by the Laravel API and should be restricted to authorized administrators.</p></div></div>{error && <div className="admin-inline-error">{error}</div>}{success && <div className="admin-inline-success"><Check size={16}/>{success}</div>}{loading ? <div className="admin-loading"><span className="admin-spinner"/> Loading race pricing…</div> : <div className="admin-pricing-list">{Object.values(byRace).map(race => <section className="admin-panel admin-pricing-card" key={race.race_id}><div className="admin-pricing-race"><span className="admin-pricing-icon"><Ticket size={18}/></span><div><h3>{race.race_name}</h3><p>{race.distance_label} · Race category</p></div><StatusPill value="active"/></div><div className="admin-pricing-options">{race.rows.map(item => <div className="admin-price-option" key={item.id}><div className="admin-price-title"><span className={`admin-kit-dot ${item.kit_type}`}/><div><b>{titleCase(item.kit_type)} Kit</b><small>{item.kit_type === 'full' ? 'Includes T-shirt and full kit items' : 'Race essentials kit'}</small></div></div><label className="admin-price-input"><span>EGP</span><input type="number" min="1.01" step="0.01" value={draft[item.id] ?? item.price} onChange={e => setDraft(d => ({ ...d, [item.id]: e.target.value }))}/></label><button className="admin-save-price" onClick={() => save(item)} disabled={saving === String(item.id)}>{saving === String(item.id) ? 'Saving…' : 'Save price'}</button></div>)}</div></section>)}</div>}</>;
}

function NotFoundAdmin() { return <div className="admin-empty-page"><Gauge size={32}/><h2>Page not found</h2><p>The admin route you're looking for doesn't exist.</p><Link to="/admin" className="admin-primary-button">Back to overview</Link></div>; }

export default function AdminPortal() {
  return <Routes>
    <Route path="/admin/login" element={<AdminLogin/>}/>
    <Route path="/admin" element={<AdminGate/>}>
      <Route index element={<Dashboard/>}/>
      <Route path="registrations" element={<RegistrationsPage/>}/>
      <Route path="registrations/:registrationId" element={<RegistrationDetailsPage/>}/>
      <Route path="payments" element={<PaymentsPage/>}/>
      <Route path="pricing" element={<PricingManagementPage/>}/>
      <Route path="races" element={<RaceManagementPage/>}/>
      <Route path="team-import" element={<TeamImportPage/>}/>
      <Route path="*" element={<NotFoundAdmin/>}/>
    </Route>
  </Routes>;
}
