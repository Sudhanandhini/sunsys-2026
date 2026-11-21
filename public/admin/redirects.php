<?php
// Admin > SEO > Redirects: send old or moved addresses to their new page (301/302/307/308), or mark them gone (410).
require __DIR__ . '/_inc/layout.php';
require __DIR__ . '/_inc/seo-admin.php';
require_once dirname(__DIR__) . '/api/_lib/redirects.php';
$user = require_admin();
// Pages moved to a new address in Admin > SEO keep a 301 from their old one; list any that are missing
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_sync_page_moves();

$form = ['id' => 0, 'source' => '', 'target' => '', 'code' => 301, 'note' => '', 'active' => 1];
$error = '';
$importText = '';
$back = 'redirects.php' . (isset($_GET['q']) && $_GET['q'] !== '' ? '?q=' . rawurlencode((string) $_GET['q']) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'save':
            $form = [
                'id' => $id,
                'source' => clean($_POST['source'] ?? '', 600),
                'target' => clean($_POST['target'] ?? '', 600),
                'code' => (int) ($_POST['code'] ?? 301),
                'note' => clean($_POST['note'] ?? '', 200),
                'active' => empty($_POST['active']) ? 0 : 1,
            ];
            $error = redirect_save($form['source'], $form['target'], $form['code'], $form['note'], $form['active'], $id);
            if (!$error) {
                flash($id ? 'Redirect updated.' : 'Redirect added.');
                redirect($back);
            }
            break;
        case 'toggle':
            q('UPDATE seo_redirects SET active = 1 - active WHERE id = ?', [$id]);
            flash('Redirect ' . (q('SELECT active FROM seo_redirects WHERE id = ?', [$id])->fetchColumn() ? 'switched on.' : 'switched off.'));
            redirect($back);
        case 'delete':
            q('DELETE FROM seo_redirects WHERE id = ?', [$id]);
            flash('Redirect deleted.');
            redirect($back);
        case 'reset':
            q('UPDATE seo_redirects SET hits = 0, last_hit = NULL WHERE id = ?', [$id]);
            flash('Hit counter reset.');
            redirect($back);
        case 'import':
            $importText = (string) ($_POST['lines'] ?? '');
            $code = (int) ($_POST['code'] ?? 301);
            $ok = 0;
            $bad = [];
            foreach (preg_split('/\R/', $importText) as $n => $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                $parts = preg_split('/[\s,]+/', $line);
                $lineCode = isset($parts[2]) && isset(REDIRECT_CODES[(int) $parts[2]]) ? (int) $parts[2] : $code;
                if (isset($parts[1]) && isset(REDIRECT_CODES[(int) $parts[1]]) && (int) $parts[1] === 410) { $lineCode = 410; $parts[1] = ''; }
                $err = redirect_save($parts[0], $parts[1] ?? '', $lineCode, 'Bulk import');
                if ($err) $bad[] = 'Line ' . ($n + 1) . ': ' . $err;
                else $ok++;
            }
            if (!$bad) {
                flash($ok ? "Imported $ok " . ($ok === 1 ? 'redirect.' : 'redirects.') : 'Nothing to import.', $ok ? 'ok' : 'err');
                redirect('redirects.php');
            }
            $error = ($ok ? "Imported $ok. " : '') . 'These lines were skipped and are left in the box: ' . implode(' · ', $bad);
            // keep only the lines that failed, so they can be fixed and imported again
            $failed = array_map(fn ($b) => (int) substr($b, 5) - 1, $bad);
            $all = preg_split('/\R/', $importText);
            $importText = implode("\n", array_map(fn ($i) => $all[$i], $failed));
            break;
    }
}

if (!$form['id'] && isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r = q('SELECT * FROM seo_redirects WHERE id = ?', [(int) $_GET['edit']])->fetch();
    if ($r) $form = ['id' => (int) $r['id'], 'source' => redirect_label($r['source_path'], $r['source_query']), 'target' => $r['target'], 'code' => (int) $r['code'], 'note' => $r['note'], 'active' => (int) $r['active']];
}

$search = clean($_GET['q'] ?? '', 100);
$where = $search !== '' ? 'WHERE source_path LIKE ? OR source_query LIKE ? OR target LIKE ? OR note LIKE ?' : '';
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
$params = $search !== '' ? [$like, $like, $like, $like] : [];
$total = (int) q("SELECT COUNT(*) FROM seo_redirects $where", $params)->fetchColumn();
[$per, $off, $page, $pages] = paginate($total, 25);
$rows = q("SELECT * FROM seo_redirects $where ORDER BY created_at DESC, id DESC LIMIT $per OFFSET $off", $params)->fetchAll();
$all = (int) q('SELECT COUNT(*) FROM seo_redirects')->fetchColumn();

admin_head('Redirects', 'seo.php', $user);
?>
<div class="toolbar">
  <?= seo_tabs('redirects.php') ?>
  <form class="search" method="get">
    <input name="q" placeholder="Search old or new address" value="<?= e($search) ?>" aria-label="Search redirects">
  </form>
</div>
<?php if ($error): ?><p class="alert err"><?= e($error) ?></p><?php endif; ?>

<div class="redirect-layout">
  <section class="card flush">
    <?php if (!$rows): ?>
      <p class="empty"><?= $search !== '' ? 'No redirects match. <a href="redirects.php">Show all</a>' : 'No redirects yet. Add one when a page moves, is renamed or is removed, so visitors and Google land on the right page.' ?></p>
    <?php else: ?>
    <table class="table">
      <thead><tr><th>Old address</th><th>Redirects to</th><th>Type</th><th>Hits</th><th class="r">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $from = redirect_label($r['source_path'], $r['source_query']); ?>
        <tr<?= $r['active'] ? '' : ' class="off"' ?>>
          <td><code class="path"><?= e($from) ?></code><?php if (!$r['active']): ?> <span class="pill pill-draft">Off</span><?php endif; ?><?php if ($r['note'] !== ''): ?><br><span class="muted small"><?= e($r['note']) ?></span><?php endif; ?></td>
          <td class="redirect-to"><?= (int) $r['code'] === 410 ? '<span class="muted">— removed —</span>' : '<code class="path">' . e($r['target']) . '</code>' ?></td>
          <td class="nowrap"><span class="pill <?= in_array((int) $r['code'], [301, 308], true) ? 'pill-published' : ((int) $r['code'] === 410 ? 'pill-rejected' : 'pill-reviewed') ?>" title="<?= e(REDIRECT_CODES[(int) $r['code']] ?? '') ?>"><?= (int) $r['code'] ?></span></td>
          <td class="nowrap" title="<?= $r['last_hit'] ? 'Last used ' . e(fmt_date($r['last_hit'], true)) : 'Not used yet' ?>"><?= number_format((int) $r['hits']) ?><?php if ($r['last_hit']): ?><br><span class="muted small"><?= e(fmt_date($r['last_hit'])) ?></span><?php endif; ?></td>
          <td class="r">
            <a class="lnk" href="?<?= e(http_build_query(['edit' => $r['id']] + ($search !== '' ? ['q' => $search] : []))) ?>">Edit</a>
            <a class="lnk" href="<?= e(base_path() . $r['source_path'] . ($r['source_query'] !== '' ? '?' . $r['source_query'] : '')) ?>" target="_blank" rel="noopener" title="Open the old address to check it">Test</a>
            <form method="post" class="inline" action="<?= e($back) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="lnk" name="action" value="toggle"><?= $r['active'] ? 'Turn off' : 'Turn on' ?></button>
              <?php if ($r['hits']): ?><button class="lnk" name="action" value="reset" title="Reset the hit counter">Reset</button><?php endif; ?>
              <button class="lnk danger" name="action" value="delete" onclick="return confirm('Delete this redirect? The old address will show the 404 page again.')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

  <div class="stack">
    <form method="post" class="card stack" action="<?= e($back) ?>" id="redirect-form"><?= csrf_field() ?>
      <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $form['id'] ?>">
      <h2><?= $form['id'] ? 'Edit redirect' : 'Add a redirect' ?></h2>
      <label>Old address <span class="muted">(path or full URL)</span>
        <input name="source" required maxlength="600" value="<?= e($form['source']) ?>" placeholder="/old-page or /blog/old-post">
      </label>
      <label>Type
        <select name="code" id="redirect-code">
          <?php foreach (REDIRECT_CODES as $c => $label): ?><option value="<?= $c ?>" <?= $form['code'] === $c ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label id="redirect-target">New address <span class="muted">(path on this site or https:// URL)</span>
        <input name="target" maxlength="600" value="<?= e($form['target']) ?>" placeholder="/new-page/">
      </label>
      <label>Note <span class="muted">(optional, only shown here)</span><input name="note" maxlength="200" value="<?= e($form['note']) ?>" placeholder="e.g. Old site URL"></label>
      <label class="check"><input type="checkbox" name="active" value="1" <?= $form['active'] ? 'checked' : '' ?>> Active</label>
      <div class="redirect-btns">
        <button class="btn" type="submit"><?= $form['id'] ? 'Save changes' : 'Add redirect' ?></button>
        <?php if ($form['id']): ?><a class="btn btn-line" href="<?= e($back) ?>">Cancel</a><?php endif; ?>
      </div>
    </form>

    <div class="card stack small redirect-help">
      <h2>Which type to use</h2>
      <p><b>301 Permanent</b>: the page has moved for good. Google moves the old page's ranking to the new address. Use this in almost every case.</p>
      <p><b>302 / 307 Temporary</b>: the old page will come back (maintenance, a seasonal offer). Google keeps the old address indexed.</p>
      <p><b>308</b>: like 301, but form submissions keep their method. Rarely needed for pages.</p>
      <p><b>410 Gone</b>: the page was removed and has no replacement. Google drops it faster than a 404.</p>
      <p class="muted">Query parameters on the old address must match (extra ones like <code>utm_source</code> are fine). Chains are followed so visitors get one hop. Redirected addresses are left out of the sitemap.</p>
    </div>

    <form method="post" class="card stack"><?= csrf_field() ?><input type="hidden" name="action" value="import">
      <h2>Bulk import</h2>
      <p class="muted small">One per line: old address, new address, and optionally the type. Use <code>410</code> in place of the new address for removed pages. Lines starting with # are ignored.</p>
      <textarea name="lines" rows="6" placeholder="/old-about /about/&#10;/services.html /services/ 301&#10;https://sunsys.in/promo /offers/ 302&#10;/discontinued-page 410"><?= e($importText) ?></textarea>
      <label>Default type
        <select name="code"><?php foreach (REDIRECT_CODES as $c => $label): if ($c === 410) continue; ?><option value="<?= $c ?>"><?= e($label) ?></option><?php endforeach; ?></select>
      </label>
      <button class="btn btn-line" type="submit">Import</button>
    </form>
  </div>
</div>
<?= pager($page, $pages, $total, $per, 'redirects') ?>
<p class="muted small seo-foot"><?= $all ?> <?= $all === 1 ? 'redirect' : 'redirects' ?></p>
<script>
  // 410 has no new address
  (function () {
    var code = document.getElementById('redirect-code'), target = document.getElementById('redirect-target');
    function sync() { target.hidden = code.value === '410'; }
    code.addEventListener('change', sync); sync();
  })();
</script>
<?php admin_foot();
