<?php

/**
 * Πίνακας τιμών καταλόγου (lookup_values) — 30/08/2026.
 *
 * ΓΙΑΤΙ: οι ταξινομίες ζούσαν σε PHP helpers (SpecialLicenseTypes,
 * OperatorSpecialities κ.λπ.). Κάθε φορά που η νομοθεσία προσθέτει μια
 * κατηγορία, χρειαζόταν αλλαγή κώδικα και deploy — ενώ ο Κώστας, που
 * ξέρει τον κλάδο, δεν μπορούσε να το κάνει μόνος του.
 *
 * ΣΧΕΔΙΑΣΗ:
 *  - domain: σε ποιον κατάλογο ανήκει η τιμή (special_license, ...)
 *  - code:   σταθερός κωδικός — ΔΕΝ αλλάζει ποτέ (τον δείχνουν οι
 *            εγγραφές των οδηγών)
 *  - label:  το κείμενο που βλέπει ο χρήστης — αυτό διορθώνεται ελεύθερα
 *  - sort_order: σειρά εμφάνισης
 *  - is_active:  ΑΝΤΙ ΓΙΑ ΔΙΑΓΡΑΦΗ. Απενεργοποιημένη τιμή δεν προσφέρεται
 *                σε νέες καταχωρήσεις, αλλά όσοι την έχουν ήδη τη βλέπουν
 *                κανονικά — δεν σβήνουμε ποτέ δεδομένα οδηγών από κάτω τους.
 *  - is_system:  τιμές που το ταίριασμα/η βαθμολογία υπολογίζουν πάνω τους·
 *                ο διαχειριστής μπορεί να αλλάξει την ετικέτα τους, όχι να
 *                τις απενεργοποιήσει.
 *
 * Το seed γίνεται από τους helpers, ώστε βάση και κώδικας να ξεκινούν
 * συμφωνημένοι. Idempotent: INSERT IGNORE σε unique (domain, code).
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler (από bootstrap) τυπώνει σελίδα 500 σε σφάλμα —
// τον βγάζουμε ώστε το πραγματικό σφάλμα να φανεί ΩΜΟ στο log του deploy.
restore_exception_handler();
restore_error_handler();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS lookup_values (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        domain VARCHAR(40) NOT NULL,
        code VARCHAR(40) NOT NULL,
        label VARCHAR(255) NOT NULL,
        short_label VARCHAR(80) NULL,
        sort_order INT(11) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        is_system TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
        updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        UNIQUE KEY uniq_domain_code (domain, code),
        KEY idx_domain_active (domain, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
echo "OK: πίνακας lookup_values έτοιμος.\n";

/*
 * Seed ειδικών αδειών. Τα δύο ΠΕΕ προστέθηκαν 30/08 μετά από υπόδειξη
 * του Κώστα: είναι ΔΥΟ ξεχωριστά πιστοποιητικά (εμπορευματικές /
 * επιβατικές μεταφορές) — δεν συγχωνεύονται σε ένα.
 */
/*
 * is_system ΜΟΝΟ στο «other»: είναι το δίχτυ ασφαλείας της φόρμας (ό,τι
 * δεν χωρά αλλού). Οι υπόλοιπες κατηγορίες είναι νομοθετικές και πρέπει
 * να μπορεί ο διαχειριστής να τις αποσύρει αν καταργηθούν — γι' αυτό
 * ακριβώς φτιάχτηκε ο κατάλογος.
 */
$specialLicenses = [
    ['edx_taxi',      'Ειδική άδεια ΕΔΧ (ΤΑΞΙ)', 'ΕΔΧ (ΤΑΞΙ)', 10, 0],
    ['live_animals',  'Πιστοποιητικό Επάρκειας Οδηγών και Συνοδών Μεταφορικών Μέσων Ζώντων Ζώων', 'Μεταφορά ζώντων ζώων', 20, 0],
    ['rental_driver', 'Πιστοποιητικό οδηγού για ενοικίαση οχήματος με οδηγό', 'Ενοικίαση με οδηγό', 30, 0],
    ['pee_freight',   'Πιστοποιητικό Επαγγελματικής Επάρκειας (ΠΕΕ) Εμπορευματικών Μεταφορών', 'ΠΕΕ Εμπορευματικών', 40, 0],
    ['pee_passenger', 'Πιστοποιητικό Επαγγελματικής Επάρκειας (ΠΕΕ) Επιβατικών Μεταφορών', 'ΠΕΕ Επιβατικών', 50, 0],
    ['other',         'Άλλο πιστοποιητικό οδηγού', 'Άλλο πιστοποιητικό', 900, 1],
];

$ins = $pdo->prepare(
    'INSERT IGNORE INTO lookup_values (domain, code, label, short_label, sort_order, is_system)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$added = 0;
foreach ($specialLicenses as [$code, $label, $short, $order, $isSystem]) {
    $ins->execute(['special_license', $code, $label, $short, $order, $isSystem]);
    $added += $ins->rowCount();
}

// Idempotent διόρθωση: μόνο το «other» παραμένει βασική τιμή συστήματος.
$pdo->exec("UPDATE lookup_values SET is_system = 0 WHERE domain = 'special_license' AND code <> 'other'");

echo "OK: ειδικές άδειες — προστέθηκαν $added νέες τιμές.\n";
