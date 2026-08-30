<?php

/**
 * Κατάλογοι τιμών — πίνακας + όλα τα seed σε ΕΝΑ migration (30/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΕΝΑ ΑΡΧΕΙΟ ΚΑΙ ΟΧΙ ΤΡΙΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Πρώτη γραφή: τρία ξεχωριστά migrations (lookup_values,
 * lookup_cert_categories, lookup_system_flags). Ο runner τα εκτελεί
 * ΑΛΦΑΒΗΤΙΚΑ — και αλφαβητικά το «cert_categories» και το
 * «system_flags» προηγούνται του «values», δηλαδή τα δύο που
 * ΧΡΗΣΙΜΟΠΟΙΟΥΝ τον πίνακα έτρεχαν πριν από αυτό που τον ΔΗΜΙΟΥΡΓΕΙ:
 *
 *   SQLSTATE[42S02]: Table 'lookup_values' doesn't exist
 *
 * Τοπικά δεν φάνηκε επειδή τα είχα τρέξει σταδιακά, καθένα μόλις το
 * έγραφα· στον server έτρεξαν όλα μαζί, με τη σειρά που ορίζει το
 * αλφάβητο. ΜΑΘΗΜΑ: κάθε νέο migration δοκιμάζεται από ΜΗΔΕΝΙΚΗ βάση,
 * και αλληλεξαρτώμενα βήματα μπαίνουν στο ΙΔΙΟ αρχείο — η σειρά μέσα
 * σε ένα script είναι ρητή, η σειρά μεταξύ αρχείων είναι σύμβαση που
 * σπάει εύκολα.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΚΑΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Οι ταξινομίες ζούσαν σε PHP σταθερές: κάθε νέα κατηγορία της
 * νομοθεσίας απαιτούσε αλλαγή κώδικα και deploy. Τώρα τις συντηρεί ο
 * διαχειριστής από το /admin/lookups.
 *
 *  - domain     : σε ποιον κατάλογο ανήκει η τιμή
 *  - code       : σταθερός κωδικός — ΔΕΝ αλλάζει ποτέ (τον δείχνουν οι
 *                 εγγραφές των οδηγών)
 *  - label      : το κείμενο που βλέπει ο χρήστης — διορθώνεται ελεύθερα
 *  - is_active  : ΑΝΤΙ ΓΙΑ ΔΙΑΓΡΑΦΗ — ανενεργή τιμή δεν προσφέρεται σε
 *                 νέες καταχωρήσεις, αλλά όσοι την έχουν τη διατηρούν
 *  - is_system  : μόνο στο «other» κάθε καταλόγου (δίχτυ ασφαλείας της
 *                 φόρμας)· οι νομοθετικές κατηγορίες πρέπει να μπορούν
 *                 να αποσυρθούν — γι' αυτό ακριβώς φτιάχτηκε ο κατάλογος
 *
 * Idempotent από άκρη σε άκρη: CREATE TABLE IF NOT EXISTS, INSERT IGNORE
 * σε unique (domain, code), UPDATE που ξανατρέχει αβλαβώς.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler (από bootstrap) τυπώνει σελίδα 500 σε σφάλμα —
// τον βγάζουμε ώστε το πραγματικό σφάλμα να φανεί ΩΜΟ στο log του deploy.
restore_exception_handler();
restore_error_handler();

// ── 1. Ο πίνακας ────────────────────────────────────────────────────────
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

$ins = $pdo->prepare(
    'INSERT IGNORE INTO lookup_values (domain, code, label, short_label, sort_order, is_system)
     VALUES (?, ?, ?, ?, ?, ?)'
);

// ── 2. Ειδικές άδειες & πιστοποιητικά οδηγού ────────────────────────────
// Τα δύο ΠΕΕ είναι ΔΥΟ ξεχωριστά πιστοποιητικά (εμπορευματικές /
// επιβατικές) — δεν συγχωνεύονται σε ένα.
$specialLicenses = [
    ['edx_taxi',      'Ειδική άδεια ΕΔΧ (ΤΑΞΙ)', 'ΕΔΧ (ΤΑΞΙ)', 10, 0],
    ['live_animals',  'Πιστοποιητικό Επάρκειας Οδηγών και Συνοδών Μεταφορικών Μέσων Ζώντων Ζώων', 'Μεταφορά ζώντων ζώων', 20, 0],
    ['rental_driver', 'Πιστοποιητικό οδηγού για ενοικίαση οχήματος με οδηγό', 'Ενοικίαση με οδηγό', 30, 0],
    ['pee_freight',   'Πιστοποιητικό Επαγγελματικής Επάρκειας (ΠΕΕ) Εμπορευματικών Μεταφορών', 'ΠΕΕ Εμπορευματικών', 40, 0],
    ['pee_passenger', 'Πιστοποιητικό Επαγγελματικής Επάρκειας (ΠΕΕ) Επιβατικών Μεταφορών', 'ΠΕΕ Επιβατικών', 50, 0],
    ['other',         'Άλλο πιστοποιητικό οδηγού', 'Άλλο πιστοποιητικό', 900, 1],
];

$addedSpecial = 0;
foreach ($specialLicenses as [$code, $label, $short, $order, $isSystem]) {
    $ins->execute(['special_license', $code, $label, $short, $order, $isSystem]);
    $addedSpecial += $ins->rowCount();
}
echo "OK: ειδικές άδειες — $addedSpecial νέες τιμές.\n";

// ── 3. Θεματολογίες σεμιναρίων & πιστοποιητικών ─────────────────────────
$certCategories = [
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

$addedCert = 0;
foreach ($certCategories as [$code, $label, $order]) {
    $ins->execute(['cert_category', $code, $label, null, $order, $code === 'other' ? 1 : 0]);
    $addedCert += $ins->rowCount();
}
echo "OK: θεματολογίες πιστοποιητικών — $addedCert νέες τιμές.\n";

// ── 4. Σημαίες συστήματος: βασική μόνο η «other» κάθε καταλόγου ─────────
// (Διορθώνει και βάσεις όπου μια προηγούμενη εκτέλεση τα είχε σημάνει όλα.)
$fix = $pdo->prepare("UPDATE lookup_values SET is_system = 0 WHERE code <> 'other'");
$fix->execute();
echo 'OK: ' . $fix->rowCount() . " τιμές έγιναν διαχειρίσιμες από τον admin.\n";
