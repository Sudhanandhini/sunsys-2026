import { useEffect, useRef, useState } from 'react';

function Counter({ value, suffix, run }) {
  const [n, setN] = useState(value);
  useEffect(() => {
    if (!run) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return setN(value);
    let raf; const start = performance.now(); const dur = 1400;
    const tick = (t) => {
      const p = Math.min((t - start) / dur, 1);
      setN(Math.round(value * (1 - Math.pow(1 - p, 3))));
      if (p < 1) raf = requestAnimationFrame(tick);
    };
    setN(0); raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [run, value]);
  return <>{n.toLocaleString('en-IN')}{suffix}</>;
}

export default function Stats({ items, classic = false, dark = false }) {
  const ref = useRef(null);
  const [run, setRun] = useState(false);
  useEffect(() => {
    const io = new IntersectionObserver(([e]) => e.isIntersecting && (setRun(true), io.disconnect()), { threshold: 0.4 });
    ref.current && io.observe(ref.current);
    return () => io.disconnect();
  }, []);
  return (
    <dl ref={ref} className={`grid grid-cols-2 md:grid-cols-4 md:divide-x ${dark ? 'divide-white/15' : 'divide-line'}`}>
      {items.map((s) => (
        <div key={s.label} className="flex flex-col-reverse items-center px-4 py-8 text-center">
          <dt className={classic ? `mt-3 text-[11px] font-semibold uppercase tracking-[.2em] ${dark ? 'text-white/60' : 'text-gold'}` : 'mt-1 text-[15px] text-body'}>{s.label}</dt>
          <dd className={classic ? `font-serif text-[40px] font-light leading-none tracking-[-0.02em] md:text-[56px] ${dark ? 'text-gold-soft' : 'text-navy'}` : 'font-head text-[34px] font-bold leading-none text-ink md:text-[44px]'}>
            <Counter {...s} run={run} />
          </dd>
        </div>
      ))}
    </dl>
  );
}
