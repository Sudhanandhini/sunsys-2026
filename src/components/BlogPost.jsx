import { useEffect, useMemo, useRef, useState } from 'react';
import { url } from '../lib/url';
import BlogSidebar from './BlogSidebar.jsx';
import { Card } from './BlogList.jsx';

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

// Table of contents. `sidebar`: the sticky box at the foot of the right-hand sidebar (lg up); otherwise the
// collapsible box above the article on smaller screens.
function Toc({ toc, sidebar = false }) {
  const [active, setActive] = useState(toc[0]?.id);

  useEffect(() => {
    if (!sidebar) return; // only the sidebar copy tracks the current section
    const els = toc.map((t) => document.getElementById(t.id)).filter(Boolean);
    // the current section is the last heading that has scrolled past the sticky header
    const io = new IntersectionObserver(() => {
      const passed = els.filter((el) => el.getBoundingClientRect().top < 140);
      setActive((passed.at(-1) ?? els[0])?.id);
    }, { rootMargin: '-120px 0px -60% 0px', threshold: [0, 1] });
    els.forEach((el) => io.observe(el));
    return () => io.disconnect();
  }, [toc, sidebar]);

  const title = (
    <>
      <span className="font-serif text-[22px] text-ink">Table of contents</span>
      <span aria-hidden="true" className="mt-2.5 flex items-center gap-1.5"><span className="h-px w-8 bg-gold" /><span className="size-1 rotate-45 bg-gold" /></span>
    </>
  );
  if (sidebar) {
    return (
      <nav aria-label="Table of contents" className="blog-rise hidden rounded-2xl bg-white p-6 ring-1 ring-gold/20 lg:block" style={{ '--d': '.1s' }}>
        <h2 className="flex flex-col">{title}</h2>
        <TocLinks toc={toc} active={active} />
      </nav>
    );
  }
  return (
    <aside className="blog-rise lg:hidden" style={{ '--d': '.1s' }}>
      <details className="group rounded-2xl bg-white p-6 ring-1 ring-gold/20">
        <summary className="flex cursor-pointer list-none items-start justify-between gap-4 [&::-webkit-details-marker]:hidden">
          <span className="flex flex-col">{title}</span>
          <span aria-hidden="true" className="mt-2 size-2.5 rotate-45 border-b-2 border-r-2 border-navy transition-transform group-open:-rotate-[135deg]" />
        </summary>
        <nav aria-label="Table of contents"><TocLinks toc={toc} active={active} /></nav>
      </details>
    </aside>
  );
}

/**
 * Sticky offset for the article sidebar. When the column fits on screen it pins 112px from the top (below the
 * header); when it is taller (a long table of contents), the offset goes negative so it scrolls with the page
 * until its bottom reaches the bottom of the screen and pins there: everything stays reachable, no inner scrollbar.
 */
function useStickySide() {
  const ref = useRef(null);
  const [top, setTop] = useState(112);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const fit = () => setTop(Math.min(112, window.innerHeight - el.offsetHeight - 24));
    fit();
    const ro = new ResizeObserver(fit);
    ro.observe(el);
    window.addEventListener('resize', fit);
    return () => { ro.disconnect(); window.removeEventListener('resize', fit); };
  });
  return [ref, top];
}

// "Written by" box at the end of an article: photo, name, designation, LinkedIn profile and short description,
// from Admin > Blog posts > Authors.
function AuthorBox({ a }) {
  return (
    <aside aria-label="About the author" className="mt-12 flex flex-col gap-5 rounded-2xl bg-ivory p-6 ring-1 ring-gold/20 sm:flex-row sm:items-start md:p-8">
      {a.photo
        ? <img src={a.photo} alt={a.name} width="96" height="96" loading="lazy" className="size-24 shrink-0 rounded-full object-cover ring-2 ring-gold/40" />
        : <span aria-hidden="true" className="grid size-24 shrink-0 place-items-center rounded-full bg-navy font-serif text-[38px] text-gold-soft">{a.name.charAt(0)}</span>}
      <div className="min-w-0">
        <span className="text-[11px] font-bold uppercase tracking-[.2em] text-gold">Written by</span>
        <p className="mt-1 font-serif text-[26px] leading-tight text-ink">{a.name}</p>
        {a.designation && <p className="mt-1 text-[14px] font-semibold uppercase tracking-[.12em] text-body/80">{a.designation}</p>}
        {a.bio && <p className="mt-4 text-[16px] leading-relaxed">{a.bio}</p>}
        {a.linkedin && (
          <a href={a.linkedin} target="_blank" rel="noopener noreferrer" className="mt-5 inline-flex items-center gap-2 rounded-[2px] border border-navy px-4 py-2.5 text-[12px] font-bold uppercase tracking-[.14em] text-navy transition-colors hover:border-[#0a66c2] hover:bg-[#0a66c2] hover:text-white">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z" /></svg>
            View LinkedIn profile
          </a>
        )}
      </div>
    </aside>
  );
}

// One article, at /blog/<slug> (Apache serves this page for it) or ?slug=, from GET /api/posts.php?slug=. The body HTML is produced server-side from a
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
  const [sideRef, sideTop] = useStickySide();

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
  const side = state.side ?? {};
  const related = side.related ?? [];
  // wider than the site's 1200px container, so the article itself gets more room beside the sidebar
  const wide = 'container-x !max-w-[1440px]';
  return (
    <article>
      <header className="relative overflow-hidden bg-navy text-white">
        <div aria-hidden="true" className="pointer-events-none absolute -right-40 -top-40 size-[34rem] rounded-full bg-gold/15 blur-3xl" />
        <div className={`${wide} relative py-16 md:py-20`}>
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
            <span>{p.author ? `By ${p.author.name}` : 'Sunsys Technologies'}</span>
          </p>
        </div>
      </header>

      <div className="bg-ivory py-14 md:py-20">
        {/* article (wide) + sidebar: table of contents, then recent posts */}
        <div className={`${wide} grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-10 xl:grid-cols-[minmax(0,1fr)_340px] xl:gap-12`}>
          <div className="grid min-w-0 content-start gap-8">
            {body.toc.length > 0 && <Toc toc={body.toc} />}
            <div className="overflow-hidden rounded-2xl bg-white ring-1 ring-gold/20">
              {p.cover && <img src={p.cover} alt={p.title} className="post-cover w-full bg-white object-contain" />}
              <div className="p-7 md:p-12">
                {p.excerpt && <p className="post-in border-l-2 border-gold pl-5 font-serif text-[21px] italic leading-relaxed text-ink/80" style={{ '--d': '.3s' }}>{p.excerpt}</p>}
                <div className="prose-sunsys post-in mt-8" style={{ '--d': '.38s' }} dangerouslySetInnerHTML={{ __html: body.html }} />
                {p.author && <AuthorBox a={p.author} />}
              </div>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-4">
              <a href={url('/blog')} className="border-b border-gold pb-1 text-[13px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">← All articles</a>
              <a href={url('/contact?type=quote')} className="rounded-[2px] bg-navy px-6 py-3 text-[12px] font-bold uppercase tracking-[.14em] text-white transition-colors hover:bg-gold hover:text-navy">Talk to our team</a>
            </div>
          </div>

          {/* sidebar: table of contents (full height, no inner scroll), then recent posts; see useStickySide */}
          <div ref={sideRef} className="grid min-w-0 content-start gap-6 lg:sticky lg:self-start" style={{ top: sideTop }}>
            {body.toc.length > 0 && <Toc toc={body.toc} sidebar />}
            <BlogSidebar recent={side.recent} exclude={p.slug} search={false} sticky={false} />
          </div>
        </div>

        {related.length > 0 && (
          <section aria-labelledby="related-title" className={`${wide} mt-16 md:mt-20`}>
            <div className="flex flex-wrap items-end justify-between gap-4">
              <div>
                <span className="text-[12px] font-bold uppercase tracking-[.2em] text-gold">Keep reading</span>
                <h2 id="related-title" className="mt-2 font-serif text-[30px] leading-tight text-ink md:text-[38px]">Related articles</h2>
              </div>
              {p.category && (
                <a href={url(`/blog?category=${encodeURIComponent(p.category_slug)}`)} className="border-b border-gold pb-1 text-[13px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">
                  More in {p.category} →
                </a>
              )}
            </div>
            <ul className="mt-8 grid gap-7 md:grid-cols-2 lg:grid-cols-3">
              {related.map((r, i) => <Card key={r.slug} p={r} i={i} />)}
            </ul>
          </section>
        )}
      </div>
    </article>
  );
}
