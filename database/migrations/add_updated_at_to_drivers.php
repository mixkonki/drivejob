<?php

/**
 * Migration: Προσθήκη στήλης updated_at στον πίνακα drivers
 *
 * Διορθώνει προϋπάρχον bug: ο DriversController στέλνει updated_at
 * στο update του προφίλ, αλλά η στήλη δεν υπήρχε ποτέ στον πίνακα —
 * κάθε αποθήκευση προφίλ οδηγού απέτυχε με SQLSTATE[42S22].
 * (Τεκμηρίωση: logs/app.log, εμφανίζεται ήδη από 29/07/2026.)
 *
 * Εκτέλεση:  php database/migrations/add_updated_at_to_drivers.php
 * Idempotent: ασφαλές να τρέξει πολλές φορές.
 */

$envCfg = require __DIR__ . '/_config.php';
$config = [
    'host' => $envCfg['host'],
    'dbname' => $envCfg['database'],
    'username' => $envCfg['username'],
    'password' => $envCfg['password'],
    'charset' => 'utf8mb4',
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "🔧 Migration: updated_at στον πίνακα drivers\n\n";

    $exists = $pdo->query("
        SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = 'drivejob' AND table_name = 'drivers' AND column_name = 'updated_at'
    ")->fetchColumn();

    if ($exists == 0) {
        $pdo->exec("ALTER TABLE drivers
                    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP");
        echo "  ✅ Προστέθηκε η στήλη updated_at (auto-update σε κάθε αλλαγή).\n";
    } else {
        echo "  ⏭️  Η στήλη updated_at υπάρχει ήδη.\n";
    }

    // Έλεγχος και για created_at — συχνά λείπει μαζί
    $exists2 = $pdo->query("
        SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = 'drivejob' AND table_name = 'drivers' AND column_name = 'created_at'
    ")->fetchColumn();

    if ($exists2 == 0) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        echo "  ✅ Προστέθηκε και η στήλη created_at.\n";
    } else {
        echo "  ⏭️  Η στήλη created_at υπάρχει ήδη.\n";
    }

    echo "\n🟢 Ολοκληρώθηκε. Δοκίμασε ξανά την αποθήκευση προφίλ.\n";
} catch (PDOException $e) {
    echo "❌ Σφάλμα: " . $e->getMessage() . "\n";
    exit(1);
}
