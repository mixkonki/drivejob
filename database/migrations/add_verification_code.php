<?php

/**
 * Migration: verification_code & verification_expires σε drivers και companies.
 *
 * Ο κώδικας εγγραφής (src/Models/Auth/UserRegistration.php και
 * src/Repositories/UserRepository.php) γράφει σε αυτές τις στήλες, αλλά το
 * σχήμα είχε μόνο το παλιό «verification_token» χωρίς ημερομηνία λήξης.
 * Αποτέλεσμα: κάθε εγγραφή απέτυχε με
 *   SQLSTATE[42S22] Unknown column 'verification_code' in 'INSERT INTO'
 *
 * Το verification_token διατηρείται — το χρησιμοποιούν ακόμη τα
 * DriversRepository / CompaniesRepository και η εξαγωγή δεδομένων GDPR.
 *
 * Εκτέλεση:  php database/migrations/add_verification_code.php   (idempotent)
 */

$pdo = require __DIR__ . '/_bootstrap.php';

echo "🔑 Migration: verification_code / verification_expires\n\n";

$columns = [
    'verification_code'    => "VARCHAR(64) NULL",
    'verification_expires' => "DATETIME NULL",
];

foreach (['drivers', 'companies'] as $table) {
    foreach ($columns as $column => $definition) {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
        );
        $check->execute([$table, $column]);

        if ($check->fetchColumn() > 0) {
            echo "  ⏭️  {$table}.{$column} υπάρχει ήδη.\n";
            continue;
        }

        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        echo "  ✅ Προστέθηκε {$table}.{$column}\n";
    }

    // Ευρετήριο για την αναζήτηση κατά την επαλήθευση λογαριασμού
    $idx = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?"
    );
    $idx->execute([$table, "idx_{$table}_verification_code"]);

    if ($idx->fetchColumn() == 0) {
        $pdo->exec("CREATE INDEX idx_{$table}_verification_code ON {$table} (verification_code)");
        echo "  ✅ Ευρετήριο idx_{$table}_verification_code\n";
    } else {
        echo "  ⏭️  Ευρετήριο idx_{$table}_verification_code υπάρχει ήδη.\n";
    }
}

echo "\n🟢 Ολοκληρώθηκε.\n";
