<?php
// SEO for the built website. Pages are static HTML from Astro; api/page.php (and blog/post/index.php) pass them
// through seo_apply() so the title, description, robots, social and schema tags set in Admin > SEO take effect
// without a rebuild. Per-page values live in seo_pages (keyed by path), site-wide ones in settings.
declare(strict_types=1);

/** Folder holding the built site (public_html on the server). */
function site_root(): string
{
    return dirname(__DIR__, 2);
}

const SEO_SKIP = ['admin', 'api', 'uploads', '_astro', 'images'];

function seo_setting_defaults(): array
{
    return [
        'site_name' => 'Sunsys Technologies',
        'site_url' => '',
        'title_template' => '%s | Sunsys Technologies',
        'default_description' => '',
        'default_og_image' => '',
        'google_verification' => '',
        'bing_verification' => '',
        'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\n",
        'sitemap_enabled' => '1',
    ];
}

function seo_settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = seo_setting_defaults();
        try {
            foreach (q('SELECT k, v FROM settings')->fetchAll() as $r) if (array_key_exists($r['k'], $s)) $s[$r['k']] = $r['v'];
        } catch (Throwable $ex) {
            // not installed yet: defaults
        }
    }
    return $s;
}

/**
 * URL of the live site's home page without the trailing slash, e.g. https://sunsys.in or
 * https://sunsysweb.co.in/sunsys-2026: the setting, else the home canonical the build wrote, else this request.
 */
function seo_origin(): string
{
    $set = rtrim(seo_settings()['site_url'], '/');
    if ($set !== '') return $set;
    $home = @file_get_contents(site_root() . '/index.html', false, null, 0, 65536);
    if ($home && preg_match('#<link rel="canonical" href="(https?://[^"]+)"#', $home, $m)) return rtrim($m[1], '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
}

/** A site path like /services/web-hosting/ (lowercase letters, digits, - _ / only), or null. */
function seo_path(string $p): ?string
{
    $p = '/' . trim(trim($p), '/');
    $p = $p === '/' ? '/' : $p . '/';
    return preg_match('#^/(?:[a-z0-9_-]+/)*$#i', $p) && strlen($p) <= 255 ? strtolower($p) : null;
}

/**
 * Where this site's service and industry pages moved when they took the old WordPress site's addresses (the
 * `path` of each in src/data/site.ts; keep the two in step): old page => [new page, type]. Used to label pages
 * in the admin, and once (bootstrap.php migrate) to move their SEO settings and add 301s from the old addresses.
 */
const SEO_SITE_MOVES = [
    '/services/' => ['/service/', 'Page'],
    '/services/website-development/' => ['/website-development/', 'Service'],
    '/services/wordpress-development/' => ['/wordpress-development-company-india/', 'Service'],
    '/services/ecommerce-development/' => ['/ecommerce-web-development-india/', 'Service'],
    '/services/custom-web-development/' => ['/php-web-development-company/', 'Service'],
    '/services/woocommerce-development/' => ['/woocommerce-web-development-company/', 'Service'],
    '/services/landing-page/' => ['/website-development-landing-page/', 'Service'],
    '/services/cms-web-development/' => ['/cms-development-company-bangalore/', 'Service'],
    '/services/website-maintenance/' => ['/website-maintenance-india/', 'Service'],
    '/services/website-redesign/' => ['/website-redesign-services/', 'Service'],
    '/services/responsive-web-design/' => ['/responsive-web-design/', 'Service'],
    '/services/linux-hosting/' => ['/linux-hosting-company-bangalore/', 'Service'],
    '/services/web-hosting/' => ['/web-hosting-services-company-in-bangalore/', 'Service'],
    '/services/email-hosting/' => ['/email-hosting-service-provider/', 'Service'],
    '/industries/tours-and-travels/' => ['/travel-hospitality/', 'Industry'],
    '/industries/hotel-and-resort/' => ['/hotel/', 'Industry'],
    '/industries/healthcare/' => ['/healthcare/', 'Industry'],
    '/industries/corporate/' => ['/b2b-business/', 'Industry'],
    '/industries/education/' => ['/education/', 'Industry'],
    '/industries/real-estate-and-construction/' => ['/real-estate/', 'Industry'],
];

function seo_type(string $path): string
{
    static $types = null;
    $types ??= array_column(SEO_SITE_MOVES, 1, 0);
    if (isset($types[$path])) return $types[$path]; // service and industry pages sit at the site root
    return match (true) {
        $path === '/' => 'Home',
        str_starts_with($path, '/services/') && $path !== '/services/' => 'Service',
        str_starts_with($path, '/industries/') => 'Industry',
        str_starts_with($path, '/blog/') => 'Blog',
        default => 'Page',
    };
}

/** Title and description the build wrote into a page, for showing defaults in the admin. */
function seo_read_head(string $file): array
{
    $h = (string) @file_get_contents($file, false, null, 0, 65536);
    $get = fn ($re) => preg_match($re, $h, $m) ? html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    return ['title' => $get('#<title>([^<]*)</title>#'), 'description' => $get('#<meta name="description" content="([^"]*)"#')];
}

/** Folder to list pages from: the site itself, or locally (where PHP serves public/) the last `npm run build`. */
function seo_pages_root(): string
{
    $root = site_root();
    if (is_file("$root/index.html")) return $root;
    $dist = dirname($root) . '/dist';
    return is_file("$dist/index.html") ? $dist : $root;
}

/** Every page the build produced: path => [file, title, description]. The blog article template is excluded. */
function seo_discover(): array
{
    $root = seo_pages_root();
    $pages = [];
    $walk = function (string $dir, string $path, int $depth) use (&$walk, &$pages, $root) {
        // Old /services/… and /industries/… folders can be left on the server by an earlier upload: those pages
        // moved (SEO_SITE_MOVES) and their addresses redirect, so they aren't pages of the site any more
        if (is_file("$dir/index.html") && $path !== '/blog/post/' && !isset(SEO_SITE_MOVES[$path])) $pages[$path] = ['file' => "$dir/index.html"] + seo_read_head("$dir/index.html");
        if ($depth > 4) return;
        foreach (scandir($dir) ?: [] as $f) {
            if ($f[0] === '.' || ($depth === 0 && in_array($f, SEO_SKIP, true)) || !is_dir("$dir/$f") || !preg_match('/^[a-z0-9_-]+$/i', $f)) continue;
            $walk("$dir/$f", "$path$f/", $depth + 1);
        }
    };
    $walk($root, '/', 0);
    ksort($pages);
    return $pages;
}

function seo_page(string $path): ?array
{
    try {
        return q('SELECT * FROM seo_pages WHERE path = ?', [$path])->fetch() ?: null;
    } catch (Throwable $ex) {
        return null;
    }
}

/** Full <title>: a title set in the admin goes inside the site's title template unless it already names the site. */
function seo_title(string $title): string
{
    $s = seo_settings();
    $tpl = $s['title_template'];
    if ($title === '' || !str_contains($tpl, '%s') || ($s['site_name'] !== '' && stripos($title, $s['site_name']) !== false)) return $title;
    return str_replace('%s', $title, $tpl);
}

/** Absolute URL for an image or page path. */
function seo_abs(string $u): string
{
    return $u === '' || preg_match('#^https?://#i', $u) ? $u : seo_origin() . '/' . ltrim($u, '/');
}

/** Schema JSON from the admin, re-encoded so it can't break out of its <script> tag; null if invalid. */
function seo_schema(?string $json): ?string
{
    if ($json === null || trim($json) === '') return null;
    $d = json_decode($json, true);
    return is_array($d) ? json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) : null;
}

/**
 * Apply SEO tags to a built page. $m (all optional): title (full), description, keywords, canonical, og_title,
 * og_description, og_image, og_type, noindex, nofollow, schema (JSON string), extra_head (trusted HTML).
 * Empty values keep what the build wrote; site settings add verification codes, site name and a default image.
 */
function seo_apply(string $html, array $m): string
{
    $s = seo_settings();
    $tag = function (string $re, string $new) use (&$html): void {
        $n = 0;
        $html = preg_replace_callback($re, fn () => $new, $html, 1, $n);
        if (!$n) $html = preg_replace_callback('#</head>#', fn () => $new . '</head>', $html, 1);
    };
    $meta = fn (string $attr, string $key, string $val) => $tag('#<meta ' . $attr . '="' . preg_quote($key, '#') . '" content="[^"]*">#', '<meta ' . $attr . '="' . $key . '" content="' . e($val) . '">');
    $current = fn (string $re) => preg_match($re, $html, $x) ? html_entity_decode($x[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

    $title = ($m['title'] ?? '') !== '' ? $m['title'] : $current('#<title>([^<]*)</title>#');
    $desc = ($m['description'] ?? '') !== '' ? $m['description'] : ($current('#<meta name="description" content="([^"]*)"#') ?: $s['default_description']);
    $canonical = ($m['canonical'] ?? '') !== '' ? seo_abs($m['canonical']) : $current('#<link rel="canonical" href="([^"]*)"#');
    // Page addresses have no trailing slash: https://sunsys.in/about/ (older builds) → https://sunsys.in/about
    $origin = seo_origin();
    if (stripos($canonical, $origin . '/') === 0 && strlen($canonical) > strlen($origin) + 1 && !str_contains($canonical, '?')) $canonical = rtrim($canonical, '/');
    $ogTitle = ($m['og_title'] ?? '') !== '' ? $m['og_title'] : $title;
    $ogDesc = ($m['og_description'] ?? '') !== '' ? $m['og_description'] : $desc;
    $image = seo_abs(($m['og_image'] ?? '') !== '' ? $m['og_image'] : $s['default_og_image']);

    if ($title !== '') $tag('#<title>[^<]*</title>#', '<title>' . e($title) . '</title>');
    if ($desc !== '') $meta('name', 'description', $desc);
    if (($m['keywords'] ?? '') !== '') $meta('name', 'keywords', $m['keywords']);
    if ($canonical !== '') $tag('#<link rel="canonical" href="[^"]*">#', '<link rel="canonical" href="' . e($canonical) . '">');

    $robots = array_filter([!empty($m['noindex']) ? 'noindex' : '', !empty($m['nofollow']) ? 'nofollow' : '']);
    if ($robots) {
        $meta('name', 'robots', implode(', ', $robots));
        header('X-Robots-Tag: ' . implode(', ', $robots));
    }

    if ($ogTitle !== '') $meta('property', 'og:title', $ogTitle);
    if ($ogDesc !== '') $meta('property', 'og:description', $ogDesc);
    if (($m['og_type'] ?? '') !== '') $meta('property', 'og:type', $m['og_type']);
    if ($canonical !== '') $meta('property', 'og:url', $canonical);
    if ($s['site_name'] !== '') $meta('property', 'og:site_name', $s['site_name']);
    if ($image !== '') $meta('property', 'og:image', $image);
    $meta('name', 'twitter:card', $image !== '' ? 'summary_large_image' : 'summary');
    if ($ogTitle !== '') $meta('name', 'twitter:title', $ogTitle);
    if ($ogDesc !== '') $meta('name', 'twitter:description', $ogDesc);
    if ($image !== '') $meta('name', 'twitter:image', $image);

    if ($s['google_verification'] !== '') $meta('name', 'google-site-verification', $s['google_verification']);
    if ($s['bing_verification'] !== '') $meta('name', 'msvalidate.01', $s['bing_verification']);

    $head = '';
    if ($schema = seo_schema($m['schema'] ?? null)) $head .= '<script type="application/ld+json">' . $schema . '</script>';
    $head .= $m['extra_head'] ?? '';
    if ($head !== '') $html = preg_replace_callback('#</head>#', fn () => $head . '</head>', $html, 1);
    return $html;
}

/**
 * Address of a blog article: /blog/<slug>. The one place it is defined in PHP (src/components/BlogSidebar.jsx
 * postHref is the front end's); the old /<slug> (WordPress) and /blog/post/?slug= forms 301 here.
 */
function blog_path(string $slug): string
{
    return '/blog/' . rawurlencode($slug);
}

/** Folders a page's custom address may not use: they belong to the system, or the blog article template. */
const SEO_RESERVED = '#^/(admin|api|uploads|_astro|images|blog/post)(/|$)#i';

/** Custom addresses set in Admin > SEO for built pages: built path => address the page is served at. */
function seo_aliases(): array
{
    static $a = null;
    if ($a === null) {
        $a = [];
        try {
            foreach (q("SELECT path, url_path FROM seo_pages WHERE url_path <> '' AND custom = 0")->fetchAll() as $r) $a[$r['path']] = $r['url_path'];
        } catch (Throwable $ex) {
            // not installed yet / database unavailable: pages keep their built addresses
        }
    }
    return $a;
}

/** How a page path is written in URLs: without the trailing slash (/about), except the home page (/). */
function seo_url_path(string $path): string
{
    return $path === '/' ? '/' : (rtrim($path, '/') ?: '/');
}

/** Address a built page is shown at: its custom address, else the one from the build. */
function seo_public_path(string $path): string
{
    return seo_aliases()[$path] ?? $path;
}

/** Built page served at this custom address (path like /web-design/), or null. */
function seo_alias_target(string $public): ?string
{
    $built = array_search($public, seo_aliases(), true);
    return $built === false ? null : (string) $built;
}

/**
 * Point links in a built page at the custom addresses set in the admin: href="/services/x", href="/services/x/#a",
 * the canonical (full URL), island props (&quot;…&quot;) and JSON-LD strings. Links are written with the base
 * path (subfolder builds) or as full URLs on the site's own address.
 */
function seo_rewrite_links(string $html): string
{
    $map = seo_aliases();
    if (!$map) return $html;
    $olds = [];
    foreach ($map as $old => $new) if ($old !== '/') $olds[rtrim($old, '/')] = seo_url_path($new);
    if (!$olds) return $html;
    uksort($olds, fn ($a, $b) => strlen($b) <=> strlen($a));
    $base = base_path();
    $prefix = '(' . preg_quote(seo_origin(), '#') . ($base !== '' ? '|' . preg_quote($base, '#') . ')' : ')?');
    $paths = implode('|', array_map(fn ($p) => preg_quote($p, '#'), array_keys($olds)));
    $byLower = array_change_key_case($olds, CASE_LOWER);
    return preg_replace_callback(
        '#(?<=href="|href=\'|&quot;|")' . $prefix . '(' . $paths . ')/?(?=["\'\#?&])#i',
        fn ($m) => $m[1] . $byLower[strtolower($m[2])], // keeps the origin or base it matched with
        $html
    ) ?? $html;
}

/** Send a built page (path like /about/) with its SEO from the admin applied and links at custom addresses. */
function seo_serve(string $path): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    $html = (string) file_get_contents(site_root() . $path . 'index.html');
    try {
        echo seo_apply(seo_rewrite_links($html), seo_meta_for(seo_page($path)));
    } catch (Throwable $ex) {
        echo $html; // database unavailable: the page as built
    }
}

/** Tags for a page from its seo_pages row. */
function seo_meta_for(?array $row): array
{
    if (!$row) return [];
    return [
        'title' => seo_title($row['title']),
        'description' => $row['description'],
        'keywords' => $row['keywords'],
        'canonical' => $row['canonical'],
        'og_title' => $row['og_title'],
        'og_description' => $row['og_description'],
        'og_image' => $row['og_image'],
        'noindex' => (int) $row['noindex'],
        'nofollow' => (int) $row['nofollow'],
        'schema' => $row['schema_json'],
    ];
}
