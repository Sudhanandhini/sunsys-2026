<?php
// Copy this file to config.php (same folder) on the server and fill in your details.
// config.php is never part of the build, so re-uploading dist/ will not overwrite it.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'cpaneluser_sunsys',   // database created in cPanel > MySQL Databases
        'user' => 'cpaneluser_sunsys',
        'pass' => 'CHANGE-ME',
    ],
    // Where uploaded CVs are stored. Best outside public_html, e.g. '/home/cpaneluser/sunsys-private'.
    // Leave null to use api/_private (blocked from the web by .htaccess).
    'private_dir' => null,
    // Optional: email address that receives a notice for each new enquiry or application (uses PHP mail()).
    'notify_email' => null,
    'timezone' => 'Asia/Kolkata',
    'debug' => false,
];
