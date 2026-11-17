<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'export') {
        // CSV of newsletter signups
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="newsletter-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['email', 'signed_up']);
        foreach (q("SELECT email, MIN(created_at) d FROM enquiries WHERE source = 'newsletter' GROUP BY email ORDER BY d") as $r) fputcsv($out, [$r['email'], $r['d']]);
        exit;
    }
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), fn($i) => $i > 0));
    if (($_POST['action'] ?? '') === 'delete' && $ids) {
        q('DELETE FROM enquiries WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
        flash(count($ids) === 1 ? 'Enquiry deleted.' : count($ids) . ' enquiries deleted.');
    }
    redirect('enquiries.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

$statuses = ['new', 'read', 'replied', 'archived'];
$sources = ['contact' => 'Contact form', 'quote' => 'Quote form', 'newsletter' => 'Newsletter'];
$status = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$source = isset($sources[$_GET['source'] ?? '']) ? $_GET['source'] : '';
$search = clean($_GET['q'] ?? '', 100);

$where = $status ? 'WHERE status = ?' : "WHERE status <> 'archived'";
$args = $status ? [$status] : [];
if ($source) { $where .= ' AND source = ?'; $args[] = $source; }
if ($search !== '') { $where .= ' AND (name LIKE ? OR email LIKE ? OR subject LIKE ?)'; array_push($args, "%$search%", "%$search%", "%$search%"); }
$total = (int) q("SELECT COUNT(*) FROM enquiries $where", $args)->fetchColumn();
[$per, $off, $page, $pages] = paginate($total);
$rows = q("SELECT id, source, name, email, phone, subject, message, status, created_at FROM enquiries $where ORDER BY created_at DESC LIMIT $per OFFSET $off", $args)->fetchAll();

admin_head('Enquiries', 'enquiries.php', $user);
$keep = fn(array $x) => e(http_build_query(array_filter($x + ['source' => $source ?: null, 'q' => $search ?: null])));
?>
<div class="toolbar">
  <nav class="tabs">
    <a href="?<?= $keep(['status' => null]) ?>" class="<?= !$status ? 'on' : '' ?>">Inbox</a>
    <?php foreach ($statuses as $s): ?><a href="?<?= $keep(['status' => $s]) ?>" class="<?= $status === $s ? 'on' : '' ?>"><?= ucfirst($s) ?></a><?php endforeach; ?>
  </nav>
  <form class="search" method="get">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <select name="source" onchange="this.form.submit()" aria-label="Filter by form"><option value="">All forms</option><?php foreach ($sources as $k => $v): ?><option value="<?= $k ?>" <?= $source === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <input name="q" placeholder="Search" value="<?= e($search) ?>" aria-label="Search enquiries">
  </form>
  <form method="post"><?= csrf_field() ?><button class="btn btn-line" name="action" value="export">Export newsletter CSV</button></form>
</div>

<form method="post" id="bulk"><?= csrf_field() ?><input type="hidden" name="action" value="delete"></form>
<section class="card flush">
  <?php if (!$rows): ?><p class="empty">Nothing here.</p><?php else: ?>
  <div class="bulkbar" hidden><span><b data-count>0</b> selected</span><button class="lnk danger" form="bulk" type="submit" onclick="return confirm('Delete the selected enquiries permanently?')">Delete selected</button></div>
  <table class="table">
    <thead><tr><th class="chk"><input type="checkbox" data-all aria-label="Select all"></th><th>From</th><th>Message</th><th>Form</th><th>Received</th><th>Status</th><th class="r">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['status'] === 'new' ? 'unread' : '' ?>">
        <td class="chk"><input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" form="bulk" aria-label="Select <?= e($r['name'] ?: $r['email']) ?>"></td>
        <td><a href="enquiry.php?id=<?= $r['id'] ?>"><b><?= e($r['name'] ?: $r['email']) ?></b></a><small class="block"><?= e($r['email']) ?></small></td>
        <td class="clip"><?= $r['subject'] ? '<b>' . e($r['subject']) . '</b> · ' : '' ?><?= e(mb_strimwidth($r['message'], 0, 80, '…')) ?></td>
        <td><?= e($sources[$r['source']] ?? $r['source']) ?></td>
        <td class="nowrap"><?= fmt_date($r['created_at']) ?></td>
        <td><?= status_pill($r['status']) ?></td>
        <td class="r nowrap"><a class="lnk" href="enquiry.php?id=<?= $r['id'] ?>">View</a> <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ids[]" value="<?= $r['id'] ?>"><button class="lnk danger" onclick="return confirm('Delete this enquiry permanently?')">Delete</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?= pager($page, $pages) ?>
<script src="assets/bulk.js"></script>
<?php admin_foot();
