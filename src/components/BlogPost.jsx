import { useEffect, useState } from 'react';
import BlogSidebar from './BlogSidebar.jsx';

const fmt = (d) => (d ? new Date(d.replace(' ', 'T')).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' }) : '');

// One article, by ?slug=, from GET /api/posts.php?slug=. The body HTML is produced server-side from a
// restricted Markdown subset with all text escaped, so it is safe to insert.
export default function BlogPost({ api = '/api/posts.php' }) {
  const [state, setState] = useState({ status: 'loading' });

  useEffect(() => {
    const slug = new URLSearchParams(location.search).get('slug');
    if (!slug) return setState({ status: 'missing' });
    fetch(`${api}?slug=${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' } })
      .then((r) => (r.status === 404 ? r.json().then((d) => ({ ...d, missing: true })) : r.ok ? r.json() : Promise.reject()))
      .then((d) => {
        if (d.missing) return setState({ status: 'missing', side: d });
        setState({ status: 'ok', post: d.post, side: d });
        document.title = `${d.post.title} | Sunsys Technologies`;
        document.querySelector('meta[name="description"]')?.setAttribute('content', d.post.excerpt);
      })
      .catch(() => setState({ status: 'error' }));
  }, [api]);

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
        <a href="/blog" className="mt-8 inline-block border-b border-gold pb-1 text-[13px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">← All articles</a>
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
            <a href="/" className="hover:text-gold-soft">Home</a>
            <span aria-hidden="true" className="size-1 rotate-45 bg-gold-soft/70" />
            <a href="/blog" className="hover:text-gold-soft">Blog</a>
            {p.category && <><span aria-hidden="true" className="size-1 rotate-45 bg-gold-soft/70" /><a href={`/blog?category=${encodeURIComponent(p.category_slug)}`} className="text-gold-soft hover:text-white">{p.category}</a></>}
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
        <div className="container-x grid gap-10 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-12">
          <div className="min-w-0">
            <div className="overflow-hidden rounded-2xl bg-white ring-1 ring-gold/20">
              {p.cover && <img src={p.cover} alt="" className="post-cover w-full object-cover" />}
              <div className="p-7 md:p-12">
                {p.excerpt && <p className="post-in border-l-2 border-gold pl-5 font-serif text-[21px] italic leading-relaxed text-ink/80" style={{ '--d': '.3s' }}>{p.excerpt}</p>}
                <div className="prose-sunsys post-in mt-8" style={{ '--d': '.38s' }} dangerouslySetInnerHTML={{ __html: p.html }} />
              </div>
            </div>
            <div className="mt-8 flex flex-wrap items-center justify-between gap-4">
              <a href="/blog" className="border-b border-gold pb-1 text-[13px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">← All articles</a>
              <a href="/contact?type=quote" className="rounded-[2px] bg-navy px-6 py-3 text-[12px] font-bold uppercase tracking-[.14em] text-white transition-colors hover:bg-gold hover:text-navy">Talk to our team</a>
            </div>
          </div>
          <BlogSidebar categories={state.side?.categories ?? []} recent={(state.side?.recent ?? []).filter((r) => r.slug !== p.slug).slice(0, 4)} category={p.category_slug ?? ''} />
        </div>
      </div>
    </article>
  );
}
