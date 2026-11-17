<?php
// Article page with per-post SEO tags. Articles live at /blog/<slug> (blog_path()); the root .htaccess sends those
// here, and also every one-word address that isn't a file or page (/<word>, the old WordPress article addresses),
// as ?slug=<word>. An article at any other address (/<slug>, /blog/post/?slug=…, a trailing slash) 301s to
// /blog/<slug>. The page fills the Astro-built index.html next to it with the post's title, description,
// canonical, robots and social tags (via seo_apply), and embeds the post so the browser doesn't fetch it again.
// A slug that isn't an article is handed to api/redirect.php (custom page addresses, redirects, else the 404 page).
require_once dirname(__DIR__, 2) . '/api/_lib/bootstrap.php';
require dirname(__DIR__, 2) . '/api/_lib/blog.php';
require_once dirname(__DIR__, 2) . '/api/_lib/redirects.php';

// Admin > SEO > Redirects come first: old addresses like /chennai, or /blog/post/?slug=old-slug
redirect_serve(function () {
    header('Content-Type: text/html; charset=utf-8');
    @readfile(dirname(__DIR__, 2) . '/404.html');
});

$slug = (string) ($_GET['slug'] ?? '');
$uri = request_site_path();

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');

$html = @file_get_contents(__DIR__ . '/index.html');
if ($html === false) { http_response_code(500); exit('Missing blog/post/index.html: run npm run build and upload dist/.'); }

try {
    $post = $slug !== '' ? blog_post($slug) : null;
    $side = blog_sidebar();
} catch (Throwable $ex) {
    exit($html); // database unavailable: the page shows its own error state
}
// Not an article: a page at a custom address from Admin > SEO, or nothing (404)
if (!$post && $slug !== '') {
    require dirname(__DIR__, 2) . '/api/redirect.php';
    exit;
}
// One address per article: /blog/<slug>. Old ones (/<slug>, /blog/post/?slug=…, /blog/<slug>/) move there.
if ($post && rawurldecode($uri) !== '/blog/' . $post['slug']) {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    unset($qs['slug']); // other parameters (utm_… tags) are kept
    header('Location: ' . base_path() . blog_path($post['slug']) . ($qs ? '?' . http_build_query($qs) : ''), true, 301);
    exit;
}

$json = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
$origin = seo_origin();

if ($post) {
    $seo = blog_seo($post);
    $url = $origin . blog_path($post['slug']);
    $image = $post['cover'] ? $origin . $post['cover'] : '';
    $published = date('c', strtotime($post['published_at']));
    $org = ['@type' => 'Organization', 'name' => seo_settings()['site_name'] ?: 'Sunsys Technologies', 'url' => $origin . '/'];
    // The post's author as a person (name, job title, LinkedIn, photo); the company when the post has none
    $a = $post['author'];
    $author = $a ? array_filter([
        '@type' => 'Person',
        'name' => $a['name'],
        'jobTitle' => $a['designation'] ?: null,
        'description' => $a['bio'] ?: null,
        'image' => $a['photo'] ? $origin . $a['photo'] : null,
        'sameAs' => $a['linkedin'] ? [$a['linkedin']] : null,
        'worksFor' => $org,
    ]) : $org;
    $schema = $json(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'description' => $seo['description'],
        'image' => $image ?: null,
        'datePublished' => $published,
        'dateModified' => date('c', strtotime($post['updated_at'])),
        'author' => $author,
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
        'extra_head' => '<meta property="article:published_time" content="' . $published . '">' . ($a ? '<meta name="author" content="' . e($a['name']) . '">' : ''),
    ];
    unset($post['updated_at'], $post['meta_title'], $post['meta_description'], $post['noindex'], $post['nofollow']);
    $data = ['post' => $post + ['seo' => $seo], 'related' => blog_related($post)] + $side;
} else {
    http_response_code(404);
    $meta = ['noindex' => 1];
    $data = ['missing' => true] + $side;
}

$meta['extra_head'] = ($meta['extra_head'] ?? '') . '<script>window.__BLOG_POST__=' . $json(blog_public($data)) . '</script>';
echo seo_apply(seo_rewrite_links($html), $meta);
