<?php
// Shared bits of the Admin > SEO pages (seo.php, seo-edit.php, seo-settings.php).
require_once dirname(__DIR__, 2) . '/api/_lib/seo.php';

const SEO_TYPES = ['Home', 'Page', 'Service', 'Industry', 'Blog', 'Landing page', 'Other'];

function seo_tabs(string $on): string
{
    $tabs = ['seo.php' => 'All pages', 'seo-edit.php?new=1' => 'Add page', 'seo-settings.php' => 'Site settings'];
    $h = '<nav class="tabs">';
    foreach ($tabs as $href => $label) $h .= '<a href="' . $href . '" class="' . ($on === $href ? 'on' : '') . '">' . $label . '</a>';
    return $h . '</nav>';
}

/** Display name for a built page: the first part of its title ("About | Sunsys…" → "About"). */
function seo_name(string $path, string $title): string
{
    if ($path === '/') return 'Home';
    $first = trim(explode('|', $title)[0]);
    return $first !== '' ? $first : ucwords(str_replace('-', ' ', basename(rtrim($path, '/'))));
}

function seo_has_overrides(?array $r): bool
{
    if (!$r) return false;
    foreach (['title', 'description', 'keywords', 'canonical', 'og_title', 'og_description', 'og_image', 'schema_json'] as $k) if (trim((string) $r[$k]) !== '') return true;
    return (bool) ($r['noindex'] || $r['nofollow']);
}

/** Every website page the admin can edit: built pages (with any saved settings) and pages added by hand.
 * Blog posts are not listed: each post has its own SEO fields in the post editor. */
function seo_admin_rows(): array
{
    $db = [];
    foreach (q('SELECT * FROM seo_pages')->fetchAll() as $r) $db[$r['path']] = $r;
    $rows = [];
    foreach (seo_discover() as $path => $b) {
        $r = $db[$path] ?? null;
        unset($db[$path]);
        $rows[] = [
            'key' => 'page:' . $path, 'path' => $path, 'built' => true,
            'name' => $r && $r['name'] !== '' ? $r['name'] : seo_name($path, $b['title']),
            'type' => $r['type'] ?? seo_type($path),
            'title' => $r && $r['title'] !== '' ? seo_title($r['title']) : $b['title'],
            'noindex' => (int) ($r['noindex'] ?? 0), 'nofollow' => (int) ($r['nofollow'] ?? 0),
            'edited' => seo_has_overrides($r),
            'edit' => 'seo-edit.php?path=' . rawurlencode($path), 'view' => $path,
        ];
    }
    foreach ($db as $path => $r) {
        $rows[] = [
            'key' => 'page:' . $path, 'path' => $path, 'built' => false,
            'name' => $r['name'] ?: $path, 'type' => $r['type'], 'title' => seo_title($r['title']),
            'noindex' => (int) $r['noindex'], 'nofollow' => (int) $r['nofollow'], 'edited' => true,
            'edit' => 'seo-edit.php?path=' . rawurlencode($path), 'view' => $path,
        ];
    }
    return $rows;
}

/** Accept either the bare code or the whole <meta … content="…"> tag the search console hands out. */
function seo_verification_code(string $v): string
{
    $v = trim($v);
    if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $v, $m)) $v = $m[1];
    return preg_replace('/[^A-Za-z0-9_\-=+\/.]/', '', $v);
}

/** A link or image address: http(s) URL or a site path. Returns null when invalid (empty is allowed). */
function seo_url_field(string $v): ?string
{
    $v = trim($v);
    if ($v === '') return '';
    return preg_match('#^(https?://[^\s"<>]+|/[^\s"<>]*)$#i', $v) ? $v : null;
}
