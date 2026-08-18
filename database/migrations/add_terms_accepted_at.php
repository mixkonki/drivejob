<?php

/**
 * Migration: Στήλη terms_accepted_at σε drivers & companies (Πακέτο 7 — GDPR)
 *
 * Καταγράφει ΠΟΤΕ ο χρήστης αποδέχθηκε Όρους Χρήσης & Πολιτική Απορρήτου
 * (accountability, άρθρο 5§2 GDPR). Για υφιστάμενους λογαριασμούς μένει NULL
 * — θα ζητηθεί αποδοχή στην επόμενη σύνδεση αν χρειαστεί.
 *
 * Εκτέλεση:  php database/migrations/add_terms_accepted_at.php   (idempotent)
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=drivejob;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "⚖️  Migration: terms_accepted_at (GDPR)\n\n";

foreach (['drivers', 'companies'] as $table) {
    $exists = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'terms_accepted_at'"
    );
    $exists->execute([$table]);

    if ($exists->fetchColumn() > 0) {
        echo "  ⏭️  {$table}.terms_accepted_at υπάρχει ήδη.\n";
        continue;
    }

    $pdo->exec("ALTER TABLE {$table} ADD COLUMN terms_accepted_at DATETIME NULL AFTER created_at");
    echo "  ✅ Προστέθηκε {$table}.terms_accepted_at\n";
}

echo "\n🟢 Ολοκληρώθηκε.\n";
