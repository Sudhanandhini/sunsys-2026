import { useEffect, useState } from 'react';
import { services } from '../data/site';
import { nameInput, phoneInput, emailInput, nameError, phoneError, emailError, textError } from '../lib/validate';

const initial = { name: '', email: '', phone: '', service: '', message: '' };

// Contact page form. Posts JSON to `endpoint` (PUBLIC_FORM_ENDPOINT). Styled like QuoteForm (classes in global.css).
export default function ContactForm({ endpoint = '' }) {
  const [form, setForm] = useState(initial);
  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState('idle'); // idle | sending | sent | error

  useEffect(() => {
    const p = new URLSearchParams(window.location.search);
    const s = p.get('service');
    const role = p.get('role');
    if (role) setForm((f) => ({ ...f, message: role === 'General application' ? 'I would like to work with Sunsys. A little about me: ' : `I would like to apply for the ${role} role. A little about me: ` }));
    else if (s) setForm((f) => ({ ...f, service: s }));
    else if (p.get('type') === 'quote') setForm((f) => ({ ...f, message: 'I would like a quote for ' }));
  }, []);

  const clean = { name: nameInput, phone: phoneInput, email: emailInput };
  const rules = {
    name: nameError,
    email: emailError,
    phone: (v) => phoneError(v, { required: false }),
    message: (v) => textError(v, { min: 10, label: 'how we can help' }),
  };
  // Typing filters out characters a field can't contain; a field already showing an error re-checks live
  const set = (k) => (e) => {
    const v = clean[k] ? clean[k](e.target.value) : e.target.value;
    setForm((f) => ({ ...f, [k]: v }));
    if (errors[k] && rules[k]) setErrors((er) => ({ ...er, [k]: rules[k](v) }));
  };
  const check = (k) => () => rules[k] && form[k] && setErrors((er) => ({ ...er, [k]: rules[k](form[k]) }));

  const validate = () => {
    const e = Object.fromEntries(Object.entries(rules).map(([k, r]) => [k, r(form[k])]).filter(([, m]) => m));
    setErrors(e);
    return !Object.keys(e).length;
  };

  const submit = async (e) => {
    e.preventDefault();
    if (!validate()) return;
    if (!endpoint) return setStatus('error');
    setStatus('sending');
    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });
      if (res.status === 422) {
        const d = await res.json().catch(() => ({}));
        setErrors(d.fields ?? {});
        return setStatus('idle');
      }
      if (!res.ok) throw new Error();
      setStatus('sent');
      setForm(initial);
    } catch {
      setStatus('error');
    }
  };

  if (status === 'sent') {
    return (
      <div className="qf-sent flex min-h-[26rem] flex-col items-center justify-center text-center" role="status">
        <svg viewBox="0 0 52 52" width="64" height="64" aria-hidden="true">
          <circle className="qf-sent-circle" cx="26" cy="26" r="24" fill="none" stroke="#b08d57" strokeWidth="1.5" />
          <path className="qf-sent-check" d="M15 27l7 7 15-16" fill="none" stroke="#062a52" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
        <p className="mt-6 font-serif text-[28px] leading-tight text-ink">Message sent</p>
        <p className="mt-2 max-w-xs text-[15px]">Thanks for writing to us. We reply within one working hour.</p>
        <button type="button" className="mt-6 border-b border-gold pb-1 text-[13px] font-semibold uppercase tracking-[.15em] text-navy hover:text-gold" onClick={() => setStatus('idle')}>
          Send another message
        </button>
      </div>
    );
  }

  const line = (k) => `qf-input peer w-full border-0 border-b bg-transparent px-0 pb-2.5 pt-6 text-[16px] text-ink outline-none transition-colors ${
    errors[k] ? 'border-[#c62828]' : 'border-navy/20 hover:border-navy/40'
  }`;
  const label = 'qf-label pointer-events-none absolute left-0 top-6 origin-left text-[16px] text-body/70 transition-all duration-300';
  const underline = <span aria-hidden="true" className="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-gold transition-transform duration-500 peer-focus:scale-x-100" />;
  const err = (k) => errors[k] && <p id={`cf-${k}-err`} className="mt-1.5 text-[13px] text-[#c62828]">{errors[k]}</p>;
  const aria = (k) => ({ 'aria-invalid': !!errors[k], 'aria-describedby': errors[k] ? `cf-${k}-err` : undefined });

  return (
    <form onSubmit={submit} noValidate className="grid gap-x-6 gap-y-7 sm:grid-cols-2">
      <div className="cf-field relative" style={{ '--i': 0 }}>
        <input id="cf-name" className={line('name')} value={form.name} onChange={set('name')} onBlur={check('name')} autoComplete="name" maxLength={60} placeholder=" " {...aria('name')} />
        <label htmlFor="cf-name" className={label}>Your name</label>
        {underline}
        {err('name')}
      </div>
      <div className="cf-field relative" style={{ '--i': 1 }}>
        <input id="cf-email" type="email" className={line('email')} value={form.email} onChange={set('email')} onBlur={check('email')} autoComplete="email" inputMode="email" placeholder=" " {...aria('email')} />
        <label htmlFor="cf-email" className={label}>Email address</label>
        {underline}
        {err('email')}
      </div>
      <div className="cf-field relative" style={{ '--i': 2 }}>
        <input id="cf-phone" type="tel" className={line('phone')} value={form.phone} onChange={set('phone')} onBlur={check('phone')} autoComplete="tel-national" inputMode="numeric" maxLength={10} placeholder=" " {...aria('phone')} />
        <label htmlFor="cf-phone" className={label}>Phone number <span className="text-body/50">(optional)</span></label>
        {underline}
        {err('phone')}
      </div>
      <div className="cf-field relative" style={{ '--i': 3 }}>
        <select id="cf-service" className={`${line('service')} cursor-pointer appearance-none pr-8`} value={form.service} onChange={set('service')}>
          <option value="">Not sure yet</option>
          {services.map((s) => <option key={s.slug} value={s.title}>{s.title}</option>)}
        </select>
        {/* select always has a value, so its label stays raised */}
        <label htmlFor="cf-service" className="pointer-events-none absolute left-0 top-0 text-[12.5px] font-semibold tracking-[.04em] text-gold">Service</label>
        <svg className="pointer-events-none absolute bottom-3.5 right-1 text-gold" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
        {underline}
      </div>
      <div className="cf-field relative sm:col-span-2" style={{ '--i': 4 }}>
        <textarea id="cf-message" rows={4} className={`${line('message')} resize-none`} value={form.message} onChange={set('message')} onBlur={check('message')} placeholder=" " {...aria('message')} />
        <label htmlFor="cf-message" className={label}>How can we help?</label>
        {underline}
        {err('message')}
      </div>

      <div className="cf-field flex flex-wrap items-center gap-4 pt-2 sm:col-span-2" style={{ '--i': 5 }}>
        <button
          type="submit"
          disabled={status === 'sending'}
          className="qf-btn group relative inline-flex items-center gap-3 overflow-hidden rounded-[2px] bg-navy px-8 py-4 text-[13px] font-bold uppercase tracking-[.14em] text-white shadow-lg shadow-navy/20 transition-transform hover:-translate-y-0.5 disabled:opacity-70"
        >
          <span aria-hidden="true" className="absolute inset-0 origin-left scale-x-0 bg-gold transition-transform duration-500 group-hover:scale-x-100" />
          <span className="relative">{status === 'sending' ? 'Sending…' : 'Send message'}</span>
          <svg className="relative transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
        </button>
        <p className="text-[13px] text-body/70">We reply within one working hour.</p>
        {status === 'error' && (
          <p className="w-full text-[13px] text-[#c62828]" role="alert">Message not sent. Check your connection and try again in a moment.</p>
        )}
      </div>
    </form>
  );
}
