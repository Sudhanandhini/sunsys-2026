<?php
// Redirects: send an old or retired address to another page (or another site). Applied by api/_lib/redirects.php.
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

$edit = ['id' => 0, 'from_path' => '', 'to_url' => '', 'code' => 301];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'save':
            $from = redirect_path((string) ($_POST['from'] ?? ''));
            $to = redirect_target((string) ($_POST['to'] ?? ''));
            $code = ($_POST['code'] ?? '') === '302' ? 302 : 301;
            $edit = ['id' => $id, 'from_path' => clean($_POST['from'] ?? '', 255), 'to_url' => clean($_POST['to'] ?? '', 500), 'code' => $code];
            if ($from === null || $from === '/') { flash('Enter the old address as a path on this site, like /old-page.', 'err'); break; }
            if ($to === null) { flash('Enter where to send visitors: a path like /contact, or a full https:// address.', 'err'); break; }
            if (strtok($to, '?') === $from) { flash('An address can’t redirect to itself.', 'err'); break; }
            if ((int) q('SELECT COUNT(*) FROM redirects WHERE from_path = ? AND id <> ?', [$from, $id])->fetchColumn()) { flash("There is already a redirect from $from.", 'err'); break; }
            // a chain (A → B → C) still works, but warn so it can be pointed straight at the end
            $chain = q('SELECT to_url FROM redirects WHERE from_path = ? AND id <> ?', [strtok($to, '?'), $id])->fetchColumn();
            if ($id) q('UPDATE redirects SET from_path = ?, to_url = ?, code = ? WHERE id = ?', [$from, $to, $code, $id]);
            else q('INSERT INTO redirects (from_path, to_url, code) VALUES (?, ?, ?)', [$from, $to, $code]);
            flash(($id ? 'Redirect updated' : 'Redirect added') . ": $from → $to" . ($chain ? ". Note: $to itself redirects to $chain; consider pointing straight there." : '.'));
            redirect('redirects.php');
        case 'delete':
            q('DELETE FROM redirects WHERE id = ?', [$id]);
            flash('Redirect deleted.');
            redirect('redirects.php');
    }
} elseif (!empty($_GET['edit'])) {
    $edit = q('SELECT * FROM redirects WHERE id = ?', [(int) $_GET['edit']])->fetch() ?: $edit;
}

$search = clean($_GET['q'] ?? '', 100);
$rows = $search !== ''
    ? q('SELECT * FROM redirects WHERE from_path LIKE ? OR to_url LIKE ? ORDER BY from_path', ["%$search%", "%$search%"])->fetchAll()
    : q('SELECT * FROM redirects ORDER BY from_path')->fetchAll();
$site = rtrim(base_path(), '/');
admin_head('Redirects', 'redirects.php', $user);
?>
<div class="grid2" style="margin-top:0">
  <section class="card flush">
    <form class="search" method="get" style="padding:16px 16px 0"><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search addresses" aria-label="Search redirects"><button class="btn btn-line" type="submit">Search</button></form>
    <?php if (!$rows): ?><p class="empty"><?= $search !== '' ? 'No redirects match.' : 'No redirects yet. Add your first one.' ?></p><?php else: ?>
    <table class="table">
      <thead><tr><th>Old address</th><th>Sends to</th><th>Type</th><th>Visits</th><th class="r">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= e($site . $r['from_path']) ?>" target="_blank" rel="noopener" title="Test this redirect"><?= e($r['from_path']) ?></a></td>
          <td><?= e($r['to_url']) ?></td>
          <td><span class="pill"><?= (int) $r['code'] ?></span></td>
          <td class="nowrap"><?= (int) $r['hits'] ?><?php if ($r['last_hit']): ?><small class="block muted">last <?= e(date('j M Y', strtotime($r['last_hit']))) ?></small><?php endif; ?></td>
          <td class="r nowrap">
            <a class="lnk" href="?edit=<?= $r['id'] ?>">Edit</a>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this redirect? Visitors to the old address will get a “page not found” instead.')">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
  <form method="post" action="redirects.php" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
    <h2><?= $edit['id'] ? 'Edit redirect' : 'Add a redirect' ?></h2>
    <label>Old address<input name="from" required maxlength="255" value="<?= e($edit['from_path']) ?>" placeholder="/old-page"></label>
    <label>Send visitors to<input name="to" required maxlength="500" value="<?= e($edit['to_url']) ?>" placeholder="/contact or https://…"></label>
    <label>Type
      <select name="code">
        <option value="301"<?= (int) $edit['code'] !== 302 ? ' selected' : '' ?>>301 Permanent (moved for good; search engines update)</option>
        <option value="302"<?= (int) $edit['code'] === 302 ? ' selected' : '' ?>>302 Temporary (coming back later)</option>
      </select>
    </label>
    <button class="btn" type="submit"><?= $edit['id'] ? 'Save redirect' : 'Add redirect' ?></button>
    <?php if ($edit['id']): ?><a class="lnk" href="redirects.php">Cancel</a><?php endif; ?>
    <p class="muted small">Use paths on this site, like <b>/old-page</b>, without the domain. A redirect also works on an address that is still a page or article, and takes it over. Use 301 for pages that have moved for good, so search engines move their ranking across.</p>
  </form>
</div>
<?php admin_foot();
