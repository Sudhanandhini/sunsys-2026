<?php
// Admin > SEO: edit one page's SEO (?path=/about/), or add a page by hand (?new=1).
require __DIR__ . '/_inc/layout.php';
require __DIR__ . '/_inc/seo-admin.php';
$user = require_admin();

$isNew = !empty($_GET['new']);
$path = $isNew ? '' : (seo_path((string) ($_GET['path'] ?? '')) ?? '');
if (!$isNew && $path === '') { flash('Choose a page to edit.', 'err'); redirect('seo.php'); }

$built = $path !== '' ? (seo_discover()[$path] ?? null) : null;
$row = $path !== '' ? seo_page($path) : null;
if (!$isNew && !$built && !$row) { flash('That page was not found.', 'err'); redirect('seo.php'); }
$blank = ['name' => '', 'type' => $path !== '' ? seo_type($path) : 'Page', 'title' => '', 'description' => '', 'keywords' => '', 'canonical' => '', 'og_title' => '', 'og_description' => '', 'og_image' => '', 'schema_json' => '', 'noindex' => 0, 'nofollow' => 0];
$p = array_merge($blank, $row ?: []);
if ($p['name'] === '' && $built) $p['name'] = seo_name($path, $built['title']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'reset' && $path !== '') {
        q('DELETE FROM seo_pages WHERE path = ?', [$path]);
        flash($built ? 'SEO reset: the page uses the website\'s own tags again.' : 'Page removed.');
        redirect($built ? 'seo-edit.php?path=' . rawurlencode($path) : 'seo.php');
    }

    $one = fn ($k, $max) => clean(preg_replace('/\s+/u', ' ', (string) ($_POST[$k] ?? '')), $max);
    foreach (['name' => 120, 'title' => 200, 'description' => 400, 'og_title' => 200, 'og_description' => 400] as $k => $max) $p[$k] = $one($k, $max);
    $p['keywords'] = implode(', ', array_filter(array_map('trim', explode(',', $one('keywords', 300)))));
    $p['type'] = in_array($_POST['type'] ?? '', SEO_TYPES, true) ? $_POST['type'] : seo_type($path ?: '/x/');
    $p['noindex'] = empty($_POST['noindex']) ? 0 : 1;
    $p['nofollow'] = empty($_POST['nofollow']) ? 0 : 1;
    $p['schema_json'] = trim((string) ($_POST['schema_json'] ?? ''));

    // A page added by hand can have its address set or changed; built pages keep theirs
    $newPath = $path;
    if ($isNew || !$built) {
        $newPath = seo_path((string) ($_POST['path'] ?? '')) ?? '';
        if ($newPath === '') $errors['path'] = 'Enter an address like /landing/offer/ (letters, numbers, - and _ only).';
        elseif ($newPath !== $path && seo_page($newPath)) $errors['path'] = 'That page already has SEO settings. Edit it from All pages.';
        elseif ($newPath !== $path && isset(seo_discover()[$newPath])) $errors['path'] = 'That page is already listed under All pages.';
    }
    foreach (['canonical' => 'Canonical URL', 'og_image' => 'Open Graph image'] as $k => $label) {
        $v = seo_url_field((string) ($_POST[$k] ?? ''));
        if ($v === null) $errors[$k] = "$label must be a full https:// address or a path starting with /.";
        else $p[$k] = mb_substr($v, 0, 500);
    }
    if ($p['schema_json'] !== '') {
        $d = json_decode($p['schema_json'], true);
        if (!is_array($d)) $errors['schema_json'] = 'Schema is not valid JSON: ' . json_last_error_msg() . '.';
        else $p['schema_json'] = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (!$errors && !empty($_FILES['og_upload']['name'])) {
        try {
            $p['og_image'] = save_image($_FILES['og_upload']);
        } catch (RuntimeException $ex) {
            $errors['og_image'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $cols = ['name', 'type', 'title', 'description', 'keywords', 'canonical', 'og_title', 'og_description', 'og_image', 'schema_json', 'noindex', 'nofollow'];
        $vals = array_map(fn ($c) => $c === 'schema_json' && $p[$c] === '' ? null : $p[$c], $cols);
        if ($row) {
            q('UPDATE seo_pages SET path = ?, custom = ?, ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE path = ?', [$newPath, $built ? 0 : 1, ...$vals, $path]);
        } else {
            q('INSERT INTO seo_pages (path, custom, ' . implode(', ', $cols) . ') VALUES (?, ?, ' . implode(', ', array_fill(0, count($cols), '?')) . ')', [$newPath, $built ? 0 : 1, ...$vals]);
        }
        flash('SEO saved. It is live on the website now.');
        redirect('seo-edit.php?path=' . rawurlencode($newPath));
    }
    if ($isNew || !$built) $path = (string) ($_POST['path'] ?? $path);
}

$s = seo_settings();
$origin = seo_origin();
$defTitle = $built['title'] ?? '';
$defDesc = $built['description'] ?? '';
$fieldErr = fn ($k) => isset($errors[$k]) ? '<span class="field-err">' . e($errors[$k]) . '</span>' : '';

admin_head($isNew ? 'Add page' : 'SEO: ' . ($p['name'] ?: $path), 'seo.php', $user);
?>
<div class="toolbar"><?= seo_tabs($isNew ? 'seo-edit.php?new=1' : '') ?><a class="crumb" href="seo.php" style="margin:0 0 0 auto">← All pages</a></div>
<?php if ($errors): ?><p class="alert err">Check the highlighted fields.</p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="editor seo-form" data-template="<?= e($s['title_template']) ?>" data-site="<?= e($s['site_name']) ?>" data-origin="<?= e($origin) ?>" data-def-title="<?= e($defTitle) ?>" data-def-desc="<?= e($defDesc) ?>">
  <?= csrf_field() ?>
  <div class="stack">
    <div class="card stack">
      <div class="card-head"><h2><?= $isNew ? 'New page' : e($p['name'] ?: $path) ?> <span class="seo-path"><?= e($path) ?></span></h2><?php if ($built): ?><a href="<?= e($path) ?>" target="_blank" rel="noopener">View page</a><?php endif; ?></div>
      <div class="grid2">
        <label>Page name <span class="muted">(only shown in the admin)</span><input name="name" maxlength="120" value="<?= e($p['name']) ?>"></label>
        <label>Type
          <select name="type"><?php foreach (SEO_TYPES as $t): ?><option <?= $p['type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
        </label>
      </div>
      <label>Address (path)
        <input name="path" id="seo-path" value="<?= e($path) ?>" placeholder="/landing/offer/" <?= $built ? 'readonly' : 'required' ?>>
        <?= $fieldErr('path') ?>
      </label>
      <?php if ($built): ?>
        <p class="note">This address comes from the website build, so it can't be changed here. Everything below is applied to the live page straight away, with no rebuild needed.</p>
      <?php else: ?>
        <p class="note">Use this for a page that will be added to the website later: its SEO applies as soon as a page exists at this address.</p>
      <?php endif; ?>
    </div>

    <div class="card stack">
      <div class="card-head"><h2>Search result</h2><span class="muted small">Google preview</span></div>
      <div class="serp" aria-hidden="true">
        <span class="serp-url" id="serp-url"></span>
        <span class="serp-title" id="serp-title"></span>
        <span class="serp-desc" id="serp-desc"></span>
      </div>
      <label>Title tag <span class="muted">(aim for 50 to 60 characters; placed in the title template "<?= e($s['title_template']) ?>")</span>
        <input name="title" id="seo-title" maxlength="200" value="<?= e($p['title']) ?>" placeholder="<?= e($defTitle ?: 'Page title') ?>">
        <span class="count" data-count="seo-title" data-max="60"></span>
      </label>
      <label>Meta description <span class="muted">(aim for 120 to 160 characters)</span>
        <textarea name="description" id="seo-desc" rows="3" maxlength="400" placeholder="<?= e($defDesc ?: 'What the page offers, in a sentence or two') ?>"><?= e($p['description']) ?></textarea>
        <span class="count" data-count="seo-desc" data-max="160"></span>
      </label>
      <label>Meta keywords <span class="muted">(comma separated; Google ignores these, some other engines don't)</span>
        <input name="keywords" maxlength="300" value="<?= e($p['keywords']) ?>" placeholder="website design bangalore, web development">
      </label>
      <label>Canonical URL <span class="muted">(leave empty to use this page's own address)</span>
        <input name="canonical" maxlength="500" value="<?= e($p['canonical']) ?>" placeholder="<?= e($origin . ($path ?: '/')) ?>">
        <?= $fieldErr('canonical') ?>
      </label>
    </div>

    <div class="card stack">
      <div class="card-head"><h2>Social sharing</h2><span class="muted small">Facebook, LinkedIn, WhatsApp, X</span></div>
      <div class="og-card" aria-hidden="true">
        <div class="og-img" id="og-img"<?= $p['og_image'] || $s['default_og_image'] ? ' style="background-image:url(\'' . e(seo_abs($p['og_image'] ?: $s['default_og_image'])) . '\')"' : '' ?>></div>
        <div class="og-body"><span class="og-host"><?= e(parse_url($origin, PHP_URL_HOST) ?: $origin) ?></span><b id="og-title"></b><span id="og-desc"></span></div>
      </div>
      <label>Open Graph title <span class="muted">(leave empty to use the title tag)</span><input name="og_title" id="og-title-in" maxlength="200" value="<?= e($p['og_title']) ?>"></label>
      <label>Open Graph description <span class="muted">(leave empty to use the meta description)</span><textarea name="og_description" id="og-desc-in" rows="2" maxlength="400"><?= e($p['og_description']) ?></textarea></label>
      <div class="grid2">
        <label>Open Graph image URL <span class="muted">(1200 × 630 works best)</span>
          <input name="og_image" maxlength="500" value="<?= e($p['og_image']) ?>" placeholder="<?= e($s['default_og_image'] ?: '/uploads/blog/share.jpg or https://…') ?>">
          <?= $fieldErr('og_image') ?>
        </label>
        <label class="file">…or upload one (JPG, PNG or WebP, up to 4 MB)<input type="file" name="og_upload" accept="image/jpeg,image/png,image/webp"></label>
      </div>
    </div>

    <div class="card stack">
      <div class="card-head"><h2>Structured data</h2><span class="muted small">Schema.org JSON-LD</span></div>
      <label>Schema (JSON-LD) <span class="muted">(optional; added to the page as a <code>&lt;script type="application/ld+json"&gt;</code> tag)</span>
        <textarea name="schema_json" rows="10" class="mono" spellcheck="false" placeholder='{ "@context": "https://schema.org", "@type": "LocalBusiness", "name": "Sunsys Technologies" }'><?= e($p['schema_json'] ?? '') ?></textarea>
        <?= $fieldErr('schema_json') ?>
      </label>
      <p class="muted small">Check it with Google's <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener">Rich Results Test</a> after saving.</p>
    </div>
  </div>

  <aside class="stack">
    <div class="card stack">
      <h2>Search engines</h2>
      <label class="check"><input type="checkbox" name="noindex" value="1" <?= $p['noindex'] ? 'checked' : '' ?>> Hide from search engines (noindex)</label>
      <label class="check"><input type="checkbox" name="nofollow" value="1" <?= $p['nofollow'] ? 'checked' : '' ?>> Don't follow links on this page (nofollow)</label>
      <p class="muted small">Hidden pages are also left out of the sitemap.</p>
      <button class="btn btn-block" type="submit">Save changes</button>
      <?php if ($row): ?>
        <button class="btn btn-line btn-block" type="submit" name="action" value="reset" formnovalidate onclick="return confirm('<?= $built ? 'Remove the custom SEO for this page and use the website\'s own tags?' : 'Remove this page?' ?>')"><?= $built ? 'Reset to website defaults' : 'Remove page' ?></button>
      <?php endif; ?>
    </div>
    <?php if ($built): ?>
    <div class="card stack small">
      <h2>From the website</h2>
      <p class="muted">Used when the fields here are empty.</p>
      <p><b>Title</b><br><?= e($defTitle) ?></p>
      <p><b>Description</b><br><?= e($defDesc ?: '—') ?></p>
    </div>
    <?php endif; ?>
  </aside>
</form>
<script src="assets/seo-preview.js"></script>
<?php admin_foot();
