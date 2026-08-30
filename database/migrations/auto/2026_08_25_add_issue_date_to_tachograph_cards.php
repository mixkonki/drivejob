<?php

/**
 * Προσθήκη ημερομηνίας έκδοσης (πεδίο 4α της κάρτας) στον πίνακα
 * driver_tachograph_cards — ΚΥΑ οικ.12527/1159/2014 (ΦΕΚ 577/Β):
 * η κάρτα οδηγού φέρει 4α έκδοση, 4β λήξη, 5α αρ. διπλώματος,
 * 5β αρ. κάρτας. Idempotent: τρέχει όσες φορές θες.
 *
 * Τοπικά:  php database/migrate.php
 * Server:  τρέχει αυτόματα στο deploy (database/migrate.php)
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler (από bootstrap) τυπώνει σελίδα 500 σε σφάλμα —
// τον βγάζουμε ώστε το πραγματικό σφάλμα να φανεί ΩΜΟ στο log του deploy.
restore_exception_handler();
restore_error_handler();

$col = $pdo->query("SHOW COLUMNS FROM driver_tachograph_cards LIKE 'issue_date'")->fetch();

if ($col) {
    echo "OK: η στήλη issue_date υπάρχει ήδη.\n";
    return;
}

$pdo->exec("ALTER TABLE driver_tachograph_cards ADD COLUMN issue_date DATE NULL AFTER card_number");
echo "OK: προστέθηκε η στήλη issue_date στο driver_tachograph_cards.\n";
