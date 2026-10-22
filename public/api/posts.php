<?php
// Public blog API.
// GET               → { posts, categories, recent }   (?q= search, ?category= slug filter)
// GET ?slug=x       → { post, categories, recent }    one published post with rendered HTML
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/blog.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['error' => 'Method not allowed'], 405);

try {
    if (!empty($_GET['slug'])) {
        $p = blog_post((string) $_GET['slug']);
        if (!$p) json_out(blog_public(['error' => 'Not found'] + blog_sidebar()), 404);
        $seo = blog_seo($p);
        unset($p['meta_title'], $p['meta_description'], $p['noindex'], $p['nofollow'], $p['updated_at']);
        json_out(blog_public(['post' => $p + ['seo' => $seo]] + blog_sidebar()));
    }

    $where = LIVE;
    $args = [];
    if (!empty($_GET['category'])) { $where .= ' AND c.slug = ?'; $args[] = (string) $_GET['category']; }
    $search = clean($_GET['q'] ?? '', 100);
    if ($search !== '') { $where .= ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.body LIKE ?)'; array_push($args, "%$search%", "%$search%", "%$search%"); }
    $posts = q('SELECT ' . COLS . " FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.published_at DESC LIMIT 100", $args)->fetchAll();
    json_out(blog_public(['posts' => $posts] + blog_sidebar()));
} catch (Throwable $ex) {
    json_out(['error' => 'Server error'], 500);
}
