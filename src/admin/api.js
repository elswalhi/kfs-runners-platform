import { defaultPrices, demoPayments, demoRegistrations } from './demoData';

const API_BASE = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '');
const DEMO_KEY = 'kfs_admin_demo_session';
const TOKEN_KEY = 'kfs_admin_token';
export const isDemoMode = !API_BASE;
export const hasAdminSession = () => isDemoMode ? sessionStorage.getItem(DEMO_KEY) === '1' : Boolean(localStorage.getItem(TOKEN_KEY));

function money(value) { return Number(value || 0); }
function demoPrices() {
  try {
    const saved = localStorage.getItem('kfs_admin_demo_prices');
    return saved ? JSON.parse(saved) : defaultPrices;
  } catch { return defaultPrices; }
}
function isAdminDemo() { return isDemoMode && sessionStorage.getItem(DEMO_KEY) === '1'; }

async function request(path, options = {}) {
  if (!API_BASE) throw new Error('Laravel API URL is not configured.');
  const token = localStorage.getItem(TOKEN_KEY);
  const headers = { Accept: 'application/json', ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }), ...(token ? { Authorization: `Bearer ${token}` } : {}), ...options.headers };
  const response = await fetch(`${API_BASE}${path}`, { ...options, headers });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 401) { localStorage.removeItem(TOKEN_KEY); window.location.href = '/admin/login'; }
    throw new Error(data.message || `Request failed (${response.status})`);
  }
  return data;
}

export async function adminLogin(email, password, demo = false) {
  if (isDemoMode) {
    if (!demo) throw new Error('API is not configured. Choose “Preview dashboard” to view demo data.');
    sessionStorage.setItem(DEMO_KEY, '1');
    return { demo: true, user: { name: 'KFS Admin', email: 'admin@kfsrunners.test' } };
  }
  const data = await request('/api/admin/login', { method: 'POST', body: JSON.stringify({ email, password }) });
  if (data.token) localStorage.setItem(TOKEN_KEY, data.token);
  return data;
}

export async function adminLogout() {
  if (isDemoMode) sessionStorage.removeItem(DEMO_KEY);
  else {
    try { await request('/api/admin/logout', { method: 'POST', body: JSON.stringify({}) }); } finally { localStorage.removeItem(TOKEN_KEY); }
  }
}

export async function getDashboardSummary() {
  if (isAdminDemo()) {
    const regs = demoRegistrations;
    const paid = regs.filter(r => r.payment_status === 'paid');
    return { data: { total_registrations: 1284, paid_registrations: 1068, pending_payments: 143, failed_payments: 23, total_revenue: 642850, conversion_rate: 83.2, recent_registrations: regs.slice(0, 5), race_breakdown: [{ label: '21 KM', count: 342 }, { label: '10 KM', count: 368 }, { label: '5 KM', count: 291 }, { label: 'Family Run', count: 191 }, { label: 'Para Race', count: 92 }], revenue_trend: [{ label: 'Mon', value: 38 }, { label: 'Tue', value: 54 }, { label: 'Wed', value: 47 }, { label: 'Thu', value: 68 }, { label: 'Fri', value: 59 }, { label: 'Sat', value: 82 }, { label: 'Sun', value: 73 }] } };
  }
  return request('/api/admin/dashboard');
}

export async function getRegistrations(params = {}) {
  if (isAdminDemo()) {
    let data = demoRegistrations;
    if (params.search) { const s = params.search.toLowerCase(); data = data.filter(r => `${r.reference} ${r.first_name} ${r.last_name} ${r.email}`.toLowerCase().includes(s)); }
    if (params.payment_status && params.payment_status !== 'all') data = data.filter(r => r.payment_status === params.payment_status);
    return { data, meta: { current_page: 1, last_page: 1, total: data.length } };
  }
  const qs = new URLSearchParams(Object.entries(params).filter(([,v]) => v !== undefined && v !== '' && v !== 'all'));
  return request(`/api/admin/registrations${qs.size ? `?${qs}` : ''}`);
}

export async function getPayments() {
  if (isAdminDemo()) return { data: demoPayments };
  return request('/api/admin/payments');
}

export async function getRaceKitPrices() {
  if (isAdminDemo()) return { data: demoPrices() };
  return request('/api/admin/race-kit-prices');
}

export async function updateRaceKitPrice(id, price, isActive) {
  if (isAdminDemo()) {
    const next = demoPrices().map(item => String(item.id) === String(id) ? { ...item, price: money(price), is_active: isActive } : item);
    localStorage.setItem('kfs_admin_demo_prices', JSON.stringify(next));
    return { data: next.find(item => String(item.id) === String(id)) };
  }
  return request(`/api/admin/race-kit-prices/${id}`, { method: 'PUT', body: JSON.stringify({ price: money(price), is_active: isActive }) });
}
