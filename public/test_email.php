<?php
// Φόρτωση του bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

// Φόρτωση ρυθμίσεων
$config = include_once ROOT_DIR . '/config/notifications.php';

// Δημιουργία του EmailService
$emailService = new \Drivejob\Services\EmailService(
    $config['smtp_host'],
    $config['smtp_port'],
    $config['smtp_username'],
    $config['smtp_password'],
    $config['sender_email'],
    $config['sender_name'],
    true // Ενεργοποίηση debug mode
);

// Δοκιμαστική αποστολή email
$result = $emailService->send(
    'toemail@example.com', // Άλλαξε με ένα δικό σου email για δοκιμή
    'Δοκιμαστικό email από DriveJob',
    '<html><body><h1>Δοκιμαστικό Email</h1><p>Αυτό είναι ένα δοκιμαστικό email από το σύστημα ειδοποιήσεων DriveJob.</p></body></html>'
);

echo "Αποτέλεσμα αποστολής: " . ($result ? "Επιτυχία" : "Αποτυχία") . "\n";
?>