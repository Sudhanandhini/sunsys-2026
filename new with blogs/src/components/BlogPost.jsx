import { useEffect, useMemo, useState } from 'react';
import { url } from '../lib/url';

const fmt = (d) => (d ? new Date(d.replace(' ', 'T')).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' }) : '');

/** Give the article's h2 headings ids and list them for the table of contents. */
function withToc(html) {
  const doc = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html');
  const used = new Set();
  const toc = [...doc.querySelectorAll('h2')].map((h) => {
    const text = h.textContent.trim();
    const base = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60) || 'section';
    let id = base;
    for (let n = 2; used.has(id); n++) id = `${base}-${n}`;
    used.add(id);
    h.id = id;
    return { id, text };
  }).filter((t) => t.text);
  return { html: doc.body.firstChild.innerHTML, toc };
}

function TocLinks({ toc, active }) {
  return (
    <ol className="mt-4 grid gap-0.5">
      {toc.map((t) => {
        const on = t.id === active;
        return (
          <li key={t.id}>
            <a
              href={`#${t.id}`}
              aria-current={on ? 'location' : undefined}
              className={`block border-l-2 py-1.5 text-[14px] leading-snug transition-colors pl-4 ${on ? 'border-gold font-semibold text-navy' : 'border-gold/15 text-body hover:border-gold/60 hover:text-navy'}`}
            >
              {t.text}
            </a>
          </li>
        );
      })}
    </ol>
  );
}

// Table of contents: sticky on the left from lg up, a collapsible box above the article on smaller screens
function Toc({ toc }) {
  const [active, setActive] = useState(toc[0]?.id);

  useEffect(() => {
    const els = toc.map((t) => document.getElementById(t.id)).filter(Boolean);
    // the current section is the last heading that has scrolled past the sticky header
    const io = new IntersectionObserver(() => {
      const passed = els.filter((el) => el.getBoundingClientRect().top < 140);
      setActive((passed.at(-1) ?? els[0])?.id);
    }, { rootMargin: '-120px 0px -60% 0px', threshold: [0, 1] });
    els.forEach((el) => io.observe(el));
    return () => io.disconnect();
  }, [toc]);

  const title = (
    <>
      <span className="font-serif text-[22px] text-ink">Table of contents</span>
      <span aria-hidden="true" className="mt-2.5 flex items-center gap-1.5"><span className="h-px w-8 bg-gold" /><span className="size-1 rotate-45 bg-gold" /></span>
    </>
  );
  return (
    <aside className="blog-rise lg:sticky lg:top-28" style={{ '--d': '.1s' }}>
      <nav aria-label="Table of contents" className="hidden max-h-[calc(100vh-9rem)] overflow-y-auto rounded-2xl bg-white p-6 ring-1 ring-gold/20 lg:block">
        <h2 className="flex flex-col">{title}</h2>
        <TocLinks toc={toc} active={active} />
      </nav>
      <details className="group rounded-2xl bg-white p-6 ring-1 ring-gold/20 lg:hidden">
        <summary className="flex cursor-pointer list-none items-start justify-between gap-4 [&::-webkit-details-marker]:hidden">
          <span className="flex flex-col">{title}</span>
          <span aria-hidden="true" className="mt-2 size-2.5 rotate-45 border-b-2 border-r-2 border-navy transition-transform group-open:-rotate-[135deg]" />
        </summary>
        <nav aria-label="Table of contents"><TocLinks toc={toc} active={active} /></nav>
      </details>
    </aside>
  );
}

// One article, at /<slug> (the old WordPress address; Apache serves this page for it) or ?slug=, from GET /api/posts.php?slug=. The body HTML is produced server-side from a
// restricted Markdown subset with all text escaped, so it is safe to insert.
export default function BlogPost({ api = url('/api/posts.php') }) {
  const [state, setState] = useState({ status: 'loading' });

  useEffect(() => {
    const last = decodeURIComponent(location.pathname.replace(/\/+$/, '').split('/').pop());
    const slug = new URLSearchParams(location.search).get('slug') || (last !== 'post' ? last : '');
    if (!slug) return setState({ status: 'missing' });
    // On the live site blog/post/index.php embeds the post (and its SEO tags); fetch it only when it didn't
    const embedded = window.__BLOG_POST__;
    (embedded ? Promise.resolve(embedded) : fetch(`${api}?slug=${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' } })
      .then((r) => (r.status === 404 ? r.json().then((d) => ({ ...d, missing: true })) : r.ok ? r.json() : Promise.reject())))
      .then((d) => {
        if (d.missing) return setState({ status: 'missing', side: d });
        setState({ status: 'ok', post: d.post, side: d });
        document.title = d.post.seo?.title ?? `${d.post.title} | Sunsys Technologies`;
        document.querySelector('meta[name="description"]')?.setAttribute('content', d.post.seo?.description ?? d.post.excerpt);
      })
      .catch(() => setState({ status: 'error' }));
  }, [api]);

  const body = useMemo(() => (state.post ? withToc(state.post.html) : null), [state.post]);

  if (state.status === 'loading') {
    return (
      <div className="mx-auto max-w-3xl animate-pulse px-5 py-24" aria-busy="true">
        <div className="h-4 w-40 bg-cream" />
        <div className="mt-6 h-12 w-full bg-cream" />
        <div className="mt-3 h-12 w-2/3 bg-cream" />
        <div className="mt-12 aspect-[16/9] bg-cream" />
      </div>
    );
  }

  if (state.status !== 'ok') {
    return (
      <div className="mx-auto max-w-xl px-5 py-28 text-center">
        <p className="font-serif text-[34px] text-ink">{state.status === 'error' ? 'This article could not be loaded' : 'Article not found'}</p>
        <p className="mt-3">{state.status === 'error' ? 'Please refresh the page in a moment.' : 'It may have been moved or unpublished.'}</p>
        <a href={url('/blog')} className="mt-8 inline-block border-b border-gold pb-1 text-[13px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">← All articles</a>
      </div>
    );
  }

  const p = state.post;
  return (
    <article>
      <header className="relative overflow-hidden bg-navy text-white">
        <div aria-hidden="true" className="pointer-events-none absolute -right-40 -top-40 size-[34rem] rounded-full bg-gold/15 blur-3xl" />
        <div className="container-x relative py-16 md:py-20">
          <nav aria-label="Breadcrumb" className="post-in flex flex-wrap items-center gap-3 text-[12px] font-semibold uppercase tracking-[.16em] text-white/55">
            <a href={url('/')} className="hover:text-gold-soft">Home</a>
            <span aria-hidden="true" className="size-1 rotate-45 bg-gold-soft/70" />
            <a href={url('/blog')} className="hover:text-gold-soft">Blog</a>
            {p.category && <><span aria-hidden="true" className="size-1 rotate-45 bg-gold-soft/70" /><a href={url(`/blog?category=${encodeURIComponent(p.category_slug)}`)} className="text-gold-soft hover:text-white">{p.category}</a></>}
          </nav>
          <h1 className="post-in mt-6 max-w-4xl font-serif text-[34px] font-light leading-[1.08] tracking-[-0.03em] !text-white md:text-[54px]" style={{ '--d': '.12s' }}>{p.title}</h1>
          <p className="post-in mt-6 flex flex-wrap items-center gap-4 text-[13px] font-semibold uppercase tracking-[.16em] text-white/60" style={{ '--d': '.22s' }}>
            <span className="text-gold-soft">{fmt(p.published_at)}</span>
            <span aria-hidden="true" className="h-px w-8 bg-gold-soft/50" />
            <span>Sunsys Technologies</span>
          </p>
        </div>
      </header>

      <div className="bg-ivory py-14 md:py-20">
        <div className={`container-x grid gap-8 lg:gap-12 ${body.toc.length ? 'lg:grid-cols-[300px_minmax(0,1fr)]' : ''}`}>
          {body.toc.length > 0 && <Toc toc={body.toc} />}
          <div className={`min-w-0 ${body.toc.length ? '' : 'mx-auto w-full max-w-4xl'}`}>
            <div className="overflow-hidden rounded-2xl bg-white ring-1 ring-gold/20">
              {p.cover && <img src={p.cover} alt={p.title} className="post-cover w-full bg-white object-contain" />}
              <div className="p-7 md:p-12">
                {p.excerpt && <p className="post-in border-l-2 border-gold pl-5 font-serif text-[21px] italic leading-relaxed text-ink/80" style={{ '--d': '.3s' }}>{p.excerpt}</p>}
                <div className="prose-sunsys post-in mt-8" style={{ '--d': '.38s' }} dangerouslySetInnerHTML={{ __html: body.html }} />
              </div>
            </div>
            <div className="mt-8 flex flex-wrap items-center justify-between gap-4">
              <a href={url('/blog')} className="border-b border-gold pb-1 text-[13px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">← All articles</a>
              <a href={url('/contact?type=quote')} className="rounded-[2px] bg-navy px-6 py-3 text-[12px] font-bold uppercase tracking-[.14em] text-white transition-colors hover:bg-gold hover:text-navy">Talk to our team</a>
            </div>
          </div>
        </div>
      </div>
    </article>
  );
}
