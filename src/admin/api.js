import { defaultPrices, demoPayments, demoRegistrations, demoRaces } from './demoData';

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
function demoRaceRows() {
  try {
    const saved = localStorage.getItem('kfs_admin_demo_races');
    if (saved) return JSON.parse(saved);
  } catch { /* Use seeded preview rows. */ }
  return demoRaces.map((race, index) => ({
    ...race,
    slug: ['21k', '10k', '5k', 'family', 'para'][index],
    sort_order: index + 1,
    registrations_count: index === 0 ? 342 : 0,
    prices: demoPrices().filter(price => String(price.race_id) === String(race.id)).map(price => ({ ...price, id: `${race.id}-${price.kit_type}` })),
  }));
}
function syncDemoRacePrices() {
  if (!isAdminDemo()) return;
  const prices = demoPrices();
  const races = demoRaceRows().map(race => ({
    ...race,
    prices: prices.filter(price => String(price.race_id) === String(race.id)).map(price => ({ ...price })),
  }));
  localStorage.setItem('kfs_admin_demo_races', JSON.stringify(races));
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
    const error = new Error(data.message || `Request failed (${response.status})`);
    error.data = data;
    error.status = response.status;
    throw error;
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

export async function getRegistrationDetails(id) {
  if (isAdminDemo()) {
    const row = demoRegistrations.find(item => String(item.id) === String(id));
    if (!row) throw new Error('Registration not found in preview data.');
    return { data: { ...row, birth_date: '1994-06-14', gender: 'prefer_not_to_say', id_number_masked: '••••••4821', document_available: false, team: row.team || null, payments: [{ id: `demo-${row.id}`, provider: 'easykash', amount: row.amount, currency: row.currency, status: row.payment_status, transaction_reference: row.reference, created_at: row.created_at }] } };
  }
  return request(`/api/admin/registrations/${id}`);
}

export async function updateRegistrationStatus(id, status) {
  if (isAdminDemo()) return { data: { id, status } };
  return request(`/api/admin/registrations/${id}`, { method: 'PATCH', body: JSON.stringify({ status }) });
}

export async function downloadRegistrationDocument(id, reference) {
  if (isAdminDemo()) throw new Error('Documents are not available in preview data.');
  const token = localStorage.getItem(TOKEN_KEY);
  const response = await fetch(`${API_BASE}/api/admin/registrations/${id}/document`, { headers: { Accept: 'application/octet-stream', ...(token ? { Authorization: `Bearer ${token}` } : {}) } });
  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    throw new Error(data.message || 'Could not download the runner document.');
  }
  const url = URL.createObjectURL(await response.blob());
  const anchor = document.createElement('a');
  anchor.href = url; anchor.download = `runner-document-${reference}`; anchor.click();
  URL.revokeObjectURL(url);
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
    syncDemoRacePrices();
    return { data: next.find(item => String(item.id) === String(id)) };
  }
  return request(`/api/admin/race-kit-prices/${id}`, { method: 'PUT', body: JSON.stringify({ price: money(price), is_active: isActive }) });
}

export async function createRaceKitPrice(data) {
  if (isAdminDemo()) {
    const prices = demoPrices();
    if (prices.some(price => String(price.race_id) === String(data.race_id) && price.kit_type === data.kit_type)) throw new Error('This race already has a price for that kit.');
    const item = { ...data, id: `demo-${Date.now()}`, race_id: Number(data.race_id), price: money(data.price), currency: data.currency || 'EGP', is_active: data.is_active ?? true };
    const next = [...prices, item]; localStorage.setItem('kfs_admin_demo_prices', JSON.stringify(next));
    syncDemoRacePrices();
    return { data: item };
  }
  return request('/api/admin/race-kit-prices', { method: 'POST', body: JSON.stringify(data) });
}

export async function deleteRaceKitPrice(id) {
  if (isAdminDemo()) {
    const next = demoPrices().filter(item => String(item.id) !== String(id));
    localStorage.setItem('kfs_admin_demo_prices', JSON.stringify(next));
    syncDemoRacePrices();
    return { message: 'Race-kit price deleted.' };
  }
  return request(`/api/admin/race-kit-prices/${id}`, { method: 'DELETE' });
}

export async function getAdminRaces() {
  if (isAdminDemo()) return { data: demoRaceRows() };
  return request('/api/admin/races');
}

export async function createRace(data) {
  if (isAdminDemo()) {
    const races = demoRaceRows();
    const race = { ...data, id: Math.max(0, ...races.map(item => Number(item.id) || 0)) + 1, registrations_count: 0, prices: [
      { id: `new-full-${Date.now()}`, race_id: 0, kit_type: 'full', price: money(data.full_price), currency: data.currency || 'EGP', is_active: true },
      { id: `new-basic-${Date.now()}`, race_id: 0, kit_type: 'basic', price: money(data.basic_price), currency: data.currency || 'EGP', is_active: true },
    ] };
    race.prices.forEach(item => { item.race_id = race.id; });
    const next = [...races, race]; localStorage.setItem('kfs_admin_demo_races', JSON.stringify(next));
    const prices = [...demoPrices(), ...race.prices]; localStorage.setItem('kfs_admin_demo_prices', JSON.stringify(prices));
    syncDemoRacePrices();
    return { data: race };
  }
  return request('/api/admin/races', { method: 'POST', body: JSON.stringify(data) });
}

export async function updateRace(id, data) {
  if (isAdminDemo()) {
    const next = demoRaceRows().map(race => String(race.id) === String(id) ? { ...race, ...data } : race);
    localStorage.setItem('kfs_admin_demo_races', JSON.stringify(next));
    return { data: next.find(race => String(race.id) === String(id)) };
  }
  return request(`/api/admin/races/${id}`, { method: 'PUT', body: JSON.stringify(data) });
}

export async function deleteRace(id) {
  if (isAdminDemo()) {
    const race = demoRaceRows().find(item => String(item.id) === String(id));
    if (race?.registrations_count) throw new Error('This race has registrations. Deactivate it instead of deleting it.');
    const next = demoRaceRows().filter(item => String(item.id) !== String(id));
    localStorage.setItem('kfs_admin_demo_races', JSON.stringify(next));
    localStorage.setItem('kfs_admin_demo_prices', JSON.stringify(demoPrices().filter(item => String(item.race_id) !== String(id))));
    syncDemoRacePrices();
    return { message: 'Race deleted.' };
  }
  return request(`/api/admin/races/${id}`, { method: 'DELETE' });
}

export async function importTeamRoster(data) {
  if (isDemoMode) throw new Error('Connect the Laravel API to import a team and create a live EasyKash checkout.');
  return request('/api/admin/team-imports', { method: 'POST', body: JSON.stringify(data) });
}
