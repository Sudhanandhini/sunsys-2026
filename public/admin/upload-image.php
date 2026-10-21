<?php
// Image upload for the post builder: POST image + csrf → { src }
require dirname(__DIR__) . '/api/_lib/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
if (!current_admin()) json_out(['error' => 'Your session expired. Log in again.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed'], 405);
start_session();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) json_out(['error' => 'Your session expired. Reload the page.'], 400);
if (empty($_FILES['image']['name'])) json_out(['error' => 'Choose an image.'], 400);
try {
    json_out(['src' => save_image($_FILES['image'])]);
} catch (RuntimeException $ex) {
    json_out(['error' => $ex->getMessage()], 400);
}
