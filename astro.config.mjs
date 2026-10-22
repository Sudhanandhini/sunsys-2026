import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwindcss from '@tailwindcss/vite';
import { readFile, writeFile } from 'node:fs/promises';

// Apache's ErrorDocument takes a full URL path, so a subfolder build (`npm run build:staging`,
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
        await writeFile(file, text.replace(/^ErrorDocument 404 \/404\.html$/m, `ErrorDocument 404 ${base}/404.html`));
      },
    },
  };
}

export default defineConfig({
  site: 'https://sunsys.in',
  integrations: [react(), htaccessBase()],
  vite: {
    plugins: [tailwindcss()],
    // In `npm run dev`, PHP routes go to the local PHP server (scripts/dev-php.mjs)
    server: {
      proxy: Object.fromEntries(['/api', '/admin', '/uploads'].map((p) => [p, { target: 'http://127.0.0.1:8099', changeOrigin: false }])),
    },
  },
  build: { inlineStylesheets: 'auto' },
  compressHTML: true,
});
