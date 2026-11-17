<?php
// Redirects managed in Admin > Redirects (table `redirects`). Checked by api/page.php (so a page itself can be
// redirected), blog/post/index.php and api/notfound.php, which between them see every request that isn't a file.
declare(strict_types=1);

/** A request path in the stored form: lowercase, leading slash, no trailing slash ("/" for the home page); or null. */
function redirect_path(string $p): ?string
{
    $p = strtolower(rawurldecode(trim($p)));
    if (preg_match('#^https?://[^/]+(/.*)?$#', $p, $m)) $p = $m[1] ?? '/'; // a full URL pasted into the admin
    $p = '/' . trim(explode('?', $p, 2)[0], '/');
    return strlen($p) <= 255 && preg_match('#^/[a-z0-9._~%/-]*$#', $p) && !str_contains($p, '//') ? $p : null;
}

/** Target as entered in the admin: a site path (/contact) or a full http(s) URL; or null. */
function redirect_target(string $t): ?string
{
    $t = trim($t);
    if (preg_match('#^https?://\S+$#i', $t)) return mb_substr($t, 0, 500);
    if (!str_starts_with($t, '/')) $t = '/' . $t;
    [$path, $qs] = array_pad(explode('?', $t, 2), 2, null);
    $path = redirect_path($path);
    return $path === null ? null : $path . ($qs !== null && $qs !== '' ? '?' . $qs : '');
}

/** If the path (relative to the site folder) has a redirect, send it and stop. */
function redirect_apply(string $uri): void
{
    $from = redirect_path($uri);
    if ($from === null) return;
    try {
        $r = q('SELECT id, to_url, code FROM redirects WHERE from_path = ?', [$from])->fetch();
        if (!$r) return;
        q('UPDATE redirects SET hits = hits + 1, last_hit = NOW() WHERE id = ?', [$r['id']]);
    } catch (Throwable $ex) {
        return; // database unavailable: carry on without redirects
    }
    $to = str_starts_with($r['to_url'], '/') ? base_path() . $r['to_url'] : $r['to_url'];
    header('Cache-Control: no-cache');
    header('Location: ' . $to, true, (int) $r['code'] === 302 ? 302 : 301);
    exit;
}

/** The request's path relative to the site folder (the site may sit in a subfolder). */
function request_site_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && ($uri === $base || str_starts_with($uri, "$base/"))) $uri = substr($uri, strlen($base)) ?: '/';
    return $uri;
}

/** Old WordPress pages with no exact match on the new site: added once, when the table is created. */
const REDIRECT_DEFAULTS = [
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
];
