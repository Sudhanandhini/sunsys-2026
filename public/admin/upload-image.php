<?php
// Image upload for the post builder: POST image + csrf → { src }
// or POST url + csrf → { src }: copies a pasted https image (e.g. from Google Docs, whose links expire) to the server
require dirname(__DIR__) . '/api/_lib/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
if (!current_admin()) json_out(['error' => 'Your session expired. Log in again.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed'], 405);
start_session();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) json_out(['error' => 'Your session expired. Reload the page.'], 400);

/** Download a public https image into a temp file shaped like an $_FILES entry. */
function fetch_remote_image(string $url): array
{
    $p = parse_url($url);
    if (($p['scheme'] ?? '') !== 'https' || empty($p['host'])) throw new RuntimeException('Only https images can be copied.');
    // public addresses only, so the server can't be pointed at itself or the local network
    $ips = gethostbynamel($p['host']) ?: [];
    if (!$ips) throw new RuntimeException('Could not reach the image address.');
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw new RuntimeException('That image address is not allowed.');
    }
    $max = 4 * 1024 * 1024;
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'follow_location' => 0, 'user_agent' => 'Mozilla/5.0 (compatible; SiteImageCopy)']]);
    $data = @file_get_contents($url, false, $ctx, 0, $max + 1);
    if ($data === false || $data === '') throw new RuntimeException('Could not download the image.');
    if (strlen($data) > $max) throw new RuntimeException('Image must be 4 MB or smaller.');
    $tmp = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tmp, $data);
    return ['error' => UPLOAD_ERR_OK, 'size' => strlen($data), 'tmp_name' => $tmp, 'remote' => true];
}

try {
    if (!empty($_POST['url'])) {
        $file = fetch_remote_image(trim((string) $_POST['url']));
        try { json_out(['src' => save_image($file)]); } finally { @unlink($file['tmp_name']); }
    }
    if (empty($_FILES['image']['name'])) json_out(['error' => 'Choose an image.'], 400);
    json_out(['src' => save_image($_FILES['image'])]);
} catch (RuntimeException $ex) {
    json_out(['error' => $ex->getMessage()], 400);
}
