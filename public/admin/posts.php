<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $cover = q('SELECT cover FROM posts WHERE id = ?', [$id])->fetchColumn();
        q('DELETE FROM posts WHERE id = ?', [$id]);
        if ($cover && str_starts_with($cover, '/uploads/blog/')) @unlink(dirname(__DIR__) . $cover);
        flash('Post deleted.');
    } elseif ($action === 'publish') {
        q("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id = ?", [$id]);
        flash('Post published.');
    } elseif ($action === 'unpublish') {
        q("UPDATE posts SET status = 'draft' WHERE id = ?", [$id]);
        flash('Post moved to drafts.');
    }
    redirect('posts.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$status = in_array($_GET['status'] ?? '', ['draft', 'published'], true) ? $_GET['status'] : '';
$search = clean($_GET['q'] ?? '', 100);
$catId = (int) ($_GET['category'] ?? 0);
$where = 'WHERE 1';
$args = [];
if ($catId) { $where .= ' AND p.category_id = ?'; $args[] = $catId; }
if ($status) { $where .= ' AND p.status = ?'; $args[] = $status; }
if ($search !== '') { $where .= ' AND p.title LIKE ?'; $args[] = '%' . $search . '%'; }
$total = (int) q("SELECT COUNT(*) FROM posts p $where", $args)->fetchColumn();
[$per, $off, $page, $pages] = paginate($total);
$posts = q("SELECT p.id, p.slug, p.title, p.excerpt, p.cover, p.status, p.published_at, p.updated_at, c.name AS category FROM posts p LEFT JOIN categories c ON c.id = p.category_id $where ORDER BY COALESCE(p.published_at, p.updated_at) DESC LIMIT $per OFFSET $off", $args)->fetchAll();

admin_head('Blog posts', 'posts.php', $user);
?>
<div class="toolbar">
  <nav class="tabs">
    <a href="posts.php" class="<?= !$status ? 'on' : '' ?>">All</a>
    <a href="?status=published" class="<?= $status === 'published' ? 'on' : '' ?>">Published</a>
    <a href="?status=draft" class="<?= $status === 'draft' ? 'on' : '' ?>">Drafts</a>
  </nav>
  <form class="search" method="get"><?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?><input name="q" placeholder="Search titles" value="<?= e($search) ?>" aria-label="Search posts"></form>
  <a class="btn btn-line" href="categories.php">Categories</a>
  <a class="btn" href="post-edit.php">+ New post</a>
</div>

<section class="card flush">
  <?php if (!$posts): ?><p class="empty">No posts here yet. <a href="post-edit.php">Write the first one</a>.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>Post</th><th>Category</th><th>Status</th><th>Date</th><th class="r">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($posts as $p): ?>
      <tr>
        <td><div class="row-title"><?php if ($p['cover']): ?><img src="<?= e($p['cover']) ?>" alt="" class="thumb"><?php else: ?><span class="thumb ph"></span><?php endif; ?><span><a href="post-edit.php?id=<?= $p['id'] ?>"><b><?= e($p['title']) ?></b></a><small><?= e(mb_strimwidth($p['excerpt'], 0, 90, '…')) ?></small></span></div></td>
        <td><?= $p['category'] ? e($p['category']) : '<span class="muted">—</span>' ?></td>
        <td><?= status_pill($p['status']) ?></td>
        <td class="nowrap"><?= fmt_date($p['published_at'] ?? $p['updated_at']) ?></td>
        <td class="r nowrap">
          <a class="lnk" href="post-edit.php?id=<?= $p['id'] ?>">Edit</a>
          <?php if ($p['status'] === 'published'): ?><a class="lnk" href="/blog/post/?slug=<?= e(urlencode($p['slug'])) ?>" target="_blank" rel="noopener">View</a><?php endif; ?>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
            <?php if ($p['status'] === 'published'): ?><button class="lnk" name="action" value="unpublish">Unpublish</button><?php else: ?><button class="lnk" name="action" value="publish">Publish</button><?php endif; ?>
            <button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this post permanently?')">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?= pager($page, $pages) ?>
<?php admin_foot();
