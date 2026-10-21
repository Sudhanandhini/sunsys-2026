<?php
// Article page with per-post SEO tags. The root .htaccess makes Apache serve this before the Astro-built
// index.html next to it: it fills that page's title, description, canonical, robots and social tags for the
// requested post (via seo_apply), and embeds the post so the browser doesn't fetch it a second time.
require dirname(__DIR__, 2) . '/api/_lib/bootstrap.php';
require dirname(__DIR__, 2) . '/api/_lib/blog.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

$html = @file_get_contents(__DIR__ . '/index.html');
if ($html === false) { http_response_code(500); exit('Missing blog/post/index.html: run npm run build and upload dist/.'); }

$slug = (string) ($_GET['slug'] ?? '');
try {
    $post = $slug !== '' ? blog_post($slug) : null;
    $side = blog_sidebar();
} catch (Throwable $ex) {
    exit($html); // database unavailable: the page shows its own error state
}

$json = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
$origin = seo_origin();

if ($post) {
    $seo = blog_seo($post);
    $url = $origin . '/blog/post/?slug=' . rawurlencode($post['slug']);
    $image = $post['cover'] ? $origin . $post['cover'] : '';
    $published = date('c', strtotime($post['published_at']));
    $org = ['@type' => 'Organization', 'name' => seo_settings()['site_name'] ?: 'Sunsys Technologies', 'url' => $origin . '/'];
    $schema = $json(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'description' => $seo['description'],
        'image' => $image ?: null,
        'datePublished' => $published,
        'dateModified' => date('c', strtotime($post['updated_at'])),
        'author' => $org,
        'publisher' => $org + ['logo' => ['@type' => 'ImageObject', 'url' => $origin . '/logo-dark.png']],
        'mainEntityOfPage' => $url,
        'articleSection' => $post['category'],
    ]));
    $meta = [
        'title' => $seo['title'],
        'description' => $seo['description'],
        'canonical' => $url,
        'og_type' => 'article',
        'og_image' => $image,
        'noindex' => (int) $post['noindex'],
        'nofollow' => (int) $post['nofollow'],
        'schema' => $schema,
        'extra_head' => '<meta property="article:published_time" content="' . $published . '">',
    ];
    unset($post['updated_at'], $post['meta_title'], $post['meta_description'], $post['noindex'], $post['nofollow']);
    $data = ['post' => $post + ['seo' => $seo]] + $side;
} else {
    http_response_code(404);
    $meta = ['noindex' => 1];
    $data = ['missing' => true] + $side;
}

$meta['extra_head'] = ($meta['extra_head'] ?? '') . '<script>window.__BLOG_POST__=' . $json($data) . '</script>';
echo seo_apply($html, $meta);
