<?php
// Admin page frame: sidebar navigation + header. Pages call admin_head() and admin_foot().
require dirname(__DIR__, 2) . '/api/_lib/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

function counts(): array
{
    return [
        'apps' => (int) q("SELECT COUNT(*) FROM applications WHERE status = 'new'")->fetchColumn(),
        'enq' => (int) q("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn(),
    ];
}

function icon(string $name): string
{
    $p = [
        'dash' => 'M3 13h8V3H3zm10 8h8V11h-8zM3 21h8v-6H3zm10-18v6h8V3z',
        'blog' => 'M4 4h16v16H4zM8 8h8M8 12h8M8 16h5',
        'jobs' => 'M4 7h16v12H4zM9 7V5h6v2M4 12h16',
        'apps' => 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
        'mail' => 'M3 5h18v14H3zM3 6l9 7 9-7',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 9a8 8 0 0 1 16 0',
        'out' => 'M15 4h4v16h-4M10 8l-4 4 4 4M6 12h11',
        'seo' => 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm9 16-4-4M8 11h6M11 8v6',
        'site' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18',
    ][$name] ?? '';
    return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $p . '"/></svg>';
}

function admin_head(string $title, string $active, array $user): void
{
    $c = counts();
    $nav = [
        ['index.php', 'dash', 'Dashboard', 0],
        ['posts.php', 'blog', 'Blog posts', 0],
        ['jobs.php', 'jobs', 'Job openings', 0],
        ['applications.php', 'apps', 'Applications', $c['apps']],
        ['enquiries.php', 'mail', 'Enquiries', $c['enq']],
        ['seo.php', 'seo', 'SEO', 0],
        ['account.php', 'user', 'Account', 0],
    ];
    $f = flash();
    ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($title) ?> · Sunsys admin</title><link rel="icon" href="/favicon.svg"><link rel="stylesheet" href="/admin/assets/admin.css"></head>
<body>
<input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
<aside class="side">
  <a class="brand" href="index.php"><img src="/logo-white.png" alt="Sunsys Technologies" width="140" height="29"><span>Admin</span></a>
  <nav>
    <?php foreach ($nav as [$href, $ic, $label, $badge]): ?>
      <a href="<?= $href ?>" class="<?= $active === $href ? 'on' : '' ?>"><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($badge): ?><b class="badge"><?= $badge ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <!-- <a href="/" target="_blank" rel="noopener"><?= icon('site') ?><span>View website</span></a> -->
    <form method="post" action="logout.php"><?= csrf_field() ?><button type="submit"><?= icon('out') ?><span>Log out</span></button></form>
  </div>
</aside>
<label for="nav-toggle" class="scrim" aria-hidden="true"></label>
<div class="main">
  <header class="top">
    <label for="nav-toggle" class="burger" aria-label="Menu"><span></span></label>
    <h1><?= e($title) ?></h1>
    <span class="who"><span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span class="who-name"><?= e($user['name']) ?></span></span>
  </header>
  <div class="content">
    <?php if ($f): ?><p class="alert <?= $f[1] === 'err' ? 'err' : 'ok' ?>"><?= e($f[0]) ?></p><?php endif; ?>
<?php
}

function admin_foot(): void
{
    echo "  </div>\n</div>\n</body></html>";
}

function status_pill(string $s): string
{
    return '<span class="pill pill-' . e($s) . '">' . e(ucfirst($s)) . '</span>';
}

function fmt_date(?string $d, bool $time = false): string
{
    return $d ? date($time ? 'j M Y, g:i a' : 'j M Y', strtotime($d)) : '—';
}

/** Small pager: returns [limit, offset, page, pages]. */
function paginate(int $total, int $per = 20): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
    return [$per, ($page - 1) * $per, $page, $pages];
}

function pager(int $page, int $pages): string
{
    if ($pages < 2) return '';
    $qs = $_GET;
    $h = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs['page'] = $i;
        $h .= $i === $page ? "<span>$i</span>" : '<a href="?' . e(http_build_query($qs)) . "\">$i</a>";
    }
    return $h . '</nav>';
}
