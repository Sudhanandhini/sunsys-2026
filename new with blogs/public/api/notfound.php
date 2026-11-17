<?php
// Requests for addresses that are neither a file, a page nor a blog article (the root .htaccess sends them here):
// follow a redirect from Admin > Redirects if there is one, else the site's 404 page.
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/seo.php';

redirect_apply(request_site_path());
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
readfile(site_root() . '/404.html');
