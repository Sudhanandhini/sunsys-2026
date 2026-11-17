<?php
// Local PHP dev server router (mimics the .htaccess rules used on Apache/cPanel).
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(api/_lib|api/_private|admin/_inc)(/|$)#', $path) || preg_match('#^/uploads/.*\.php#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
$root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$dir = $root . rtrim($path, '/');
$script = null;
if ($path === '/robots.txt') $script = "$root/api/robots.php"; // generated (see the root .htaccess)
elseif ($path === '/sitemap.xml') $script = "$root/api/sitemap.php";
elseif (is_dir($root . $path) && is_file("$dir/index.php")) $script = "$dir/index.php";
// Built pages (when serving dist/) go through api/page.php so Admin > SEO applies
elseif (!preg_match('#^/(api|admin|uploads|_astro)(/|$)#', $path) && is_dir($root . $path) && is_file("$dir/index.html")) $script = "$root/api/page.php";
// Blog articles at /blog/<slug>, and old /<slug> article addresses (which 301 there; a word that isn't an article
// goes on to api/redirect.php)
elseif (preg_match('#^(?:/blog)?/([a-z0-9-]+)/?$#', $path, $m) && !file_exists($root . $path) && is_file("$root/blog/post/index.php")) {
    $_GET['slug'] = $m[1];
    $script = "$root/blog/post/index.php";
}
// Anything else missing: custom page addresses and Admin > SEO > Redirects, else the 404 page (as the root .htaccess does)
elseif (!preg_match('#^/(api|admin|_astro)(/|$)#', $path) && !file_exists($root . $path)) $script = "$root/api/redirect.php";
if (!$script) return false;
// Required here at the top level, not inside a function, so the script's globals (e.g. $CONFIG) stay global
chdir(dirname($script));
require $script;
