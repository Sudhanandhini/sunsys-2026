<?php
// Serves a built page with the SEO set in Admin > SEO applied. The root .htaccess routes every page request
// here (folders containing an index.html); everything else is served by Apache as usual.
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/seo.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// Site in a subfolder (e.g. /sunsys-2026/about/): page paths are relative to it
$base = base_path();
if ($base !== '' && ($uri === $base || str_starts_with($uri, "$base/"))) $uri = substr($uri, strlen($base)) ?: '/';
$path = seo_path(rawurldecode($uri));
$file = $path ? site_root() . $path . 'index.html' : null;
if (!$file || !is_file($file)) {
    http_response_code(404);
    readfile(site_root() . '/404.html');
    exit;
}
// Same URL style as the build (and its canonicals): /about → /about/
if (!str_ends_with($uri, '/')) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $base . $path . ($qs !== '' ? "?$qs" : ''), true, 301);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
$html = file_get_contents($file);
try {
    echo seo_apply($html, seo_meta_for(seo_page($path)));
} catch (Throwable $ex) {
    echo $html; // database unavailable: the page as built
}
