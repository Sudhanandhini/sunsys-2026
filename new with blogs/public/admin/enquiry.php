<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$r = q('SELECT * FROM enquiries WHERE id = ?', [$id])->fetch();
if (!$r) { flash('Enquiry not found.', 'err'); redirect('enquiries.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        q('DELETE FROM enquiries WHERE id = ?', [$id]);
        flash('Enquiry deleted.');
        redirect('enquiries.php');
    }
    $s = $_POST['status'] ?? '';
    if (in_array($s, ['new', 'read', 'replied', 'archived'], true)) { q('UPDATE enquiries SET status = ? WHERE id = ?', [$s, $id]); flash('Status updated.'); }
    redirect('enquiry.php?id=' . $id);
}
if ($r['status'] === 'new') { q("UPDATE enquiries SET status = 'read' WHERE id = ?", [$id]); $r['status'] = 'read'; }

$sources = ['contact' => 'Contact form', 'quote' => 'Home quote form', 'newsletter' => 'Newsletter signup'];
admin_head('Enquiry', 'enquiries.php', $user);
?>
<a class="crumb" href="enquiries.php">← All enquiries</a>
<div class="editor">
  <section class="card stack">
    <div class="person">
      <span class="avatar lg"><?= e(mb_strtoupper(mb_substr($r['name'] ?: $r['email'], 0, 1))) ?></span>
      <div><h2><?= e($r['name'] ?: $r['email']) ?></h2><p class="muted"><?= e($sources[$r['source']] ?? $r['source']) ?> · <?= fmt_date($r['created_at'], true) ?></p></div>
    </div>
    <dl class="facts">
      <div><dt>Email</dt><dd><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></dd></div>
      <div><dt>Phone</dt><dd><?= $r['phone'] ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $r['phone'])) . '">' . e($r['phone']) . '</a>' : '—' ?></dd></div>
      <div><dt>Subject / service</dt><dd><?= e($r['subject'] ?: '—') ?></dd></div>
    </dl>
    <div><h3>Message</h3><p class="prewrap"><?= e($r['message']) ?></p></div>
  </section>
  <aside class="stack">
    <form method="post" class="card stack"><?= csrf_field() ?>
      <h2>Status</h2>
      <div class="seg">
        <?php foreach (['read', 'replied', 'archived', 'new'] as $s): ?><button name="status" value="<?= $s ?>" class="<?= $r['status'] === $s ? 'on' : '' ?>"><?= ucfirst($s) ?></button><?php endforeach; ?>
      </div>
      <a class="btn btn-line btn-block" href="mailto:<?= e($r['email']) ?>?subject=<?= rawurlencode('Re: ' . ($r['subject'] ?: 'Your enquiry')) ?>">Reply by email</a>
    </form>
    <form method="post" class="card"><?= csrf_field() ?><button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this enquiry permanently?')">Delete enquiry</button></form>
  </aside>
</div>
<?php admin_foot();
