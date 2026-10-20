<?php
// Public blog API.
// GET               → { posts, categories, recent }   (?q= search, ?category= slug filter)
// GET ?slug=x       → { post, categories, recent }    one published post with rendered HTML
require __DIR__ . '/_lib/bootstrap.php';
require __DIR__ . '/_lib/markdown.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['error' => 'Method not allowed'], 405);

const LIVE = "p.status = 'published' AND p.published_at <= NOW()";
const COLS = 'p.slug, p.title, p.excerpt, p.cover, p.published_at, c.name AS category, c.slug AS category_slug';

/** Sidebar data shared by both responses. */
function sidebar(): array
{
    return [
        'categories' => q('SELECT c.name, c.slug, COUNT(p.id) AS count FROM categories c LEFT JOIN posts p ON p.category_id = c.id AND ' . LIVE . ' GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll(),
        'recent' => q('SELECT ' . COLS . ' FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . LIVE . ' ORDER BY p.published_at DESC LIMIT 4')->fetchAll(),
    ];
}

try {
    if (!empty($_GET['slug'])) {
        $p = q('SELECT ' . COLS . ', p.body FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND ' . LIVE, [(string) $_GET['slug']])->fetch();
        if (!$p) json_out(['error' => 'Not found'] + sidebar(), 404);
        $p['html'] = markdown($p['body']);
        unset($p['body']);
        json_out(['post' => $p] + sidebar());
    }

    $where = LIVE;
    $args = [];
    if (!empty($_GET['category'])) { $where .= ' AND c.slug = ?'; $args[] = (string) $_GET['category']; }
    $search = clean($_GET['q'] ?? '', 100);
    if ($search !== '') { $where .= ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.body LIKE ?)'; array_push($args, "%$search%", "%$search%", "%$search%"); }
    $posts = q('SELECT ' . COLS . " FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.published_at DESC LIMIT 100", $args)->fetchAll();
    json_out(['posts' => $posts] + sidebar());
} catch (Throwable $ex) {
    json_out(['error' => 'Server error'], 500);
}
