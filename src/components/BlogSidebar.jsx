import { useState } from 'react';

const fmt = (d) => (d ? new Date(d.replace(' ', 'T')).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '');
export const postHref = (p) => `/blog/post/?slug=${encodeURIComponent(p.slug)}`;

function Box({ title, children, i = 0 }) {
  return (
    <section className="blog-rise rounded-2xl bg-white p-6 ring-1 ring-gold/20" style={{ '--d': `${0.1 + i * 0.1}s` }}>
      {title && (
        <>
          <h2 className="font-serif text-[22px] text-ink">{title}</h2>
          <span aria-hidden="true" className="mt-2.5 flex items-center gap-1.5"><span className="h-px w-8 bg-gold" /><span className="size-1 rotate-45 bg-gold" /></span>
        </>
      )}
      {children}
    </section>
  );
}

// Blog sidebar: search, categories with counts, recent posts.
// onSearch / onCategory are given on the list page (filter in place); on an article they navigate to /blog.
export default function BlogSidebar({ categories = [], recent = [], query = '', category = '', onSearch, onCategory }) {
  const [q, setQ] = useState(query);

  const submit = (e) => {
    e.preventDefault();
    if (onSearch) onSearch(q.trim());
    else location.href = `/blog${q.trim() ? `?q=${encodeURIComponent(q.trim())}` : ''}`;
  };
  const pick = (slug) => (e) => {
    if (!onCategory) return; // plain link navigation
    e.preventDefault();
    onCategory(slug);
  };
  const total = categories.reduce((n, c) => n + Number(c.count || 0), 0);

  return (
    <aside className="grid content-start gap-6 lg:sticky lg:top-28">
      <Box>
        <form role="search" onSubmit={submit} className="flex gap-2">
          <label htmlFor="blog-q" className="sr-only">Search articles</label>
          <input
            id="blog-q"
            type="search"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Type to start searching…"
            className="min-w-0 flex-1 rounded-lg border border-gold/30 bg-ivory px-4 py-3 text-[15px] text-ink outline-none transition-[border-color,box-shadow] focus:border-gold focus:shadow-[0_0_0_3px_rgb(176_141_87/.18)]"
          />
          <button type="submit" className="rounded-lg bg-navy px-5 text-[13px] font-bold uppercase tracking-[.12em] text-white transition-colors hover:bg-gold hover:text-navy">Search</button>
        </form>
      </Box>

      {categories.length > 0 && (
        <Box title="Categories" i={1}>
          <ul className="mt-4">
            {[{ name: 'All', slug: '', count: total }, ...categories].map((c) => {
              const on = (category || '') === c.slug;
              return (
                <li key={c.slug || 'all'} className="border-b border-gold/15 last:border-0">
                  <a
                    href={c.slug ? `/blog?category=${encodeURIComponent(c.slug)}` : '/blog'}
                    onClick={pick(c.slug)}
                    aria-current={on ? 'true' : undefined}
                    className={`group flex items-center justify-between py-3 text-[15px] transition-colors ${on ? 'font-semibold text-gold' : 'text-body hover:text-navy'}`}
                  >
                    <span className="flex items-center gap-2.5">
                      <span aria-hidden="true" className={`size-1.5 rotate-45 transition-colors ${on ? 'bg-gold' : 'bg-gold/30 group-hover:bg-gold'}`} />
                      {c.name}
                    </span>
                    <span className={`min-w-7 rounded-full px-2 py-0.5 text-center text-[12px] font-bold ${on ? 'bg-gold text-white' : 'bg-ivory text-body/70'}`}>{c.count}</span>
                  </a>
                </li>
              );
            })}
          </ul>
        </Box>
      )}

      {recent.length > 0 && (
        <Box title="Recent posts" i={2}>
          <ul className="mt-5 grid gap-5">
            {recent.map((p) => (
              <li key={p.slug}>
                <a href={postHref(p)} className="group flex items-center gap-4">
                  <span className="relative size-[70px] shrink-0 overflow-hidden rounded-lg bg-navy">
                    {p.cover && <img src={p.cover} alt="" loading="lazy" className="size-full object-cover transition-transform duration-700 group-hover:scale-110" />}
                  </span>
                  <span className="min-w-0">
                    <span className="line-clamp-2 font-serif text-[16px] leading-snug text-ink transition-colors group-hover:text-gold">{p.title}</span>
                    <span className="mt-1 block text-[12px] font-semibold uppercase tracking-[.12em] text-gold">{fmt(p.published_at)}</span>
                  </span>
                </a>
              </li>
            ))}
          </ul>
        </Box>
      )}
    </aside>
  );
}
