<?php
// Local PHP dev server router (mimics the .htaccess rules used on Apache/cPanel).
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(api/_lib|api/_private|admin/_inc)(/|$)#', $path) || preg_match('#^/uploads/.*\.php#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
$root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
if (is_dir($root . $path) && is_file($root . rtrim($path, '/') . '/index.php')) {
    chdir($root . rtrim($path, '/'));
    require $root . rtrim($path, '/') . '/index.php';
    exit;
}
return false;
