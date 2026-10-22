<?php
// Admin > SEO > Site settings: site-wide defaults, verification codes, robots.txt and sitemap.
require __DIR__ . '/_inc/layout.php';
require __DIR__ . '/_inc/seo-admin.php';
$user = require_admin();

$s = seo_settings();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $one = fn ($k, $max) => clean(preg_replace('/\s+/u', ' ', (string) ($_POST[$k] ?? '')), $max);
    $s['site_name'] = $one('site_name', 120);
    $s['title_template'] = $one('title_template', 120) ?: '%s';
    $s['default_description'] = $one('default_description', 400);
    $s['google_verification'] = seo_verification_code((string) ($_POST['google_verification'] ?? ''));
    $s['bing_verification'] = seo_verification_code((string) ($_POST['bing_verification'] ?? ''));
    $s['sitemap_enabled'] = empty($_POST['sitemap_enabled']) ? '0' : '1';
    $s['robots_txt'] = mb_substr(str_replace("\r\n", "\n", trim((string) ($_POST['robots_txt'] ?? ''))), 0, 5000);

    if (!str_contains($s['title_template'], '%s')) $errors['title_template'] = 'Include %s where the page title goes, e.g. "%s | Sunsys Technologies".';
    $url = trim((string) ($_POST['site_url'] ?? ''));
    if ($url !== '' && !preg_match('#^https?://[a-z0-9.-]+(:\d+)?/?$#i', $url)) $errors['site_url'] = 'Enter just the domain, like https://sunsys.in';
    else $s['site_url'] = rtrim($url, '/');
    $img = seo_url_field((string) ($_POST['default_og_image'] ?? ''));
    if ($img === null) $errors['default_og_image'] = 'Use a full https:// address or a path starting with /.';
    else $s['default_og_image'] = $img;
    if (!$errors && !empty($_FILES['og_upload']['name'])) {
        try {
            $s['default_og_image'] = save_image($_FILES['og_upload']);
        } catch (RuntimeException $ex) {
            $errors['default_og_image'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        foreach (array_keys(seo_setting_defaults()) as $k) q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $s[$k]]);
        flash('Site settings saved.');
        redirect('seo-settings.php');
    }
}

$detected = seo_origin();
$fieldErr = fn ($k) => isset($errors[$k]) ? '<span class="field-err">' . e($errors[$k]) . '</span>' : '';
admin_head('SEO settings', 'seo.php', $user);
?>
<div class="toolbar"><?= seo_tabs('seo-settings.php') ?></div>
<?php if ($errors): ?><p class="alert err">Check the highlighted fields.</p><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="editor">
  <?= csrf_field() ?>
  <div class="stack">
    <div class="card stack">
      <h2>Site</h2>
      <div class="grid2">
        <label>Site name <span class="muted">(og:site_name, and used in titles)</span><input name="site_name" maxlength="120" value="<?= e($s['site_name']) ?>"></label>
        <label>Website address <span class="muted">(for canonicals and the sitemap)</span><input name="site_url" value="<?= e($s['site_url']) ?>" placeholder="<?= e($detected) ?>"><?= $fieldErr('site_url') ?></label>
      </div>
      <label>Title template <span class="muted">(%s is replaced by a page's title tag set in SEO)</span>
        <input name="title_template" maxlength="120" value="<?= e($s['title_template']) ?>" placeholder="%s | Sunsys Technologies"><?= $fieldErr('title_template') ?>
      </label>
      <label>Default meta description <span class="muted">(used on any page without its own)</span>
        <textarea name="default_description" rows="3" maxlength="400"><?= e($s['default_description']) ?></textarea>
      </label>
      <div class="grid2">
        <label>Default Open Graph image URL <span class="muted">(shown when a page is shared and has no image of its own)</span>
          <input name="default_og_image" maxlength="500" value="<?= e($s['default_og_image']) ?>" placeholder="/uploads/blog/share.jpg or https://…"><?= $fieldErr('default_og_image') ?>
        </label>
        <label class="file">…or upload one (1200 × 630 works best)<input type="file" name="og_upload" accept="image/jpeg,image/png,image/webp"></label>
      </div>
      <?php if ($s['default_og_image']): ?><img src="<?= e($s['default_og_image']) ?>" alt="" class="cover-prev" style="max-width:360px"><?php endif; ?>
    </div>

    <div class="card stack">
      <h2>Search engine verification</h2>
      <p class="muted small">Paste the code, or the whole <code>&lt;meta&gt;</code> tag the console gives you. It's added to every page.</p>
      <label>Google Search Console verification code<input name="google_verification" value="<?= e($s['google_verification']) ?>" placeholder='&lt;meta name="google-site-verification" content="…"&gt;'></label>
      <label>Bing Webmaster verification code<input name="bing_verification" value="<?= e($s['bing_verification']) ?>" placeholder='&lt;meta name="msvalidate.01" content="…"&gt;'></label>
    </div>

    <div class="card stack">
      <div class="card-head"><h2>robots.txt</h2><a href="../robots.txt" target="_blank" rel="noopener">View live file</a></div>
      <label>robots.txt contents <span class="muted">(the sitemap line is added automatically)</span>
        <textarea name="robots_txt" rows="8" class="mono" spellcheck="false"><?= e($s['robots_txt']) ?></textarea>
      </label>
    </div>
  </div>

  <aside class="stack">
    <div class="card stack">
      <h2>Sitemap</h2>
      <label class="check"><input type="checkbox" name="sitemap_enabled" value="1" <?= $s['sitemap_enabled'] === '1' ? 'checked' : '' ?>> Publish sitemap.xml</label>
      <p class="muted small">Lists every page and published blog post, except ones hidden from search engines. Submit <a href="../sitemap.xml" target="_blank" rel="noopener"><?= e($detected) ?>/sitemap.xml</a> in Google Search Console.</p>
      <button class="btn btn-block" type="submit">Save settings</button>
    </div>
  </aside>
</form>
<?php admin_foot();
