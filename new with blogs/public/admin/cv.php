<?php
// Streams an applicant's CV to a logged-in admin. CVs are never directly reachable on the web.
require dirname(__DIR__) . '/api/_lib/bootstrap.php';
require_admin();

$a = q('SELECT cv_file, cv_name FROM applications WHERE id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
$path = $a && $a['cv_file'] ? storage_dir('cv') . '/' . basename($a['cv_file']) : '';
if (!$path || !is_file($path)) { http_response_code(404); exit('CV not found.'); }

$types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $a['cv_name'] ?: basename($path)) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
