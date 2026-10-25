@extends('admin.layout')
@section('title', 'Team roster import · KFS Runners Admin')
@section('section', 'Team Excel import')
@section('content')
<div class="stitch-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">BULK REGISTRATION</span>
        <h1>Team Roster Import</h1>
        <p>Read an Excel roster, validate all runners, then generate a single EasyKash checkout for the entire team.</p>
    </div>
    <div class="stitch-hero-actions">
        <button id="download-template" type="button" class="stitch-btn stitch-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
            Download Excel Template
        </button>
    </div>
</div>

<div class="stitch-callout" style="margin-bottom: 24px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <div>
        <b>One team, one group payment link</b>
        <p>All participants are validated client-side before touching the database. The spreadsheet is parsed in your browser and is never stored. Runner ID numbers are securely encrypted in the database.</p>
    </div>
</div>

<div id="team-error" class="stitch-callout" style="margin-bottom: 24px; border-left-color: #ef4444; background: #fef2f2; color: #991b1b;" role="alert" hidden></div>

<section id="team-result" class="stitch-table-panel" style="margin-bottom: 28px; padding: 28px;" hidden>
    <div style="display: flex; gap: 20px; align-items: flex-start;">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: #ecfdf5; color: #047857; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 22px; font-weight: 800;">
            ✓
        </div>
        <div style="flex: 1;">
            <div class="stitch-eyebrow" id="result-kicker">TEAM ROSTER SAVED</div>
            <h2 id="result-title" style="margin: 4px 0 8px; font-size: 20px; font-weight: 800; color: var(--st-text-primary);">Checkout is ready</h2>
            <p id="result-message" style="margin: 0 0 20px; font-size: 13px; color: var(--st-text-secondary); line-height: 1.5;"></p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; background: var(--st-surface-subtle); padding: 16px; border-radius: var(--st-radius-md); border: 1px solid var(--st-border); margin-bottom: 20px;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--st-text-muted); text-transform: uppercase;">Team / Club</div>
                    <b id="result-team" style="font-size: 14px; color: var(--st-text-primary);"></b>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--st-text-muted); text-transform: uppercase;">Reference</div>
                    <b id="result-reference" style="font-size: 14px; color: var(--st-primary); font-family: monospace;"></b>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--st-text-muted); text-transform: uppercase;">Participants</div>
                    <b id="result-count" style="font-size: 14px; color: var(--st-text-primary);"></b>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--st-text-muted); text-transform: uppercase;">Total</div>
                    <b id="result-amount" style="font-size: 14px; color: #047857;"></b>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: var(--st-text-muted); text-transform: uppercase;">Payment</div>
                    <b id="result-status" style="font-size: 14px; text-transform: uppercase;"></b>
                </div>
            </div>

            <div id="checkout-row" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;" hidden>
                <input id="checkout-link" readonly aria-label="EasyKash checkout link" class="stitch-input" style="flex: 1; min-width: 260px; font-family: monospace; font-size: 12px;">
                <button type="button" id="copy-checkout" class="stitch-btn stitch-btn-secondary">Copy Link</button>
                <a id="open-checkout" class="stitch-btn stitch-btn-primary" target="_blank" rel="noreferrer">Open Checkout ↗</a>
            </div>
        </div>
        <div>
            <button id="new-roster" type="button" class="stitch-btn stitch-btn-secondary">Import Another Team</button>
        </div>
    </div>
</section>

<form id="team-import-form">
    <div class="stitch-table-panel" style="margin-bottom: 24px;">
        <div class="stitch-panel-header">
            <div>
                <h2 class="stitch-panel-title">1. Team Contact Details</h2>
                <p class="stitch-panel-subtitle">EasyKash creates the single payment order using these organizer details.</p>
            </div>
        </div>
        <div style="padding: 24px;">
            <div class="stitch-form-grid">
                <div class="stitch-form-group">
                    <label>Team / Club Name</label>
                    <input required maxlength="150" name="team_name" placeholder="e.g. Kafr El-Sheikh Runners" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Contact Person</label>
                    <input required maxlength="150" name="contact_name" placeholder="Team captain or organizer" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Contact Email</label>
                    <input required type="email" maxlength="190" name="email" placeholder="captain@example.com" class="stitch-input">
                </div>
                <div class="stitch-form-group">
                    <label>Contact Mobile</label>
                    <input required type="tel" maxlength="32" name="phone" placeholder="01012345678" class="stitch-input">
                </div>
            </div>
        </div>
    </div>

    <div class="stitch-table-panel" style="margin-bottom: 24px;">
        <div class="stitch-panel-header">
            <div>
                <h2 class="stitch-panel-title">2. Participants Spreadsheet</h2>
                <p class="stitch-panel-subtitle">Upload the filled Roster sheet from the provided Excel template.</p>
            </div>
        </div>
        <div style="padding: 24px;">
            <label class="stitch-upload-box">
                <input id="roster-file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                <div class="stitch-upload-icon-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                </div>
                <b id="roster-file-name" style="font-size: 15px; color: var(--st-text-primary);">Click or drag & drop Excel roster here</b>
                <span id="roster-file-count" style="font-size: 12px; color: var(--st-text-muted);">XLSX files only · up to 5 MB · 2 to 200 participants</span>
            </label>

            <p style="font-size: 12px; color: var(--st-text-muted); line-height: 1.5; margin-top: 14px;">
                <b>Required headers:</b> <code>first_name, last_name, email, phone, birth_date, gender, id_number, race_slug, kit_type, shirt_size</code>.<br>
                Active race slugs: <b>{{ $races->pluck('slug')->join(', ') ?: 'None configured' }}</b>.
            </p>

            <div id="roster-preview" style="margin-top: 20px;" hidden>
                <div class="stitch-table-container">
                    <table class="stitch-table">
                        <thead>
                            <tr>
                                <th>Runner</th>
                                <th>Email & Phone</th>
                                <th>Race & Kit</th>
                                <th>Birth Date</th>
                            </tr>
                        </thead>
                        <tbody id="roster-preview-rows"></tbody>
                    </table>
                </div>
                <div id="roster-preview-foot" style="padding: 12px 16px; font-size: 12px; font-weight: 600; color: var(--st-text-muted); background: var(--st-surface-subtle); border-top: 1px solid var(--st-border);"></div>
            </div>
        </div>
    </div>

    <div style="background: #ffffff; border-radius: var(--st-radius-lg); padding: 20px 24px; box-shadow: var(--st-shadow-card); display: flex; flex-direction: column; gap: 16px;">
        <label class="stitch-checkbox-label" style="font-size: 13px;">
            <input type="checkbox" id="team-terms" required>
            I confirm that the listed team members agreed to the submitted details, waiver, and marathon terms.
        </label>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--st-border); pt: 14px; padding-top: 14px; flex-wrap: wrap; gap: 14px;">
            <span style="font-size: 12px; color: var(--st-text-muted); max-width: 480px;">
                One group checkout will be generated covering all listed participants. Entries remain pending until EasyKash callback confirmation.
            </span>
            <button class="stitch-btn stitch-btn-primary" id="submit-team" type="submit" disabled style="height: 44px; padding: 0 24px; font-size: 14px;">
                Validate Roster & Create Checkout →
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/exceljs.min.js') }}"></script>
<script>
(() => {
    const headers = ['first_name','last_name','email','phone','birth_date','gender','id_number','race_slug','kit_type','shirt_size'];
    const races = @json($races->map(fn($race) => ['slug' => $race->slug, 'is_active' => $race->is_active])->values());
    const form = document.getElementById('team-import-form');
    const fileInput = document.getElementById('roster-file');
    const submitButton = document.getElementById('submit-team');
    const errorBox = document.getElementById('team-error');
    let members = [], selectedFile = null, busy = false;
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const teamData = () => ({ name: form.elements.team_name.value.trim(), contact_name: form.elements.contact_name.value.trim(), email: form.elements.email.value.trim().toLowerCase(), phone: form.elements.phone.value.trim() });
    function showError(message, rowErrors = []) {
        errorBox.hidden = false;
        errorBox.innerHTML = `<b>${esc(message)}</b>` + (rowErrors.length ? `<ul>${rowErrors.map(item => `<li>Row ${esc(item.row)}: ${esc(item.message)}</li>`).join('')}</ul>` : '');
    }
    function hideError() { errorBox.hidden = true; errorBox.textContent = ''; }
    function updateSubmit() { submitButton.disabled = busy || members.length < 2 || !form.checkValidity(); }
    function excelDate(value) {
        let date = value;
        if (typeof value === 'number' && Number.isFinite(value)) date = new Date(Date.UTC(1899, 11, 30) + Math.round(value * 86400000));
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) return String(value ?? '').trim();
        return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    }
    async function parseFile(file) {
        if (!file || !file.name.toLowerCase().endsWith('.xlsx')) throw new Error('Please choose an .xlsx Excel workbook.');
        if (file.size > 5 * 1024 * 1024) throw new Error('The roster file must be 5 MB or smaller.');
        const workbook = new ExcelJS.Workbook();
        await workbook.xlsx.load(await file.arrayBuffer());
        const sheet = workbook.getWorksheet('Roster') || workbook.worksheets[0];
        if (!sheet) throw new Error('The workbook has no worksheet.');
        const headersInFile = sheet.getRow(1).values.slice(1).map(value => String(value || '').trim().toLowerCase());
        const columns = Object.fromEntries(headersInFile.map((header, index) => [header, index + 1]));
        const missing = headers.filter(header => !columns[header]);
        if (missing.length) throw new Error('Missing required columns: ' + missing.join(', ') + '. Download the template for the correct format.');
        const result = [];
        for (let rowNumber = 2; rowNumber <= sheet.rowCount; rowNumber++) {
            const row = sheet.getRow(rowNumber), values = {};
            let hasValues = false;
            for (const header of headers) {
                const raw = row.getCell(columns[header]).value;
                if (raw && typeof raw === 'object' && !(raw instanceof Date) && ('formula' in raw || 'sharedFormula' in raw)) throw new Error(`Formula cells are not supported (row ${rowNumber}). Enter values directly.`);
                if ((header === 'phone' || header === 'id_number') && typeof raw === 'number') throw new Error(`Phone and ID number cells must be stored as Text (row ${rowNumber}) to preserve leading zeros.`);
                values[header] = header === 'birth_date' && raw !== null && raw !== undefined ? excelDate(raw) : String(raw ?? '').trim();
                if (values[header]) hasValues = true;
            }
            if (hasValues) result.push({ ...values, row_number: rowNumber });
        }
        if (result.length < 2) throw new Error('A team roster must contain at least two participants.');
        if (result.length > 200) throw new Error('A roster can contain up to 200 participants.');
        return result;
    }
    function showPreview() {
        document.getElementById('roster-file-name').textContent = selectedFile?.name || 'Click or drag & drop Excel roster here';
        document.getElementById('roster-file-count').textContent = members.length ? `${members.length} participant rows ready` : 'XLSX only · up to 5 MB · 2–200 participants';
        const preview = document.getElementById('roster-preview');
        preview.hidden = !members.length;
        document.getElementById('roster-preview-rows').innerHTML = members.slice(0, 8).map(member => `<tr><td><b style="color:var(--st-text-primary);">${esc(member.first_name)} ${esc(member.last_name)}</b></td><td><div>${esc(member.email)}</div><small style="color:var(--st-text-muted); font-size:11px;">${esc(member.phone)}</small></td><td><span class="stitch-distance-chip">${esc(member.race_slug)}</span> <span class="stitch-badge status-active" style="font-size:10px;">${esc(member.kit_type)}</span>${member.shirt_size ? ' <span class="stitch-distance-chip">'+esc(member.shirt_size)+'</span>' : ''}</td><td>${esc(member.birth_date)}</td></tr>`).join('');
        document.getElementById('roster-preview-foot').textContent = members.length > 8 ? `Showing the first 8 of ${members.length} participants.` : `${members.length} participants ready to validate.`;
        updateSubmit();
    }
    function addDropdown(sheet, column, name, values, prompt) {
        const optionSheet = sheet.workbook.getWorksheet('Options');
        const colIndex = {F:2,H:1,I:3,J:4}[column];
        values.forEach((value, index) => { optionSheet.getCell(index + 2, colIndex).value = value; });
        const letter = String.fromCharCode(64 + colIndex);
        sheet.workbook.definedNames.add(`Options!$${letter}$2:$${letter}$${Math.max(values.length + 1, 2)}`, name);
        for (let row = 2; row <= 201; row++) sheet.getCell(`${column}${row}`).dataValidation = { type:'list', allowBlank:true, formulae:[`=${name}`], showInputMessage:true, promptTitle:'Choose from the list', prompt, showErrorMessage:true, errorStyle:'error', errorTitle:'Invalid choice', error:'Choose a value from the dropdown list.' };
    }
    document.getElementById('download-template').addEventListener('click', async () => {
        const workbook = new ExcelJS.Workbook(); workbook.creator = 'KFS Runners'; workbook.created = new Date();
        const instructions = workbook.addWorksheet('Instructions'); instructions.columns = [{width:25},{width:105}];
        [['KFS RUNNERS TEAM ROSTER','Fill the Roster sheet, one participant per row. Do not enter a row for the team contact.'],['Required columns',headers.join(', ')],['Date format','Use YYYY-MM-DD. Birth date must be before today.'],['Phone and ID','Format phone and ID cells as Text so leading zeros are preserved. Do not include formulas.'],['Gender values','male, female, prefer_not_to_say'],['Kit values','full or basic. Full Kit requires shirt_size: S, M, L, XL, or XXL.'],['Race slugs',races.map(race => race.slug).join(', ') || 'No active races configured.'],['Dropdowns','Use the dropdown lists in gender, race_slug, kit_type, and shirt_size.'],['Import limit','2 to 200 participants. All participants are checked before anything is saved.']].forEach(row => instructions.addRow(row));
        instructions.getRow(1).font = {bold:true,color:{argb:'FFFFFFFF'}}; instructions.getRow(1).fill = {type:'pattern',pattern:'solid',fgColor:{argb:'FF064B3E'}}; instructions.eachRow(row => row.height = 23);
        const sheet = workbook.addWorksheet('Roster'); sheet.columns = headers.map(header => ({header,key:header,width:header === 'id_number' ? 24 : header === 'email' ? 30 : 18}));
        sheet.getRow(1).height = 25; sheet.getRow(1).font = {bold:true,color:{argb:'FFFFFFFF'}}; sheet.getRow(1).fill = {type:'pattern',pattern:'solid',fgColor:{argb:'FF064B3E'}}; sheet.views = [{state:'frozen',ySplit:1}]; sheet.autoFilter = {from:'A1',to:'J1'}; sheet.getColumn(4).numFmt='@'; sheet.getColumn(7).numFmt='@'; sheet.getColumn(5).numFmt='yyyy-mm-dd';
        const options = workbook.addWorksheet('Options'); options.state = 'hidden';
        addDropdown(sheet,'F','GenderOptions',['male','female','prefer_not_to_say'],'Choose a gender from the list.');
        addDropdown(sheet,'H','RaceSlugOptions',races.map(race => race.slug), 'Choose an active race from the list.');
        addDropdown(sheet,'I','KitTypeOptions',['full','basic'],'Choose full or basic from the list.');
        addDropdown(sheet,'J','ShirtSizeOptions',['S','M','L','XL','XXL'],'Choose a shirt size; leave blank for Basic Kit.');
        const bytes = await workbook.xlsx.writeBuffer(), url = URL.createObjectURL(new Blob([bytes],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}));
        const anchor = document.createElement('a'); anchor.href = url; anchor.download = 'kfs-team-roster-template.xlsx'; anchor.click(); URL.revokeObjectURL(url);
    });
    fileInput.addEventListener('change', async () => {
        selectedFile = fileInput.files?.[0] || null; members = []; hideError();
        if (!selectedFile) { showPreview(); return; }
        document.getElementById('roster-file-name').textContent = 'Reading workbook…'; submitButton.disabled = true;
        try { members = await parseFile(selectedFile); } catch (error) { showError(error.message); }
        showPreview();
    });
    form.addEventListener('input', updateSubmit);
    form.addEventListener('submit', async event => {
        event.preventDefault(); hideError();
        if (!form.reportValidity() || members.length < 2) return;
        busy = true; submitButton.disabled = true; submitButton.textContent = 'Validating roster and creating checkout…';
        try {
            const team = teamData();
            const normalizedMembers = members.map(member => Object.fromEntries(headers.map(header => [header, header === 'email' ? String(member[header]).trim().toLowerCase() : String(member[header]).trim()])));
            const canonicalTeam = {...team, phone: team.phone.trim()};
            const canonical = JSON.stringify({team:canonicalTeam,members:normalizedMembers.slice().sort((left,right)=>JSON.stringify(left).localeCompare(JSON.stringify(right)))});
            const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(canonical));
            const requestKey = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2,'0')).join('');
            const response = await fetch('{{ route('admin.team-import.store') }}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({request_key:requestKey,team,members,terms:document.getElementById('team-terms').checked})});
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (payload.data?.reference) {
                    const prior = payload.data;
                    showResult({...prior,checkout_url:prior.checkout_url || '',uncertain:!prior.checkout_url && prior.payment_status !== 'paid',already_imported:true});
                }
                throw Object.assign(new Error(payload.message || `Request failed (${response.status})`), {rowErrors:payload.row_errors || []});
            }
            showResult({...payload.data,checkout_url:payload.payment?.redirect_url || '',uncertain:!payload.payment?.redirect_url});
        } catch (error) { showError(error.message, error.rowErrors || []); }
        finally { busy = false; submitButton.textContent = 'Validate roster & create checkout →'; submitButton.disabled = members.length < 2; }
    });
    function showResult(result) {
        document.getElementById('team-result').hidden = false; form.hidden = true;
        const uncertain = Boolean(result.uncertain);
        document.getElementById('team-result').classList.toggle('uncertain', uncertain);
        document.getElementById('result-kicker').textContent = uncertain ? 'PAYMENT LINK NEEDS REVIEW' : result.already_imported ? 'EXISTING TEAM REFERENCE' : 'TEAM ROSTER SAVED';
        document.getElementById('result-title').textContent = uncertain ? 'Payment not confirmed' : result.already_imported ? 'Roster already imported' : 'Checkout is ready';
        document.getElementById('result-message').textContent = uncertain ? 'The roster may already be saved and EasyKash may have received the request. Do not import it again. Contact support with this reference before retrying.' : result.already_imported ? 'This roster was already imported. Use the saved EasyKash checkout link if available.' : 'Share this hosted EasyKash link with the team contact. Members remain pending until its signed callback confirms payment.';
        document.getElementById('result-team').textContent = result.team_name || teamData().name;
        document.getElementById('result-reference').textContent = result.reference || '—';
        document.getElementById('result-count').textContent = result.member_count ?? members.length;
        document.getElementById('result-amount').textContent = `${result.currency || 'EGP'} ${Number(result.amount || 0).toFixed(2)}`;
        document.getElementById('result-status').textContent = result.payment_status || 'pending';
        const row = document.getElementById('checkout-row'); row.hidden = !result.checkout_url;
        if (result.checkout_url) { document.getElementById('checkout-link').value = result.checkout_url; document.getElementById('open-checkout').href = result.checkout_url; }
        hideError();
    }
    document.getElementById('copy-checkout').addEventListener('click', async () => { await navigator.clipboard.writeText(document.getElementById('checkout-link').value); document.getElementById('copy-checkout').textContent = 'Copied'; });
    document.getElementById('new-roster').addEventListener('click', () => window.location.reload());
})();
</script>
@endpush
