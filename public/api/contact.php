<?php
// Receives the contact form, home quote form and newsletter signup (all POST JSON) and stores them as enquiries.
require __DIR__ . '/_lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'Method not allowed'], 405);

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) $in = $_POST;

// Honeypot: bots fill every field
if (!empty($in['website'])) json_out(['ok' => true]);

$email = strtolower(clean($in['email'] ?? '', 190));
$isNewsletter = ($in['type'] ?? '') === 'newsletter';
$source = $isNewsletter ? 'newsletter' : (($in['source'] ?? '') === 'home-quote' ? 'quote' : 'contact');
$name = preg_replace('/\s+/u', ' ', clean($in['name'] ?? '', 120));
$phone = clean($in['phone'] ?? '', 30);
$message = clean($in['message'] ?? '', 5000);

// Same rules as the forms (src/lib/validate.js); field keys match the form fields
$errors = array_filter(['email' => email_error($email)]);
if (!$isNewsletter) {
    $errors += array_filter([
        'name' => name_error($name),
        // the home quote form requires a phone number; on the contact form it is optional
        'phone' => phone_error($phone, $source === 'quote'),
    ]);
    // the quote form sends its subject as the message
    $msgError = $source === 'quote' ? text_error($message, 3, 'what it is about') : text_error($message, 10, 'how we can help');
    if ($msgError) $errors[$source === 'quote' ? 'subject' : 'message'] = $msgError;
}
if ($errors) json_out(['error' => reset($errors), 'fields' => $errors], 422);

try {
    if (!rate_ok('contact', 8, 3600)) json_out(['error' => 'Too many messages. Please try again later.'], 429);
    $subject = clean($in['subject'] ?? ($in['service'] ?? ''), 200);
    q('INSERT INTO enquiries (source, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)', [
        $source, $name, $email, $phone, $subject, $isNewsletter ? 'Newsletter signup' : $message,
    ]);
    if (!empty($CONFIG['notify_email']) && !$isNewsletter) {
        @mail($CONFIG['notify_email'], 'New website enquiry: ' . ($subject ?: $name), "From: $name <$email>\nPhone: $phone\n\n$message");
    }
    json_out(['ok' => true]);
} catch (Throwable $ex) {
    json_out(['error' => 'Server error'], 500);
}
