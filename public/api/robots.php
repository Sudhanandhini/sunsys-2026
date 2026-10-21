<?php
// /robots.txt (routed here by the root .htaccess): the text from Admin > SEO > Site settings, plus the sitemap link.
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/seo.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$s = seo_settings();
$txt = rtrim(str_replace("\r\n", "\n", $s['robots_txt']));
if ($s['sitemap_enabled'] === '1' && !preg_match('/^sitemap:/im', $txt)) $txt .= "\n\nSitemap: " . seo_origin() . '/sitemap.xml';
echo $txt . "\n";
