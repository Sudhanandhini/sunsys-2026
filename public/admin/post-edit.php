<?php
require __DIR__ . '/_inc/layout.php';
require dirname(__DIR__) . '/api/_lib/markdown.php';
require dirname(__DIR__) . '/api/_lib/blocks.php';
$user = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$post = $id ? q('SELECT * FROM posts WHERE id = ?', [$id])->fetch() : null;
if ($id && !$post) { flash('That post no longer exists.', 'err'); redirect('posts.php'); }
$post = $post ?: ['id' => 0, 'slug' => '', 'title' => '', 'excerpt' => '', 'body' => '', 'blocks' => null, 'meta_title' => '', 'meta_description' => '', 'noindex' => 0, 'nofollow' => 0, 'cover' => null, 'category_id' => null, 'status' => 'draft', 'published_at' => null];
$cats = q('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
$error = '';
// Images in the saved version, so ones removed in the builder can be deleted from disk
$oldImages = $post['blocks'] ? blocks_images(json_decode($post['blocks'], true) ?: []) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post['title'] = clean($_POST['title'] ?? '', 200);
    $post['slug'] = slugify(clean($_POST['slug'] ?? '', 140) ?: $post['title']);
    $post['excerpt'] = clean($_POST['excerpt'] ?? '', 400);
    $blocks = blocks_clean(json_decode((string) ($_POST['blocks'] ?? ''), true) ?: []);
    $post['blocks'] = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $post['body'] = blocks_text($blocks); // plain text, used by blog search
    $post['meta_title'] = clean(preg_replace('/\s+/', ' ', $_POST['meta_title'] ?? ''), 200);
    $post['meta_description'] = clean(preg_replace('/\s+/', ' ', $_POST['meta_description'] ?? ''), 400);
    $post['noindex'] = empty($_POST['noindex']) ? 0 : 1;
    $post['nofollow'] = empty($_POST['nofollow']) ? 0 : 1;
    $post['status'] = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $cid = (int) ($_POST['category_id'] ?? 0);
    $post['category_id'] = $cid && in_array($cid, array_map('intval', array_column($cats, 'id')), true) ? $cid : null;
    $date = clean($_POST['published_at'] ?? '', 20);
    $post['published_at'] = $date ? date('Y-m-d H:i:s', strtotime($date)) : ($post['status'] === 'published' ? ($post['published_at'] ?: date('Y-m-d H:i:s')) : $post['published_at']);

    if ($post['title'] === '') $error = 'Add a title.';
    elseif ($post['body'] === '' && !blocks_images($blocks)) $error = 'Add some content to the article.';
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
        if ($post['excerpt'] === '') $post['excerpt'] = mb_strimwidth(trim(preg_replace('/\s+/', ' ', blocks_text($blocks, true) ?: $post['body'])), 0, 200, '…');
        $vals = [$post['slug'], $post['title'], $post['excerpt'], $post['body'], $post['blocks'], $post['meta_title'], $post['meta_description'], $post['noindex'], $post['nofollow'], $post['cover'], $post['category_id'], $post['status'], $post['published_at']];
        if ($post['id']) {
            q('UPDATE posts SET slug=?, title=?, excerpt=?, body=?, blocks=?, meta_title=?, meta_description=?, noindex=?, nofollow=?, cover=?, category_id=?, status=?, published_at=? WHERE id=?', [...$vals, $post['id']]);
        } else {
            q('INSERT INTO posts (slug, title, excerpt, body, blocks, meta_title, meta_description, noindex, nofollow, cover, category_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $vals);
            $post['id'] = (int) db()->lastInsertId();
        }
        foreach (array_diff($oldImages, blocks_images($blocks), [$post['cover']]) as $gone) @unlink(dirname(__DIR__) . $gone);
        flash($post['status'] === 'published' ? 'Post saved and published.' : 'Draft saved.');
        redirect('post-edit.php?id=' . $post['id']);
    }
}

// Builder content: saved blocks, or an older Markdown post converted on the fly
$builder = $post['blocks'] ? (json_decode($post['blocks'], true) ?: []) : ($post['body'] !== '' ? blocks_from_html(markdown($post['body'])) : []);
$builder = blocks_clean($builder);
if (!$builder['rows']) $builder['rows'] = [['cols' => [['widgets' => []]]]];

admin_head($post['id'] ? 'Edit post' : 'New post', 'posts.php', $user);
?>
<a class="crumb" href="posts.php">← All posts</a>
<?php if ($error): ?><p class="alert err"><?= e($error) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="editor">
  <?= csrf_field() ?>
  <div class="stack">
  <div class="card stack">
    <label>Title<input name="title" required maxlength="200" value="<?= e($post['title']) ?>" class="big" id="title"></label>
    <label>Web address <span class="muted">/blog/post/?slug=</span><input name="slug" maxlength="140" value="<?= e($post['slug']) ?>" id="slug" placeholder="made from the title if left empty"></label>
    <label>Short summary <span class="muted">(shown on the blog list, up to 400 characters)</span><textarea name="excerpt" rows="2" maxlength="400" id="excerpt"><?= e($post['excerpt']) ?></textarea></label>
    <div class="stack">
      <span class="field-label">Content</span>
      <div id="builder" class="pb"></div>
      <input type="hidden" name="blocks" id="blocks">
      <script type="application/json" id="blocks-data"><?= json_encode($builder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>
  </div>

  <div class="card stack" id="seo">
    <div class="card-head"><h2>Search engine listing</h2><span class="muted small">SEO</span></div>
    <p class="muted small">How this post can appear in Google and when the link is shared. Leave a field empty to use the title or short summary.</p>
    <div class="serp" aria-hidden="true">
      <span class="serp-url">sunsys.in › blog › <span id="serp-slug"></span></span>
      <span class="serp-title" id="serp-title"></span>
      <span class="serp-desc" id="serp-desc"></span>
    </div>
    <label>Meta title <span class="muted">(aim for 50 to 60 characters)</span>
      <input name="meta_title" maxlength="200" value="<?= e($post['meta_title']) ?>" id="meta-title">
      <span class="count" data-count="meta-title" data-max="60"></span>
    </label>
    <label>Meta description <span class="muted">(aim for 120 to 160 characters)</span>
      <textarea name="meta_description" rows="3" maxlength="400" id="meta-desc"><?= e($post['meta_description']) ?></textarea>
      <span class="count" data-count="meta-desc" data-max="160"></span>
    </label>
    <label class="check"><input type="checkbox" name="noindex" value="1" <?= $post['noindex'] ? 'checked' : '' ?>> Hide from search engines (noindex)</label>
    <label class="check"><input type="checkbox" name="nofollow" value="1" <?= $post['nofollow'] ? 'checked' : '' ?>> Don't follow links in this post (nofollow)</label>
  </div>
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

  // SEO preview: mirrors blog_seo() in api/_lib/blog.php (empty fields fall back to the title and summary)
  const mt = document.getElementById('meta-title'), md = document.getElementById('meta-desc'), ex = document.getElementById('excerpt');
  const clip = (str, n) => (str.length > n ? str.slice(0, n - 1).trimEnd() + '…' : str);
  const seo = () => {
    const title = mt.value.trim() || (t.value.trim() ? `${t.value.trim()} | Sunsys Technologies` : '');
    const desc = md.value.trim() || ex.value.trim();
    mt.placeholder = t.value.trim() ? `${t.value.trim()} | Sunsys Technologies` : 'Uses the post title';
    md.placeholder = ex.value.trim() || 'Uses the short summary (or the start of the article)';
    document.getElementById('serp-slug').textContent = s.value || 'post';
    document.getElementById('serp-title').textContent = clip(title || 'Post title', 60);
    document.getElementById('serp-desc').textContent = clip(desc || 'The short summary or the start of the article is shown here.', 160);
    document.querySelectorAll('[data-count]').forEach((c) => {
      const field = document.getElementById(c.dataset.count), max = +c.dataset.max;
      const n = (field.value.trim() || field.placeholder).length;
      c.textContent = `${n} / ${max} characters${field.value.trim() ? '' : ' (default)'}`;
      c.classList.toggle('over', n > max);
    });
  };
  [t, s, mt, md, ex].forEach((el) => el.addEventListener('input', seo));
  seo();
</script>
<script src="assets/builder.js"></script>
<?php admin_foot();
