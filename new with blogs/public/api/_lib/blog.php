<?php
// Blog queries shared by api/posts.php (JSON) and blog/post/index.php (server-rendered SEO tags).
require_once __DIR__ . '/markdown.php';
require_once __DIR__ . '/blocks.php';
require_once __DIR__ . '/seo.php';

const LIVE = "p.status = 'published' AND p.published_at <= NOW()";
const COLS = 'p.slug, p.title, p.excerpt, p.cover, p.published_at, c.name AS category, c.slug AS category_slug';

/** Sidebar data shared by the list and single-post responses. */
function blog_sidebar(): array
{
    return [
        'categories' => q('SELECT c.name, c.slug, COUNT(p.id) AS count FROM categories c LEFT JOIN posts p ON p.category_id = c.id AND ' . LIVE . ' GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll(),
        'recent' => q('SELECT ' . COLS . ' FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . LIVE . ' ORDER BY p.published_at DESC LIMIT 4')->fetchAll(),
    ];
}

/** One published post with rendered HTML, or null. */
function blog_post(string $slug): ?array
{
    $p = q('SELECT ' . COLS . ', p.body, p.blocks, p.meta_title, p.meta_description, p.noindex, p.nofollow, p.updated_at FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND ' . LIVE, [$slug])->fetch();
    if (!$p) return null;
    // Builder posts store blocks; posts written before the builder are Markdown
    $blocks = $p['blocks'] ? json_decode($p['blocks'], true) : null;
    $p['html'] = blog_site_links(is_array($blocks) ? blocks_render($blocks) : markdown($p['body']));
    unset($p['body'], $p['blocks']);
    return $p;
}

/**
 * Links in an article that point at this site, as addresses on whichever host and folder is serving it. Posts
 * imported from WordPress link to https://www.sunsys.in/<page>/, which would leave the staging site (or a non-www
 * host) for the www one; site-relative links (/contact) need the folder in front. Only done on output, so the
 * database keeps the plain addresses.
 */
function blog_site_links(string $html): string
{
    return preg_replace_callback('#<(a|img)\b([^>]*)>#i', function ($m) {
        $isLink = strtolower($m[1]) === 'a';
        $attr = $isLink ? 'href' : 'src';
        if (!preg_match('#\s' . $attr . '="([^"]*)"#', $m[2], $u)) return $m[0];
        // images already get the folder from public_url() when rendered, so only their absolute addresses change
        $path = blog_site_path(html_entity_decode($u[1], ENT_QUOTES, 'UTF-8'), $isLink);
        if ($path === null) return $m[0];
        $rest = str_replace($u[0], ' ' . $attr . '="' . e(public_url($path)) . '"', $m[2]);
        if ($isLink) $rest = str_replace(' target="_blank" rel="noopener"', '', $rest);
        return "<{$m[1]}$rest>";
    }, $html);
}

/** Site path for a link to this site (http(s)://[www.]sunsys.in/…, or /… when $relative), else null. */
function blog_site_path(string $u, bool $relative): ?string
{
    if (preg_match('#^/(?!/)#', $u)) return $relative ? $u : null;
    if (!preg_match('#^https?://(?:www\.)?sunsys\.in(?::\d+)?([/?\#].*)?$#i', $u, $m)) return null;
    $p = ($m[1] ?? '') === '' ? '/' : $m[1];
    if ($p[0] !== '/') $p = '/' . $p;
    // Old WordPress addresses end in a slash; pages here don't, so drop it and save a redirect
    return preg_replace('#(?<=.)/+(?=$|[?\#])#', '', $p);
}

/** Response data with every cover as a browser URL (the site may sit in a subfolder); the database keeps /uploads/... */
function blog_public(array $data): array
{
    array_walk_recursive($data, function (&$v, $k) {
        if ($k === 'cover' && is_string($v)) $v = public_url($v);
    });
    return $data;
}

/** Title and description for search results and link previews, falling back to the post's own text. */
function blog_seo(array $p): array
{
    return [
        'title' => ($p['meta_title'] ?? '') !== '' ? $p['meta_title'] : seo_title($p['title']),
        'description' => ($p['meta_description'] ?? '') !== '' ? $p['meta_description'] : $p['excerpt'],
    ];
}
