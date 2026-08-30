<?php

/**
 * Runner αυτόματων migrations — τρέχει στο τέλος κάθε deploy (25/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΠΩΣ ΔΟΥΛΕΥΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Αυτόματα τρέχουν ΜΟΝΟ τα αρχεία του database/migrations/auto/, με
 * αλφαβητική σειρά (γι' αυτό η ονομασία τους ξεκινά με ημερομηνία:
 * 2026_08_25_add_issue_date...). Ο ριζικός φάκελος migrations/ έχει
 * ιστορικά και βοηθητικά scripts (check_*, reset_admin_password κλπ)
 * που ΔΕΝ επιτρέπεται να τρέχουν σε κάθε deploy — μένουν χειροκίνητα
 * μέχρι το beta-cleanup.
 *
 * Κάθε migration που ολοκληρώνεται καταγράφεται στον πίνακα
 * dj_schema_migrations και δεν ξανατρέχει. (Το πρόθεμα dj_ είναι
 * σκόπιμο — μάθημα 30/08: στην παραγωγή υπήρχε ΗΔΗ πίνακας
 * schema_migrations με άλλο σχήμα, από παλιότερη απόπειρα· το
 * CREATE TABLE IF NOT EXISTS τον άφηνε ως είχε και το SELECT filename
 * έσκαγε με «Unknown column». Δεν πειράζουμε ξένους πίνακες:
 * κρατάμε δικό μας μητρώο.) Επιπλέον, κάθε migration
 * οφείλει να είναι ΚΑΙ idempotent (IF NOT EXISTS / έλεγχος στήλης) —
 * διπλή ασφάλεια: αν σβηστεί το μητρώο, το ξανατρέξιμο δεν χαλά τίποτα.
 *
 * ΕΚΤΕΛΕΣΗ ΜΕ include, ΟΧΙ exec (μάθημα 30/08): σε shared hosting το
 * exec()/shell_exec() είναι συχνά στα disable_functions — ο runner
 * έσκαγε στην παραγωγή ενώ τοπικά δούλευε. Τα migrations γράφονται
 * χωρίς exit() (χρησιμοποιούν return) ώστε να τρέχουν με include μέσα
 * σε συνάρτηση, και τυλίγονται σε try/catch: το πρώτο που αποτυγχάνει
 * σταματά το deploy με σαφές μήνυμα.
 *
 * Exit codes: 0 = όλα καλά (ή τίποτα προς εκτέλεση), 1 = αποτυχία —
 * το GitHub Action κοκκινίζει και το deploy θεωρείται αποτυχημένο.
 *
 * Χειροκίνητο τρέξιμο: php database/migrate.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

/*
 * Το bootstrap εγκαθιστά τον web ExceptionHandler, που σε κάθε σφάλμα
 * render-άρει σελίδα 500 (HTML) — άχρηστο και παραπλανητικό σε CLI:
 * το πρώτο πρόβλημα του runner εμφανιζόταν ως… ολόκληρη homepage.
 * Εδώ επαναφέρουμε τους default handlers: τα σφάλματα τυπώνονται ωμά
 * και ο runner τερματίζει με μη μηδενικό exit code, όπως πρέπει.
 */
restore_exception_handler();
restore_error_handler();

echo 'migrate.php — PHP ' . PHP_VERSION . ' (' . PHP_SAPI . ")\n";

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "Σύνδεση στη βάση: OK\n";

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS dj_schema_migrations (
        filename VARCHAR(191) NOT NULL PRIMARY KEY,
        ran_at TIMESTAMP NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$dir = __DIR__ . '/migrations/auto';
if (!is_dir($dir)) {
    echo "OK: δεν υπάρχει φάκελος auto/ — τίποτα προς εκτέλεση.\n";
    exit(0);
}

$files = glob($dir . '/*.php');
sort($files, SORT_STRING);

$ran = $pdo->query('SELECT filename FROM dj_schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$ran = array_flip($ran);

$pending = array_values(array_filter($files, static function ($f) use ($ran) {
    return !isset($ran[basename($f)]);
}));

echo 'Βρέθηκαν ' . count($files) . ' αρχεία, ' . count($pending) . " προς εκτέλεση.\n";

if (!$pending) {
    echo "OK: κανένα νέο migration.\n";
    exit(0);
}

/**
 * Εκτελεί ένα migration σε δικό του scope. Το $pdo δίνεται ως τοπική
 * μεταβλητή — τα migrations κάνουν το δικό τους require του database.php,
 * που είναι ακίνδυνο (επιστρέφει νέα σύνδεση).
 */
$runMigration = static function (string $file): void {
    require $file;
};

foreach ($pending as $file) {
    $name = basename($file);
    echo "Εκτέλεση: $name\n";

    try {
        $runMigration($file);
    } catch (Throwable $e) {
        echo 'ΑΠΟΤΥΧΙΑ στο ' . $name . ': ' . get_class($e) . ' — ' . $e->getMessage() . "\n";
        echo '  στο ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo "Το deploy σταματά.\n";
        exit(1);
    }

    $stmt = $pdo->prepare('INSERT INTO dj_schema_migrations (filename) VALUES (?)');
    $stmt->execute([$name]);
    echo "  ✓ καταγράφηκε\n";
}

echo 'OK: εκτελέστηκαν ' . count($pending) . " migration(s).\n";
exit(0);
