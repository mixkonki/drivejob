<?php

/**
 * Migration: Κατάργηση ποινικού μητρώου (GDPR άρθρο 10)
 *
 * - Αφαιρεί τη στήλη drivers.criminal_record_file
 * - Το πεδίο legal_status παραμένει ως υπεύθυνη δήλωση (yes/no)
 * - Προσθέτει timestamp δήλωσης legal_status_declared_at για αποδεικτικότητα
 *
 * Εκτέλεση:  php database/migrations/remove_criminal_record.php
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
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "🔒 Migration: Κατάργηση ποινικού μητρώου\n\n";

    // 1. Έλεγχος αν υπάρχει η στήλη
    $col = $pdo->query("
        SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = 'drivejob' AND table_name = 'drivers'
          AND column_name = 'criminal_record_file'
    ")->fetchColumn();

    if ($col > 0) {
        // Πόσοι οδηγοί είχαν αρχείο (για το log)
        $count = $pdo->query("SELECT COUNT(*) FROM drivers WHERE criminal_record_file IS NOT NULL AND criminal_record_file != ''")->fetchColumn();
        echo "  ℹ️  {$count} οδηγοί είχαν καταχωρημένο αρχείο — τα paths διαγράφονται.\n";

        $pdo->exec("ALTER TABLE drivers DROP COLUMN criminal_record_file");
        echo "  ✅ Η στήλη criminal_record_file διαγράφηκε.\n";
    } else {
        echo "  ⏭️  Η στήλη criminal_record_file δεν υπάρχει (ήδη διαγραμμένη).\n";
    }

    // 2. Timestamp υπεύθυνης δήλωσης
    $col2 = $pdo->query("
        SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = 'drivejob' AND table_name = 'drivers'
          AND column_name = 'legal_status_declared_at'
    ")->fetchColumn();

    if ($col2 == 0) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN legal_status_declared_at DATETIME NULL
                    COMMENT 'Πότε έγινε η υπεύθυνη δήλωση λευκού μητρώου' AFTER legal_status");
        echo "  ✅ Προστέθηκε στήλη legal_status_declared_at.\n";
    } else {
        echo "  ⏭️  Η στήλη legal_status_declared_at υπάρχει ήδη.\n";
    }

    echo "\n🟢 Ολοκληρώθηκε.\n";
} catch (PDOException $e) {
    echo "❌ Σφάλμα: " . $e->getMessage() . "\n";
    exit(1);
}
