<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

$n = fn(string $sql) => (int) q($sql)->fetchColumn();
$stats = [
    ['Published posts', $n("SELECT COUNT(*) FROM posts WHERE status='published'"), $n("SELECT COUNT(*) FROM posts WHERE status='draft'") . ' drafts', 'posts.php', 'blog'],
    ['Open jobs', $n("SELECT COUNT(*) FROM jobs WHERE status='open'"), $n("SELECT COUNT(*) FROM jobs WHERE status='closed'") . ' closed', 'jobs.php', 'jobs'],
    ['New applications', $n("SELECT COUNT(*) FROM applications WHERE status='new'"), $n('SELECT COUNT(*) FROM applications') . ' total', 'applications.php?status=new', 'apps'],
    ['New enquiries', $n("SELECT COUNT(*) FROM enquiries WHERE status='new'"), $n("SELECT COUNT(*) FROM enquiries WHERE created_at > NOW() - INTERVAL 30 DAY") . ' in 30 days', 'enquiries.php?status=new', 'mail'],
];
$apps = q('SELECT id, name, job_title, status, created_at FROM applications ORDER BY created_at DESC LIMIT 5')->fetchAll();
$enqs = q('SELECT id, name, email, source, subject, status, created_at FROM enquiries ORDER BY created_at DESC LIMIT 5')->fetchAll();

// Activity over the last 14 days (applications + enquiries)
$days = [];
for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach (q("SELECT DATE(created_at) d, COUNT(*) c FROM (SELECT created_at FROM applications UNION ALL SELECT created_at FROM enquiries) x WHERE created_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d")->fetchAll() as $r) {
    if (isset($days[$r['d']])) $days[$r['d']] = (int) $r['c'];
}
$max = max(1, ...array_values($days));

admin_head('Dashboard', 'index.php', $user);
?>
<p class="hello">Good to see you, <?= e(explode(' ', $user['name'])[0]) ?>. Here is what is happening on the website.</p>

<div class="stats">
  <?php foreach ($stats as $i => [$label, $value, $sub, $href, $ic]): ?>
    <a class="stat" href="<?= $href ?>" style="--i:<?= $i ?>">
      <span class="stat-ic"><?= icon($ic) ?></span>
      <span class="stat-val"><?= $value ?></span>
      <span class="stat-label"><?= e($label) ?></span>
      <span class="stat-sub"><?= e($sub) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card-head"><h2>Activity</h2><span class="muted">Applications and enquiries, last 14 days</span></div>
  <div class="chart" role="img" aria-label="Daily applications and enquiries for the last 14 days">
    <?php foreach ($days as $d => $c): ?>
      <div class="bar" title="<?= e(date('j M', strtotime($d))) ?>: <?= $c ?>"><span style="--h:<?= round($c / $max * 100) ?>%"><?php if ($c): ?><b><?= $c ?></b><?php endif; ?></span><em><?= date('j', strtotime($d)) ?></em></div>
    <?php endforeach; ?>
  </div>
</section>

<div class="grid2">
  <section class="card">
    <div class="card-head"><h2>Latest applications</h2><a href="applications.php">View all</a></div>
    <?php if (!$apps): ?><p class="empty">No applications yet.</p><?php else: ?>
    <ul class="list">
      <?php foreach ($apps as $a): ?>
        <li><a href="application.php?id=<?= $a['id'] ?>"><span><b><?= e($a['name']) ?></b><small><?= e($a['job_title']) ?> · <?= fmt_date($a['created_at']) ?></small></span><?= status_pill($a['status']) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>Latest enquiries</h2><a href="enquiries.php">View all</a></div>
    <?php if (!$enqs): ?><p class="empty">No enquiries yet.</p><?php else: ?>
    <ul class="list">
      <?php foreach ($enqs as $q): ?>
        <li><a href="enquiry.php?id=<?= $q['id'] ?>"><span><b><?= e($q['name'] ?: $q['email']) ?></b><small><?= e(ucfirst($q['source'])) ?><?= $q['subject'] ? ' · ' . e($q['subject']) : '' ?> · <?= fmt_date($q['created_at']) ?></small></span><?= status_pill($q['status']) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>

<section class="card quick">
  <a class="btn" href="post-edit.php">+ New blog post</a>
  <a class="btn btn-line" href="job-edit.php">+ New job opening</a>
</section>
<?php admin_foot();
