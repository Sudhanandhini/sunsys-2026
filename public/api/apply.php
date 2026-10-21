<?php
// Receives job applications (multipart form) with an optional CV upload. CVs go to private storage.
require __DIR__ . '/_lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed'], 405);
if (!empty($_POST['website'])) json_out(['ok' => true]); // honeypot

$name = preg_replace('/\s+/u', ' ', clean($_POST['name'] ?? '', 120));
$email = strtolower(clean($_POST['email'] ?? '', 190));
$phone = clean($_POST['phone'] ?? '', 30);
$message = clean($_POST['message'] ?? '', 5000);
$jobId = (int) ($_POST['job_id'] ?? 0);

$experience = clean($_POST['experience'] ?? '', 60);

// Same rules as the form (src/lib/validate.js)
$errors = array_filter([
    'name' => name_error($name),
    'email' => email_error($email),
    'phone' => phone_error($phone),
    'experience' => $experience === '' || (preg_match('/^\d{1,2}(\.\d)?$/', $experience) && (float) $experience <= 50) ? '' : 'Enter years as a number, like 2 or 3.5.',
]);
if ($errors) json_out(['error' => 'Check the highlighted fields.', 'fields' => $errors], 422);

try {
    if (!rate_ok('apply', 5, 3600)) json_out(['error' => 'Too many applications. Please try again later.'], 429);

    $job = $jobId ? q("SELECT id, title FROM jobs WHERE id = ? AND status = 'open'", [$jobId])->fetch() : null;
    $jobTitle = $job['title'] ?? 'General application';

    $cvFile = $cvName = null;
    $f = $_FILES['cv'] ?? null;
    if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK) json_out(['error' => 'CV upload failed. Try a smaller file.', 'fields' => ['cv' => 'Upload failed.']], 422);
        if ($f['size'] > 5 * 1024 * 1024) json_out(['error' => 'CV must be 5 MB or smaller.', 'fields' => ['cv' => 'Max 5 MB.']], 422);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $allowed = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        if (!isset($allowed[$mime])) json_out(['error' => 'CV must be a PDF or Word document.', 'fields' => ['cv' => 'PDF or Word only.']], 422);
        $cvFile = date('Ymd') . '-' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
        $cvName = mb_substr(preg_replace('/[^\w.\- ]+/u', '_', basename($f['name'])), 0, 200);
        if (!move_uploaded_file($f['tmp_name'], storage_dir('cv') . '/' . $cvFile)) json_out(['error' => 'Could not save your CV. Please try again.'], 500);
    }

    q('INSERT INTO applications (job_id, job_title, name, email, phone, experience, message, cv_file, cv_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $job['id'] ?? null, $jobTitle, $name, $email, $phone, $experience, $message, $cvFile, $cvName,
    ]);
    if (!empty($CONFIG['notify_email'])) {
        @mail($CONFIG['notify_email'], "New application: $jobTitle", "From: $name <$email>\nPhone: $phone\n\nSee the admin dashboard for details.");
    }
    json_out(['ok' => true]);
} catch (Throwable $ex) {
    json_out(['error' => 'Server error'], 500);
}
