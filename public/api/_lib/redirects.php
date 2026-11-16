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
    return str_starts_with($target, '/') && !str_starts_with($target, '//') ? base_path() . $target : $target;
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
