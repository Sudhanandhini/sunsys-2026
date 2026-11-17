<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'delete': q('DELETE FROM jobs WHERE id = ?', [$id]); flash('Job deleted. Its applications are kept.'); break;
        case 'open': q("UPDATE jobs SET status = 'open' WHERE id = ?", [$id]); flash('Job is now open.'); break;
        case 'close': q("UPDATE jobs SET status = 'closed' WHERE id = ?", [$id]); flash('Job closed. It no longer shows on the website.'); break;
        case 'up':
        case 'down':
            // Swap order with the neighbour
            $rows = q('SELECT id FROM jobs ORDER BY sort_order, id')->fetchAll(PDO::FETCH_COLUMN);
            $i = array_search($id, array_map('intval', $rows), true);
            $j = $_POST['action'] === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && isset($rows[$j])) { [$rows[$i], $rows[$j]] = [$rows[$j], $rows[$i]]; }
            foreach ($rows as $k => $rid) q('UPDATE jobs SET sort_order = ? WHERE id = ?', [$k, $rid]);
            break;
    }
    redirect('jobs.php');
}

$jobs = q("SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) apps, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'new') new_apps FROM jobs j ORDER BY sort_order, id")->fetchAll();

admin_head('Job openings', 'jobs.php', $user);
?>
<div class="toolbar">
  <p class="muted">Open roles appear on the Career page in this order.</p>
  <a class="btn" href="job-edit.php">+ New job</a>
</div>

<section class="card flush">
  <?php if (!$jobs): ?><p class="empty">No job openings. <a href="job-edit.php">Add one</a>.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>Role</th><th>Status</th><th>Applications</th><th class="r">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($jobs as $k => $j): ?>
      <tr>
        <td><a href="job-edit.php?id=<?= $j['id'] ?>"><b><?= e($j['title']) ?></b></a><small class="block"><?= e($j['type']) ?> · <?= e($j['experience']) ?> · <?= e($j['location']) ?></small></td>
        <td><?= status_pill($j['status']) ?></td>
        <td><a href="applications.php?job=<?= $j['id'] ?>"><?= (int) $j['apps'] ?></a><?php if ($j['new_apps']): ?> <span class="pill pill-new"><?= (int) $j['new_apps'] ?> new</span><?php endif; ?></td>
        <td class="r nowrap">
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $j['id'] ?>">
            <button class="lnk" name="action" value="up" <?= $k === 0 ? 'disabled' : '' ?> aria-label="Move up">↑</button>
            <button class="lnk" name="action" value="down" <?= $k === count($jobs) - 1 ? 'disabled' : '' ?> aria-label="Move down">↓</button>
            <a class="lnk" href="job-edit.php?id=<?= $j['id'] ?>">Edit</a>
            <?php if ($j['status'] === 'open'): ?><button class="lnk" name="action" value="close">Close</button><?php else: ?><button class="lnk" name="action" value="open">Reopen</button><?php endif; ?>
            <button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this job? Applications already received are kept.')">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?php admin_foot();
