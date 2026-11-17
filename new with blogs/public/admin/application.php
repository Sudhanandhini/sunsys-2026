<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$a = q('SELECT * FROM applications WHERE id = ?', [$id])->fetch();
if (!$a) { flash('Application not found.', 'err'); redirect('applications.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        if ($a['cv_file']) @unlink(storage_dir('cv') . '/' . basename($a['cv_file']));
        q('DELETE FROM applications WHERE id = ?', [$id]);
        flash('Application deleted.');
        redirect('applications.php');
    }
    $s = $_POST['status'] ?? '';
    if (in_array($s, ['new', 'reviewed', 'shortlisted', 'rejected'], true)) {
        q('UPDATE applications SET status = ? WHERE id = ?', [$s, $id]);
        flash('Status updated.');
    }
    redirect('application.php?id=' . $id);
}
// Opening a new application marks it reviewed
if ($a['status'] === 'new') { q("UPDATE applications SET status = 'reviewed' WHERE id = ?", [$id]); $a['status'] = 'reviewed'; }

admin_head('Application', 'applications.php', $user);
?>
<a class="crumb" href="applications.php">← All applications</a>
<div class="editor">
  <section class="card stack">
    <div class="person">
      <span class="avatar lg"><?= e(mb_strtoupper(mb_substr($a['name'], 0, 1))) ?></span>
      <div><h2><?= e($a['name']) ?></h2><p class="muted">Applied for <b><?= e($a['job_title']) ?></b> · <?= fmt_date($a['created_at'], true) ?></p></div>
    </div>
    <dl class="facts">
      <div><dt>Email</dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd></div>
      <div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $a['phone'])) ?>"><?= e($a['phone']) ?></a></dd></div>
      <div><dt>Experience</dt><dd><?= e($a['experience'] ?: '—') ?></dd></div>
      <div><dt>CV</dt><dd><?php if ($a['cv_file']): ?><a href="cv.php?id=<?= $a['id'] ?>"><?= e($a['cv_name'] ?: 'Download CV') ?></a><?php else: ?>Not attached<?php endif; ?></dd></div>
    </dl>
    <div><h3>Message</h3><p class="prewrap"><?= e($a['message'] ?: '—') ?></p></div>
  </section>
  <aside class="stack">
    <form method="post" class="card stack"><?= csrf_field() ?>
      <h2>Status</h2>
      <div class="seg">
        <?php foreach (['reviewed', 'shortlisted', 'rejected', 'new'] as $s): ?>
          <button name="status" value="<?= $s ?>" class="<?= $a['status'] === $s ? 'on' : '' ?>"><?= ucfirst($s) ?></button>
        <?php endforeach; ?>
      </div>
      <a class="btn btn-line btn-block" href="mailto:<?= e($a['email']) ?>?subject=<?= rawurlencode('Your application: ' . $a['job_title']) ?>">Reply by email</a>
    </form>
    <form method="post" class="card"><?= csrf_field() ?>
      <button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this application and its CV permanently?')">Delete application</button>
    </form>
  </aside>
</div>
<?php admin_foot();
