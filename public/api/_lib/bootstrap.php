<?php
// Shared setup for the API and admin: config, database, sessions, CSRF, helpers.
declare(strict_types=1);

// Local dev passes SUNSYS_CONFIG (see scripts/dev-php.mjs); the server uses api/_lib/config.php.
$configFile = getenv('SUNSYS_CONFIG') ?: __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing api/_lib/config.php. Copy config.sample.php to config.php and fill in your database details.');
}
$CONFIG = require $configFile;

ini_set('display_errors', $CONFIG['debug'] ?? false ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Kolkata');

/** Private storage (CVs). Defaults to api/_private, which is blocked by .htaccess. */
function storage_dir(string $sub): string
{
    global $CONFIG;
    $base = rtrim($CONFIG['private_dir'] ?? (dirname(__DIR__) . '/_private'), '/\\');
    $dir = $base . '/' . $sub;
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    return $dir;
}

/** Public uploads (blog images) live in /uploads at the site root. */
function public_upload_dir(string $sub): string
{
    $dir = dirname(__DIR__, 2) . '/uploads/' . $sub;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo) return $pdo;
    $c = $CONFIG['db'];
    $pdo = new PDO(
        "mysql:host={$c['host']};port=" . ($c['port'] ?? 3306) . ";dbname={$c['name']};charset=utf8mb4",
        $c['user'],
        $c['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    migrate($pdo);
    return $pdo;
}

/** Small schema upgrades for sites installed before a feature existed. Safe to run repeatedly. */
function migrate(PDO $pdo): void
{
    $hasPosts = $pdo->query("SHOW TABLES LIKE 'posts'")->fetchColumn();
    if (!$hasPosts) return; // not installed yet; install.php creates everything
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(90) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $col = $pdo->query("SHOW COLUMNS FROM posts LIKE 'category_id'")->fetchColumn();
    if (!$col) $pdo->exec('ALTER TABLE posts ADD category_id INT UNSIGNED NULL AFTER cover, ADD INDEX (category_id)');
    $col = $pdo->query("SHOW COLUMNS FROM posts LIKE 'meta_title'")->fetchColumn();
    if (!$col) $pdo->exec("ALTER TABLE posts ADD meta_title VARCHAR(200) NOT NULL DEFAULT '' AFTER body, ADD meta_description VARCHAR(400) NOT NULL DEFAULT '' AFTER meta_title");
    $col = $pdo->query("SHOW COLUMNS FROM posts LIKE 'blocks'")->fetchColumn();
    if (!$col) $pdo->exec('ALTER TABLE posts ADD blocks MEDIUMTEXT NULL AFTER body');
    // SEO manager (Admin > SEO)
    if (!$pdo->query("SHOW TABLES LIKE 'seo_pages'")->fetchColumn()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS seo_pages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        path VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL DEFAULT '',
        type VARCHAR(30) NOT NULL DEFAULT 'Page',
        custom TINYINT(1) NOT NULL DEFAULT 0,
        title VARCHAR(200) NOT NULL DEFAULT '',
        description VARCHAR(400) NOT NULL DEFAULT '',
        keywords VARCHAR(300) NOT NULL DEFAULT '',
        canonical VARCHAR(500) NOT NULL DEFAULT '',
        og_title VARCHAR(200) NOT NULL DEFAULT '',
        og_description VARCHAR(400) NOT NULL DEFAULT '',
        og_image VARCHAR(500) NOT NULL DEFAULT '',
        schema_json MEDIUMTEXT NULL,
        noindex TINYINT(1) NOT NULL DEFAULT 0,
        nofollow TINYINT(1) NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        k VARCHAR(60) NOT NULL PRIMARY KEY,
        v MEDIUMTEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $col = $pdo->query("SHOW COLUMNS FROM posts LIKE 'noindex'")->fetchColumn();
    if (!$col) $pdo->exec('ALTER TABLE posts ADD noindex TINYINT(1) NOT NULL DEFAULT 0 AFTER meta_description, ADD nofollow TINYINT(1) NOT NULL DEFAULT 0 AFTER noindex');
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

/** Simple per-IP rate limit backed by the rate_limits table. Returns false when the limit is hit. */
function rate_ok(string $bucket, int $max, int $windowSeconds): bool
{
    $ip = client_ip();
    q('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    $n = (int) q('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at > ?', [$bucket, $ip, date('Y-m-d H:i:s', time() - $windowSeconds)])->fetchColumn();
    if ($n >= $max) return false;
    q('INSERT INTO rate_limits (bucket, ip, created_at) VALUES (?, ?, NOW())', [$bucket, $ip]);
    return true;
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? substr($s, 0, 120) : 'post-' . time();
}

function clean(?string $s, int $max = 5000): string
{
    return mb_substr(trim((string) $s), 0, $max);
}

// ---------- sessions & auth (admin) ----------

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('sunsys_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
    // Idle timeout: 2 hours
    if (isset($_SESSION['seen']) && time() - $_SESSION['seen'] > 7200) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['seen'] = time();
}

function current_admin(): ?array
{
    start_session();
    if (empty($_SESSION['admin_id'])) return null;
    static $u = null;
    if ($u === null) $u = q('SELECT id, name, email FROM admins WHERE id = ?', [$_SESSION['admin_id']])->fetch() ?: null;
    return $u;
}

function require_admin(): array
{
    $u = current_admin();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    start_session();
    if ($msg !== null) {
        $_SESSION['flash'] = [$msg, $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** Validate and store an uploaded image for the blog. Returns the public path or throws. */
function save_image(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
    if ($file['size'] > 4 * 1024 * 1024) throw new RuntimeException('Image must be 4 MB or smaller.');
    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) throw new RuntimeException('Image must be a JPG, PNG or WebP file.');
    $name = date('Ym') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], public_upload_dir('blog') . '/' . $name)) throw new RuntimeException('Could not save the image.');
    return '/uploads/blog/' . $name;
}

// ---------- form rules (same as src/lib/validate.js) ----------

/** Error message for a person's name, or '' if it is fine: letters, spaces and . ' - only. */
function name_error(string $s): string
{
    if ($s === '') return 'Enter your name.';
    if (preg_match_all('/\p{L}/u', $s) < 2) return 'Enter your full name.';
    if (!preg_match("/^\p{L}[\p{L}\p{M}]*(?:[ .'-]+\p{L}[\p{L}\p{M}]*)*\.?$/u", $s)) return 'Use letters only (no numbers or symbols).';
    return '';
}

/** Error message for a 10-digit phone number ('' if fine, or if empty and not required). */
function phone_error(string $s, bool $required = true): string
{
    if ($s === '') return $required ? 'Enter your phone number.' : '';
    if (!preg_match('/^\d{10}$/', $s)) return 'Enter a 10-digit phone number.';
    if ($s[0] === '0' || preg_match('/^(\d)\1{9}$/', $s)) return 'Enter a valid phone number.';
    return '';
}

/** Error message for an email address, or '' if it is fine. */
function email_error(string $s): string
{
    if ($s === '') return 'Enter your email address.';
    $ok = filter_var($s, FILTER_VALIDATE_EMAIL)
        && !str_contains($s, '..')
        && preg_match('/^[a-z0-9](?:[a-z0-9._%+-]{0,62}[a-z0-9])?@(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i', $s);
    return $ok ? '' : 'Enter a valid email address, like name@example.com.';
}

/** Error message for free text (message, subject): long enough and containing words. */
function text_error(string $s, int $min, string $label): string
{
    if ($s === '') return "Enter $label.";
    if (mb_strlen($s) < $min) return "Please write at least $min characters.";
    if (preg_match_all('/\p{L}/u', $s) < min($min, 3)) return 'Please describe it in words.';
    return '';
}
