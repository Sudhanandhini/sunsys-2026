<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'password') {
        $hash = q('SELECT password_hash FROM admins WHERE id = ?', [$user['id']])->fetchColumn();
        $new = (string) ($_POST['new'] ?? '');
        if (!password_verify((string) ($_POST['current'] ?? ''), $hash)) flash('Current password is incorrect.', 'err');
        elseif (strlen($new) < 10) flash('New password must be at least 10 characters.', 'err');
        elseif ($new !== ($_POST['confirm'] ?? '')) flash('The new passwords do not match.', 'err');
        else { q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]); session_regenerate_id(true); flash('Password changed.'); }
    } elseif ($action === 'add') {
        $name = clean($_POST['name'] ?? '', 120);
        $email = strtolower(clean($_POST['email'] ?? '', 190));
        $pass = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Enter a name and a valid email.', 'err');
        elseif (strlen($pass) < 10) flash('Password must be at least 10 characters.', 'err');
        elseif (q('SELECT id FROM admins WHERE email = ?', [$email])->fetch()) flash('An admin with that email already exists.', 'err');
        else { q('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]); flash("$name can now log in."); }
    } elseif ($action === 'remove') {
        $rid = (int) ($_POST['id'] ?? 0);
        if ($rid === (int) $user['id']) flash('You cannot remove your own account.', 'err');
        else { q('DELETE FROM admins WHERE id = ?', [$rid]); flash('Admin removed.'); }
    }
    redirect('account.php');
}

$admins = q('SELECT id, name, email, last_login_at FROM admins ORDER BY name')->fetchAll();
admin_head('Account', 'account.php', $user);
?>
<div class="grid2">
  <form method="post" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="password">
    <h2>Change your password</h2>
    <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
    <label>New password <span class="muted">(at least 10 characters)</span><input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
    <label>Confirm new password<input type="password" name="confirm" required minlength="10" autocomplete="new-password"></label>
    <button class="btn" type="submit">Update password</button>
  </form>

  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>Admins</h2></div>
      <ul class="list">
        <?php foreach ($admins as $a): ?>
          <li><span class="li-row"><span><b><?= e($a['name']) ?></b><?= (int) $a['id'] === (int) $user['id'] ? ' <span class="muted">(you)</span>' : '' ?><small><?= e($a['email']) ?> · last login <?= fmt_date($a['last_login_at'], true) ?></small></span>
            <?php if ((int) $a['id'] !== (int) $user['id']): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="lnk danger" name="action" value="remove" onclick="return confirm('Remove this admin?')">Remove</button></form><?php endif; ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <form method="post" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="add">
      <h2>Add an admin</h2>
      <label>Name<input name="name" required maxlength="120"></label>
      <label>Email<input type="email" name="email" required></label>
      <label>Temporary password<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
      <button class="btn btn-line" type="submit">Add admin</button>
    </form>
  </div>
</div>
<?php admin_foot();
