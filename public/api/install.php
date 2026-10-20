<?php
// One-time setup: creates tables and the first admin account. Refuses to run once an admin exists.
require __DIR__ . '/_lib/bootstrap.php';

$schema = [
    "CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(140) NOT NULL UNIQUE,
        title VARCHAR(200) NOT NULL,
        excerpt VARCHAR(400) NOT NULL DEFAULT '',
        body MEDIUMTEXT NOT NULL,
        cover VARCHAR(255) NULL,
        category_id INT UNSIGNED NULL,
        status ENUM('draft','published') NOT NULL DEFAULT 'draft',
        published_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (status, published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(90) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS jobs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(160) NOT NULL,
        type VARCHAR(60) NOT NULL DEFAULT 'Full-time',
        experience VARCHAR(60) NOT NULL DEFAULT '',
        location VARCHAR(120) NOT NULL DEFAULT 'Bengaluru',
        summary VARCHAR(400) NOT NULL DEFAULT '',
        description TEXT NOT NULL,
        status ENUM('open','closed') NOT NULL DEFAULT 'open',
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS applications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        job_id INT UNSIGNED NULL,
        job_title VARCHAR(160) NOT NULL,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(30) NOT NULL DEFAULT '',
        experience VARCHAR(60) NOT NULL DEFAULT '',
        message TEXT NOT NULL,
        cv_file VARCHAR(120) NULL,
        cv_name VARCHAR(200) NULL,
        status ENUM('new','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (status), INDEX (job_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS enquiries (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(40) NOT NULL DEFAULT 'contact',
        name VARCHAR(120) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(30) NOT NULL DEFAULT '',
        subject VARCHAR(200) NOT NULL DEFAULT '',
        message TEXT NOT NULL,
        status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (status), INDEX (source)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS rate_limits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bucket VARCHAR(30) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX (bucket, ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

$error = '';
$done = false;
try {
    foreach ($schema as $sql) db()->exec($sql);
    $hasAdmin = (int) q('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
} catch (Throwable $ex) {
    $hasAdmin = false;
    $error = 'Could not connect to the database. Check the details in api/_lib/config.php. (' . $ex->getMessage() . ')';
}

if (!$error && !$hasAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '', 120);
    $email = strtolower(clean($_POST['email'] ?? '', 190));
    $pass = (string) ($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter your name and a valid email.';
    elseif (strlen($pass) < 10) $error = 'Use a password of at least 10 characters.';
    else {
        q('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        // Seed the current jobs and blog post titles so nothing disappears from the live site
        if ((int) q('SELECT COUNT(*) FROM jobs')->fetchColumn() === 0) {
            $jobs = [
                ['React Developer', 'Full-time', '1 to 3 years', 'Build React and Node.js applications for client projects.'],
                ['WordPress / PHP Developer', 'Full-time', '1 to 3 years', 'Develop custom themes and plugins and maintain client sites.'],
                ['Digital Marketing Executive', 'Full-time', '0 to 2 years', 'Run SEO and social media campaigns for our clients.'],
            ];
            foreach ($jobs as $i => $j) q('INSERT INTO jobs (title, type, experience, summary, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)', [$j[0], $j[1], $j[2], $j[3], $j[3], $i]);
        }
        if ((int) q('SELECT COUNT(*) FROM posts')->fetchColumn() === 0) {
            $posts = [
                ['How long does it take to build a business website?', 'A realistic timeline for a 10 to 15 page site, and what speeds it up or slows it down.'],
                ['WordPress or custom build: which fits your business?', 'When a CMS is the right call, and when a custom application saves money long term.'],
                ['Five checks before you switch web hosts', 'Backups, DNS, email and SSL: what to confirm so the move causes no downtime.'],
            ];
            // Drafts: these only had titles before, so they need a body before publishing
            foreach ($posts as $p) q('INSERT INTO posts (slug, title, excerpt, body, status) VALUES (?, ?, ?, ?, ?)', [slugify($p[0]), $p[0], $p[1], $p[1], 'draft']);
        }
        $done = true;
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Install · Sunsys admin</title><link rel="stylesheet" href="/admin/assets/admin.css"></head>
<body class="auth">
  <main class="auth-card">
    <p class="eyebrow">Sunsys admin</p>
    <h1>Set up</h1>
    <?php if ($error): ?><p class="alert err"><?= e($error) ?></p><?php endif; ?>
    <?php if ($done): ?>
      <p class="alert ok">All set. Your admin account is ready.</p>
      <p>For safety, delete <code>api/install.php</code> from the server now.</p>
      <a class="btn" href="/admin/login.php">Go to login</a>
    <?php elseif ($hasAdmin): ?>
      <p class="alert ok">Setup is already complete.</p>
      <p>Delete <code>api/install.php</code> from the server if it is still there.</p>
      <a class="btn" href="/admin/login.php">Go to login</a>
    <?php elseif (!$error || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
      <p class="muted">Create the first admin account. The database tables have been created.</p>
      <form method="post" class="stack">
        <label>Your name<input name="name" required maxlength="120" autocomplete="name"></label>
        <label>Email<input name="email" type="email" required autocomplete="email"></label>
        <label>Password <span class="muted">(at least 10 characters)</span><input name="password" type="password" required minlength="10" autocomplete="new-password"></label>
        <button class="btn" type="submit">Create admin account</button>
      </form>
    <?php endif; ?>
  </main>
</body></html>
