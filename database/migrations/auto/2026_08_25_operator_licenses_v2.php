<?php

/**
 * Άδειες χειριστή ΜΕ v2 — πολλές άδειες ανά χειριστή (25/08/2026).
 *
 * Από πραγματικά βιβλιάρια (υπόδειγμα ΠΚΜ 2016: ένας χειριστής, 8 άδειες
 * 22241-22248, κάθε μία Ομάδα+Ειδικότητα+κάλυψη): ο πίνακας
 * driver_operator_licenses παύει να είναι «μία γραμμή ανά οδηγό» και
 * γίνεται «μία γραμμή ανά ΑΔΕΙΑ». Προστίθενται:
 *  - group_type  : Ομάδα Α΄/Β΄ της άδειας
 *  - issue_date  : ημερομηνία χορήγησης
 *  - covers_all  : 1 = «το σύνολο των μηχανημάτων της ειδικότητας»,
 *                  0 = συγκεκριμένες υποειδικότητες (πίνακας sub_specialities)
 * Στον πίνακα drivers προστίθεται operator_registry_number (Αρ. Μητρώου
 * βιβλιαρίου — ένα ανά κάτοχο).
 *
 * Idempotent: κάθε ALTER ελέγχει πρώτα αν η στήλη υπάρχει.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler (από bootstrap) τυπώνει σελίδα 500 σε σφάλμα —
// τον βγάζουμε ώστε το πραγματικό σφάλμα να φανεί ΩΜΟ στο log του deploy.
restore_exception_handler();
restore_error_handler();

function ensureColumn(PDO $pdo, string $table, string $column, string $ddl): void
{
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->execute([$table, $column]);
    if ($stmt->fetch()) {
        echo "OK: $table.$column υπάρχει ήδη.\n";
        return;
    }
    $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl");
    echo "OK: προστέθηκε $table.$column.\n";
}

ensureColumn($pdo, 'driver_operator_licenses', 'group_type', "group_type CHAR(1) NULL AFTER speciality");
ensureColumn($pdo, 'driver_operator_licenses', 'issue_date', "issue_date DATE NULL AFTER license_number");
ensureColumn($pdo, 'driver_operator_licenses', 'covers_all', "covers_all TINYINT(1) NOT NULL DEFAULT 0 AFTER issue_date");
ensureColumn($pdo, 'drivers', 'operator_registry_number', "operator_registry_number VARCHAR(20) NULL");

echo "OK: operator licenses v2 έτοιμο.\n";
