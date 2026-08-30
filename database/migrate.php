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
 * schema_migrations και δεν ξανατρέχει. Επιπλέον, κάθε migration
 * οφείλει να είναι ΚΑΙ idempotent (IF NOT EXISTS / έλεγχος στήλης) —
 * διπλή ασφάλεια: αν σβηστεί το μητρώο, το ξανατρέξιμο δεν χαλά τίποτα.
 *
 * Κάθε migration εκτελείται ως ΞΕΧΩΡΙΣΤΟ process: τα υπάρχοντα scripts
 * κάνουν exit() — μέσα σε include θα σκότωναν τον runner.
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

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
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

$ran = $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$ran = array_flip($ran);

$pending = array_values(array_filter($files, function ($f) use ($ran) {
    return !isset($ran[basename($f)]);
}));

if (!$pending) {
    echo "OK: κανένα νέο migration.\n";
    exit(0);
}

foreach ($pending as $file) {
    $name = basename($file);
    echo "Εκτέλεση: $name\n";

    // Ξεχωριστό process — τα migrations κάνουν exit() και require bootstrap.
    $output = [];
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file) . ' 2>&1';
    exec($cmd, $output, $code);
    echo '  ' . implode("\n  ", $output) . "\n";

    if ($code !== 0) {
        echo "ΑΠΟΤΥΧΙΑ: το $name επέστρεψε exit code $code — το deploy σταματά.\n";
        exit(1);
    }

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
    $stmt->execute([$name]);
}

echo 'OK: εκτελέστηκαν ' . count($pending) . " migration(s).\n";
exit(0);
