<?php
// /sitemap.xml (routed here by the root .htaccess): every built page and published blog post, minus anything
// marked "Hide from search engines" in Admin > SEO or on the post.
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/seo.php';

if (seo_settings()['sitemap_enabled'] !== '1') { http_response_code(404); exit; }

$origin = seo_origin();
$urls = [];
$rows = [];
try {
    foreach (q('SELECT path, canonical, noindex FROM seo_pages')->fetchAll() as $r) $rows[$r['path']] = $r;
} catch (Throwable $ex) {
}
foreach (seo_discover() as $path => $p) {
    $r = $rows[$path] ?? null;
    if ($r && $r['noindex']) continue;
    // a page whose canonical points elsewhere is a duplicate: list the canonical, not this URL
    if ($r && $r['canonical'] !== '' && seo_abs($r['canonical']) !== $origin . $path) continue;
    $urls[] = [$origin . $path, date('Y-m-d', filemtime($p['file'])), $path === '/' ? '1.0' : (substr_count($path, '/') > 2 ? '0.6' : '0.8')];
}
try {
    foreach (q("SELECT slug, updated_at FROM posts WHERE status = 'published' AND published_at <= NOW() AND noindex = 0 ORDER BY published_at DESC")->fetchAll() as $p) {
        $urls[] = [$origin . '/blog/post/?slug=' . rawurlencode($p['slug']), date('Y-m-d', strtotime($p['updated_at'])), '0.6'];
    }
} catch (Throwable $ex) {
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $mod, $pri]) {
    echo '  <url><loc>', htmlspecialchars($loc, ENT_XML1), '</loc><lastmod>', $mod, '</lastmod><priority>', $pri, "</priority></url>\n";
}
echo "</urlset>\n";
