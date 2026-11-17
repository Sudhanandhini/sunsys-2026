<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $name = clean($_POST['name'] ?? '', 80);
    switch ($_POST['action'] ?? '') {
        case 'add':
        case 'rename':
            if ($name === '') { flash('Enter a category name.', 'err'); break; }
            $slug = slugify($name);
            if ((int) q('SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?', [$slug, $id])->fetchColumn()) { flash('A category with that name already exists.', 'err'); break; }
            if ($_POST['action'] === 'add') {
                $order = (int) q('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM categories')->fetchColumn();
                q('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)', [$name, $slug, $order]);
                flash("Category “{$name}” added.");
            } else {
                q('UPDATE categories SET name = ?, slug = ? WHERE id = ?', [$name, $slug, $id]);
                flash('Category renamed.');
            }
            break;
        case 'delete':
            q('UPDATE posts SET category_id = NULL WHERE category_id = ?', [$id]);
            q('DELETE FROM categories WHERE id = ?', [$id]);
            flash('Category deleted. Its posts are now uncategorised.');
            break;
    }
    redirect('categories.php');
}

$cats = q('SELECT c.*, COUNT(p.id) posts FROM categories c LEFT JOIN posts p ON p.category_id = c.id GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll();
admin_head('Blog categories', 'posts.php', $user);
?>
<a class="crumb" href="posts.php">← Blog posts</a>
<div class="grid2" style="margin-top:0">
  <section class="card flush">
    <?php if (!$cats): ?><p class="empty">No categories yet. Add your first one.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Category</th><th>Posts</th><th class="r">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td>
            <form method="post" class="inline-edit"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
              <input name="name" value="<?= e($c['name']) ?>" maxlength="80" aria-label="Category name"><button class="lnk" name="action" value="rename">Save</button>
            </form>
          </td>
          <td><a href="posts.php?category=<?= $c['id'] ?>"><?= (int) $c['posts'] ?></a></td>
          <td class="r"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this category? Its posts are kept, without a category.')">Delete</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
  <form method="post" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h2>Add a category</h2>
    <label>Name<input name="name" required maxlength="80" placeholder="e.g. Buyer’s guide"></label>
    <button class="btn" type="submit">Add category</button>
    <p class="muted small">Categories appear in the blog sidebar with a post count, and as a label on each article.</p>
  </form>
</div>
<?php admin_foot();
