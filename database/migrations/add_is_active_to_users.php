<?php

/**
 * Migration για την προσθήκη του πεδίου is_active στους πίνακες drivers και companies
 * 
 * Αυτό το migration προσθέτει το πεδίο is_active στους πίνακες drivers και companies,
 * το οποίο θα χρησιμοποιείται για να ελέγχει αν ένας χρήστης είναι ενεργός ή όχι.
 */

// Σύνδεση με τη βάση δεδομένων
require_once __DIR__ . '/../../config/database.php';

try {
    // Προσθήκη του πεδίου is_active στον πίνακα drivers
    $pdo->exec("
        ALTER TABLE drivers
        ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
        AFTER is_verified
    ");

    echo "Προστέθηκε το πεδίο is_active στον πίνακα drivers.\n";

    // Προσθήκη του πεδίου is_active στον πίνακα companies
    $pdo->exec("
        ALTER TABLE companies
        ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
        AFTER is_verified
    ");

    echo "Προστέθηκε το πεδίο is_active στον πίνακα companies.\n";

    echo "Το migration ολοκληρώθηκε με επιτυχία.\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά την εκτέλεση του migration: " . $e->getMessage());
}
