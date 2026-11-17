<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), fn($i) => $i > 0));
    if (($_POST['action'] ?? '') === 'delete' && $ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        foreach (q("SELECT cv_file FROM applications WHERE id IN ($in) AND cv_file IS NOT NULL", $ids)->fetchAll(PDO::FETCH_COLUMN) as $f) @unlink(storage_dir('cv') . '/' . basename($f));
        q("DELETE FROM applications WHERE id IN ($in)", $ids);
        flash(count($ids) === 1 ? 'Application deleted.' : count($ids) . ' applications deleted.');
    }
    redirect('applications.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

$statuses = ['new', 'reviewed', 'shortlisted', 'rejected'];
$status = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$jobId = (int) ($_GET['job'] ?? 0);
$search = clean($_GET['q'] ?? '', 100);

$where = 'WHERE 1';
$args = [];
if ($status) { $where .= ' AND status = ?'; $args[] = $status; }
if ($jobId) { $where .= ' AND job_id = ?'; $args[] = $jobId; }
if ($search !== '') { $where .= ' AND (name LIKE ? OR email LIKE ?)'; $args[] = "%$search%"; $args[] = "%$search%"; }
$total = (int) q("SELECT COUNT(*) FROM applications $where", $args)->fetchColumn();
[$per, $off, $page, $pages] = paginate($total);
$rows = q("SELECT id, job_title, name, email, phone, cv_file, status, created_at FROM applications $where ORDER BY created_at DESC LIMIT $per OFFSET $off", $args)->fetchAll();
$jobs = q('SELECT id, title FROM jobs ORDER BY sort_order, id')->fetchAll();

admin_head('Applications', 'applications.php', $user);
?>
<div class="toolbar">
  <nav class="tabs">
    <a href="?<?= e(http_build_query(array_filter(['job' => $jobId ?: null, 'q' => $search ?: null]))) ?>" class="<?= !$status ? 'on' : '' ?>">All</a>
    <?php foreach ($statuses as $s): ?><a href="?<?= e(http_build_query(array_filter(['status' => $s, 'job' => $jobId ?: null, 'q' => $search ?: null]))) ?>" class="<?= $status === $s ? 'on' : '' ?>"><?= ucfirst($s) ?></a><?php endforeach; ?>
  </nav>
  <form class="search" method="get">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <select name="job" onchange="this.form.submit()" aria-label="Filter by job"><option value="">All roles</option><?php foreach ($jobs as $j): ?><option value="<?= $j['id'] ?>" <?= $jobId === (int) $j['id'] ? 'selected' : '' ?>><?= e($j['title']) ?></option><?php endforeach; ?></select>
    <input name="q" placeholder="Search name or email" value="<?= e($search) ?>" aria-label="Search applications">
  </form>
</div>

<form method="post" id="bulk"><?= csrf_field() ?><input type="hidden" name="action" value="delete"></form>
<section class="card flush">
  <?php if (!$rows): ?><p class="empty">No applications match.</p><?php else: ?>
  <div class="bulkbar" hidden><span><b data-count>0</b> selected</span><button class="lnk danger" form="bulk" type="submit" onclick="return confirm('Delete the selected applications and their CVs permanently?')">Delete selected</button></div>
  <table class="table">
    <thead><tr><th class="chk"><input type="checkbox" data-all aria-label="Select all"></th><th>Applicant</th><th>Role</th><th>Received</th><th>Status</th><th>CV</th><th class="r">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $a): ?>
      <tr class="<?= $a['status'] === 'new' ? 'unread' : '' ?>">
        <td class="chk"><input type="checkbox" name="ids[]" value="<?= $a['id'] ?>" form="bulk" aria-label="Select <?= e($a['name']) ?>"></td>
        <td><a href="application.php?id=<?= $a['id'] ?>"><b><?= e($a['name']) ?></b></a><small class="block"><?= e($a['email']) ?> · <?= e($a['phone']) ?></small></td>
        <td><?= e($a['job_title']) ?></td>
        <td class="nowrap"><?= fmt_date($a['created_at']) ?></td>
        <td><?= status_pill($a['status']) ?></td>
        <td><?php if ($a['cv_file']): ?><a class="lnk" href="cv.php?id=<?= $a['id'] ?>">Download</a><?php else: ?><span class="muted">—</span><?php endif; ?></td>
        <td class="r nowrap"><a class="lnk" href="application.php?id=<?= $a['id'] ?>">View</a> <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ids[]" value="<?= $a['id'] ?>"><button class="lnk danger" onclick="return confirm('Delete this application and its CV permanently?')">Delete</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?= pager($page, $pages) ?>
<script src="assets/bulk.js"></script>
<?php admin_foot();
