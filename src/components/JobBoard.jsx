import { useEffect, useRef, useState } from 'react';

const pad = (n) => String(n).padStart(2, '0');
const Arrow = ({ className = '' }) => (
  <svg className={className} viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
);

// Open roles from the admin (GET /api/jobs.php), with an application dialog posting to /api/apply.php.
// `initial` is the build-time list, shown until live data arrives (and if the API is unreachable).
export default function JobBoard({ initial = [], api = '/api/jobs.php', applyUrl = '/api/apply.php' }) {
  const [jobs, setJobs] = useState(initial);
  const [open, setOpen] = useState(null); // job object, or { id: 0, title: 'General application' }
  const [detail, setDetail] = useState(null); // expanded job id

  useEffect(() => {
    fetch(api, { headers: { Accept: 'application/json' } })
      .then((r) => (r.ok ? r.json() : Promise.reject()))
      .then((d) => Array.isArray(d.jobs) && setJobs(d.jobs))
      .catch(() => {});
  }, [api]);

  useEffect(() => {
    const onGeneral = () => setOpen({ id: 0, title: 'General application' });
    window.addEventListener('apply:general', onGeneral);
    return () => window.removeEventListener('apply:general', onGeneral);
  }, []);

  return (
    <>
      {jobs.length === 0 ? (
        <p className="border-y border-gold/30 py-12 text-center font-serif text-[22px] text-ink">There are no open roles right now. You can still send us your details below.</p>
      ) : (
        <ul className="border-t border-gold/30">
          {jobs.map((j, i) => (
            <li key={j.id ?? j.title} className="job-row relative border-b border-gold/30" style={{ '--d': `${i * 0.1}s` }}>
              <div className="group relative grid gap-6 py-8 md:grid-cols-[6.5rem_1fr_auto] md:items-center md:gap-8 md:py-10">
                <span aria-hidden="true" className="absolute inset-0 origin-left scale-x-0 bg-ivory transition-transform duration-700 ease-[cubic-bezier(.65,0,.35,1)] group-hover:scale-x-100" />
                <span aria-hidden="true" className="absolute inset-y-0 left-0 w-0.5 origin-top scale-y-0 bg-gold transition-transform duration-500 group-hover:scale-y-100" />
                <span className="relative font-serif text-[44px] font-light italic leading-none text-gold/60 transition-colors duration-500 group-hover:text-gold md:pl-6">{pad(i + 1)}</span>
                <span className="relative min-w-0">
                  <span className="block font-serif text-[26px] leading-tight tracking-[-0.015em] text-ink md:text-[30px]">{j.title}</span>
                  <span className="mt-2 block max-w-[60ch] text-[15.5px] leading-relaxed">{j.summary ?? j.text}</span>
                  <span className="mt-4 flex flex-wrap items-center gap-2">
                    {[j.type, j.experience ?? j.exp, j.location ?? 'Bengaluru'].filter(Boolean).map((t) => (
                      <span key={t} className="border border-gold/35 bg-white/70 px-3 py-1.5 text-[12.5px] text-body">{t}</span>
                    ))}
                    {j.description && j.description !== j.summary && (
                      <button type="button" className="ml-1 border-b border-gold/60 text-[12px] font-bold uppercase tracking-[.14em] text-navy hover:text-gold" aria-expanded={detail === j.id} onClick={() => setDetail(detail === j.id ? null : j.id)}>
                        {detail === j.id ? 'Hide details' : 'Role details'}
                      </button>
                    )}
                  </span>
                  <span className={`job-more grid ${detail === j.id ? 'is-open' : ''}`}>
                    <span className="overflow-hidden"><span className="block whitespace-pre-line pt-5 text-[15px] leading-relaxed">{j.description}</span></span>
                  </span>
                </span>
                <button type="button" onClick={() => setOpen(j)} className="relative inline-flex items-center gap-3 self-start text-[12px] font-bold uppercase tracking-[.16em] text-navy md:self-center md:pr-6">
                  Apply now
                  <span className="grid size-11 place-items-center rounded-full border border-navy/20 transition-all duration-500 group-hover:-rotate-45 group-hover:border-navy group-hover:bg-navy group-hover:text-gold-soft"><Arrow /></span>
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
      {open && <ApplyDialog job={open} jobs={jobs} url={applyUrl} onClose={() => setOpen(null)} />}
    </>
  );
}

function ApplyDialog({ job, jobs, url, onClose }) {
  const ref = useRef(null);
  const [status, setStatus] = useState('idle'); // idle | sending | sent | error
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState('');
  const [fileName, setFileName] = useState('');

  useEffect(() => {
    const d = ref.current;
    d.showModal();
    document.body.style.overflow = 'hidden';
    return () => { document.body.style.overflow = ''; };
  }, []);

  const close = () => { ref.current.close(); };

  const submit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const err = {};
    if (!String(fd.get('name')).trim()) err.name = 'Enter your name.';
    if (!/^\S+@\S+\.\S+$/.test(String(fd.get('email')))) err.email = 'Enter a valid email address.';
    if (!/^[+\d\s-]{7,20}$/.test(String(fd.get('phone')).trim())) err.phone = 'Enter a valid phone number.';
    const cv = fd.get('cv');
    if (cv && cv.size > 5 * 1024 * 1024) err.cv = 'CV must be 5 MB or smaller.';
    setErrors(err);
    if (Object.keys(err).length) return;
    setStatus('sending');
    try {
      const res = await fetch(url, { method: 'POST', body: fd });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) { setErrors(data.fields ?? {}); setMessage(data.error ?? 'Application not sent.'); setStatus('error'); return; }
      setStatus('sent');
    } catch {
      setMessage('Application not sent. Check your connection and try again.');
      setStatus('error');
    }
  };

  const field = (k) => `qf-input peer w-full border-0 border-b bg-transparent px-0 pb-2.5 pt-6 text-[16px] text-ink outline-none transition-colors ${errors[k] ? 'border-[#c62828]' : 'border-navy/20 hover:border-navy/40'}`;
  const label = 'qf-label pointer-events-none absolute left-0 top-6 origin-left text-[16px] text-body/70 transition-all duration-300';
  const line = <span aria-hidden="true" className="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-gold transition-transform duration-500 peer-focus:scale-x-100" />;
  const err = (k) => errors[k] && <p className="mt-1.5 text-[13px] text-[#c62828]">{errors[k]}</p>;

  return (
    <dialog ref={ref} onClose={onClose} onClick={(e) => e.target === ref.current && close()} className="apply-dialog m-auto w-[min(640px,calc(100%-24px))] max-h-[calc(100dvh-24px)] overflow-y-auto bg-transparent p-0 backdrop:bg-navy/70 backdrop:backdrop-blur-sm" aria-labelledby="apply-title">
      <div className="relative bg-white p-7 md:p-10">
        <span aria-hidden="true" className="absolute inset-x-0 top-0 h-1 bg-gold" />
        <button type="button" onClick={close} className="absolute right-4 top-4 grid size-10 place-items-center rounded-full border border-gold/40 text-navy hover:bg-navy hover:text-white" aria-label="Close">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </button>

        {status === 'sent' ? (
          <div className="qf-sent flex min-h-[20rem] flex-col items-center justify-center text-center" role="status">
            <svg viewBox="0 0 52 52" width="64" height="64" aria-hidden="true">
              <circle className="qf-sent-circle" cx="26" cy="26" r="24" fill="none" stroke="#b08d57" strokeWidth="1.5" />
              <path className="qf-sent-check" d="M15 27l7 7 15-16" fill="none" stroke="#062a52" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <p className="mt-6 font-serif text-[28px] leading-tight text-ink">Application received</p>
            <p className="mt-2 max-w-sm text-[15px]">Thank you. Our team will review your details and get back to you if your profile matches.</p>
            <button type="button" onClick={close} className="mt-6 border-b border-gold pb-1 text-[13px] font-semibold uppercase tracking-[.15em] text-navy hover:text-gold">Close</button>
          </div>
        ) : (
          <>
            <p className="text-[11px] font-bold uppercase tracking-[.2em] text-gold">Apply for</p>
            <h2 id="apply-title" className="mt-2 pr-10 font-serif text-[30px] leading-tight text-ink">{job.title}</h2>
            <form onSubmit={submit} noValidate className="mt-6 grid gap-x-6 gap-y-6 sm:grid-cols-2">
              <input type="text" name="website" tabIndex={-1} autoComplete="off" className="hidden" aria-hidden="true" />
              {job.id ? <input type="hidden" name="job_id" value={job.id} /> : (
                <div className="relative sm:col-span-2">
                  <select name="job_id" defaultValue="0" className={`${field('job')} cursor-pointer appearance-none`} id="ap-job">
                    <option value="0">Any suitable role</option>
                    {jobs.filter((j) => j.id).map((j) => <option key={j.id} value={j.id}>{j.title}</option>)}
                  </select>
                  <label htmlFor="ap-job" className="pointer-events-none absolute left-0 top-0 text-[12.5px] font-semibold tracking-[.04em] text-gold">Role</label>
                  {line}
                </div>
              )}
              <div className="relative"><input id="ap-name" name="name" className={field('name')} placeholder=" " autoComplete="name" /><label htmlFor="ap-name" className={label}>Full name</label>{line}{err('name')}</div>
              <div className="relative"><input id="ap-email" name="email" type="email" className={field('email')} placeholder=" " autoComplete="email" /><label htmlFor="ap-email" className={label}>Email address</label>{line}{err('email')}</div>
              <div className="relative"><input id="ap-phone" name="phone" type="tel" className={field('phone')} placeholder=" " autoComplete="tel" /><label htmlFor="ap-phone" className={label}>Phone number</label>{line}{err('phone')}</div>
              <div className="relative"><input id="ap-exp" name="experience" className={field('experience')} placeholder=" " /><label htmlFor="ap-exp" className={label}>Years of experience</label>{line}</div>
              <div className="relative sm:col-span-2"><textarea id="ap-msg" name="message" rows={3} className={`${field('message')} resize-none`} placeholder=" " /><label htmlFor="ap-msg" className={label}>About you <span className="text-body/50">(optional)</span></label>{line}</div>

              <label className={`group relative flex cursor-pointer items-center gap-4 border border-dashed px-5 py-4 transition-colors sm:col-span-2 ${errors.cv ? 'border-[#c62828]' : 'border-gold/50 hover:border-gold hover:bg-ivory'}`}>
                <span className="grid size-11 shrink-0 place-items-center rounded-full bg-navy text-gold-soft">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14" /></svg>
                </span>
                <span className="min-w-0">
                  <span className="block truncate font-semibold text-ink">{fileName || 'Attach your CV'}</span>
                  <span className="block text-[13px] text-body/70">PDF or Word, up to 5 MB</span>
                </span>
                <input type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" className="sr-only" onChange={(e) => setFileName(e.target.files?.[0]?.name ?? '')} />
              </label>
              {errors.cv && <p className="-mt-4 text-[13px] text-[#c62828] sm:col-span-2">{errors.cv}</p>}

              <div className="flex flex-wrap items-center gap-4 pt-1 sm:col-span-2">
                <button type="submit" disabled={status === 'sending'} className="qf-btn group relative inline-flex items-center gap-3 overflow-hidden rounded-[2px] bg-navy px-8 py-4 text-[13px] font-bold uppercase tracking-[.14em] text-white shadow-lg shadow-navy/20 disabled:opacity-70">
                  <span aria-hidden="true" className="absolute inset-0 origin-left scale-x-0 bg-gold transition-transform duration-500 group-hover:scale-x-100" />
                  <span className="relative">{status === 'sending' ? 'Sending…' : 'Submit application'}</span>
                  <Arrow className="relative transition-transform duration-300 group-hover:translate-x-1" />
                </button>
                {status === 'error' && <p className="w-full text-[13px] text-[#c62828]" role="alert">{message}</p>}
              </div>
            </form>
          </>
        )}
      </div>
    </dialog>
  );
}
