<?php

/**
 * Script για καθαρισμό όλων των sessions
 * Καθαρίζει τόσο τα αρχεία session όσο και τη βάση δεδομένων
 */

require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Session;

echo "=== Καθαρισμός όλων των Sessions ===\n\n";

// 1. Καθαρισμός sessions από τη βάση δεδομένων (αν χρησιμοποιείται)
if (defined('USE_DB_SESSIONS') && USE_DB_SESSIONS) {
    try {
        $pdo = $container->get('pdo');
        $stmt = $pdo->prepare("TRUNCATE TABLE sessions");
        $stmt->execute();
        echo "✓ Καθαρίστηκαν τα sessions από τη βάση δεδομένων\n";
    } catch (Exception $e) {
        echo "✗ Σφάλμα κατά τον καθαρισμό της βάσης: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ Δεν χρησιμοποιούνται database sessions\n";
}

// 2. Καθαρισμός αρχείων session
$sessionPath = session_save_path();
if (empty($sessionPath)) {
    $sessionPath = sys_get_temp_dir();
}

echo "\nSession save path: $sessionPath\n";

if (is_dir($sessionPath)) {
    $sessionFiles = glob($sessionPath . '/sess_*');
    $count = 0;

    foreach ($sessionFiles as $file) {
        if (is_file($file)) {
            if (@unlink($file)) {
                $count++;
            }
        }
    }

    echo "✓ Διαγράφηκαν $count αρχεία session\n";
} else {
    echo "✗ Δεν βρέθηκε ο φάκελος sessions\n";
}

// 3. Καθαρισμός του τρέχοντος session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
    echo "✓ Καθαρίστηκε το τρέχον session\n";
}

// 4. Καθαρισμός cookies
if (!headers_sent()) {
    // Διαγραφή όλων των session cookies
    $cookieParams = session_get_cookie_params();

    // Διαγραφή με διάφορα ονόματα και paths
    $cookieNames = ['PHPSESSID', 'DRIVEJOBSESSION', session_name()];
    $paths = ['/', '/drivejob/', '/drivejob/public/'];

    foreach ($cookieNames as $name) {
        foreach ($paths as $path) {
            setcookie($name, '', 1, $path);
        }
    }

    echo "✓ Καθαρίστηκαν τα session cookies\n";
} else {
    echo "ℹ Τα headers έχουν ήδη σταλεί, δεν μπορούν να διαγραφούν τα cookies\n";
}

// 5. Έλεγχος για sessions στον φάκελο storage/sessions
$storagePath = __DIR__ . '/storage/sessions';
if (is_dir($storagePath)) {
    $storageFiles = glob($storagePath . '/*');
    $count = 0;

    foreach ($storageFiles as $file) {
        if (is_file($file) && $file !== $storagePath . '/.gitkeep') {
            if (@unlink($file)) {
                $count++;
            }
        }
    }

    if ($count > 0) {
        echo "✓ Διαγράφηκαν $count αρχεία από storage/sessions\n";
    }
}

echo "\n=== Ολοκληρώθηκε ο καθαρισμός ===\n";
echo "\nΜπορείτε τώρα να δοκιμάσετε τη σύνδεση στο:\n";
echo "http://localhost/drivejob/public/login.php\n";
