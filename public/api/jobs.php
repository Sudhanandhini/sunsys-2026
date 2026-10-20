<?php
// Public careers API. GET → open roles.
require __DIR__ . '/_lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['error' => 'Method not allowed'], 405);

try {
    $jobs = q("SELECT id, title, type, experience, location, summary, description FROM jobs WHERE status = 'open' ORDER BY sort_order, id")->fetchAll();
    json_out(['jobs' => $jobs]);
} catch (Throwable $ex) {
    json_out(['error' => 'Server error'], 500);
}
