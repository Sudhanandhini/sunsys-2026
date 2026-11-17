<?php
// Admin > SEO: every page of the website with its title and indexing, bulk index/follow changes.
require __DIR__ . '/_inc/layout.php';
require __DIR__ . '/_inc/seo-admin.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $set = ['noindex' => ['noindex', 1], 'index' => ['noindex', 0], 'nofollow' => ['nofollow', 1], 'follow' => ['nofollow', 0]][$action] ?? null;
    $n = 0;
    if ($set) {
        [$col, $val] = $set;
        foreach ((array) ($_POST['ids'] ?? []) as $key) {
            if (str_starts_with((string) $key, 'page:') && ($path = seo_path(substr((string) $key, 5)))) {
                q("INSERT INTO seo_pages (path, type, $col) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE $col = VALUES($col)", [$path, seo_type($path), $val]);
                $n++;
            }
        }
    }
    flash($n ? "Updated $n " . ($n === 1 ? 'page' : 'pages') . '.' : 'Select at least one page.', $n ? 'ok' : 'err');
    redirect('seo.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

$rows = seo_admin_rows();
$search = clean($_GET['q'] ?? '', 100);
$type = clean($_GET['type'] ?? '', 30);
$types = array_values(array_unique(array_column($rows, 'type')));
sort($types);
$rows = array_values(array_filter($rows, function ($r) use ($search, $type) {
    if ($type !== '' && $r['type'] !== $type) return false;
    if ($search === '') return true;
    return mb_stripos($r['name'] . ' ' . $r['path'] . ' ' . $r['title'], $search) !== false;
}));
[$per, $off, $page, $pages] = paginate(count($rows), 25);
$shown = array_slice($rows, $off, $per);
$noindexCount = count(array_filter($rows, fn ($r) => $r['noindex']));

/** Badge that switches one page's indexing or following when clicked. */
function seo_toggle(string $key, string $action, string $label, bool $on): string
{
    $ask = ['noindex' => 'Hide this page from search engines?', 'nofollow' => 'Stop search engines following links on this page?'][$action] ?? '';
    $confirm = $ask ? ' onclick="return confirm(' . e(json_encode($ask)) . ')"' : '';
    return '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="ids[]" value="' . e($key) . '">'
        . '<button class="pill pill-btn ' . ($on ? 'pill-published' : 'pill-rejected') . '" name="action" value="' . $action . '" title="Click to switch to ' . $action . '"' . $confirm . '>' . $label . '</button></form>';
}

admin_head('SEO', 'seo.php', $user);
?>
<div class="toolbar">
  <?= seo_tabs('seo.php') ?>
  <form class="search" method="get">
    <input name="q" placeholder="Search page, path or title" value="<?= e($search) ?>" aria-label="Search pages">
    <select name="type" aria-label="Filter by type" onchange="this.form.submit()">
      <option value="">All types</option>
      <?php foreach ($types as $t): ?><option <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
    </select>
  </form>
</div>

<?php if (!array_filter($rows, fn ($r) => $r['built']) && $search === '' && $type === ''): ?>
  <p class="alert err">No website pages found yet. Run <code>npm run build</code> once and reload: pages are listed from the build.</p>
<?php endif; ?>
<p class="muted small">Website pages. Blog posts have their own SEO fields in <a href="posts.php">Blog posts</a> → Edit.</p>

<form method="post" id="bulk"><?= csrf_field() ?></form>
<section class="card flush">
  <div class="bulkbar" hidden>
    <span><b data-count>0</b> selected</span>
    <span class="bulk-actions">
      <span class="muted small">Set selected to:</span>
      <button class="seo-act ok" form="bulk" name="action" value="index">✓ Index</button>
      <button class="seo-act no" form="bulk" name="action" value="noindex" onclick="return confirm('Hide the selected pages from Google and other search engines?')">✕ Noindex</button>
      <button class="seo-act ok" form="bulk" name="action" value="follow">✓ Follow</button>
      <button class="seo-act no" form="bulk" name="action" value="nofollow" onclick="return confirm('Tell search engines not to follow links on the selected pages?')">✕ Nofollow</button>
    </span>
  </div>
  <?php if (!$shown): ?><p class="empty">No pages match. <a href="seo.php">Show all</a></p><?php else: ?>
  <table class="table">
    <thead><tr><th class="chk"><input type="checkbox" data-all aria-label="Select all"></th><th>Page</th><th>Path</th><th>Type</th><th>Title</th><th>Indexing</th><th>Following</th><th class="r">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($shown as $r): ?>
      <tr>
        <td class="chk"><input type="checkbox" name="ids[]" value="<?= e($r['key']) ?>" form="bulk" aria-label="Select <?= e($r['name']) ?>"></td>
        <td><a href="<?= e($r['edit']) ?>"><b><?= e($r['name']) ?></b></a><?php if ($r['edited']): ?> <span class="pill pill-reviewed">Custom SEO</span><?php endif; ?><?php if (!$r['built']): ?> <span class="pill pill-draft">Not built</span><?php endif; ?></td>
        <td><code class="path"><?= e($r['path']) ?></code></td>
        <td class="nowrap"><?= e($r['type']) ?></td>
        <td class="seo-title-cell"><?= $r['title'] !== '' ? e($r['title']) : '<span class="muted">—</span>' ?></td>
        <td><?= seo_toggle($r['key'], $r['noindex'] ? 'index' : 'noindex', $r['noindex'] ? 'noindex' : 'index', !$r['noindex']) ?></td>
        <td><?= seo_toggle($r['key'], $r['nofollow'] ? 'follow' : 'nofollow', $r['nofollow'] ? 'nofollow' : 'follow', !$r['nofollow']) ?></td>
        <td class="r nowrap"><a class="lnk" href="<?= e($r['edit']) ?>">Edit</a><?php if ($r['view']): ?> <a class="lnk" href="<?= e($r['view']) ?>" target="_blank" rel="noopener">View</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?= pager($page, $pages) ?>
<p class="muted small seo-foot"><?= count($rows) ?> pages<?= $noindexCount ? " · $noindexCount hidden from search engines" : '' ?> · <a href="../sitemap.xml" target="_blank" rel="noopener">sitemap.xml</a> · <a href="../robots.txt" target="_blank" rel="noopener">robots.txt</a></p>
<script src="assets/bulk.js"></script>
<?php admin_foot();
