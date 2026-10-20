import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: 'https://sunsys.in',
  integrations: [react()],
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
