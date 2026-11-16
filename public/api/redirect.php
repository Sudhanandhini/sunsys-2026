<?php
// Requests for addresses that don't exist on the site (the root .htaccess sends them here): applies a redirect
// from Admin > SEO > Redirects if one matches, otherwise answers with the 404 page.
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/seo.php';
require __DIR__ . '/_lib/redirects.php';

$notFound = function () {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    $page = site_root() . '/404.html';
    if (is_file($page)) readfile($page);
    else echo '<!doctype html><title>Page not found</title><h1>Page not found</h1>';
};

redirect_serve($notFound);
http_response_code(404);
$notFound();
