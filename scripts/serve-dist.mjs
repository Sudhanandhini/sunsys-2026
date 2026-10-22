// Serves the production build (dist/) the way public/.htaccess does on the live host: gzip for text,
// a year's cache for hashed /_astro/ files. Use it for Lighthouse runs (`npm run perf`); `npm run dev`
// serves unminified, uncached modules and always scores badly.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const root = path.resolve('dist');
const port = Number(process.env.PORT || 4400);
const types = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.json': 'application/json',
  '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.webp': 'image/webp', '.woff2': 'font/woff2',
  '.xml': 'application/xml', '.txt': 'text/plain', '.ico': 'image/x-icon',
};

http.createServer((req, res) => {
  const urlPath = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  let file = path.join(root, urlPath);
  if (!file.startsWith(root)) { res.writeHead(403); return res.end(); }
  if (fs.existsSync(file) && fs.statSync(file).isDirectory()) file = path.join(file, 'index.html');
  if (!fs.existsSync(file)) file = path.join(root, '404.html');

  const type = types[path.extname(file)] || 'application/octet-stream';
  const headers = { 'Content-Type': type };
  if (urlPath.startsWith('/_astro/')) headers['Cache-Control'] = 'public, max-age=31536000, immutable';
  else if (type.startsWith('text/html')) headers['Cache-Control'] = 'no-cache';
  else headers['Cache-Control'] = 'public, max-age=604800';

  let body = fs.readFileSync(file);
  if (/text|javascript|json|svg|xml|icon/.test(type) && /gzip/.test(req.headers['accept-encoding'] || '')) {
    body = zlib.gzipSync(body);
    headers['Content-Encoding'] = 'gzip';
  }
  res.writeHead(file.endsWith('404.html') && !urlPath.endsWith('404.html') ? 404 : 200, headers);
  res.end(body);
}).listen(port, () => console.log(`Production build on http://localhost:${port}/ (run Lighthouse here)`));
