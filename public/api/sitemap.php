<?php
// /sitemap.xml (routed here by the root .htaccess): every built page and published blog post, minus anything
// marked "Hide from search engines" in Admin > SEO or on the post.
require_once __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/seo.php';
require_once __DIR__ . '/_lib/redirects.php';

if (seo_settings()['sitemap_enabled'] !== '1') { http_response_code(404); exit; }

$origin = seo_origin();
$urls = [];
$rows = [];
$moved = redirect_sources(); // redirected addresses are left out: search engines should index the target
try {
    foreach (q('SELECT path, canonical, noindex FROM seo_pages')->fetchAll() as $r) $rows[$r['path']] = $r;
} catch (Throwable $ex) {
}
foreach (seo_discover() as $path => $p) {
    $r = $rows[$path] ?? null;
    if ($r && $r['noindex']) continue;
    $public = seo_url_path(seo_public_path($path)); // custom address from Admin > SEO, if set; no trailing slash
    if (redirect_is_source($moved, $public)) continue;
    // a page whose canonical points elsewhere is a duplicate: list the canonical, not this URL
    if ($r && $r['canonical'] !== '' && rtrim(seo_abs($r['canonical']), '/') !== rtrim($origin . $public, '/')) continue;
    $urls[] = [$origin . $public, date('Y-m-d', filemtime($p['file'])), $path === '/' ? '1.0' : (substr_count($path, '/') > 2 ? '0.6' : '0.8')];
}
try {
    foreach (q("SELECT slug, updated_at FROM posts WHERE status = 'published' AND published_at <= NOW() AND noindex = 0 ORDER BY published_at DESC")->fetchAll() as $p) {
        // articles live at /blog/<slug>
        if (redirect_is_source($moved, blog_path($p['slug']))) continue;
        $urls[] = [$origin . blog_path($p['slug']), date('Y-m-d', strtotime($p['updated_at'])), '0.6'];
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
