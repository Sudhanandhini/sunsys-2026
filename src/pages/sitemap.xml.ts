// Static /sitemap.xml written into the build (and served by `npm run dev`).
// On the live Apache server the root .htaccess routes /sitemap.xml to api/sitemap.php instead, which also
// lists blog posts and leaves out pages hidden in Admin > SEO; this file is the fallback.
import type { APIRoute } from 'astro';
import { services, industries } from '../data/site';
import { url } from '../lib/url';

const pages: [string, number][] = [
  ['/', 1.0],
  ['/about/', 0.8],
  ['/services/', 0.8],
  ['/blog/', 0.8],
  ['/career/', 0.7],
  ['/contact/', 0.8],
  ['/sitemap/', 0.3],
  ...services.map((s): [string, number] => [`/services/${s.slug}/`, 0.7]),
  ...industries.map((i): [string, number] => [`/industries/${i.slug}/`, 0.6]),
];

export const GET: APIRoute = ({ site }) => {
  const origin = (site ?? new URL('https://sunsys.in')).origin;
  const today = new Date().toISOString().slice(0, 10);
  const urls = pages
    .map(([path, priority]) => `  <url><loc>${origin}${url(path)}</loc><lastmod>${today}</lastmod><priority>${priority.toFixed(1)}</priority></url>`)
    .join('\n');
  return new Response(`<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`, {
    headers: { 'Content-Type': 'application/xml; charset=utf-8' },
  });
};
