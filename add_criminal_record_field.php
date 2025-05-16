<?php
require 'src/bootstrap.php';

try {
    $pdo = $container->get('pdo');

    // Έλεγχος αν το πεδίο υπάρχει ήδη
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'criminal_record_file'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Προσθήκη του πεδίου criminal_record_file
        $sql = "ALTER TABLE drivers ADD COLUMN criminal_record_file VARCHAR(255) AFTER resume_file";
        $pdo->exec($sql);
        echo "Το πεδίο criminal_record_file προστέθηκε με επιτυχία στον πίνακα drivers.\n";
    } else {
        echo "Το πεδίο criminal_record_file υπάρχει ήδη στον πίνακα drivers.\n";
    }
} catch (PDOException $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}
