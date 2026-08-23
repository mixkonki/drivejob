<?php

/**
 * Επέκταση του ENUM job_listings.job_type.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  Η ΑΓΓΕΛΙΑ ΠΟΥ ΔΕΝ ΑΠΟΘΗΚΕΥΟΤΑΝ ΠΟΤΕ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Τρία σημεία μιλούσαν διαφορετική γλώσσα για το ίδιο πράγμα:
 *
 *   Η φόρμα δημιουργίας πρόσφερε:  full_time, part_time, contract,
 *                                  temporary, seasonal
 *   Ο έλεγχος εγκυρότητας δεχόταν: … + seasonal, freelance, internship
 *   Η στήλη της βάσης δεχόταν:     full_time, part_time, contract, temporary
 *
 * Η MySQL τρέχει σε STRICT_TRANS_TABLES. Μια τιμή εκτός ENUM δεν κόβεται
 * σιωπηλά — απορρίπτεται ολόκληρο το INSERT. Οπότε ο χρήστης που επέλεγε
 * «Εποχική» συμπλήρωνε τη φόρμα, πατούσε αποθήκευση, και η αγγελία απλώς
 * δεν υπήρχε. Χωρίς μήνυμα λάθους στη φόρμα, γιατί το σφάλμα γεννιόταν στη
 * βάση, μετά από κάθε έλεγχο εγκυρότητας.
 *
 * Η εποχική απασχόληση ΔΕΝ είναι ακραία περίπτωση στις μεταφορές: τουριστικά
 * λεωφορεία, αγροτικές μεταφορές, η περίοδος των εορτών στα courier. Η σωστή
 * κατεύθυνση της διόρθωσης είναι να δεχτεί η βάση τις τιμές, όχι να τις κόψει
 * η φόρμα.
 *
 * Idempotent: διαβάζει το τρέχον ENUM και δεν αγγίζει τίποτα αν οι τιμές
 * υπάρχουν ήδη.
 */

require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

restore_exception_handler();
restore_error_handler();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, "\n❌ " . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, '   ' . $e->getFile() . ':' . $e->getLine() . "\n\n");
    exit(1);
});

echo "Επέκταση του ENUM job_listings.job_type\n";
echo str_repeat('─', 60), "\n\n";

$wanted = ['full_time', 'part_time', 'contract', 'temporary', 'seasonal', 'freelance', 'internship'];

$col = $pdo->query("SHOW COLUMNS FROM job_listings LIKE 'job_type'")->fetch(PDO::FETCH_ASSOC);

if (!$col) {
    fwrite(STDERR, "❌ Η στήλη job_type δεν βρέθηκε.\n");
    exit(1);
}

echo "Τώρα:  {$col['Type']}\n\n";

preg_match_all("/'([^']+)'/", (string) $col['Type'], $m);
$current = $m[1] ?? [];

$missing = array_values(array_diff($wanted, $current));

if (empty($missing)) {
    echo "Όλες οι τιμές υπάρχουν ήδη — καμία αλλαγή.\n";
    exit(0);
}

echo 'Λείπουν: ' . implode(', ', $missing) . "\n\n";

/*
 * Η σειρά είναι η ΕΠΙΘΥΜΗΤΗ, όχι «υπάρχουσες + νέες».
 *
 * Το ENUM αποθηκεύει εσωτερικά τη ΘΕΣΗ, όχι το κείμενο. Αλλαγή της σειράς
 * των υπαρχουσών τιμών θα άλλαζε το νόημα κάθε αποθηκευμένης εγγραφής:
 * «πλήρης απασχόληση» θα γινόταν «μερική». Γι' αυτό οι τέσσερις πρώτες
 * τιμές μένουν ακριβώς εκεί που ήταν και οι νέες μπαίνουν στο τέλος.
 */
$ordered = array_merge($current, $missing);

$quoted = implode(', ', array_map(static fn(string $v): string => "'" . $v . "'", $ordered));

$null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
$default = $col['Default'] !== null ? " DEFAULT '" . $col['Default'] . "'" : '';

$pdo->exec("ALTER TABLE job_listings MODIFY job_type ENUM($quoted) $null$default");

$after = $pdo->query("SHOW COLUMNS FROM job_listings LIKE 'job_type'")->fetch(PDO::FETCH_ASSOC);

echo "✓ Ενημερώθηκε.\n\n";
echo "Τώρα:  {$after['Type']}\n\n";

$counts = $pdo->query('SELECT job_type, COUNT(*) n FROM job_listings GROUP BY job_type')->fetchAll(PDO::FETCH_ASSOC);
echo "Κατανομή αγγελιών:\n";
foreach ($counts as $c) {
    printf("  %-14s %d\n", $c['job_type'] ?: '(κενό)', $c['n']);
}

echo "\nΗ «Εποχική απασχόληση» αποθηκεύεται πλέον κανονικά.\n";
