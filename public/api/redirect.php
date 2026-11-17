<?php
// Requests for addresses that don't exist on the site (the root .htaccess sends them here): applies a redirect
// from Admin > SEO > Redirects if one matches, otherwise answers with the 404 page. Also serves built pages
// at the custom addresses set in Admin > SEO.
require_once __DIR__ . '/_lib/bootstrap.php';
require_once __DIR__ . '/_lib/seo.php';
require_once __DIR__ . '/_lib/redirects.php';

$notFound = function () {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    $page = site_root() . '/404.html';
    if (is_file($page)) readfile($page);
    else echo '<!doctype html><title>Page not found</title><h1>Page not found</h1>';
};

// A built page given a custom address in Admin > SEO is served at that address
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = base_path();
$rel = $base !== '' && ($uri === $base || str_starts_with($uri, "$base/")) ? (substr($uri, strlen($base)) ?: '/') : $uri;
$public = seo_path(rawurldecode($rel));
$built = $public ? seo_alias_target($public) : null;
if ($built && is_file(site_root() . $built . 'index.html')) {
    if (str_ends_with($uri, '/')) { // written without the trailing slash: /about-us
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        header('Location: ' . $base . seo_url_path($public) . ($qs !== '' ? "?$qs" : ''), true, 301);
        exit;
    }
    seo_serve($built);
    exit;
}

redirect_serve($notFound);
http_response_code(404);
$notFound();
