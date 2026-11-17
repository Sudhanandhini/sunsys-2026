// Live Google + social previews and character counters for Admin > SEO > edit page.
// Mirrors seo_title() in api/_lib/seo.php: a title set here goes into the template unless it already names the site.
(() => {
  const form = document.querySelector('.seo-form');
  if (!form) return;
  const { template, site, origin, defTitle, defDesc } = form.dataset;
  const $ = (id) => document.getElementById(id);
  const title = $('seo-title'), desc = $('seo-desc'), path = $('seo-path'), ogTitle = $('og-title-in'), ogDesc = $('og-desc-in');
  const clip = (s, n) => (s.length > n ? s.slice(0, n - 1).trimEnd() + '…' : s);
  const full = (t) => (!t || !template.includes('%s') || (site && t.toLowerCase().includes(site.toLowerCase())) ? t : template.replace('%s', t));

  const update = () => {
    const t = full(title.value.trim()) || defTitle || 'Page title';
    const d = desc.value.trim() || defDesc || 'Add a meta description so Google shows your own summary of the page.';
    const host = origin.replace(/^https?:\/\//, '');
    $('serp-url').textContent = host + (path.value.trim() || '/').replace(/\/$/, '').split('/').filter(Boolean).map((p) => ' › ' + p).join('');
    $('serp-title').textContent = clip(t, 60);
    $('serp-desc').textContent = clip(d, 160);
    $('og-title').textContent = clip(ogTitle.value.trim() || t, 90);
    $('og-desc').textContent = clip(ogDesc.value.trim() || d, 110);
    document.querySelectorAll('[data-count]').forEach((c) => {
      const f = $(c.dataset.count), max = +c.dataset.max;
      const v = f.id === 'seo-title' ? full(f.value.trim()) : f.value.trim();
      const n = (v || f.placeholder).length;
      c.textContent = `${n} / ${max} characters${v ? '' : ' (website default)'}`;
      c.classList.toggle('over', n > max);
    });
  };
  [title, desc, path, ogTitle, ogDesc].forEach((el) => el.addEventListener('input', update));
  update();
})();
