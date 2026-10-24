import { useEffect, useMemo, useState } from 'react';
import ExcelJS from 'exceljs';
import { Activity, ArrowRight, Check, Download, ExternalLink, FileSpreadsheet, Pencil, Plus, Save, ShieldCheck, Ticket, Trash2, Upload, Users, X } from 'lucide-react';
import { createRace, createRaceKitPrice, deleteRace, deleteRaceKitPrice, getAdminRaces, getRaceKitPrices, importTeamRoster, isDemoMode, updateRace, updateRaceKitPrice } from './api';

const moneyText = (value, currency = 'EGP') => new Intl.NumberFormat('en-EG', { style: 'currency', currency, maximumFractionDigits: 2 }).format(Number(value || 0));
const titleText = value => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
const TEAM_HEADERS = ['first_name', 'last_name', 'email', 'phone', 'birth_date', 'gender', 'id_number', 'race_slug', 'kit_type', 'shirt_size'];

function PageTitle({ eyebrow, title, accent, description, action }) {
  return <div className="admin-page-heading"><div><div className="admin-eyebrow">{eyebrow}</div><h1>{title} <span>{accent}</span></h1><p>{description}</p></div>{action && <div className="admin-heading-action">{action}</div>}</div>;
}

function Pill({ value }) {
  const status = String(value || 'pending').toLowerCase();
  return <span className={'admin-status status-' + status}>{titleText(status)}</span>;
}

function ActionButton({ children, onClick, disabled, danger = false, title }) {
  return <button type="button" title={title} className={'admin-row-action' + (danger ? ' danger' : '')} onClick={onClick} disabled={disabled}>{children}</button>;
}

export function RaceManagementPage() {
  const [races, setRaces] = useState([]);
  const [form, setForm] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  async function reload() { const response = await getAdminRaces(); setRaces(response.data || response); }
  useEffect(() => { reload().catch(err => setError(err.message)).finally(() => setLoading(false)); }, []);
  function startCreate() {
    setError(''); setSuccess('');
    setForm({ mode: 'create', name: '', slug: '', distance_label: '', category_type: 'individual', sort_order: String(races.length + 1), is_active: true, full_price: '', basic_price: '', currency: 'EGP' });
  }
  function startEdit(race) {
    setError(''); setSuccess('');
    setForm({ mode: 'edit', id: race.id, name: race.name, slug: race.slug, distance_label: race.distance_label, category_type: race.category_type, sort_order: String(race.sort_order ?? 0), is_active: Boolean(race.is_active) });
  }
  async function save(event) {
    event.preventDefault(); setSaving(true); setError(''); setSuccess('');
    const payload = { ...form, sort_order: Number(form.sort_order), slug: form.slug.trim().toLowerCase().replace(/\s+/g, '-') };
    try {
      if (form.mode === 'create') {
        payload.full_price = Number(form.full_price); payload.basic_price = Number(form.basic_price);
        await createRace(payload);
      } else {
        delete payload.mode; delete payload.id;
        await updateRace(form.id, payload);
      }
      await reload(); setForm(null); setSuccess(form.mode === 'create' ? 'Race and its Full / Basic prices were created.' : 'Race details saved.');
    } catch (err) { setError(err.message); }
    finally { setSaving(false); }
  }
  async function remove(race) {
    if (!window.confirm('Delete ' + race.name + '? A race with registrations cannot be deleted.')) return;
    setError(''); setSuccess('');
    try { await deleteRace(race.id); await reload(); setSuccess('Race deleted.'); }
    catch (err) { setError(err.message); }
  }
  return <>
    <PageTitle eyebrow="EVENT CONFIGURATION" title="Race" accent="management" description="Create, edit, activate, and remove the race categories shown on the public registration form." action={<button className="admin-primary-button" onClick={startCreate}><Plus size={16}/> Add race</button>}/>
    {isDemoMode && <div className="admin-demo-inline">Preview mode changes are kept in this browser. Connect Laravel to manage live race data.</div>}
    {error && <div className="admin-inline-error">{error}</div>}{success && <div className="admin-inline-success"><Check size={16}/>{success}</div>}
    {form && <section className="admin-panel admin-form-panel"><div className="admin-panel-heading"><div><h3>{form.mode === 'create' ? 'Create a race' : 'Edit race'}</h3><p>{form.mode === 'create' ? 'New races start with Full and Basic kit prices.' : 'Keep both kit prices active before activating a race.'}</p></div><button className="admin-table-icon" aria-label="Close race editor" onClick={() => setForm(null)}><X size={16}/></button></div><form className="admin-form-grid" onSubmit={save}>
      <label>Race name<input required maxLength="120" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })}/></label>
      <label>URL slug<input required maxLength="120" pattern="[A-Za-z0-9_-]+" value={form.slug} onChange={e => setForm({ ...form, slug: e.target.value })} placeholder="e.g. 21k"/></label>
      <label>Distance label<input required maxLength="40" value={form.distance_label} onChange={e => setForm({ ...form, distance_label: e.target.value })} placeholder="e.g. 21 KM"/></label>
      <label>Category type<select value={form.category_type} onChange={e => setForm({ ...form, category_type: e.target.value })}><option value="individual">Individual</option><option value="family">Family</option><option value="para">Para</option></select></label>
      <label>Sort order<input required type="number" min="0" max="10000" value={form.sort_order} onChange={e => setForm({ ...form, sort_order: e.target.value })}/></label>
      {form.mode === 'create' && <><label>Full Kit price (EGP)<input required type="number" min="1.01" step="0.01" value={form.full_price} onChange={e => setForm({ ...form, full_price: e.target.value })}/></label><label>Basic Kit price (EGP)<input required type="number" min="1.01" step="0.01" value={form.basic_price} onChange={e => setForm({ ...form, basic_price: e.target.value })}/></label></>}
      {form.mode === 'edit' && <label className="admin-checkbox-field"><input type="checkbox" checked={form.is_active} onChange={e => setForm({ ...form, is_active: e.target.checked })}/> Active on public registration</label>}
      <div className="admin-form-actions"><button className="admin-primary-button" disabled={saving}>{saving ? 'Saving…' : form.mode === 'create' ? 'Create race' : 'Save race'} <Save size={15}/></button><button type="button" className="admin-secondary-button" onClick={() => setForm(null)}>Cancel</button></div>
    </form></section>}
    <section className="admin-panel admin-race-crud"><div className="admin-panel-heading"><div><h3>Race categories</h3><p>{races.length} configured race categories</p></div><Activity size={18} className="admin-panel-heading-icon"/></div>{loading ? <div className="admin-loading"><span className="admin-spinner"/> Loading races…</div> : races.length ? <div className="admin-table-scroll"><table className="admin-table"><thead><tr><th>Race</th><th>Slug</th><th>Type</th><th>Kit prices</th><th>Registrations</th><th>Status</th><th/></tr></thead><tbody>{races.map(race => <tr key={race.id}><td><b>{race.name}</b><small className="admin-cell-sub">{race.distance_label}</small></td><td className="admin-mono">{race.slug}</td><td>{titleText(race.category_type)}</td><td>{(race.prices || []).map(price => titleText(price.kit_type) + ' ' + moneyText(price.price)).join(' · ') || 'No prices'}</td><td>{Number(race.registrations_count || 0).toLocaleString()}</td><td><Pill value={race.is_active ? 'active' : 'inactive'}/></td><td className="admin-row-actions"><ActionButton title="Edit race" onClick={() => startEdit(race)}><span className="sr-only">Edit</span><Pencil size={15}/></ActionButton><ActionButton title="Delete race" danger onClick={() => remove(race)}><span className="sr-only">Delete</span><Trash2 size={15}/></ActionButton></td></tr>)}</tbody></table></div> : <div className="admin-empty-state"><Ticket size={24}/><b>No race categories</b><span>Create a race to show it on the public registration form.</span></div>}</section>
  </>;
}

export function PricingManagementPage() {
  const [prices, setPrices] = useState([]);
  const [races, setRaces] = useState([]);
  const [draft, setDraft] = useState({});
  const [activeDraft, setActiveDraft] = useState({});
  const [form, setForm] = useState({ race_id: '', kit_type: 'full', price: '', currency: 'EGP', is_active: true });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  async function reload() {
    const [priceResponse, raceResponse] = await Promise.all([getRaceKitPrices(), getAdminRaces()]);
    const nextPrices = priceResponse.data || priceResponse;
    const nextRaces = raceResponse.data || raceResponse;
    setPrices(nextPrices); setRaces(nextRaces);
    setDraft(Object.fromEntries(nextPrices.map(item => [item.id, String(item.price)])));
    setActiveDraft(Object.fromEntries(nextPrices.map(item => [item.id, Boolean(item.is_active)])));
    setForm(previous => {
      const selectedRace = nextRaces.find(race => String(race.id) === String(previous.race_id)) || nextRaces[0];
      const missing = selectedRace ? ['full', 'basic'].filter(kit => !nextPrices.some(price => String(price.race_id) === String(selectedRace.id) && price.kit_type === kit)) : [];
      return { ...previous, race_id: String(selectedRace?.id || ''), kit_type: missing.includes(previous.kit_type) ? previous.kit_type : (missing[0] || 'full') };
    });
  }
  useEffect(() => { reload().catch(err => setError(err.message)).finally(() => setLoading(false)); }, []);
  const byRace = useMemo(() => prices.reduce((result, price) => {
    const key = String(price.race_id);
    if (!result[key]) result[key] = { race_id: price.race_id, race_name: price.race_name || races.find(race => String(race.id) === key)?.name || 'Race', distance_label: price.distance_label || races.find(race => String(race.id) === key)?.distance_label, rows: [] };
    result[key].rows.push(price); return result;
  }, {}), [prices, races]);
  async function save(priceItem) {
    setSaving(String(priceItem.id)); setError(''); setSuccess('');
    const amount = Number(draft[priceItem.id]);
    if (!Number.isFinite(amount) || amount <= 1) { setError('Price must be greater than 1 EGP to support EasyKash checkout.'); setSaving(''); return; }
    try { await updateRaceKitPrice(priceItem.id, amount, activeDraft[priceItem.id]); await reload(); setSuccess(priceItem.race_name + ' · ' + titleText(priceItem.kit_type) + ' price saved.'); }
    catch (err) { setError(err.message); }
    finally { setSaving(''); }
  }
  async function addPrice(event) {
    event.preventDefault(); setSaving('new'); setError(''); setSuccess('');
    try { await createRaceKitPrice({ ...form, race_id: Number(form.race_id), price: Number(form.price) }); await reload(); setForm(previous => ({ ...previous, price: '' })); setSuccess('New kit price added.'); }
    catch (err) { setError(err.message); }
    finally { setSaving(''); }
  }
  async function remove(price) {
    if (!window.confirm('Delete the ' + titleText(price.kit_type) + ' Kit price for ' + price.race_name + '?')) return;
    setError(''); setSuccess('');
    try { await deleteRaceKitPrice(price.id); await reload(); setSuccess('Race-kit price deleted.'); }
    catch (err) { setError(err.message); }
  }
  const missingPrices = races.flatMap(race => ['full', 'basic'].filter(kit => !(race.prices || []).some(price => price.kit_type === kit) && !prices.some(price => String(price.race_id) === String(race.id) && price.kit_type === kit)).map(kit => ({ race, kit })));
  const selectedRaceMissing = missingPrices.filter(item => String(item.race.id) === String(form.race_id)).map(item => item.kit);
  return <>
    <PageTitle eyebrow="EVENT CONFIGURATION" title="Race &" accent="pricing" description="Add, update, deactivate, and remove race-kit prices used by the registration and payment flows."/>
    <div className="admin-info-callout pricing-callout"><Ticket size={17}/><div><b>Pricing changes update the live checkout amount</b><p>EasyKash prices must be greater than 1 EGP. An active race must keep both Full and Basic prices available. Existing registrations retain their original amounts.</p></div></div>
    {isDemoMode && <div className="admin-demo-inline">Preview mode changes are kept in this browser. Connect Laravel to manage live pricing.</div>}
    {error && <div className="admin-inline-error">{error}</div>}{success && <div className="admin-inline-success"><Check size={16}/>{success}</div>}
    <section className="admin-panel admin-form-panel"><div className="admin-panel-heading"><div><h3>Add a kit price</h3><p>Only races missing this kit type can be selected.</p></div><Plus size={18} className="admin-panel-heading-icon"/></div><form className="admin-form-grid admin-price-create-form" onSubmit={addPrice}>
      <label>Race<select required value={form.race_id} onChange={e => { const raceId = e.target.value; const missing = ['full', 'basic'].filter(kit => !prices.some(price => String(price.race_id) === String(raceId) && price.kit_type === kit)); setForm({ ...form, race_id: raceId, kit_type: missing.includes(form.kit_type) ? form.kit_type : (missing[0] || 'full') }); }}>{races.map(race => <option key={race.id} value={race.id}>{race.name} · {race.distance_label}</option>)}</select></label>
      <label>Kit type<select required value={form.kit_type} onChange={e => setForm({ ...form, kit_type: e.target.value })}><option value="full" disabled={!selectedRaceMissing.includes('full')}>Full Kit{!selectedRaceMissing.includes('full') ? ' (already set)' : ''}</option><option value="basic" disabled={!selectedRaceMissing.includes('basic')}>Basic Kit{!selectedRaceMissing.includes('basic') ? ' (already set)' : ''}</option></select></label>
      <label>Price (EGP)<input required type="number" min="1.01" step="0.01" value={form.price} onChange={e => setForm({ ...form, price: e.target.value })}/></label>
      <label>Currency<input required maxLength="3" minLength="3" value={form.currency} onChange={e => setForm({ ...form, currency: e.target.value.toUpperCase() })}/></label>
      <label className="admin-checkbox-field"><input type="checkbox" checked={form.is_active} onChange={e => setForm({ ...form, is_active: e.target.checked })}/> Active price</label>
      <div className="admin-form-actions"><button className="admin-primary-button" disabled={saving === 'new' || selectedRaceMissing.length === 0}>{saving === 'new' ? 'Adding…' : 'Add price'} <Plus size={15}/></button></div>
      {missingPrices.length === 0 && <div className="admin-form-hint">Every configured race already has a Full and Basic price.</div>}
    </form></section>
    {loading ? <div className="admin-loading"><span className="admin-spinner"/> Loading race pricing…</div> : <div className="admin-pricing-list">{Object.values(byRace).map(race => <section className="admin-panel admin-pricing-card" key={race.race_id}><div className="admin-pricing-race"><span className="admin-pricing-icon"><Ticket size={18}/></span><div><h3>{race.race_name}</h3><p>{race.distance_label} · {races.find(item => String(item.id) === String(race.race_id))?.category_type || 'Race category'}</p></div><Pill value={races.find(item => String(item.id) === String(race.race_id))?.is_active ? 'active' : 'inactive'}/></div><div className="admin-pricing-options">{race.rows.map(item => <div className="admin-price-option" key={item.id}><div className="admin-price-title"><span className={'admin-kit-dot ' + item.kit_type}/><div><b>{titleText(item.kit_type)} Kit</b><small>{item.kit_type === 'full' ? 'Includes T-shirt and full kit items' : 'Race essentials kit'}</small></div></div><label className="admin-price-input"><span>EGP</span><input type="number" min="1.01" step="0.01" value={draft[item.id] ?? item.price} onChange={e => setDraft(previous => ({ ...previous, [item.id]: e.target.value }))}/></label><label className="admin-price-active"><input type="checkbox" checked={activeDraft[item.id] ?? item.is_active} onChange={e => setActiveDraft(previous => ({ ...previous, [item.id]: e.target.checked }))}/> Active</label><div className="admin-price-actions"><button className="admin-save-price" onClick={() => save(item)} disabled={saving === String(item.id)}>{saving === String(item.id) ? 'Saving…' : 'Save changes'}</button><button className="admin-delete-price" title="Delete price" onClick={() => remove(item)}><Trash2 size={14}/><span>Delete</span></button></div></div>)}</div></section>)}{!Object.keys(byRace).length && <div className="admin-panel admin-empty-state"><Ticket size={24}/><b>No kit prices configured</b><span>Add prices above to enable a race category.</span></div>}</div>}
  </>;
}

async function downloadTemplate(races) {
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'KFS Runners'; workbook.created = new Date();
  const instructions = workbook.addWorksheet('Instructions');
  instructions.columns = [{ width: 25 }, { width: 100 }];
  instructions.addRow(['KFS RUNNERS TEAM ROSTER', 'Fill the Roster sheet, one participant per row. Do not enter a row for the team contact.']);
  instructions.addRow(['Required columns', TEAM_HEADERS.join(', ')]);
  instructions.addRow(['Date format', 'Use YYYY-MM-DD. Birth date must be before today.']);
  instructions.addRow(['Phone and ID', 'Format phone and ID number cells as Text so leading zeros are preserved. Do not include formulas.']);
  instructions.addRow(['Gender values', 'male, female, prefer_not_to_say']);
  instructions.addRow(['Kit values', 'full or basic. Full Kit requires shirt_size: S, M, L, XL, or XXL.']);
  instructions.addRow(['Race slugs', races.filter(race => race.is_active).map(race => race.slug).join(', ') || 'Load active race categories in the dashboard first.']);
  instructions.addRow(['Import limit', '2 to 200 participants per team. All participants are checked before anything is saved.']);
  instructions.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } }; instructions.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF064B3E' } };
  instructions.eachRow(row => { row.height = 23; });
  const roster = workbook.addWorksheet('Roster');
  roster.columns = TEAM_HEADERS.map(header => ({ header, key: header, width: header === 'id_number' ? 24 : header === 'email' ? 30 : 18 }));
  roster.getRow(1).height = 25; roster.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } }; roster.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF064B3E' } };
  roster.views = [{ state: 'frozen', ySplit: 1 }]; roster.autoFilter = { from: 'A1', to: 'J1' };
  roster.getColumn(4).numFmt = '@'; roster.getColumn(7).numFmt = '@'; roster.getColumn(5).numFmt = 'yyyy-mm-dd';
  const bytes = await workbook.xlsx.writeBuffer();
  const url = URL.createObjectURL(new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
  const anchor = document.createElement('a'); anchor.href = url; anchor.download = 'kfs-team-roster-template.xlsx'; anchor.click(); URL.revokeObjectURL(url);
}

function formattedExcelDate(value) {
  let date = value;
  if (typeof value === 'number' && Number.isFinite(value)) date = new Date(Date.UTC(1899, 11, 30) + Math.round(value * 86400000));
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return String(value ?? '').trim();
  return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
}

async function parseRosterFile(file) {
  if (!file) throw new Error('Choose a .xlsx roster file first.');
  if (!file.name.toLowerCase().endsWith('.xlsx')) throw new Error('Please upload an .xlsx Excel workbook.');
  if (file.size > 5 * 1024 * 1024) throw new Error('The roster file must be 5 MB or smaller.');
  const workbook = new ExcelJS.Workbook();
  await workbook.xlsx.load(await file.arrayBuffer());
  const sheet = workbook.getWorksheet('Roster') || workbook.worksheets[0];
  if (!sheet) throw new Error('The workbook has no worksheet.');
  const headers = sheet.getRow(1).values.slice(1).map(value => String(value || '').trim().toLowerCase());
  const columns = Object.fromEntries(headers.map((header, index) => [header, index + 1]));
  const missing = TEAM_HEADERS.filter(header => !columns[header]);
  if (missing.length) throw new Error('Missing required columns: ' + missing.join(', ') + '. Download the template for the correct format.');
  const members = [];
  for (let rowNumber = 2; rowNumber <= sheet.rowCount; rowNumber += 1) {
    const row = sheet.getRow(rowNumber);
    const values = {};
    let hasValues = false;
    for (const header of TEAM_HEADERS) {
      const cell = row.getCell(columns[header]);
      const raw = cell.value;
      if (raw && typeof raw === 'object' && !(raw instanceof Date) && ('formula' in raw || 'sharedFormula' in raw)) throw new Error('Formula cells are not supported (row ' + rowNumber + '). Enter values directly.');
      if ((header === 'phone' || header === 'id_number') && typeof raw === 'number') throw new Error('Phone and ID number cells must be stored as Text to preserve leading zeros (row ' + rowNumber + ').');
      let value = header === 'birth_date' && raw !== null && raw !== undefined ? formattedExcelDate(raw) : String(raw ?? '').trim();
      if (value) hasValues = true;
      values[header] = value;
    }
    if (hasValues) members.push({ ...values, row_number: rowNumber });
  }
  if (members.length < 2) throw new Error('A team roster must contain at least two participants.');
  if (members.length > 200) throw new Error('A roster can contain up to 200 participants.');
  return members;
}

async function rosterRequestKey(team, members) {
  if (!globalThis.crypto?.subtle) throw new Error('Secure roster fingerprinting is unavailable in this browser. Open the dashboard over HTTPS and try again.');
  const canonicalTeam = { name: team.name.trim(), contact_name: team.contact_name.trim(), email: team.email.trim().toLowerCase(), phone: team.phone.trim() };
  const canonicalMembers = members.map(member => Object.fromEntries(TEAM_HEADERS.map(header => [header, header === 'email' ? String(member[header]).trim().toLowerCase() : String(member[header]).trim()]))).sort((left, right) => JSON.stringify(left).localeCompare(JSON.stringify(right)));
  const canonical = JSON.stringify({ team: canonicalTeam, members: canonicalMembers });
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(canonical));
  return Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
}

export function TeamImportPage() {
  const [races, setRaces] = useState([]);
  const [team, setTeam] = useState({ name: '', contact_name: '', email: '', phone: '' });
  const [terms, setTerms] = useState(false);
  const [members, setMembers] = useState([]);
  const [fileName, setFileName] = useState('');
  const [busy, setBusy] = useState(false);
  const [reading, setReading] = useState(false);
  const [error, setError] = useState('');
  const [rowErrors, setRowErrors] = useState([]);
  const [result, setResult] = useState(null);
  const [copied, setCopied] = useState(false);
  useEffect(() => { getAdminRaces().then(response => setRaces((response.data || response).filter(race => race.is_active))).catch(err => setError(err.message)); }, []);
  async function chooseFile(file) {
    if (!file) return;
    setReading(true); setError(''); setRowErrors([]); setResult(null); setMembers([]); setFileName(file.name);
    try { setMembers(await parseRosterFile(file)); }
    catch (err) { setError(err.message); }
    finally { setReading(false); }
  }
  function updateTeam(field, value) { setTeam(previous => ({ ...previous, [field]: value })); }
  async function createGroupCheckout(event) {
    event.preventDefault(); setBusy(true); setError(''); setRowErrors([]); setResult(null);
    try {
      const requestKey = await rosterRequestKey(team, members);
      const response = await importTeamRoster({ request_key: requestKey, team, members, terms });
      const data = response.data || response;
      setResult({ ...data, checkout_url: response.payment?.redirect_url || '', uncertain: false });
    } catch (err) {
      if (err.data?.row_errors) setRowErrors(err.data.row_errors);
      if (err.data?.data?.reference) {
        const duplicatePaid = err.data.data.payment_status === 'paid';
        const checkoutUrl = err.data.data.checkout_url || '';
        setResult({ ...err.data.data, checkout_url: checkoutUrl, uncertain: !duplicatePaid && !checkoutUrl, already_imported: true });
      }
      setError(err.message || 'The team roster could not be imported.');
    } finally { setBusy(false); }
  }
  async function copyCheckout() {
    try { await navigator.clipboard.writeText(result.checkout_url); setCopied(true); window.setTimeout(() => setCopied(false), 1800); }
    catch { setError('Clipboard access is unavailable. Select and copy the checkout link manually.'); }
  }
  function reset() { setTeam({ name: '', contact_name: '', email: '', phone: '' }); setTerms(false); setMembers([]); setFileName(''); setError(''); setRowErrors([]); setResult(null); setCopied(false); }
  return <>
    <PageTitle eyebrow="BULK REGISTRATION" title="Team roster" accent="import" description="Read a team roster from Excel, validate all runners, then create one EasyKash checkout for the group." action={<button className="admin-secondary-button" onClick={() => downloadTemplate(races)}><Download size={15}/> Download Excel template</button>}/>
    <div className="admin-info-callout"><ShieldCheck size={17}/><div><b>One team, one payment link</b><p>All participants are validated before the database is changed. The roster is parsed in your browser and sent securely to Laravel; the spreadsheet itself is not uploaded or stored. Runner ID numbers are encrypted in the database.</p></div></div>
    {isDemoMode && <div className="admin-demo-inline">Preview mode can download the spreadsheet template, but team registration and live EasyKash checkout require the Laravel API.</div>}
    {error && <div className="admin-inline-error" role="alert">{error}</div>}{rowErrors.length > 0 && <div className="admin-inline-error"><b>Spreadsheet rows to fix</b><ul>{rowErrors.map((issue, index) => <li key={index}>Row {issue.row}: {issue.message}</li>)}</ul></div>}
    {result ? <section className={'admin-panel admin-team-result ' + (result.uncertain ? 'uncertain' : '')}><div className="admin-team-result-icon">{result.uncertain ? <ShieldCheck size={22}/> : <Check size={22}/>}</div><div className="admin-team-result-copy"><div className="admin-eyebrow">{result.uncertain ? 'PAYMENT LINK NEEDS REVIEW' : result.already_imported ? 'EXISTING TEAM REFERENCE' : 'TEAM ROSTER SAVED'}</div><h2>{result.uncertain ? 'Payment not confirmed' : result.already_imported ? (result.payment_status === 'paid' ? 'Team payment confirmed' : 'Roster already imported') : 'Checkout is ready'}</h2><p>{result.uncertain ? 'The roster may already have been saved and EasyKash may have received the request. Do not import it again. Contact support with this reference before retrying.' : result.already_imported ? (result.checkout_url ? 'This roster was already imported. No new checkout was created; use the saved EasyKash link below.' : 'This roster was already imported. No new checkout was created. Use this reference for payment reconciliation.') : 'Share this hosted EasyKash link with the team contact. Members remain pending until the signed payment callback confirms the group payment.'}</p><div className="admin-team-result-meta">{result.team_name && <span>Team / club <b>{result.team_name}</b></span>}<span>Team reference <b>{result.reference}</b></span><span>Participants <b>{result.member_count ?? '—'}</b></span><span>Group total <b>{moneyText(result.amount, result.currency || 'EGP')}</b></span><span>Payment status <b><Pill value={result.payment_status}/></b></span></div>{result.checkout_url && <div className="admin-checkout-link"><input aria-label="EasyKash checkout link" readOnly value={result.checkout_url}/><button className="admin-secondary-button" onClick={copyCheckout}>{copied ? <Check size={15}/> : <ClipboardCopy size={15}/>} {copied ? 'Copied' : 'Copy link'}</button><a className="admin-secondary-button" href={result.checkout_url} target="_blank" rel="noreferrer"><ExternalLink size={15}/> Open checkout</a></div>}</div><button className="admin-secondary-button admin-new-roster" onClick={reset}>Import another team</button></section> : <form onSubmit={createGroupCheckout}>
      <section className="admin-panel admin-form-panel admin-team-contact"><div className="admin-panel-heading"><div><h3>Team contact</h3><p>EasyKash sends the group checkout under this contact’s details.</p></div><Users size={18} className="admin-panel-heading-icon"/></div><div className="admin-form-grid"><label>Team / club name<input required maxLength="150" value={team.name} onChange={e => updateTeam('name', e.target.value)} placeholder="e.g. Kafr El-Sheikh Runners"/></label><label>Contact person<input required maxLength="150" value={team.contact_name} onChange={e => updateTeam('contact_name', e.target.value)} placeholder="Team captain or organizer"/></label><label>Contact email<input required type="email" maxLength="190" value={team.email} onChange={e => updateTeam('email', e.target.value)}/></label><label>Contact mobile<input required type="tel" maxLength="32" value={team.phone} onChange={e => updateTeam('phone', e.target.value)} placeholder="01012345678"/></label></div></section>
      <section className="admin-panel admin-form-panel admin-roster-upload"><div className="admin-panel-heading"><div><h3>Participants spreadsheet</h3><p>Upload the completed Roster sheet from the Excel template.</p></div><FileSpreadsheet size={19} className="admin-panel-heading-icon"/></div><label className="admin-upload-zone"><input type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" onChange={e => chooseFile(e.target.files?.[0])}/><span className="admin-upload-icon"><Upload size={20}/></span><b>{reading ? 'Reading workbook…' : fileName || 'Choose an Excel roster'}</b><small>{members.length ? members.length + ' participant rows ready' : 'XLSX only · up to 5 MB · 2–200 participants'}</small></label><p className="admin-form-hint">Required columns: {TEAM_HEADERS.join(', ')}. Keep phone and ID cells formatted as Text to preserve leading zeros. Valid races: {races.map(race => race.slug).join(', ') || 'No active races configured'}.</p>
      {members.length > 0 && <div className="admin-table-scroll admin-roster-preview"><table className="admin-table"><thead><tr><th>Runner</th><th>Email / phone</th><th>Race / kit</th><th>Date of birth</th></tr></thead><tbody>{members.slice(0, 8).map((member, index) => <tr key={member.row_number}><td>{member.first_name} {member.last_name}</td><td>{member.email}<small className="admin-cell-sub">{member.phone}</small></td><td>{member.race_slug} · {titleText(member.kit_type)}{member.shirt_size ? ' · ' + member.shirt_size : ''}</td><td>{member.birth_date}</td></tr>)}</tbody></table>{members.length > 8 && <div className="admin-table-footer">Showing first 8 of {members.length} participants.</div>}</div>}</section>
      <label className="admin-checkbox-field admin-team-terms"><input type="checkbox" required checked={terms} onChange={e => setTerms(e.target.checked)}/> I confirm the listed team members agreed to the submitted details and race terms.</label>
      <div className="admin-team-submit-row"><span>One payment request will cover every listed member. No payment will be marked as paid until EasyKash’s signed callback arrives.</span><button className="admin-primary-button" disabled={busy || reading || members.length < 2 || isDemoMode}>{busy ? 'Validating and creating checkout…' : 'Validate roster & create checkout'} <ArrowRight size={16}/></button></div>
    </form>}
  </>;
}
