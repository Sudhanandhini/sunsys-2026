import { useState } from 'react';
import { nameInput, phoneInput, emailInput, nameError, phoneError, emailError, textError } from '../lib/validate';

const initial = { name: '', phone: '', email: '', subject: '' };

const fields = [
  { k: 'name', label: 'Your name', type: 'text', auto: 'name', max: 60 },
  { k: 'phone', label: 'Phone number', type: 'tel', auto: 'tel-national', mode: 'numeric', max: 10, hint: '10-digit mobile number' },
  { k: 'email', label: 'Email address', type: 'email', auto: 'email', mode: 'email' },
  { k: 'subject', label: 'Subject', type: 'text', auto: 'off', hint: 'e.g. New website, online store, hosting' },
];

// Short quote request for the home page. Posts JSON to `endpoint` (PUBLIC_FORM_ENDPOINT).
export default function QuoteForm({ endpoint = '' }) {
  const [form, setForm] = useState(initial);
  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState('idle'); // idle | sending | sent | error

  const clean = { name: nameInput, phone: phoneInput, email: emailInput };
  const rules = {
    name: nameError,
    phone: (v) => phoneError(v),
    email: emailError,
    subject: (v) => textError(v, { min: 3, label: 'what it is about' }),
  };
  // Typing filters out characters a field can't contain; a field already showing an error re-checks live
  const set = (k) => (e) => {
    const v = clean[k] ? clean[k](e.target.value) : e.target.value;
    setForm((f) => ({ ...f, [k]: v }));
    if (errors[k]) setErrors((er) => ({ ...er, [k]: rules[k](v) }));
  };
  const check = (k) => () => form[k] && setErrors((er) => ({ ...er, [k]: rules[k](form[k]) }));

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
        body: JSON.stringify({ ...form, message: form.subject, source: 'home-quote' }),
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
      <div className="qf-sent flex min-h-[22rem] flex-col items-center justify-center text-center" role="status">
        <svg viewBox="0 0 52 52" width="64" height="64" aria-hidden="true">
          <circle className="qf-sent-circle" cx="26" cy="26" r="24" fill="none" stroke="#b08d57" strokeWidth="1.5" />
          <path className="qf-sent-check" d="M15 27l7 7 15-16" fill="none" stroke="#062a52" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
        <p className="mt-6 font-serif text-[28px] leading-tight text-ink">Thank you</p>
        <p className="mt-2 max-w-xs text-[15px]">We have your request and will call or email you within one working hour.</p>
        <button type="button" className="mt-6 border-b border-gold pb-1 text-[13px] font-semibold uppercase tracking-[.15em] text-navy hover:text-gold" onClick={() => setStatus('idle')}>
          Send another request
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={submit} noValidate className="grid gap-x-5 gap-y-6 sm:grid-cols-2">
      {fields.map((f) => (
        <div key={f.k} className="qf-field relative" data-qf-field>
          <input
            id={`qf-${f.k}`}
            type={f.type}
            value={form[f.k]}
            onChange={set(f.k)}
            onBlur={check(f.k)}
            autoComplete={f.auto}
            inputMode={f.mode}
            maxLength={f.max}
            placeholder=" "
            aria-invalid={!!errors[f.k]}
            aria-describedby={errors[f.k] ? `qf-${f.k}-err` : f.hint ? `qf-${f.k}-hint` : undefined}
            className={`qf-input peer w-full border-0 border-b bg-transparent px-0 pb-2.5 pt-6 text-[16px] text-ink outline-none transition-colors ${errors[f.k] ? 'border-[#c62828]' : 'border-navy/20 hover:border-navy/40'}`}
          />
          <label htmlFor={`qf-${f.k}`} className="qf-label pointer-events-none absolute left-0 top-6 origin-left text-[16px] text-body/70 transition-all duration-300">
            {f.label}
          </label>
          <span aria-hidden="true" className="qf-line absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-gold transition-transform duration-500 peer-focus:scale-x-100"></span>
          {errors[f.k]
            ? <p id={`qf-${f.k}-err`} className="mt-1.5 text-[13px] text-[#c62828]">{errors[f.k]}</p>
            : f.hint && <p id={`qf-${f.k}-hint`} className="mt-1.5 text-[13px] text-body/60">{f.hint}</p>}
        </div>
      ))}

      <div className="flex flex-wrap items-center gap-4 pt-2 sm:col-span-2" data-qf-field>
        <button
          type="submit"
          disabled={status === 'sending'}
          className="qf-btn group relative inline-flex items-center gap-3 overflow-hidden rounded-[2px] bg-navy px-8 py-4 text-[13px] font-bold uppercase tracking-[.14em] text-white shadow-lg shadow-navy/20 transition-transform hover:-translate-y-0.5 disabled:opacity-70"
        >
          <span aria-hidden="true" className="absolute inset-0 origin-left scale-x-0 bg-gold transition-transform duration-500 group-hover:scale-x-100"></span>
          <span className="relative">{status === 'sending' ? 'Sending…' : 'Request a free quote'}</span>
          <svg className="relative transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
        </button>
        <p className="text-[13px] text-body/70">No obligation. We reply within one working hour.</p>
        {status === 'error' && (
          <p className="w-full text-[13px] text-[#c62828]" role="alert">Request not sent. Check your connection and try again in a moment.</p>
        )}
      </div>
    </form>
  );
}
