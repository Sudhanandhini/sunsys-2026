<?php
require __DIR__ . '/_inc/layout.php';
$user = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$job = $id ? q('SELECT * FROM jobs WHERE id = ?', [$id])->fetch() : null;
if ($id && !$job) { flash('That job no longer exists.', 'err'); redirect('jobs.php'); }
$job = $job ?: ['id' => 0, 'title' => '', 'type' => 'Full-time', 'experience' => '', 'location' => 'Bengaluru', 'summary' => '', 'description' => '', 'status' => 'open'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['title' => 160, 'type' => 60, 'experience' => 60, 'location' => 120, 'summary' => 400, 'description' => 20000] as $k => $max) $job[$k] = clean($_POST[$k] ?? '', $max);
    $job['status'] = ($_POST['status'] ?? '') === 'closed' ? 'closed' : 'open';
    if ($job['title'] === '') $error = 'Add the job title.';
    elseif ($job['summary'] === '') $error = 'Add a one-line summary.';
    else {
        if ($job['description'] === '') $job['description'] = $job['summary'];
        $vals = [$job['title'], $job['type'], $job['experience'], $job['location'], $job['summary'], $job['description'], $job['status']];
        if ($job['id']) q('UPDATE jobs SET title=?, type=?, experience=?, location=?, summary=?, description=?, status=? WHERE id=?', [...$vals, $job['id']]);
        else {
            $order = (int) q('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM jobs')->fetchColumn();
            q('INSERT INTO jobs (title, type, experience, location, summary, description, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [...$vals, $order]);
        }
        flash('Job saved.');
        redirect('jobs.php');
    }
}

admin_head($job['id'] ? 'Edit job' : 'New job', 'jobs.php', $user);
?>
<a class="crumb" href="jobs.php">← All jobs</a>
<?php if ($error): ?><p class="alert err"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="editor">
  <?= csrf_field() ?>
  <div class="card stack">
    <label>Job title<input name="title" required maxlength="160" value="<?= e($job['title']) ?>" class="big" placeholder="e.g. React Developer"></label>
    <div class="cols3">
      <label>Type
        <select name="type"><?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $t): ?><option <?= $job['type'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
      </label>
      <label>Experience<input name="experience" maxlength="60" value="<?= e($job['experience']) ?>" placeholder="e.g. 1 to 3 years"></label>
      <label>Location<input name="location" maxlength="120" value="<?= e($job['location']) ?>"></label>
    </div>
    <label>Summary <span class="muted">(one line on the Career page)</span><input name="summary" required maxlength="400" value="<?= e($job['summary']) ?>"></label>
    <label>Full description <span class="muted">(responsibilities, skills; shown when an applicant opens the role)</span><textarea name="description" rows="12"><?= e($job['description']) ?></textarea></label>
  </div>
  <aside class="stack">
    <div class="card stack">
      <h2>Visibility</h2>
      <label>Status
        <select name="status">
          <option value="open" <?= $job['status'] === 'open' ? 'selected' : '' ?>>Open (shown on website)</option>
          <option value="closed" <?= $job['status'] === 'closed' ? 'selected' : '' ?>>Closed (hidden)</option>
        </select>
      </label>
      <button class="btn btn-block" type="submit">Save job</button>
    </div>
  </aside>
</form>
<?php admin_foot();
