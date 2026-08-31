<?php

/**
 * Δοκιμή SMTP από τη γραμμή εντολών (01/09/2026 — Φάση Γ).
 *
 * Τρέχει ΜΕΣΑ στον server (όπως το health-cli.php) και απαντά σε ένα
 * ερώτημα: «αν η εφαρμογή προσπαθήσει να στείλει email ΤΩΡΑ, θα φύγει;»
 *
 * Χρήση:
 *   /usr/php83/usr/bin/php bin/email-test-cli.php                  → δείχνει τις ρυθμίσεις (χωρίς τον κωδικό)
 *   /usr/php83/usr/bin/php bin/email-test-cli.php you@example.com  → στέλνει πραγματικό δοκιμαστικό email
 *
 * Χρησιμοποιεί την ΙΔΙΑ διαδρομή με την εφαρμογή (bootstrap → config/email.php
 * → EmailService), όχι δικό της PHPMailer setup — ώστε αν περάσει η δοκιμή,
 * να έχει περάσει και ό,τι θα στείλει η εφαρμογή.
 */

if (php_sapi_name() !== 'cli') {
    exit(1); // μόνο από γραμμή εντολών — δεν είναι web endpoint
}

require_once __DIR__ . '/../src/bootstrap.php';

echo "══ Ρυθμίσεις SMTP (από .env) ══\n";
echo 'SMTP_HOST:       ' . (SMTP_HOST !== '' ? SMTP_HOST : '(κενό — το email είναι ΑΝΕΝΕΡΓΟ)') . "\n";
echo 'SMTP_PORT:       ' . SMTP_PORT . ' (' . (SMTP_PORT == 465 ? 'SSL' : 'STARTTLS') . ")\n";
echo 'SMTP_USERNAME:   ' . SMTP_USERNAME . "\n";
echo 'SMTP_PASSWORD:   ' . (SMTP_PASSWORD !== '' ? str_repeat('•', 8) . ' (ορισμένος)' : '(ΚΕΝΟΣ)') . "\n";
echo 'SMTP_FROM_EMAIL: ' . SMTP_FROM_EMAIL . "\n";
echo 'SMTP_FROM_NAME:  ' . SMTP_FROM_NAME . "\n\n";

if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
    echo "Λείπουν ρυθμίσεις. Πρόσθεσε στο .env:\n";
    echo "  SMTP_HOST=...\n  SMTP_PORT=465\n  SMTP_USERNAME=admin@drivejob.gr\n";
    echo "  SMTP_PASSWORD=...\n  SMTP_FROM_EMAIL=admin@drivejob.gr\n  SMTP_FROM_NAME=DriveJob\n";
    exit(1);
}

$to = $argv[1] ?? null;
if ($to === null) {
    echo "Οι ρυθμίσεις φαίνονται πλήρεις. Για πραγματική αποστολή:\n";
    echo "  php bin/email-test-cli.php το-email-σου@example.com\n";
    exit(0);
}

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Μη έγκυρη διεύθυνση: $to\n";
    exit(1);
}

echo "Αποστολή δοκιμαστικού στο $to ...\n";

$svc = new \Drivejob\Services\EmailService(
    SMTP_HOST,
    SMTP_PORT,
    SMTP_USERNAME,
    SMTP_PASSWORD,
    SMTP_FROM_EMAIL,
    SMTP_FROM_NAME,
    true // debug: τυπώνει τον διάλογο SMTP — χρήσιμο αν κάτι στραβώσει
);

$ok = $svc->send(
    $to,
    'Δοκιμή SMTP — DriveJob',
    '<h2>Το SMTP του DriveJob δουλεύει ✔</h2>'
        . '<p>Αυτό το μήνυμα στάλθηκε από το <code>bin/email-test-cli.php</code> '
        . 'στις ' . date('d/m/Y H:i:s') . ' μέσω ' . htmlspecialchars(SMTP_HOST, ENT_QUOTES, 'UTF-8') . '.</p>'
        . '<p>Αν το διαβάζεις, τα emails της πλατφόρμας (επαλήθευση εγγραφής, '
        . 'επαναφορά κωδικού, προσκλήσεις σύστασης, ειδοποιήσεις) θα φεύγουν κανονικά.</p>'
);

if ($ok) {
    echo "\n✔ Εστάλη. Έλεγξε τα εισερχόμενα (και τα ανεπιθύμητα) στο $to.\n";
    echo "Στο Gmail: άνοιξε το μήνυμα → ⋮ → «Εμφάνιση πρωτοτύπου» και δες\n";
    echo "ότι SPF και DKIM γράφουν PASS — αυτό κρίνει αν θα καταλήγουν στα spam.\n";
    exit(0);
}

echo "\n✘ Απέτυχε. Ο διάλογος SMTP παραπάνω δείχνει το γιατί —\n";
echo "συνήθη αίτια: λάθος κωδικός, λάθος host/port, ή ανύπαρκτη θυρίδα.\n";
exit(1);
