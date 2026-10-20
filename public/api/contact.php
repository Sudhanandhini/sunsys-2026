<?php
// Receives the contact form, home quote form and newsletter signup (all POST JSON) and stores them as enquiries.
require __DIR__ . '/_lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed'], 405);

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) $in = $_POST;

// Honeypot: bots fill every field
if (!empty($in['website'])) json_out(['ok' => true]);

$email = strtolower(clean($in['email'] ?? '', 190));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Enter a valid email address.'], 422);

$isNewsletter = ($in['type'] ?? '') === 'newsletter';
$source = $isNewsletter ? 'newsletter' : (($in['source'] ?? '') === 'home-quote' ? 'quote' : 'contact');
$name = clean($in['name'] ?? '', 120);
$message = clean($in['message'] ?? '', 5000);
if (!$isNewsletter && ($name === '' || $message === '')) json_out(['error' => 'Enter your name and a message.'], 422);

try {
    if (!rate_ok('contact', 8, 3600)) json_out(['error' => 'Too many messages. Please try again later.'], 429);
    $subject = clean($in['subject'] ?? ($in['service'] ?? ''), 200);
    q('INSERT INTO enquiries (source, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)', [
        $source, $name, $email, clean($in['phone'] ?? '', 30), $subject, $isNewsletter ? 'Newsletter signup' : $message,
    ]);
    if (!empty($CONFIG['notify_email']) && !$isNewsletter) {
        @mail($CONFIG['notify_email'], 'New website enquiry: ' . ($subject ?: $name), "From: $name <$email>\nPhone: " . clean($in['phone'] ?? '', 30) . "\n\n$message");
    }
    json_out(['ok' => true]);
} catch (Throwable $ex) {
    json_out(['error' => 'Server error'], 500);
}
