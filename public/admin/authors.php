<?php
// Admin > Blog posts > Authors: who writes the articles. Each post can name an author (post editor); the author's
// photo, name, designation and LinkedIn profile, and the short description, are shown at the end of the article.
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

$blank = ['id' => 0, 'name' => '', 'designation' => '', 'bio' => '', 'linkedin' => '', 'photo' => null];
$form = $blank;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $row = $id ? q('SELECT * FROM authors WHERE id = ?', [$id])->fetch() : null;
    if ($id && !$row) { flash('That author no longer exists.', 'err'); redirect('authors.php'); }

    if (($_POST['action'] ?? '') === 'delete' && $row) {
        q('UPDATE posts SET author_id = NULL WHERE author_id = ?', [$id]);
        q('DELETE FROM authors WHERE id = ?', [$id]);
        if ($row['photo'] && str_starts_with($row['photo'], '/uploads/blog/')) @unlink(dirname(__DIR__) . $row['photo']);
        flash('Author deleted. Their articles now show no author.');
        redirect('authors.php');
    }

    $form = [
        'id' => $id,
        'name' => clean(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')), 100),
        'designation' => clean(preg_replace('/\s+/u', ' ', (string) ($_POST['designation'] ?? '')), 120),
        'bio' => clean(trim((string) ($_POST['bio'] ?? '')), 600),
        'linkedin' => trim((string) ($_POST['linkedin'] ?? '')),
        'photo' => $row['photo'] ?? null,
    ];
    if ($form['name'] === '') $errors['name'] = 'Enter the author’s name.';
    if ($form['linkedin'] !== '') {
        if (!preg_match('#^https?://#i', $form['linkedin'])) $form['linkedin'] = 'https://' . $form['linkedin'];
        if (!preg_match('#^https://([a-z]{2,3}\.)?linkedin\.com/[^\s"<>]+$#i', $form['linkedin']) || strlen($form['linkedin']) > 255) {
            $errors['linkedin'] = 'Enter a LinkedIn profile address, like https://www.linkedin.com/in/name';
        }
    }
    if (!$errors && !empty($_POST['remove_photo']) && $form['photo']) {
        if (str_starts_with($form['photo'], '/uploads/blog/')) @unlink(dirname(__DIR__) . $form['photo']);
        $form['photo'] = null;
    }
    if (!$errors && !empty($_FILES['photo']['name'])) {
        try {
            $old = $form['photo'];
            $form['photo'] = save_image($_FILES['photo']);
            if ($old && str_starts_with($old, '/uploads/blog/')) @unlink(dirname(__DIR__) . $old);
        } catch (RuntimeException $ex) {
            $errors['photo'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $vals = [$form['name'], $form['designation'], $form['bio'], $form['linkedin'], $form['photo']];
        if ($id) {
            q('UPDATE authors SET name = ?, designation = ?, bio = ?, linkedin = ?, photo = ? WHERE id = ?', [...$vals, $id]);
            flash('Author saved.');
        } else {
            q('INSERT INTO authors (name, designation, bio, linkedin, photo) VALUES (?, ?, ?, ?, ?)', $vals);
            flash("Author “{$form['name']}” added. Choose them on a post under Author.");
        }
        redirect('authors.php');
    }
} elseif (isset($_GET['edit'])) {
    $form = q('SELECT * FROM authors WHERE id = ?', [(int) $_GET['edit']])->fetch() ?: $blank;
}

$authors = q('SELECT a.*, COUNT(p.id) posts FROM authors a LEFT JOIN posts p ON p.author_id = a.id GROUP BY a.id ORDER BY a.name')->fetchAll();
$err = fn ($k) => isset($errors[$k]) ? '<span class="field-err">' . e($errors[$k]) . '</span>' : '';
admin_head('Blog authors', 'posts.php', $user);
?>
<a class="crumb" href="posts.php">← Blog posts</a>
<?php if ($errors): ?><p class="alert err">Check the highlighted fields.</p><?php endif; ?>
<div class="grid2" style="margin-top:0">
  <section class="card flush">
    <?php if (!$authors): ?><p class="empty">No authors yet. Add your first one.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Author</th><th>Posts</th><th class="r">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($authors as $a): ?>
        <tr>
          <td>
            <div class="author-cell">
              <?php if ($a['photo']): ?><img src="<?= e(public_url($a['photo'])) ?>" alt="" width="44" height="44"><?php else: ?><span class="author-initial" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($a['name'], 0, 1))) ?></span><?php endif; ?>
              <span><b><?= e($a['name']) ?></b><?php if ($a['designation']): ?><br><span class="muted small"><?= e($a['designation']) ?></span><?php endif; ?></span>
            </div>
          </td>
          <td><?= (int) $a['posts'] ?></td>
          <td class="r nowrap">
            <a class="lnk" href="authors.php?edit=<?= $a['id'] ?>">Edit</a>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this author? Their articles are kept, without an author.')">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

  <form method="post" enctype="multipart/form-data" class="card stack"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
    <h2><?= $form['id'] ? 'Edit author' : 'Add an author' ?></h2>
    <label>Name<input name="name" required maxlength="100" value="<?= e($form['name']) ?>" placeholder="e.g. Manjunatha Narasimhaiah"><?= $err('name') ?></label>
    <label>Designation<input name="designation" maxlength="120" value="<?= e($form['designation']) ?>" placeholder="e.g. Founder &amp; CEO"></label>
    <label>LinkedIn profile<input name="linkedin" maxlength="255" value="<?= e($form['linkedin']) ?>" placeholder="https://www.linkedin.com/in/name"><?= $err('linkedin') ?></label>
    <label>Short description <span class="muted">(two or three sentences about the author)</span>
      <textarea name="bio" rows="4" maxlength="600"><?= e($form['bio']) ?></textarea>
    </label>
    <?php if ($form['photo']): ?>
      <div class="author-cell"><img src="<?= e(public_url($form['photo'])) ?>" alt="" width="64" height="64"><label class="check"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label></div>
    <?php endif; ?>
    <label class="file"><?= $form['photo'] ? 'Replace photo' : 'Photo' ?> <span class="muted">(square works best; JPG, PNG or WebP, up to 4 MB)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><?= $err('photo') ?></label>
    <button class="btn" type="submit"><?= $form['id'] ? 'Save author' : 'Add author' ?></button>
    <?php if ($form['id']): ?><a class="small" href="authors.php">Cancel</a><?php endif; ?>
    <p class="muted small">The photo, name, designation and LinkedIn profile, with the short description, appear in an author box at the end of each article written by this author.</p>
  </form>
</div>
<?php admin_foot();
