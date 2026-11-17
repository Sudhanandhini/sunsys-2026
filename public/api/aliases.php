<?php
// Custom page addresses set in Admin > SEO, as {"/about/": "/about-us/"} (build address => address served).
// On the live site api/page.php and api/redirect.php use them directly; `npm run dev` (Astro, which doesn't run
// that PHP) fetches this so localhost:4321 serves, redirects and links pages the same way.
require_once __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/seo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode((object) seo_aliases(), JSON_UNESCAPED_SLASHES);
