<?php
require __DIR__ . '/_inc/layout.php';
require dirname(__DIR__) . '/api/_lib/markdown.php';
$user = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$post = $id ? q('SELECT * FROM posts WHERE id = ?', [$id])->fetch() : null;
if ($id && !$post) { flash('That post no longer exists.', 'err'); redirect('posts.php'); }
$post = $post ?: ['id' => 0, 'slug' => '', 'title' => '', 'excerpt' => '', 'body' => '', 'cover' => null, 'category_id' => null, 'status' => 'draft', 'published_at' => null];
$cats = q('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post['title'] = clean($_POST['title'] ?? '', 200);
    $post['slug'] = slugify(clean($_POST['slug'] ?? '', 140) ?: $post['title']);
    $post['excerpt'] = clean($_POST['excerpt'] ?? '', 400);
    $post['body'] = clean($_POST['body'] ?? '', 200000);
    $post['status'] = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $cid = (int) ($_POST['category_id'] ?? 0);
    $post['category_id'] = $cid && in_array($cid, array_map('intval', array_column($cats, 'id')), true) ? $cid : null;
    $date = clean($_POST['published_at'] ?? '', 20);
    $post['published_at'] = $date ? date('Y-m-d H:i:s', strtotime($date)) : ($post['status'] === 'published' ? ($post['published_at'] ?: date('Y-m-d H:i:s')) : $post['published_at']);

    if ($post['title'] === '') $error = 'Add a title.';
    elseif ($post['body'] === '') $error = 'Write the article body.';
    elseif ((int) q('SELECT COUNT(*) FROM posts WHERE slug = ? AND id <> ?', [$post['slug'], $post['id']])->fetchColumn()) $error = 'Another post already uses this web address. Change the slug.';

    if (!$error) {
        try {
            if (!empty($_FILES['cover']['name'])) {
                $old = $post['cover'];
                $post['cover'] = save_image($_FILES['cover']);
                if ($old && str_starts_with($old, '/uploads/blog/')) @unlink(dirname(__DIR__) . $old);
            } elseif (!empty($_POST['remove_cover']) && $post['cover']) {
                if (str_starts_with($post['cover'], '/uploads/blog/')) @unlink(dirname(__DIR__) . $post['cover']);
                $post['cover'] = null;
            }
        } catch (RuntimeException $ex) {
            $error = $ex->getMessage();
        }
    }

    if (!$error) {
        if ($post['excerpt'] === '') $post['excerpt'] = mb_strimwidth(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(implode(' ', preg_match_all('#<p>(.*?)</p>#s', markdown($post['body']), $m) ? $m[1] : [$post['body']])), ENT_QUOTES, 'UTF-8'))), 0, 200, '…');
        $vals = [$post['slug'], $post['title'], $post['excerpt'], $post['body'], $post['cover'], $post['category_id'], $post['status'], $post['published_at']];
        if ($post['id']) {
            q('UPDATE posts SET slug=?, title=?, excerpt=?, body=?, cover=?, category_id=?, status=?, published_at=? WHERE id=?', [...$vals, $post['id']]);
        } else {
            q('INSERT INTO posts (slug, title, excerpt, body, cover, category_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $vals);
            $post['id'] = (int) db()->lastInsertId();
        }
        flash($post['status'] === 'published' ? 'Post saved and published.' : 'Draft saved.');
        redirect('post-edit.php?id=' . $post['id']);
    }
}

admin_head($post['id'] ? 'Edit post' : 'New post', 'posts.php', $user);
?>
<a class="crumb" href="posts.php">← All posts</a>
<?php if ($error): ?><p class="alert err"><?= e($error) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="editor">
  <?= csrf_field() ?>
  <div class="card stack">
    <label>Title<input name="title" required maxlength="200" value="<?= e($post['title']) ?>" class="big" id="title"></label>
    <label>Web address <span class="muted">/blog/post/?slug=</span><input name="slug" maxlength="140" value="<?= e($post['slug']) ?>" id="slug" placeholder="made from the title if left empty"></label>
    <label>Short summary <span class="muted">(shown on the blog list, up to 400 characters)</span><textarea name="excerpt" rows="2" maxlength="400"><?= e($post['excerpt']) ?></textarea></label>
    <label>Article
      <div class="md-bar" role="toolbar" aria-label="Formatting">
        <button type="button" data-md="## " title="Heading">H2</button>
        <button type="button" data-md="### " title="Subheading">H3</button>
        <button type="button" data-wrap="**" title="Bold"><b>B</b></button>
        <button type="button" data-wrap="*" title="Italic"><i>I</i></button>
        <button type="button" data-md="- " title="Bullet list">• List</button>
        <button type="button" data-md="> " title="Quote">❝</button>
        <button type="button" data-link title="Link">Link</button>
      </div>
      <textarea name="body" rows="20" required id="body" class="mono"><?= e($post['body']) ?></textarea>
    </label>
    <p class="muted small">Formatting: start a line with <code>##</code> for a heading, <code>-</code> for a list, <code>&gt;</code> for a quote. Use <code>**bold**</code>, <code>*italic*</code> and <code>[text](https://link)</code>. Leave a blank line between paragraphs.</p>
  </div>

  <aside class="stack">
    <div class="card stack">
      <h2>Publish</h2>
      <label>Status
        <select name="status">
          <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft (hidden)</option>
          <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        </select>
      </label>
      <label>Publish date <span class="muted">(future dates go live then)</span><input type="datetime-local" name="published_at" value="<?= $post['published_at'] ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '' ?>"></label>
      <label>Category
        <select name="category_id">
          <option value="">No category</option>
          <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int) $post['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <a href="categories.php" class="small" style="font-weight:600">Manage categories</a>
      </label>
      <button class="btn btn-block" type="submit">Save</button>
      <?php if ($post['id'] && $post['status'] === 'published'): ?><a class="btn btn-line btn-block" href="/blog/post/?slug=<?= e(urlencode($post['slug'])) ?>" target="_blank" rel="noopener">View on website</a><?php endif; ?>
    </div>
    <div class="card stack">
      <h2>Cover image</h2>
      <?php if ($post['cover']): ?><img src="<?= e($post['cover']) ?>" alt="" class="cover-prev"><label class="check"><input type="checkbox" name="remove_cover" value="1"> Remove image</label><?php endif; ?>
      <label class="file">JPG, PNG or WebP, up to 4 MB<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
    </div>
  </aside>
</form>

<script>
  // Slug follows the title until edited by hand
  const t = document.getElementById('title'), s = document.getElementById('slug');
  let manual = !!s.value;
  s.addEventListener('input', () => (manual = true));
  t.addEventListener('input', () => { if (!manual) s.value = t.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 120); });

  // Formatting buttons
  const b = document.getElementById('body');
  const edit = (fn) => { const [a, z] = [b.selectionStart, b.selectionEnd]; const r = fn(b.value.slice(a, z)); b.setRangeText(r, a, z, 'end'); b.focus(); };
  document.querySelectorAll('.md-bar button').forEach((btn) => btn.addEventListener('click', () => {
    if (btn.dataset.wrap) edit((sel) => btn.dataset.wrap + (sel || 'text') + btn.dataset.wrap);
    else if (btn.dataset.md) { const ls = b.value.lastIndexOf('\n', b.selectionStart - 1) + 1; b.setRangeText(btn.dataset.md, ls, ls, 'end'); b.focus(); }
    else if ('link' in btn.dataset) { const url = prompt('Link address (https://…)'); if (url) edit((sel) => `[${sel || 'link text'}](${url})`); }
  }));
</script>
<?php admin_foot();
