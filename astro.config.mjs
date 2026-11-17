import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwindcss from '@tailwindcss/vite';
import { readFile, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';

// Apache's ErrorDocument and RewriteBase take a full URL path, so a subfolder build (`npm run build:staging`,
// base /sunsys-2026) gets that folder written into dist/.htaccess. Everything else in it is folder-relative.
function htaccessBase() {
  let base = '/';
  return {
    name: 'htaccess-base',
    hooks: {
      'astro:config:done': ({ config }) => { base = config.base.replace(/\/$/, ''); },
      'astro:build:done': async ({ dir }) => {
        if (!base) return;
        const file = new URL('.htaccess', dir);
        const text = await readFile(file, 'utf8');
        await writeFile(file, text
          .replace(/^ErrorDocument 404 \/404\.html$/m, `ErrorDocument 404 ${base}/404.html`)
          .replace(/^(\s*)RewriteBase \/$/m, `$1RewriteBase ${base}/`));
      },
    },
  };
}

// `npm run dev` only: custom page addresses from Admin > SEO (api/aliases.php on the PHP server), applied the way
// api/page.php and api/redirect.php apply them on the live site: the new address serves the page, the build's
// address 301-redirects to it, and links in the HTML point to the new address. Addresses have no trailing slash.
function devPageAliases() {
  const PHP = 'http://127.0.0.1:8099';
  const SITE = 'https://www.sunsys.in';
  let cache = { at: 0, map: {} };
  const aliases = async () => {
    if (Date.now() - cache.at < 2000) return cache.map;
    try {
      const r = await fetch(`${PHP}/api/aliases.php`);
      cache = { at: Date.now(), map: r.ok ? await r.json() : {} };
    } catch {
      cache = { at: Date.now(), map: {} }; // PHP not running: pages keep their build addresses
    }
    return cache.map;
  };
  const trim = (p) => (p === '/' ? '/' : p.replace(/\/+$/, ''));
  const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const rewrite = (html, map) => {
    const olds = Object.keys(map).filter((p) => p !== '/').map(trim).sort((a, b) => b.length - a.length);
    if (!olds.length) return html;
    const byOld = Object.fromEntries(Object.entries(map).map(([o, n]) => [trim(o).toLowerCase(), trim(n)]));
    const re = new RegExp(`(?<=href="|href='|&quot;|")(${esc(SITE)})?(${olds.map(esc).join('|')})/?(?=["'#?&])`, 'gi');
    return html.replace(re, (_, origin = '', old) => origin + byOld[old.toLowerCase()]);
  };
  return {
    name: 'dev-page-aliases',
    configureServer(server) {
      server.middlewares.use(async (req, res, next) => {
        const [pathname, qs = ''] = (req.url || '/').split('?');
        // only page requests: no Vite/Astro internals, files or PHP folders
        if (req.method !== 'GET' || /^\/(@|_astro|src|node_modules|api|admin|uploads)(\/|$)/.test(pathname) || /\.[a-z0-9]+$/i.test(pathname)) return next();
        // Page addresses have no trailing slash, as on the live site: /about/ → /about (the blog article page keeps it)
        const map = await aliases();
        if (pathname !== '/' && pathname.endsWith('/') && !/^\/blog\/post\/$/.test(pathname)) {
          // straight to a moved page's new address in one hop: /career/ → /careers
          const moved = Object.entries(map).find(([b]) => trim(b) === trim(decodeURIComponent(pathname)).toLowerCase())?.[1];
          res.writeHead(301, { Location: trim(moved || pathname) + (qs ? `?${qs}` : ''), 'Cache-Control': 'no-store' });
          return res.end();
        }
        if (!Object.keys(map).length) return next();
        const key = trim(decodeURIComponent(pathname)).toLowerCase();
        const built = Object.entries(map).find(([, pub]) => trim(pub) === key)?.[0];
        const pub = Object.entries(map).find(([b]) => trim(b) === key)?.[1];
        if (pub && !built) {
          res.writeHead(301, { Location: trim(pub) + (qs ? `?${qs}` : ''), 'Cache-Control': 'no-store' });
          return res.end();
        }
        if (built) req.url = built + (qs ? `?${qs}` : '');
        // point links in the page's HTML at the custom addresses
        const isHtml = () => String(res.getHeader('content-type') || '').includes('text/html');
        const chunks = [];
        let html = false;
        const { write, end, writeHead } = res;
        res.writeHead = function (status, ...rest) {
          const h = rest.find((x) => x && typeof x === 'object');
          if (h) for (const k of Object.keys(h)) if (k.toLowerCase() === 'content-type' && String(h[k]).includes('text/html')) html = true;
          if (h && (html || isHtml())) for (const k of Object.keys(h)) if (k.toLowerCase() === 'content-length') delete h[k];
          return writeHead.call(this, status, ...rest);
        };
        res.write = function (c, ...a) {
          if (!(html || isHtml())) return write.call(this, c, ...a);
          if (c) chunks.push(Buffer.from(c));
          return true;
        };
        res.end = function (c, ...a) {
          if (!(html || isHtml())) return end.call(this, c, ...a);
          if (c && typeof c !== 'function') chunks.push(Buffer.from(c));
          if (!res.headersSent) res.removeHeader('content-length');
          return end.call(this, rewrite(Buffer.concat(chunks).toString('utf8'), map));
        };
        next();
      });
    },
  };
}

// `npm run dev` only: blog articles live at /blog/<slug> (Apache routes those to blog/post/ on the server), so
// those are handed to the article page. Any other one-word address that isn't a page here is an old article
// address (/<slug>, the WordPress blog's) and 301s to /blog/<slug>, as blog/post/index.php does on the server.
function devBlogSlugs() {
  return {
    name: 'dev-blog-slugs',
    configureServer(server) {
      server.middlewares.use(async (req, res, next) => {
        const art = req.method === 'GET' && /^\/blog\/([a-z0-9-]+)\/?(\?.*)?$/.exec(req.url ?? '');
        if (art && art[1] !== 'post') {
          req.url = `/blog/post/?slug=${art[1]}`;
          return next();
        }
        const m = req.method === 'GET' && /^\/([a-z0-9-]+)\/?(\?.*)?$/.exec(req.url ?? '');
        if (!m) return next();
        const slug = m[1];
        const { services, industries } = await server.ssrLoadModule('/src/data/site.ts');
        const pages = new URL('./src/pages/', import.meta.url);
        const isPage = [...services, ...industries].some((s) => s.path === slug)
          || existsSync(new URL(`${slug}.astro`, pages)) || existsSync(new URL(`${slug}/`, pages)) || existsSync(new URL(`../public/${slug}`, pages));
        if (!isPage) {
          res.writeHead(301, { Location: `/blog/${slug}${m[2] ?? ''}`, 'Cache-Control': 'no-store' });
          return res.end();
        }
        next();
      });
    },
  };
}

export default defineConfig({
  // The old WordPress site's address (with www), so canonicals and old links stay identical
  site: 'https://www.sunsys.in',
  integrations: [react(), htaccessBase()],
  vite: {
    // devPageAliases first: a custom address (/about-us) becomes its page before the article check runs
    plugins: [tailwindcss(), devPageAliases(), devBlogSlugs()],
    // In `npm run dev`, PHP routes go to the local PHP server (scripts/dev-php.mjs)
    server: {
      proxy: Object.fromEntries(['/api', '/admin', '/uploads'].map((p) => [p, { target: 'http://127.0.0.1:8099', changeOrigin: false }])),
    },
  },
  build: { inlineStylesheets: 'auto' },
  compressHTML: true,
});
