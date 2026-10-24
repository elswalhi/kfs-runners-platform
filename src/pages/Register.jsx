import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ArrowLeft, ArrowRight, CheckCircle2, ChevronDown, Copy, CreditCard, FileUp, LockKeyhole, Medal, ShieldCheck, Shirt, UserRound, Users } from 'lucide-react';
import { races, kitOptions, money } from '../data/races';
import { getPublicRaces, getRegistrationPaymentStatus, isApiConfigured, submitRegistration } from '../lib/api';
import './payment.css';

const initialValues = {
  firstName: '', lastName: '', email: '', phone: '', birthDate: '', gender: '', idNumber: '',
  race: '21k', kit: 'full', shirtSize: '', teamName: '', terms: false, idFile: null,
};

export default function Register() {
  const [params] = useSearchParams();
  const checkoutReturn = useMemo(() => ({
    reference: params.get('customerReference') || params.get('reference') || '',
    voucher: params.get('voucher') || '',
    status: (params.get('status') || '').toLowerCase(),
  }), [params]);
  const [checkoutReturnReference] = useState(() => {
    const reference = checkoutReturn.reference || window.sessionStorage.getItem('kfs:lastPaymentReference') || '';
    if (reference) window.sessionStorage.removeItem('kfs:lastPaymentReference');
    return reference;
  });
  const [checkoutReturnVoucher] = useState(() => checkoutReturn.voucher);
  const [checkoutReturnStatus] = useState(() => checkoutReturn.status);
  const returningFromCheckout = Boolean(checkoutReturnReference);
  const initialRace = races.some(r => r.id === params.get('race')) ? params.get('race') : '21k';
  const [values, setValues] = useState({ ...initialValues, race: initialRace });
  const [raceOptions, setRaceOptions] = useState(races);
  const [loadingRaceOptions, setLoadingRaceOptions] = useState(isApiConfigured());
  const [raceLoadError, setRaceLoadError] = useState('');
  const [status, setStatus] = useState({ type: '', message: '' });
  const [submitting, setSubmitting] = useState(false);
  const [paymentInfo, setPaymentInfo] = useState(null);
  const [voucherCopied, setVoucherCopied] = useState(false);

  useEffect(() => {
    if (!paymentInfo?.reference || paymentInfo.status !== 'pending') return undefined;
    let active = true;
    const timer = window.setInterval(async () => {
      try {
        const latest = await getRegistrationPaymentStatus(paymentInfo.reference);
        if (!active) return;
        setPaymentInfo(previous => ({ ...previous, ...latest, voucher: latest.voucher || previous?.voucher || null }));
        if (latest.status === 'paid') setStatus({ type: 'success', message: latest.team ? 'Payment confirmed for ' + latest.team.name + '. The team registrations are complete.' : 'Payment confirmed. Your registration is complete.' });
        if (latest.status === 'failed' || latest.status === 'refunded') setStatus({ type: 'error', message: 'The payment could not be completed. Please contact the race team with your registration reference.' });
      } catch {
        // Keep the voucher visible while the next status check retries.
      }
    }, 10000);
    return () => { active = false; window.clearInterval(timer); };
  }, [paymentInfo?.reference, paymentInfo?.status]);

  useEffect(() => {
    if (!isApiConfigured()) return undefined;
    let active = true;
    getPublicRaces()
      .then(serverRaces => {
        if (!active) return;
        const mapped = serverRaces.map(serverRace => {
          const fallback = races.find(r => r.id === serverRace.slug) || races[0];
          const full = serverRace.prices?.find(p => p.kit_type === 'full');
          const basic = serverRace.prices?.find(p => p.kit_type === 'basic');
          if (!full || !basic) throw new Error(`Missing Full Kit or Basic Kit price for ${serverRace.distance_label || serverRace.name}. Configure both prices in the admin dashboard.`);
          return {
            ...fallback,
            id: serverRace.slug,
            distance: serverRace.distance_label,
            title: serverRace.name,
            fullPrice: Number(full.price),
            basicPrice: Number(basic.price),
          };
        });
        if (!mapped.length) throw new Error('No active race categories are configured in Laravel.');
        setRaceOptions(mapped);
        setValues(previous => ({ ...previous, race: mapped.some(r => r.id === previous.race) ? previous.race : mapped[0].id }));
        setRaceLoadError('');
      })
      .catch(error => { if (active) setRaceLoadError(error.message || 'Could not load live race options.'); })
      .finally(() => { if (active) setLoadingRaceOptions(false); });
    return () => { active = false; };
  }, []);

  const selectedRace = raceOptions.find(r => r.id === values.race) || raceOptions[0] || races[0];
  const selectedKit = kitOptions.find(k => k.id === values.kit) || kitOptions[0];
  const price = values.kit === 'full' ? selectedRace.fullPrice : selectedRace.basicPrice;
  const canSubmit = !submitting && !loadingRaceOptions && !raceLoadError;
  const registrationLabel = useMemo(() => `${selectedRace.distance} · ${selectedKit.name}`, [selectedRace, selectedKit]);
  const registrationButtonLabel = submitting
    ? 'Opening EasyKash…'
    : returningFromCheckout && !paymentInfo
      ? 'Checking payment status…'
    : paymentInfo
      ? (paymentInfo.status === 'failed' || paymentInfo.status === 'refunded' ? 'Payment not completed' : paymentInfo.voucher || paymentInfo.status === 'paid' ? 'Registration submitted' : 'Payment status pending')
      : 'Continue to payment';

  useEffect(() => {
    if (!checkoutReturnReference) return undefined;
    let active = true;
    getRegistrationPaymentStatus(checkoutReturnReference)
      .then(latest => {
        if (!active) return;
        setPaymentInfo({
          ...latest,
          checkout_returned: true,
          voucher: latest.voucher || checkoutReturnVoucher || null,
        });
        if (latest.status === 'paid') setStatus({ type: 'success', message: latest.team ? 'EasyKash confirmed payment for ' + latest.team.name + '. The team registrations are complete.' : 'Payment confirmed by EasyKash. Your registration is complete.' });
        else if (latest.status === 'failed' || latest.status === 'refunded') setStatus({ type: 'error', message: 'EasyKash reports that this payment did not complete. Contact the race team with your registration reference.' });
        else setStatus({
          type: 'info',
          message: checkoutReturnStatus === 'success'
            ? 'EasyKash returned a successful payment result. We are confirming it securely.'
            : 'You are back from EasyKash. We are checking the payment confirmation securely.',
        });
      })
      .catch(error => {
        if (active) setStatus({ type: 'error', message: `${error.message || 'We could not load your payment status.'} Keep your registration reference and contact the race team.` });
      });
    return () => { active = false; };
  }, [checkoutReturnReference, checkoutReturnStatus, checkoutReturnVoucher]);

  function update(name, value) {
    setValues(prev => ({ ...prev, [name]: value }));
    if (status.message) setStatus({ type: '', message: '' });
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setSubmitting(true);
    setStatus({ type: '', message: '' });
    const data = new FormData();
    Object.entries(values).forEach(([key, value]) => {
      if (value instanceof File) data.append(key, value);
      else if (value !== null && value !== undefined) data.append(key, String(value));
    });
    data.set('race', selectedRace.id);
    data.set('race_label', selectedRace.distance);
    data.set('kit', selectedKit.id);
    data.set('kit_label', selectedKit.name);
    data.set('amount_preview_egp', String(price));

    try {
      const result = await submitRegistration(data);
      if (result.connected && result.payment?.redirect_url) {
        const reference = result.payment.reference || result.data?.reference;
        if (reference) window.sessionStorage.setItem('kfs:lastPaymentReference', reference);
        window.location.assign(result.payment.redirect_url);
        return;
      }
      if (result.connected && result.payment) {
        setPaymentInfo(result.payment);
        setStatus({ type: 'info', message: result.message || 'Your registration is saved. Continue to EasyKash to choose a payment method.' });
        return;
      }
      setStatus({
        type: 'info',
        message: result.message || 'Your form is ready. Connect the Laravel API and payment endpoint to enable live registration.',
      });
    } catch (error) {
      if (error.data?.reference && error.data?.payment_status === 'pending') {
        setPaymentInfo({ reference: error.data.reference, status: 'pending' });
      }
      const referenceHint = error.data?.reference ? ` Registration reference: ${error.data.reference}.` : '';
      setStatus({ type: 'error', message: `${error.message || 'We could not submit your registration. Please try again.'}${referenceHint}` });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="register-page">
      <section className="register-hero">
        <div className="register-hero-bg" />
        <div className="wrap register-hero-inner">
          <div className="register-intro">
            <Link to="/" className="back-link"><ArrowLeft size={16} /> Back to home</Link>
            <span className="eyebrow-pill"><span className="pulse-dot" /> KFS RUNNERS MARATHON</span>
            <h1>YOUR RACE.<br /><span>YOUR STORY.</span></h1>
            <p>Choose your distance, pick your kit, and take the first step toward your next finish line.</p>
            <div className="register-points"><span><ShieldCheck size={16} /> Secure registration</span><span><Users size={16} /> Solo or team entry</span></div>
          </div>
          <div className="register-summary-top"><span>YOUR SELECTED RACE</span><b>{selectedRace.distance}</b><small>{selectedRace.title}</small><div className="summary-rule" /><span>PACKAGE</span><b>{selectedKit.name}</b><small>{selectedKit.subtitle}</small><div className="summary-price"><small>Ticket price preview</small><strong>{money(price)}</strong></div></div>
        </div>
      </section>

      <section className="registration-workspace">
        <div className="wrap registration-layout">
          <div className="registration-form-col">
            <div className="form-title-row"><div><span className="form-step">STEP 01 / 02</span><h2>RUNNER <span>DETAILS</span></h2><p>Tell us a little about the runner.</p></div><div className="secure-label"><LockKeyhole size={16} /> Your information stays protected</div></div>
            {loadingRaceOptions && <div className="form-alert alert-info" role="status">Loading live race options and prices from the Laravel backend…</div>}
            {raceLoadError && <div className="form-alert alert-error" role="alert">{raceLoadError} Registration is disabled until the race list is available. Check that Laravel is running and seeded.</div>}
            <form className="runner-form" onSubmit={handleSubmit}>
              <div className="form-section-heading"><UserRound size={18}/><span>Personal information</span></div>
              <div className="form-grid two-col">
                <label>First name <b>*</b><input required autoComplete="given-name" value={values.firstName} onChange={e => update('firstName', e.target.value)} placeholder="Enter first name" /></label>
                <label>Last name <b>*</b><input required autoComplete="family-name" value={values.lastName} onChange={e => update('lastName', e.target.value)} placeholder="Enter last name" /></label>
                <label>Email address <b>*</b><input required type="email" autoComplete="email" value={values.email} onChange={e => update('email', e.target.value)} placeholder="you@example.com" /></label>
                <label>Phone number <b>*</b><input required type="tel" autoComplete="tel" value={values.phone} onChange={e => update('phone', e.target.value)} placeholder="010 1234 5678 or +20 10 1234 5678" /><small className="phone-input-hint">Use an Egyptian mobile number. Local and +20 formats are accepted.</small></label>
                <label>Date of birth <b>*</b><input required type="date" value={values.birthDate} onChange={e => update('birthDate', e.target.value)} /></label>
                <label>Gender <b>*</b><span className="select-wrap"><select required value={values.gender} onChange={e => update('gender', e.target.value)}><option value="">Choose gender</option><option value="male">Male</option><option value="female">Female</option><option value="prefer_not_to_say">Prefer not to say</option></select><ChevronDown size={16}/></span></label>
                <label className="full-span">ID / Passport number <b>*</b><input required value={values.idNumber} onChange={e => update('idNumber', e.target.value)} placeholder="Enter ID or passport number" /></label>
              </div>

              <div className="form-section-heading section-heading-spaced"><Medal size={18}/><span>Choose your race</span></div>
              <div className="race-select-grid">
                {raceOptions.map(race => (
                  <button type="button" key={race.id} onClick={() => update('race', race.id)} className={`race-select-card ${values.race === race.id ? 'selected' : ''}`}>
                    <span className="race-select-check">{values.race === race.id ? <CheckCircle2 size={17} /> : <span className="radio-empty" />}</span>
                    <span className="select-distance">{race.distance}</span><strong>{race.title}</strong><small>{race.description}</small>
                  </button>
                ))}
              </div>

              <div className="form-section-heading section-heading-spaced"><Shirt size={18}/><span>Select your kit</span></div>
              <div className="kit-select-grid">
                {kitOptions.map(kit => (
                  <button type="button" key={kit.id} className={`kit-select-card ${values.kit === kit.id ? 'selected' : ''}`} onClick={() => update('kit', kit.id)}>
                    <span className="kit-check">{values.kit === kit.id ? <CheckCircle2 size={18} /> : <span className="radio-empty" />}</span>
                    <span className={`kit-chip ${kit.id}`}>{kit.id === 'full' ? 'MOST COMPLETE' : 'RACE ESSENTIALS'}</span>
                    <strong>{kit.name}</strong><small>{kit.subtitle}</small>
                    <ul>{kit.items.map(item => <li key={item}><CheckCircle2 size={14} />{item}</li>)}</ul>
                    <span className="kit-price">{money(kit.id === 'full' ? selectedRace.fullPrice : selectedRace.basicPrice)}</span>
                  </button>
                ))}
              </div>

              {values.kit === 'full' && <div className="shirt-size-panel"><div className="shirt-size-copy"><Shirt size={20}/><div><b>T-shirt size <span>*</span></b><small>Full Kit includes the official race T-shirt. Select your size.</small></div></div><div className="shirt-size-options">{['S','M','L','XL','XXL'].map(size => <button type="button" className={values.shirtSize === size ? 'size-active' : ''} key={size} onClick={() => update('shirtSize', size)}>{size}</button>)}</div>{!values.shirtSize && <small className="field-hint">Please select a size to continue.</small>}</div>}

              <div className="form-section-heading section-heading-spaced"><FileUp size={18}/><span>Runner document</span><small>Optional for this front-end preview</small></div>
              <label className="upload-drop"><input type="file" accept="image/*,.pdf" onChange={e => update('idFile', e.target.files?.[0] || null)} /><span className="upload-icon"><FileUp size={22}/></span><span><b>{values.idFile ? values.idFile.name : 'Upload ID / passport (optional)'}</b><small>JPG, PNG or PDF · Max 2 MB (backend validation to be added)</small></span></label>
              <label className="team-name-label">Team / running club <small>(optional)</small><input value={values.teamName} onChange={e => update('teamName', e.target.value)} placeholder="e.g. Kafr El-Sheikh Runners" /></label>
              <label className="terms-check"><input type="checkbox" checked={values.terms} onChange={e => update('terms', e.target.checked)} required /><span>I confirm that the details provided are correct and agree to the race terms and conditions. <b>*</b></span></label>
              {status.message && !(paymentInfo?.status === 'pending' && status.type === 'error') && <div className={`form-alert alert-${status.type}`} role="status">{status.message}</div>}
              {paymentInfo && <div className={`cash-voucher ${paymentInfo.status === 'paid' ? 'cash-voucher-paid' : ''} ${paymentInfo.status === 'failed' || paymentInfo.status === 'refunded' ? 'cash-voucher-failed' : ''} ${!paymentInfo.voucher && paymentInfo.status === 'pending' ? 'cash-voucher-pending' : ''}`} aria-live="polite">
                <span className="cash-voucher-kicker">EASYKASH PAYMENT</span>
                <h3>{paymentInfo.status === 'paid' ? 'Payment confirmed' : paymentInfo.status === 'failed' || paymentInfo.status === 'refunded' ? 'Payment not completed' : paymentInfo.voucher ? 'Cash voucher ready' : paymentInfo.checkout_returned ? 'Checking payment status' : 'Registration saved; payment unconfirmed'}</h3>
                {paymentInfo.status !== 'paid' && paymentInfo.status !== 'failed' && paymentInfo.status !== 'refunded' && <>
                  {paymentInfo.voucher ? <>
                    <p>Use this voucher at the payment provider selected in EasyKash.</p>
                    <div className="cash-voucher-code"><strong>{paymentInfo.voucher}</strong><button type="button" aria-label="Copy voucher code" onClick={async () => { try { await navigator.clipboard.writeText(String(paymentInfo.voucher)); setVoucherCopied(true); } catch { setVoucherCopied(false); } }}><Copy size={16}/>{voucherCopied ? 'Copied' : 'Copy'}</button></div>
                    {paymentInfo.expiry_date && <small>Expires: {paymentInfo.expiry_date}</small>}
                  </> : paymentInfo.checkout_returned
                    ? <p>{paymentInfo.team ? 'The roster for ' + paymentInfo.team.name + ' is saved with ' + paymentInfo.team.member_count + ' participants. EasyKash’s signed server confirmation will update this page automatically.' : 'Your registration is saved. EasyKash’s signed server confirmation will update this page automatically.'} Keep your reference while we verify the result.</p>
                    : <p>EasyKash did not return a checkout link, so we cannot confirm whether payment was started. Do not submit another registration. Contact the race team with your reference before trying again.</p>}
                </>}
                <div className="cash-voucher-meta">{paymentInfo.team && <><span>Team</span><b>{paymentInfo.team.name} · {paymentInfo.team.member_count} participants</b></>}<span>{paymentInfo.team ? 'Team reference' : 'Registration reference'}</span><b>{paymentInfo.reference}</b><span>Payment status</span><b>{paymentInfo.status === 'paid' ? 'Paid' : paymentInfo.status === 'failed' || paymentInfo.status === 'refunded' ? 'Not completed' : paymentInfo.voucher ? 'Waiting for payment' : paymentInfo.checkout_returned ? 'Confirming' : 'Unconfirmed'}</b></div>
                <small className="cash-voucher-note">Payment confirmation comes from EasyKash’s signed server callback. Keep your registration reference.</small>
              </div>}
              <button className="btn btn-red form-submit" type="submit" disabled={!canSubmit || returningFromCheckout || Boolean(paymentInfo) || (values.kit === 'full' && !values.shirtSize)}>{registrationButtonLabel} <ArrowRight size={18}/></button>
              <p className="form-fineprint"><LockKeyhole size={13}/> Payment is confirmed by EasyKash’s signed server callback.</p>
            </form>
          </div>

          <aside className="registration-order-col">
            <div className="order-card">
              <div className="order-card-heading"><div><span className="form-step">YOUR ENTRY</span><h3>Registration <span>summary</span></h3></div><span className="order-lock"><LockKeyhole size={16}/></span></div>
              <div className="order-race-image"><div className="order-race-photo"/><div className="order-race-overlay"><span>KFS RUNNERS</span><b>{selectedRace.distance}</b><small>{selectedRace.title}</small></div></div>
              <div className="order-detail-row"><span>Race distance</span><b>{selectedRace.distance}</b></div>
              <div className="order-detail-row"><span>Race category</span><b>{selectedRace.title}</b></div>
              <div className="order-detail-row"><span>Race kit</span><b>{selectedKit.name}</b></div>
              {values.kit === 'full' && <div className="order-detail-row"><span>T-shirt size</span><b>{values.shirtSize || 'Not selected'}</b></div>}
              <div className="order-divider" />
              <div className="order-price-line"><span>Entry fee</span><strong>{money(price)}</strong></div>
              <div className="order-price-total"><span>Total preview</span><strong>{money(price)}</strong></div>
              <p className="order-tax-note">Final price, availability, and payment status will be confirmed by the Laravel backend.</p>
              <div className="order-benefits"><span><ShieldCheck size={15}/> Secure registration</span><span><CreditCard size={15}/> EasyKash checkout</span></div>
            </div>
            <div className="need-help-card"><h4>Need help choosing?</h4><p>Choose the distance that feels right for you. The best race is the one you show up for.</p><Link to="/" className="text-link">Explore the event <ArrowRight size={16}/></Link></div>
          </aside>
        </div>
      </section>
    </main>
  );
}
