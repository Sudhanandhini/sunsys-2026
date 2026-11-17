<?php
// URL redirects managed in Admin > SEO > Redirects. Each row sends one old address (path, optionally with
// query parameters such as ?slug=…) to a new one with a 301/302/307/308, or answers 410 Gone.
// Checked by api/page.php (built pages), blog/post/index.php (articles) and api/redirect.php (missing URLs).
declare(strict_types=1);

const REDIRECT_CODES = [
    301 => 'Permanent (301)',
    302 => 'Temporary (302)',
    307 => 'Temporary, keep method (307)',
    308 => 'Permanent, keep method (308)',
    410 => 'Gone (410)',
];

/**
 * Normalise an old address typed in the admin, or a request URI, to [path, query]: path decoded, without the
 * site's subfolder and without a trailing slash ('/' stays '/'); query parameters sorted. Full URLs are accepted
 * (only the path and query are kept). Returns null for something that isn't a usable address.
 */
function redirect_split(string $v, bool $stripBase = true): ?array
{
    $v = trim($v);
    if ($v === '' || preg_match('/[\s<>"]/', $v)) return null;
    if (preg_match('#^https?://#i', $v)) {
        $u = parse_url($v);
        if ($u === false) return null;
        $v = ($u['path'] ?? '/') . (isset($u['query']) ? '?' . $u['query'] : '');
    }
    $v = explode('#', $v, 2)[0];
    if ($v === '' || $v[0] !== '/') $v = '/' . $v;
    [$path, $query] = array_pad(explode('?', $v, 2), 2, '');
    $path = preg_replace('#/{2,}#', '/', rawurldecode($path));
    $base = base_path();
    if ($stripBase && $base !== '' && (strcasecmp($path, $base) === 0 || stripos($path, "$base/") === 0)) $path = substr($path, strlen($base)) ?: '/';
    $path = $path === '/' ? '/' : rtrim($path, '/');
    if ($path === '' || mb_strlen($path) > 255) return null;
    parse_str($query, $params);
    ksort($params);
    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    if (mb_strlen($query) > 255) return null;
    return [$path, $query];
}

/** How an old address is shown in the admin: /old-page or /blog/post?slug=x */
function redirect_label(string $path, string $query): string
{
    return $path . ($query !== '' ? '?' . rawurldecode($query) : '');
}

/** True when the target is on this website (a path, or a full URL on the site's own address). */
function redirect_internal(string $target): ?array
{
    if (str_starts_with($target, '/') && !str_starts_with($target, '//')) return redirect_split($target);
    $origin = seo_origin();
    if (stripos($target, $origin . '/') === 0 || strcasecmp($target, $origin) === 0) return redirect_split(substr($target, strlen($origin)) ?: '/', false);
    return null;
}

/** Active redirect for this path and query: one whose parameters all appear in the request wins over a plain path match. */
function redirect_lookup(string $path, string $query): ?array
{
    $rows = q('SELECT * FROM seo_redirects WHERE active = 1 AND source_path = ?', [$path])->fetchAll();
    if (!$rows) return null;
    parse_str($query, $have);
    $best = null;
    $bestN = -1;
    foreach ($rows as $r) {
        parse_str($r['source_query'], $want);
        foreach ($want as $k => $v) if (!array_key_exists($k, $have) || (string) $have[$k] !== (string) $v) continue 2;
        if (count($want) > $bestN) { $best = $r; $bestN = count($want); }
    }
    return $best;
}

/**
 * Where a request for [path, query] ends up: ['row' => first redirect, 'code' => status, 'location' => final
 * target or ''] after following redirect chains (up to 5 hops) so visitors and search engines get one hop.
 * Returns null when no redirect applies or the chain loops.
 */
function redirect_resolve(string $path, string $query): ?array
{
    $first = redirect_lookup($path, $query);
    if (!$first) return null;
    $seen = [strtolower("$path?$query") => true];
    $row = $first;
    for ($hop = 0; $hop < 5; $hop++) {
        if ((int) $row['code'] === 410) return ['row' => $first, 'code' => 410, 'location' => ''];
        $next = redirect_internal($row['target']);
        if (!$next) break; // another website: the end of the chain
        $key = strtolower($next[0] . '?' . $next[1]);
        if (isset($seen[$key])) return null; // loop
        $seen[$key] = true;
        $further = redirect_lookup($next[0], $next[1]);
        if (!$further) break;
        $row = $further;
    }
    return ['row' => $first, 'code' => (int) $first['code'], 'location' => $row['target']];
}

/** Browser address for a stored target: site paths get the subfolder (if any) in front. */
function redirect_location(string $target): string
{
    if (!str_starts_with($target, '/') || str_starts_with($target, '//')) return $target;
    // Page addresses have no trailing slash: /about-us/ → /about-us (folders like /blog/post/?slug= keep theirs)
    [$p, $q] = array_pad(explode('?', $target, 2), 2, null);
    if ($q === null && $p !== '/') $p = rtrim($p, '/') ?: '/';
    return base_path() . $p . ($q !== null ? "?$q" : '');
}

/**
 * Send the redirect for the current request if one is set, and stop. Does nothing when none applies or the
 * database is unavailable. 410 responses are left to the caller's $gone callback (to print a page).
 */
function redirect_serve(?callable $gone = null): void
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    try {
        $src = redirect_split($uri);
        if (!$src) return;
        $hit = redirect_resolve($src[0], $src[1]);
        if (!$hit) return;
        q('UPDATE seo_redirects SET hits = hits + 1, last_hit = NOW() WHERE id = ?', [$hit['row']['id']]);
    } catch (Throwable $ex) {
        return;
    }
    if ($hit['code'] === 410) {
        http_response_code(410);
        header('X-Robots-Tag: noindex');
        if ($gone) $gone();
        exit;
    }
    $loc = redirect_location($hit['location']);
    // A plain path match carries the visitor's query string over (e.g. utm_ tags), unless the target sets its own
    $qs = parse_url($uri, PHP_URL_QUERY) ?: '';
    if ($qs !== '' && $hit['row']['source_query'] === '' && !str_contains($loc, '?')) $loc .= '?' . $qs;
    // Permanent redirects may be cached by browsers; keep that short so a change in the admin takes effect
    header('Cache-Control: ' . (in_array($hit['code'], [301, 308], true) ? 'public, max-age=3600' : 'no-store'));
    header('Location: ' . $loc, true, $hit['code']);
    exit;
}

/** Set of "path?query" strings that currently redirect (for the sitemap). */
function redirect_sources(): array
{
    $set = [];
    try {
        foreach (q('SELECT source_path, source_query FROM seo_redirects WHERE active = 1')->fetchAll() as $r) $set[strtolower($r['source_path'] . '?' . $r['source_query'])] = true;
    } catch (Throwable $ex) {
    }
    return $set;
}

/** Whether a site URL (path with optional query) is redirected away, per redirect_sources(). */
function redirect_is_source(array $sources, string $url): bool
{
    $s = redirect_split($url, false);
    return $s && isset($sources[strtolower($s[0] . '?' . $s[1])]);
}

/**
 * Save a redirect (insert, or update $id). Returns an error message, or '' on success.
 * Used by the admin form, the bulk import and the blog editor when a post's address changes.
 */
function redirect_save(string $source, string $target, int $code, string $note = '', int $active = 1, int $id = 0): string
{
    if (!isset(REDIRECT_CODES[$code])) return 'Choose a redirect type.';
    $src = redirect_split($source);
    if (!$src) return 'Enter the old address as a path like /old-page or a full URL.';
    if (preg_match('#^/(admin|api|_astro)(/|$)#i', $src[0])) return 'The admin, api and _astro folders cannot be redirected.';
    $target = trim($target);
    if ($code === 410) {
        $target = '';
    } else {
        if ($target === '') return 'Enter the new address.';
        if (!preg_match('#^https?://#i', $target) && !str_starts_with($target, '/')) $target = '/' . $target;
        if (!preg_match('#^(https?://[^\s"<>]+|/[^\s"<>]*)$#i', $target) || str_starts_with($target, '//')) return 'The new address must be a path starting with / or a full https:// URL.';
        if (mb_strlen($target) > 500) return 'The new address is too long.';
        $to = redirect_internal($target);
        if ($to && $to === $src) return 'The old and new addresses are the same page.';
        if ($to) {
            // Would this redirect send visitors in a circle (A → B → … → A)?
            $seen = [strtolower($src[0] . '?' . $src[1]) => true];
            for ($hop = 0, $cur = $to; $cur && $hop < 10; $hop++) {
                $k = strtolower($cur[0] . '?' . $cur[1]);
                if (isset($seen[$k])) return 'This would create a redirect loop: ' . redirect_label(...$to) . ' already leads back to ' . redirect_label(...$src) . '.';
                $seen[$k] = true;
                $r = redirect_lookup($cur[0], $cur[1]);
                if (!$r || ($id && (int) $r['id'] === $id) || (int) $r['code'] === 410) break;
                $cur = redirect_internal($r['target']);
            }
        }
    }
    $dup = q('SELECT id FROM seo_redirects WHERE source_path = ? AND source_query = ? AND id <> ?', [$src[0], $src[1], $id])->fetchColumn();
    if ($dup) return 'A redirect for ' . redirect_label(...$src) . ' already exists. Edit that one instead.';
    $note = mb_substr(trim($note), 0, 200);
    if ($id) q('UPDATE seo_redirects SET source_path = ?, source_query = ?, target = ?, code = ?, note = ?, active = ? WHERE id = ?', [$src[0], $src[1], $target, $code, $note, $active ? 1 : 0, $id]);
    else q('INSERT INTO seo_redirects (source_path, source_query, target, code, note, active) VALUES (?, ?, ?, ?, ?, ?)', [$src[0], $src[1], $target, $code, $note, $active ? 1 : 0]);
    return '';
}

/**
 * A built page's address changed in Admin > SEO, from $from to $to ($built = the address from the website build).
 * Each old address (the build's and the previous custom one) gets a 301 to $to, listed in Admin > SEO > Redirects;
 * redirects that led to $from now go straight to $to, and none may start at $to, which serves the page.
 */
function redirect_page_moved(string $built, string $from, string $to): void
{
    $drop = function (string $p): void {
        if ($s = redirect_split($p)) q("DELETE FROM seo_redirects WHERE source_path = ? AND source_query = ''", [$s[0]]);
    };
    $drop($to);
    $to = seo_url_path($to);
    if ($from !== $to) q('UPDATE seo_redirects SET target = ? WHERE target IN (?, ?)', [$to, $from, seo_url_path($from)]);
    foreach (array_unique([$built, $from]) as $old) {
        if (seo_url_path($old) === $to) continue;
        $drop($old);
        redirect_save($old, $to, 301, 'Page address changed in SEO');
    }
}

/** Make sure every custom page address set in Admin > SEO has its 301 from the build's address listed. */
function redirect_sync_page_moves(): void
{
    foreach (seo_aliases() as $built => $public) {
        q('UPDATE seo_redirects SET target = ? WHERE target = ?', [seo_url_path($public), $public]); // written with a slash before
        $public = seo_url_path($public);
        $s = redirect_split($built);
        if ($s && !q("SELECT 1 FROM seo_redirects WHERE source_path = ? AND source_query = ''", [$s[0]])->fetchColumn()) {
            redirect_save($built, $public, 301, 'Page address changed in SEO');
        }
    }
}

/** The request's path relative to the site folder (the site may sit in a subfolder). */
function request_site_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && ($uri === $base || str_starts_with($uri, "$base/"))) $uri = substr($uri, strlen($base)) ?: '/';
    return $uri;
}

/** Old WordPress pages with no exact match on this site: added once to Admin > SEO > Redirects (see below). */
const REDIRECT_WP_DEFAULTS = [
    '/home' => '/', '/thank-you' => '/', '/404-2' => '/',
    '/html-website-development-company' => '/php-web-development-company',
    '/web-portal-development-company-bangalore' => '/php-web-development-company',
    '/ecommerce-bangalore' => '/ecommerce-web-development-india',
    '/ecommerce-web-development-india/ahmedabad' => '/ecommerce-web-development-india',
    '/ecommerce-web-development-india/bangalore' => '/ecommerce-web-development-india',
    '/ecommerce-web-development-india/chennai' => '/ecommerce-web-development-india',
    '/ecommerce-web-development-india/kolkata' => '/ecommerce-web-development-india',
    '/ecommerce-web-development-india/mumbai' => '/ecommerce-web-development-india',
    '/wordpress-development-new' => '/wordpress-development-company-india',
    '/wordpress-development-company-india/bangalore' => '/wordpress-development-company-india',
    '/wordpress-development-company-india/chennai' => '/wordpress-development-company-india',
    '/wordpress-development-company-india/delhi' => '/wordpress-development-company-india',
    '/wordpress-development-company-india/hyderabad' => '/wordpress-development-company-india',
    '/website-maintenance-india/bangalore' => '/website-maintenance-india',
    '/redesign-new' => '/website-redesign-services',
    '/chennai' => '/website-development', '/hyderabad' => '/website-development', '/india' => '/website-development',
    '/kolkata' => '/website-development', '/mumbai' => '/website-development', '/uk' => '/website-development',
    '/usa' => '/website-development',
    '/manufacturing-industrial' => '/service', '/ngo' => '/service', '/retail' => '/service',
    // linked from the imported articles, but not on the old site any more
    '/website-maintenance-company-bangalore' => '/website-maintenance-india',
    '/seo-strategies-for-startups' => '/blog/5-effective-seo-strategies-for-successful-startups',
    '/impact-of-covid-19-on-ecommerce' => '/blog?category=e-commerce',
];

/**
 * One-off, run by migrate() in bootstrap.php when this site moved to the old WordPress addresses:
 *  - SEO settings saved for a service/industry page follow it to its new address (SEO_SITE_MOVES);
 *  - each old address gets a 301 to the new one, and the old WordPress pages without a match get theirs,
 *    all listed (and editable) in Admin > SEO > Redirects;
 *  - redirects kept in the `redirects` table by an earlier copy of this admin are brought over.
 * Existing redirects for the same address are left alone (redirect_save refuses duplicates and loops).
 */
function redirect_seed_site_moves(): void
{
    foreach (SEO_SITE_MOVES as $old => [$new, $type]) {
        if (!q('SELECT 1 FROM seo_pages WHERE path = ?', [$new])->fetchColumn()) q('UPDATE seo_pages SET path = ?, type = ? WHERE path = ?', [$new, $type, $old]);
        redirect_save($old, seo_url_path($new), 301, 'Page moved to its old WordPress address');
    }
    foreach (REDIRECT_WP_DEFAULTS as $from => $to) redirect_save($from, $to, 301, 'Old WordPress page');
    // only that admin's own table (from_path, to_url); other leftovers named `redirects` are ignored
    if (q("SHOW TABLES LIKE 'redirects'")->fetchColumn() && q("SHOW COLUMNS FROM redirects LIKE 'from_path'")->fetchColumn()) {
        foreach (q('SELECT from_path, to_url, code FROM redirects')->fetchAll() as $r) {
            redirect_save($r['from_path'], $r['to_url'], (int) $r['code'] === 302 ? 302 : 301, 'Imported from the earlier Redirects list');
        }
    }
}

/**
 * One-off, run by migrate() in bootstrap.php when articles moved from /<slug> to /blog/<slug> (blog_path()):
 * links between articles (https://www.sunsys.in/<slug>, as the old WordPress blog wrote them) and redirects
 * that ended at an article now point at /blog/<slug> directly, instead of going through the 301.
 */
function redirect_blog_paths_v1(): void
{
    $slugs = q('SELECT slug FROM posts')->fetchAll(PDO::FETCH_COLUMN);
    if (!$slugs) return;
    usort($slugs, fn ($a, $b) => strlen($b) <=> strlen($a));
    $alt = implode('|', array_map(fn ($s) => preg_quote($s, '#'), $slugs));
    // the site's own address + /<slug>, any trailing slashes; ends at a quote (also \" inside JSON), ?, #, < or space
    $re = '#(https?://(?:www\.)?sunsys\.in)/(' . $alt . ')/*(?=[\\\\"\'?\#<\s)]|$)#i';
    foreach (q('SELECT id, body, blocks FROM posts')->fetchAll() as $p) {
        $body = preg_replace($re, '$1/blog/$2', (string) $p['body']);
        $blocks = $p['blocks'] === null ? null : preg_replace($re, '$1/blog/$2', $p['blocks']);
        if ($body !== (string) $p['body'] || $blocks !== $p['blocks']) q('UPDATE posts SET body = ?, blocks = ? WHERE id = ?', [$body, $blocks, $p['id']]);
    }
    foreach ($slugs as $s) q('UPDATE seo_redirects SET target = ? WHERE target IN (?, ?)', [blog_path($s), '/' . $s, '/' . $s . '/']);
}
