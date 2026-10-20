import { useCallback, useEffect, useState } from 'react';
import BlogSidebar, { postHref } from './BlogSidebar.jsx';

const fmt = (d) => (d ? new Date(d.replace(' ', 'T')).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '');
const placeholder = {
  backgroundImage: 'radial-gradient(rgb(217 195 155 / .18) 1px, transparent 1.4px), linear-gradient(150deg, #0b3a6e, #062a52)',
  backgroundSize: '18px 18px, cover',
};

function Card({ p, i }) {
  return (
    <li className="blog-rise h-full" style={{ '--d': `${(i % 2) * 0.1}s` }}>
      <a href={postHref(p)} className="blog-card group flex h-full flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-gold/20 transition-[transform,box-shadow] duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_60px_-30px_rgba(6,42,82,.35)] hover:ring-gold/50">
        <span className="relative block aspect-[16/10] overflow-hidden bg-navy">
          {p.cover
            ? <img src={p.cover} alt="" loading="lazy" className="blog-img size-full object-cover" />
            : <span className="blog-img grid size-full place-items-center" style={placeholder}><span className="font-serif text-[46px] italic text-gold-soft/40">S</span></span>}
          <span aria-hidden="true" className="absolute inset-0 bg-gradient-to-t from-navy/40 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100" />
          {p.category && <span className="absolute left-4 top-4 rounded-md bg-navy px-3 py-1.5 text-[11px] font-bold uppercase tracking-[.14em] text-gold-soft shadow-lg">{p.category}</span>}
        </span>
        <span className="flex flex-1 flex-col p-6 md:p-7">
          <span className="block font-serif text-[22px] leading-snug tracking-[-0.01em] text-ink transition-colors group-hover:text-gold">{p.title}</span>
          <span className="mt-3 line-clamp-3 flex-1 text-[15px] leading-relaxed">{p.excerpt}</span>
          <span className="mt-6 flex items-center justify-between border-t border-gold/20 pt-4">
            <time dateTime={p.published_at} className="text-[12px] font-semibold uppercase tracking-[.14em] text-gold">{fmt(p.published_at)}</time>
            <span className="inline-flex items-center gap-2 text-[12px] font-bold uppercase tracking-[.16em] text-navy">
              Read
              <span className="grid size-9 place-items-center rounded-full border border-navy/15 transition-all duration-500 group-hover:-rotate-45 group-hover:border-navy group-hover:bg-navy group-hover:text-gold-soft">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
              </span>
            </span>
          </span>
        </span>
      </a>
    </li>
  );
}

// Blog index: post grid + sidebar (search, categories, recent). Filters live in the URL (?q=, ?category=).
export default function BlogList({ api = '/api/posts.php' }) {
  const [data, setData] = useState(null); // { posts, categories, recent }
  const [failed, setFailed] = useState(false);
  const [filter, setFilter] = useState({ q: '', category: '' });
  const [loading, setLoading] = useState(true);

  const load = useCallback((f) => {
    setLoading(true);
    const qs = new URLSearchParams(Object.entries(f).filter(([, v]) => v)).toString();
    fetch(`${api}${qs ? `?${qs}` : ''}`, { headers: { Accept: 'application/json' } })
      .then((r) => (r.ok ? r.json() : Promise.reject()))
      .then((d) => { setData(d); setFailed(false); })
      .catch(() => { setFailed(true); setData((x) => x ?? { posts: [], categories: [], recent: [] }); })
      .finally(() => setLoading(false));
  }, [api]);

  useEffect(() => {
    const p = new URLSearchParams(location.search);
    const f = { q: p.get('q') ?? '', category: p.get('category') ?? '' };
    setFilter(f);
    load(f);
  }, [load]);

  const apply = (f) => {
    setFilter(f);
    const qs = new URLSearchParams(Object.entries(f).filter(([, v]) => v)).toString();
    history.replaceState(null, '', `/blog${qs ? `?${qs}` : ''}`);
    load(f);
  };

  const catName = data?.categories?.find((c) => c.slug === filter.category)?.name;
  const posts = data?.posts ?? [];

  return (
    <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-12">
      <div>
        {(filter.q || filter.category) && data && (
          <div className="mb-8 flex flex-wrap items-center gap-3 text-[15px]">
            <span>{posts.length} {posts.length === 1 ? 'article' : 'articles'}</span>
            {filter.category && <span className="rounded-full bg-navy px-3 py-1 text-[12px] font-bold uppercase tracking-[.12em] text-gold-soft">{catName ?? filter.category}</span>}
            {filter.q && <span className="rounded-full bg-white px-3 py-1 text-[13px] ring-1 ring-gold/30">“{filter.q}”</span>}
            <button type="button" onClick={() => apply({ q: '', category: '' })} className="border-b border-gold text-[12px] font-bold uppercase tracking-[.14em] text-navy hover:text-gold">Clear</button>
          </div>
        )}

        {!data ? (
          <ul className="grid gap-8 sm:grid-cols-2" aria-busy="true" aria-label="Loading articles">
            {[0, 1, 2, 3].map((i) => (
              <li key={i} className="animate-pulse overflow-hidden rounded-2xl bg-white ring-1 ring-gold/20">
                <div className="aspect-[16/10] bg-cream" />
                <div className="space-y-3 p-7"><div className="h-6 w-4/5 bg-cream" /><div className="h-4 w-full bg-cream" /><div className="h-4 w-2/3 bg-cream" /></div>
              </li>
            ))}
          </ul>
        ) : posts.length ? (
          <ul className={`grid gap-8 transition-opacity duration-300 sm:grid-cols-2 ${loading ? 'opacity-50' : ''}`}>
            {posts.map((p, i) => <Card key={p.slug} p={p} i={i} />)}
          </ul>
        ) : (
          <div className="rounded-2xl border border-gold/30 bg-white px-8 py-16 text-center">
            <p className="font-serif text-[26px] text-ink">
              {failed ? 'Articles could not be loaded' : filter.q || filter.category ? 'No articles match' : 'New articles are on the way'}
            </p>
            <p className="mx-auto mt-3 max-w-md">
              {failed ? 'Please refresh the page in a moment.' : filter.q || filter.category ? 'Try another word or category.' : 'We are writing practical guides on websites, hosting and growing online. Check back soon.'}
            </p>
          </div>
        )}
      </div>

      {data && (
        <BlogSidebar
          key={filter.q}
          categories={data.categories ?? []}
          recent={data.recent ?? []}
          query={filter.q}
          category={filter.category}
          onSearch={(q) => apply({ ...filter, q })}
          onCategory={(category) => apply({ ...filter, category })}
        />
      )}
    </div>
  );
}
