@extends('admin.layout')
@section('title', 'Team roster import · KFS Runners Admin')
@section('section', 'Team Excel import')
@section('content')
<div class="admin-page-heading"><div><div class="admin-eyebrow">BULK REGISTRATION</div><h1>Team roster <span>import</span></h1><p>Read an Excel roster, validate all runners, then create one EasyKash checkout for the group.</p></div><button id="download-template" type="button" class="admin-secondary-button">↓ Download Excel template</button></div>
<div class="admin-info-callout"><span>♢</span><div><b>One team, one payment link</b><p>All participants are validated before the database changes. The spreadsheet is parsed in your browser and is not uploaded or stored. Runner ID numbers are encrypted in the database.</p></div></div>
<div id="team-error" class="admin-inline-error" role="alert" hidden></div>
<section id="team-result" class="admin-panel admin-team-result" hidden><div class="admin-team-result-icon">✓</div><div class="admin-team-result-copy"><div class="admin-eyebrow" id="result-kicker">TEAM ROSTER SAVED</div><h2 id="result-title">Checkout is ready</h2><p id="result-message"></p><div class="admin-team-result-meta"><span>Team / club<b id="result-team"></b></span><span>Team reference<b id="result-reference"></b></span><span>Participants<b id="result-count"></b></span><span>Group total<b id="result-amount"></b></span><span>Payment status<b id="result-status"></b></span></div><div id="checkout-row" class="admin-checkout-link" hidden><input id="checkout-link" readonly aria-label="EasyKash checkout link"><button type="button" id="copy-checkout" class="admin-secondary-button">Copy link</button><a id="open-checkout" class="admin-secondary-button" target="_blank" rel="noreferrer">Open checkout ↗</a></div></div><button id="new-roster" type="button" class="admin-secondary-button admin-new-roster">Import another team</button></section>
<form id="team-import-form">
    <section class="admin-panel admin-form-panel admin-team-contact"><div class="admin-panel-heading"><div><h3>Team contact</h3><p>EasyKash creates one group checkout using this contact’s details.</p></div></div><div class="admin-form-grid"><label>Team / club name<input required maxlength="150" name="team_name" placeholder="e.g. Kafr El-Sheikh Runners"></label><label>Contact person<input required maxlength="150" name="contact_name" placeholder="Team captain or organizer"></label><label>Contact email<input required type="email" maxlength="190" name="email"></label><label>Contact mobile<input required type="tel" maxlength="32" name="phone" placeholder="01012345678"></label></div></section>
    <section class="admin-panel admin-form-panel admin-roster-upload"><div class="admin-panel-heading"><div><h3>Participants spreadsheet</h3><p>Upload the completed Roster sheet from the Excel template.</p></div></div><label class="admin-upload-zone"><input id="roster-file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"><span class="admin-upload-icon">↑</span><b id="roster-file-name">Choose an Excel roster</b><small id="roster-file-count">XLSX only · up to 5 MB · 2–200 participants</small></label><p class="admin-form-hint">Required columns: first_name, last_name, email, phone, birth_date, gender, id_number, race_slug, kit_type, shirt_size. Store phone and ID cells as Text to preserve leading zeros. Active races: {{ $races->pluck('slug')->join(', ') ?: 'None configured' }}.</p><div id="roster-preview" class="admin-table-scroll admin-roster-preview" hidden><table class="admin-table"><thead><tr><th>Runner</th><th>Email / phone</th><th>Race / kit</th><th>Date of birth</th></tr></thead><tbody id="roster-preview-rows"></tbody></table><div id="roster-preview-foot" class="admin-table-footer"></div></div></section>
    <label class="admin-checkbox-field admin-team-terms"><input type="checkbox" id="team-terms" required> I confirm that the listed team members agreed to the submitted details and race terms.</label>
    <div class="admin-team-submit-row"><span>One payment request covers every listed member. Payment stays pending until EasyKash’s signed callback arrives.</span><button class="admin-primary-button" id="submit-team" type="submit" disabled>Validate roster & create checkout →</button></div>
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
        document.getElementById('roster-file-name').textContent = selectedFile?.name || 'Choose an Excel roster';
        document.getElementById('roster-file-count').textContent = members.length ? `${members.length} participant rows ready` : 'XLSX only · up to 5 MB · 2–200 participants';
        const preview = document.getElementById('roster-preview');
        preview.hidden = !members.length;
        document.getElementById('roster-preview-rows').innerHTML = members.slice(0, 8).map(member => `<tr><td>${esc(member.first_name)} ${esc(member.last_name)}</td><td>${esc(member.email)}<small class="admin-cell-sub">${esc(member.phone)}</small></td><td>${esc(member.race_slug)} · ${esc(member.kit_type)}${member.shirt_size ? ' · '+esc(member.shirt_size) : ''}</td><td>${esc(member.birth_date)}</td></tr>`).join('');
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
