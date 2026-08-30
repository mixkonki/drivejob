<?php

/**
 * Θεματολογίες πιστοποιητικών στον κατάλογο (30/08/2026).
 *
 * Δεύτερος κατάλογος που περνά στη διαχείριση του admin, μετά τις
 * ειδικές άδειες — ίδιο μοτίβο, ώστε ο Κώστας να προσθέτει θεματολογίες
 * σεμιναρίων (π.χ. «Ηλεκτρικά οχήματα») χωρίς νέα έκδοση.
 *
 * is_system μόνο στο «other»: δίχτυ ασφαλείας της φόρμας.
 * Idempotent: INSERT IGNORE στο unique (domain, code).
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler τυπώνει σελίδα 500 σε σφάλμα — τον βγάζουμε.
restore_exception_handler();
restore_error_handler();

$categories = [
    ['road_safety',      'Οδική ασφάλεια', 10],
    ['tachograph',       'Ταχογράφος', 20],
    ['loading_securing', 'Φόρτωση - Πρόσδεση', 30],
    ['technical',        'Τεχνική επιμόρφωση', 40],
    ['commercial',       'Εμπορική επιμόρφωση', 50],
    ['procedures',       'Διαδικασίες', 60],
    ['inspections',      'Έλεγχοι', 70],
    ['first_aid',        'Πρώτες βοήθειες', 80],
    ['adr',              'ADR / Επικίνδυνα φορτία', 90],
    ['other',            'Άλλο', 900],
];

$ins = $pdo->prepare(
    'INSERT IGNORE INTO lookup_values (domain, code, label, short_label, sort_order, is_system)
     VALUES (?, ?, ?, NULL, ?, ?)'
);

$added = 0;
foreach ($categories as [$code, $label, $order]) {
    $ins->execute(['cert_category', $code, $label, $order, $code === 'other' ? 1 : 0]);
    $added += $ins->rowCount();
}

echo "OK: θεματολογίες πιστοποιητικών — προστέθηκαν $added νέες τιμές.\n";
