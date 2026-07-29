<?php

/**
 * Script για καθαρισμό sessions και δοκιμή του login system
 */

// Καθαρισμός όλων των session files
$sessionPath = session_save_path();
if (empty($sessionPath)) {
    $sessionPath = sys_get_temp_dir();
}

echo "=== Καθαρισμός Session Files ===\n";
echo "Session Path: $sessionPath\n\n";

$files = glob($sessionPath . '/sess_*');
$count = 0;
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
        $count++;
    }
}
echo "Διαγράφηκαν $count session files\n\n";

// Καθαρισμός database sessions αν υπάρχουν
require_once __DIR__ . '/src/bootstrap.php';

try {
    $pdo = require __DIR__ . '/config/database.php';

    echo "=== Καθαρισμός Database Sessions ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
    $beforeCount = $stmt->fetchColumn();
    echo "Sessions στη βάση πριν: $beforeCount\n";

    $pdo->exec("DELETE FROM sessions");
    echo "Όλα τα sessions διαγράφηκαν από τη βάση\n\n";
} catch (Exception $e) {
    echo "Σφάλμα κατά τον καθαρισμό database sessions: " . $e->getMessage() . "\n\n";
}

echo "=== Οδηγίες για Δοκιμή ===\n";
echo "1. Ανοίξτε τον browser σας\n";
echo "2. Πατήστε Ctrl+Shift+Delete για να καθαρίσετε τα cookies\n";
echo "3. Ή χρησιμοποιήστε Incognito/Private mode\n";
echo "4. Πηγαίνετε στο: http://localhost/drivejob/public/login.php\n";
echo "5. Συνδεθείτε με τα στοιχεία σας\n\n";

echo "=== Έλεγχος Ρυθμίσεων ===\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie Params:\n";
$params = session_get_cookie_params();
print_r($params);

echo "\n=== Ολοκληρώθηκε ===\n";
