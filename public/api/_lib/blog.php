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
    $p['html'] = is_array($blocks) ? blocks_render($blocks) : markdown($p['body']);
    unset($p['body'], $p['blocks']);
    return $p;
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
