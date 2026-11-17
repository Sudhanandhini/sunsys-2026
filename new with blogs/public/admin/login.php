<?php
require dirname(__DIR__) . '/api/_lib/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

if (current_admin()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(clean($_POST['email'] ?? '', 190));
    $pass = (string) ($_POST['password'] ?? '');
    if (!rate_ok('login', 6, 900)) {
        $error = 'Too many attempts. Wait 15 minutes and try again.';
    } else {
        $u = q('SELECT id, password_hash FROM admins WHERE email = ?', [$email])->fetch();
        if ($u && password_verify($pass, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $u['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            q('UPDATE admins SET last_login_at = NOW() WHERE id = ?', [$u['id']]);
            q("DELETE FROM rate_limits WHERE bucket = 'login' AND ip = ?", [client_ip()]);
            redirect('index.php');
        }
        $error = 'Email or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Log in · Sunsys admin</title><link rel="icon" href="../favicon.svg"><link rel="stylesheet" href="assets/admin.css"></head>
<body class="auth">
  <div class="auth-art" aria-hidden="true"><span></span><span></span></div>
  <main class="auth-card">
    <img src="../logo-dark.png" alt="Sunsys Technologies" width="170" height="35" class="auth-logo">
    <p class="eyebrow">Admin dashboard</p>
    <h1>Welcome <em>back</em></h1>
    <?php if ($error): ?><p class="alert err" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stack">
      <?= csrf_field() ?>
      <label>Email<input name="email" type="email" required autocomplete="username" autofocus value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
      <button class="btn btn-block" type="submit">Log in</button>
    </form>
    <a class="back" href="../">← Back to website</a>
  </main>
</body></html>
