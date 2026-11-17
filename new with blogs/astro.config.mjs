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

// `npm run dev` only: blog articles live at /<slug> (Apache routes those to blog/post/ on the server). Any
// one-word address that isn't a page here is handed to the article page instead.
function devBlogSlugs() {
  return {
    name: 'dev-blog-slugs',
    configureServer(server) {
      server.middlewares.use(async (req, _res, next) => {
        const m = req.method === 'GET' && /^\/([a-z0-9-]+)\/?(\?.*)?$/.exec(req.url ?? '');
        if (!m) return next();
        const slug = m[1];
        const { services, industries } = await server.ssrLoadModule('/src/data/site.ts');
        const pages = new URL('./src/pages/', import.meta.url);
        const isPage = [...services, ...industries].some((s) => s.path === slug)
          || existsSync(new URL(`${slug}.astro`, pages)) || existsSync(new URL(`${slug}/`, pages)) || existsSync(new URL(`../public/${slug}`, pages));
        if (!isPage) req.url = `/blog/post/?slug=${slug}`;
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
    plugins: [tailwindcss(), devBlogSlugs()],
    // In `npm run dev`, PHP routes go to the local PHP server (scripts/dev-php.mjs)
    server: {
      proxy: Object.fromEntries(['/api', '/admin', '/uploads'].map((p) => [p, { target: 'http://127.0.0.1:8099', changeOrigin: false }])),
    },
  },
  build: { inlineStylesheets: 'auto' },
  compressHTML: true,
});
