import { useState } from 'react';

export default function Newsletter({ endpoint = '' }) {
  const [email, setEmail] = useState('');
  const [state, setState] = useState('idle');

  const submit = async (e) => {
    e.preventDefault();
    if (!/^\S+@\S+\.\S+$/.test(email)) return setState('invalid');
    setState('sending');
    try {
      if (endpoint) {
        const r = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, type: 'newsletter' }) });
        if (!r.ok) throw new Error();
      }
      setState('done'); setEmail('');
    } catch { setState('error'); }
  };

  if (state === 'done') return <p className="font-head font-semibold text-white">Subscribed. Look out for our next update in your inbox.</p>;

  return (
    <form onSubmit={submit} noValidate className="w-full max-w-md">
      <div className="flex rounded-full bg-white p-1.5">
        <label htmlFor="nl-email" className="sr-only">Email address</label>
        <input
          id="nl-email" type="email" value={email} onChange={(e) => { setEmail(e.target.value); setState('idle'); }}
          placeholder="Your email address" autoComplete="email"
          className="min-w-0 flex-1 rounded-full px-4 text-ink outline-none"
        />
        <button type="submit" className="btn btn-primary !py-2.5" disabled={state === 'sending'}>Subscribe</button>
      </div>
      {state === 'invalid' && <p className="mt-2 text-sm text-white">Enter a valid email address.</p>}
      {state === 'error' && <p className="mt-2 text-sm text-white">Subscription failed. Try again in a moment.</p>}
    </form>
  );
}
