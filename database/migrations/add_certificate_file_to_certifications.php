<?php

/**
 * Migration για την προσθήκη της στήλης certificate_file στον πίνακα driver_certifications
 * 
 * Αυτό το αρχείο προσθέτει τη στήλη certificate_file στον πίνακα driver_certifications
 * για την αποθήκευση των αρχείων πιστοποιητικών των οδηγών.
 */

// Φόρτωση του container
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο πίνακας υπάρχει
$tableExists = false;
$stmt = $pdo->query("SHOW TABLES LIKE 'driver_certifications'");
if ($stmt->rowCount() > 0) {
    $tableExists = true;
    echo "Ο πίνακας driver_certifications υπάρχει.\n";
} else {
    echo "Ο πίνακας driver_certifications δεν υπάρχει. Παρακαλώ εκτελέστε πρώτα το migration create_driver_certifications_table.php.\n";
    exit(1);
}

// Έλεγχος αν η στήλη certificate_file υπάρχει ήδη
$columnExists = false;
$stmt = $pdo->query("SHOW COLUMNS FROM driver_certifications LIKE 'certificate_file'");
if ($stmt->rowCount() > 0) {
    $columnExists = true;
    echo "Η στήλη certificate_file υπάρχει ήδη στον πίνακα driver_certifications.\n";
}

// Αν η στήλη δεν υπάρχει, την προσθέτουμε
if (!$columnExists) {
    try {
        $sql = "ALTER TABLE driver_certifications 
                ADD COLUMN certificate_file VARCHAR(255) NULL AFTER description";
        $pdo->exec($sql);
        echo "Η στήλη certificate_file προστέθηκε επιτυχώς στον πίνακα driver_certifications.\n";
    } catch (PDOException $e) {
        echo "Σφάλμα κατά την προσθήκη της στήλης certificate_file: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Δημιουργία του φακέλου για τα αρχεία πιστοποιητικών αν δεν υπάρχει
$certificatesDir = ROOT_DIR . '/uploads/certificates';
if (!file_exists($certificatesDir)) {
    if (mkdir($certificatesDir, 0755, true)) {
        echo "Ο φάκελος $certificatesDir δημιουργήθηκε επιτυχώς.\n";
    } else {
        echo "Σφάλμα κατά τη δημιουργία του φακέλου $certificatesDir.\n";
    }
} else {
    echo "Ο φάκελος $certificatesDir υπάρχει ήδη.\n";
}

echo "Η διαδικασία migration ολοκληρώθηκε επιτυχώς.\n";
