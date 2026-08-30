<?php

/**
 * Έλεγχος υγείας ΑΠΟ ΤΟΝ ΙΔΙΟ ΤΟΝ SERVER. (31/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ — ΤΟ 401 ΠΟΥ ΔΕΝ ΗΤΑΝ ΔΙΚΟ ΜΑΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το deploy κοκκίνισε στο βήμα «Έλεγχος υγείας»:
 *
 *     curl -fsS https://drivejob.gr/health   →   401 Unauthorized
 *
 * Η προφανής εξήγηση («το endpoint θέλει αυθεντικοποίηση») είναι ΛΑΘΟΣ.
 * Ο HealthController δεν έχει κανέναν έλεγχο πρόσβασης και επιστρέφει
 * μόνο 200 ή 503 — ποτέ 401. Μετρημένο την ίδια στιγμή από τον
 * υπολογιστή του Κώστα: **HTTP 200**, με κάθε user-agent.
 *
 * Το 401 δεν ερχόταν από την εφαρμογή. Ερχόταν από το firewall/CDN του
 * παρόχου, που μπλοκάρει IP κέντρων δεδομένων — και τα GitHub Actions
 * runners τρέχουν σε Azure. Οικιακή σύνδεση: 200. Datacenter: 401.
 *
 * ΓΙΑΤΙ ΔΕΝ ΒΑΛΑΜΕ `|| true`:
 * Θα έκανε το βήμα διακοσμητικό. Ο έλεγχος υγείας υπάρχει για να πιάνει
 * deploy που άφησε την παραγωγή σπασμένη· ένας έλεγχος που δεν αποτυγχάνει
 * ποτέ είναι χειρότερος από κανέναν, γιατί δίνει ψεύτικη ασφάλεια.
 *
 * Η ΛΥΣΗ: ο έλεγχος τρέχει ΜΕΣΑ στον server, μέσω της ίδιας σύνδεσης SSH
 * που έκανε το deploy. Κανένα firewall στη μέση, και ελέγχει ακριβώς τα
 * ίδια πράγματα με το /health — που είναι και πιο κοντά στην αλήθεια:
 * αυτό που μας νοιάζει είναι αν η ΕΦΑΡΜΟΓΗ λειτουργεί, όχι αν το CDN
 * αφήνει τη Microsoft να τη ρωτήσει.
 *
 * Έξοδος: 0 = υγιής, 1 = πρόβλημα (κοκκινίζει το deploy).
 */

require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

// CLI: ο web ExceptionHandler render-άρει σελίδα 500 σε σφάλμα — σε
// terminal αυτό εμφανίζεται ως ολόκληρη HTML στο stdout, που κρύβει το
// πραγματικό μήνυμα.
restore_exception_handler();
restore_error_handler();

$checks = [];

// ── 1. Η εφαρμογή φορτώνει ──────────────────────────────────────────────
$checks['bootstrap'] = defined('ROOT_DIR') && defined('BASE_URL');

// ── 2. Η βάση απαντά ────────────────────────────────────────────────────
try {
    Database::getInstance()->getConnection()->query('SELECT 1');
    $checks['database'] = true;
} catch (\Throwable $e) {
    $checks['database'] = false;
    $dbError = $e->getMessage();
}

// ── 3. Η αποθήκευση είναι εγγράψιμη ─────────────────────────────────────
$storage = ROOT_DIR . '/storage/uploads';
$checks['storage'] = is_dir($storage) && is_writable($storage);

/*
 * ── 4. Τα migrations έχουν τρέξει ───────────────────────────────────────
 *
 * ΔΕΝ το ελέγχει το /health, και είναι ακριβώς το είδος αποτυχίας που
 * θέλουμε να πιάσουμε: κώδικας που περιμένει στήλη η οποία δεν
 * δημιουργήθηκε. Η σελίδα φορτώνει, η βάση απαντά, και το προφίλ σκάει
 * στον πρώτο χρήστη.
 */
try {
    $pdo = Database::getInstance()->getConnection();
    $files = glob(ROOT_DIR . '/database/migrations/auto/*.php') ?: [];
    $ran = $pdo->query('SELECT COUNT(*) FROM dj_schema_migrations')->fetchColumn();
    $checks['migrations'] = (int) $ran >= count($files);
    $migrationInfo = (int) $ran . '/' . count($files);
} catch (\Throwable $e) {
    $checks['migrations'] = false;
    $migrationInfo = 'ο πίνακας μητρώου δεν βρέθηκε';
}

// ── Αποτέλεσμα ──────────────────────────────────────────────────────────
$healthy = !in_array(false, $checks, true);

echo $healthy ? "✓ ΥΓΙΗΣ\n" : "✗ ΠΡΟΒΛΗΜΑ\n";
foreach ($checks as $name => $ok) {
    $extra = '';
    if ($name === 'migrations') {
        $extra = '  (' . ($migrationInfo ?? '') . ')';
    }
    if ($name === 'database' && !$ok) {
        $extra = '  ' . ($dbError ?? '');
    }
    printf("  %s %s%s\n", $ok ? '✓' : '✗', $name, $extra);
}

exit($healthy ? 0 : 1);
