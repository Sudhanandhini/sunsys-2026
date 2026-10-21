import { useEffect, useRef, useState } from 'react';
import { Menu, X, ChevronDown, ArrowRight } from 'lucide-react';
import { nav, services, industries } from '../data/site';

const web = services.filter((s) => s.group === 'web');
const hosting = services.filter((s) => s.group === 'hosting');

// Small gold rule + diamond used under headings, matching the footer
function Ornament() {
  return (
    <span aria-hidden="true" className="mt-2.5 flex items-center gap-1.5">
      <span className="h-px w-7 bg-gold" />
      <span className="size-1 rotate-45 bg-gold" />
    </span>
  );
}

function ColHead({ children }) {
  return (
    <div className="mb-4">
      <p className="font-serif text-[19px] text-ink">{children}</p>
      <Ornament />
    </div>
  );
}

function MegaLink({ href, children }) {
  return (
    <li>
      <a href={href} className="hdr-link inline-block py-1.5 text-[15px] text-body hover:text-gold">
        {children}
      </a>
    </li>
  );
}

export default function Header({ path = '/', promoImg = '' }) {
  const [mega, setMega] = useState(false);
  const [drawer, setDrawer] = useState(false);
  const [mobServices, setMobServices] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const closeTimer = useRef(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 40);
    const onKey = (e) => e.key === 'Escape' && (setMega(false), setDrawer(false));
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('keydown', onKey);
    return () => {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('keydown', onKey);
    };
  }, []);

  useEffect(() => {
    document.body.style.overflow = drawer ? 'hidden' : '';
  }, [drawer]);

  const open = () => { clearTimeout(closeTimer.current); setMega(true); };
  const close = () => { closeTimer.current = setTimeout(() => setMega(false), 140); };
  const isActive = (href) => (href === '/' ? path === '/' : path.startsWith(href));

  const linkCls = (on) =>
    `hdr-nav relative flex items-center gap-1 whitespace-nowrap px-2.5 py-2 font-head text-[13px] font-semibold uppercase tracking-[.1em] transition-colors xl:px-3.5 xl:text-[14px] xl:tracking-[.12em] ${
      on ? 'is-on text-navy' : 'text-navy/70 hover:text-navy'
    }`;

  return (
    <header className="sticky top-0 z-50">
      <div
        className={`relative border-b transition-[background-color,box-shadow,border-color] duration-500 ${
          scrolled || mega
            ? 'border-gold/25 bg-white/95 shadow-[0_10px_30px_-12px_rgba(6,42,82,.18)] backdrop-blur'
            : 'border-gold/20 bg-ivory'
        }`}
      >
        <nav
          className={`container-x flex items-center justify-between gap-6 transition-[height] duration-500 ${scrolled ? 'h-[68px]' : 'h-[84px]'}`}
          aria-label="Main"
        >
          <a href="/" className="shrink-0" aria-label="Sunsys Technologies home">
            <img
              src="/logo-dark.png"
              alt="Sunsys Technologies"
              width="429"
              height="89"
              className={`w-auto transition-[height] duration-500 ${scrolled ? 'h-8' : 'h-9 md:h-10'}`}
            />
          </a>

          <ul className="hidden items-center lg:flex">
            {nav.map((item) =>
              item.mega ? (
                <li key={item.href} onMouseEnter={open} onMouseLeave={close}>
                  <button
                    type="button"
                    aria-expanded={mega}
                    aria-controls="mega-menu"
                    onClick={() => setMega((v) => !v)}
                    className={linkCls(isActive(item.href) || mega)}
                  >
                    {item.label}
                    <ChevronDown size={15} className={`text-gold transition-transform duration-300 ${mega ? 'rotate-180' : ''}`} />
                  </button>
                </li>
              ) : (
                <li key={item.href}>
                  <a href={item.href} aria-current={isActive(item.href) ? 'page' : undefined} className={linkCls(isActive(item.href))}>
                    {item.label}
                  </a>
                </li>
              ),
            )}
          </ul>

          <div className="flex items-center gap-3">
            <a
              href="/contact?type=quote"
              className="hdr-cta group relative hidden items-center gap-2.5 overflow-hidden rounded-[2px] bg-navy px-6 py-3 whitespace-nowrap text-[12px] font-bold uppercase tracking-[.16em] text-white shadow-lg shadow-navy/15 sm:inline-flex lg:hidden xl:inline-flex"
            >
              <span aria-hidden="true" className="absolute inset-0 origin-left scale-x-0 bg-gold transition-transform duration-500 group-hover:scale-x-100" />
              <span className="relative">Get a quote</span>
              <ArrowRight size={14} strokeWidth={2.2} className="relative transition-transform duration-300 group-hover:translate-x-1" />
            </a>
            <button
              type="button"
              className="grid size-11 place-items-center rounded-full border border-gold/40 text-navy transition-colors hover:bg-navy hover:text-white lg:hidden"
              aria-label="Open menu"
              aria-expanded={drawer}
              onClick={() => setDrawer(true)}
            >
              <Menu size={22} />
            </button>
          </div>
        </nav>

        {/* Desktop mega menu */}
        <div
          id="mega-menu"
          onMouseEnter={open}
          onMouseLeave={close}
          className={`absolute inset-x-0 top-full hidden border-t border-gold/20 bg-white shadow-[0_30px_60px_-20px_rgba(6,42,82,.25)] lg:block ${
            mega ? 'is-open visible translate-y-0 opacity-100' : 'invisible -translate-y-2 opacity-0'
          } transition-[opacity,transform,visibility] duration-300`}
        >
          <div className="container-x grid grid-cols-[2fr_1fr_1fr_1.15fr] gap-10 py-10">
            <div className="mega-col" style={{ '--i': 0 }}>
              <ColHead>Web development</ColHead>
              <ul className="grid grid-flow-col grid-rows-[repeat(5,auto)] content-start gap-x-8">
                {web.map((s) => <MegaLink key={s.slug} href={`/services/${s.slug}`}>{s.title}</MegaLink>)}
              </ul>
            </div>
            <div className="mega-col" style={{ '--i': 1 }}>
              <ColHead>Hosting &amp; care</ColHead>
              <ul>{hosting.map((s) => <MegaLink key={s.slug} href={`/services/${s.slug}`}>{s.title}</MegaLink>)}</ul>
              <a href="/services" className="mt-5 inline-flex items-center gap-2 border-b border-gold pb-1 text-[12px] font-bold uppercase tracking-[.16em] text-navy hover:text-gold">
                All services <ArrowRight size={13} strokeWidth={2.2} />
              </a>
            </div>
            <div className="mega-col" style={{ '--i': 2 }}>
              <ColHead>Industries</ColHead>
              <ul>{industries.map((s) => <MegaLink key={s.slug} href={`/industries/${s.slug}`}>{s.title}</MegaLink>)}</ul>
            </div>
            {/* feature card */}
            <a href="/contact?type=quote" className="mega-col group relative block overflow-hidden rounded-t-[999px] bg-navy" style={{ '--i': 3 }}>
              <img src={promoImg} alt="" loading="lazy" className="absolute inset-0 size-full object-cover opacity-45 transition-transform duration-[1.2s] group-hover:scale-110" />
              <span className="absolute inset-0 bg-gradient-to-t from-navy via-navy/60 to-transparent" />
              <span className="relative flex h-full min-h-[15rem] flex-col justify-end p-6 text-white">
                <span className="text-[11px] font-bold uppercase tracking-[.2em] text-gold-soft">Free consultation</span>
                <span className="mt-2 font-serif text-[22px] leading-tight">Planning a new <em className="text-gold-soft">website</em>?</span>
                <span className="mt-4 inline-flex items-center gap-2 text-[12px] font-bold uppercase tracking-[.16em] text-gold-soft">
                  Get a quote <ArrowRight size={13} strokeWidth={2.2} className="transition-transform group-hover:translate-x-1" />
                </span>
              </span>
            </a>
          </div>
        </div>
      </div>

      {/* Mobile drawer */}
      <div className={`fixed inset-0 z-50 lg:hidden ${drawer ? 'is-open' : 'pointer-events-none'}`} aria-hidden={!drawer}>
        <div
          className={`absolute inset-0 bg-navy/60 backdrop-blur-sm transition-opacity duration-300 ${drawer ? 'opacity-100' : 'opacity-0'}`}
          onClick={() => setDrawer(false)}
        />
        <aside
          className={`absolute right-0 top-0 flex h-full w-[88%] max-w-sm flex-col bg-ivory transition-transform duration-500 ease-[cubic-bezier(.2,.7,.2,1)] ${
            drawer ? 'translate-x-0' : 'translate-x-full'
          }`}
          aria-label="Mobile menu"
        >
          <div className="flex h-[76px] items-center justify-between border-b border-gold/25 px-6">
            <img src="/logo-dark.png" alt="Sunsys Technologies" width="429" height="89" className="h-8 w-auto" />
            <button
              type="button"
              className="grid size-10 place-items-center rounded-full border border-gold/40 text-navy"
              aria-label="Close menu"
              onClick={() => setDrawer(false)}
            >
              <X size={20} />
            </button>
          </div>
          <ul className="flex-1 overflow-y-auto px-6 py-4">
            {nav.map((item, i) =>
              item.mega ? (
                <li key={item.href} className="drawer-item border-b border-gold/20" style={{ '--i': i }}>
                  <button
                    type="button"
                    className="flex w-full items-center justify-between py-4 font-serif text-[22px] text-ink"
                    aria-expanded={mobServices}
                    onClick={() => setMobServices((v) => !v)}
                  >
                    {item.label}
                    <ChevronDown size={18} className={`text-gold transition-transform ${mobServices ? 'rotate-180' : ''}`} />
                  </button>
                  {mobServices && (
                    <div className="space-y-6 pb-5">
                      <div><ColHead>Web development</ColHead><ul>{web.map((s) => <MegaLink key={s.slug} href={`/services/${s.slug}`}>{s.title}</MegaLink>)}</ul></div>
                      <div><ColHead>Hosting &amp; care</ColHead><ul>{hosting.map((s) => <MegaLink key={s.slug} href={`/services/${s.slug}`}>{s.title}</MegaLink>)}</ul></div>
                      <div><ColHead>Industries</ColHead><ul>{industries.map((s) => <MegaLink key={s.slug} href={`/industries/${s.slug}`}>{s.title}</MegaLink>)}</ul></div>
                    </div>
                  )}
                </li>
              ) : (
                <li key={item.href} className="drawer-item border-b border-gold/20" style={{ '--i': i }}>
                  <a
                    href={item.href}
                    className={`flex items-center justify-between py-4 font-serif text-[22px] ${isActive(item.href) ? 'text-gold' : 'text-ink'}`}
                  >
                    {item.label}
                    {isActive(item.href) && <span aria-hidden="true" className="size-1.5 rotate-45 bg-gold" />}
                  </a>
                </li>
              ),
            )}
          </ul>
          <div className="border-t border-gold/25 p-6">
            <a href="/contact?type=quote" className="flex w-full items-center justify-center gap-2 rounded-[2px] bg-navy px-6 py-4 text-[13px] font-bold uppercase tracking-[.16em] text-white">
              Get a free quote <ArrowRight size={14} strokeWidth={2.2} />
            </a>
          </div>
        </aside>
      </div>
    </header>
  );
}
