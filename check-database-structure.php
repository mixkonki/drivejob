<?php

/**
 * Script για έλεγχο της δομής της βάσης δεδομένων
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    $pdo = require __DIR__ . '/config/database.php';

    echo "=== Έλεγχος Δομής Βάσης Δεδομένων ===\n\n";

    // Λίστα πινάκων
    echo "--- ΠΙΝΑΚΕΣ ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "✓ $table\n";
    }
    echo "\nΣύνολο πινάκων: " . count($tables) . "\n\n";

    // Έλεγχος πίνακα sessions
    echo "--- ΠΙΝΑΚΑΣ SESSIONS ---\n";
    if (in_array('sessions', $tables)) {
        echo "✅ Ο πίνακας sessions ΥΠΑΡΧΕΙ\n\n";

        // Δομή πίνακα sessions
        echo "Δομή πίνακα sessions:\n";
        $stmt = $pdo->query("DESCRIBE sessions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }

        // Αριθμός sessions
        $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
        $count = $stmt->fetchColumn();
        echo "\nΑριθμός sessions στη βάση: $count\n\n";

        if ($count > 0) {
            echo "Τελευταία 5 sessions:\n";
            $stmt = $pdo->query("SELECT id, user_id, ip_address, last_activity FROM sessions ORDER BY last_activity DESC LIMIT 5");
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($sessions as $session) {
                echo "  ID: {$session['id']}, User: {$session['user_id']}, IP: {$session['ip_address']}, Last: {$session['last_activity']}\n";
            }
        }
    } else {
        echo "❌ Ο πίνακας sessions ΔΕΝ ΥΠΑΡΧΕΙ\n";
        echo "Τα sessions αποθηκεύονται στο filesystem.\n\n";
    }

    // Έλεγχος πίνακα drivers
    echo "\n--- ΠΙΝΑΚΑΣ DRIVERS ---\n";
    $stmt = $pdo->query("DESCRIBE drivers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Στήλες:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    // Έλεγχος για password field
    $hasPassword = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'password') {
            $hasPassword = true;
            break;
        }
    }

    if ($hasPassword) {
        echo "\n✅ Η στήλη 'password' ΥΠΑΡΧΕΙ στον πίνακα drivers\n";

        // Έλεγχος αν τα passwords είναι hashed
        $stmt = $pdo->query("SELECT id, email, password FROM drivers LIMIT 1");
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($driver) {
            $passwordLength = strlen($driver['password']);
            echo "Μήκος password: $passwordLength χαρακτήρες\n";
            if ($passwordLength >= 60) {
                echo "✅ Τα passwords φαίνονται να είναι hashed (bcrypt)\n";
            } else {
                echo "⚠️ Τα passwords μπορεί να ΜΗΝ είναι hashed!\n";
            }
        }
    } else {
        echo "\n❌ Η στήλη 'password' ΔΕΝ ΥΠΑΡΧΕΙ στον πίνακα drivers!\n";
    }

    // Έλεγχος πίνακα companies
    echo "\n--- ΠΙΝΑΚΑΣ COMPANIES ---\n";
    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Στήλες:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    // Έλεγχος πίνακα users (για admins)
    echo "\n--- ΠΙΝΑΚΑΣ USERS (Admins) ---\n";
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Στήλες:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "❌ Ο πίνακας users ΔΕΝ ΥΠΑΡΧΕΙ\n";
    }

    // Έλεγχος bootstrap.php για USE_DB_SESSIONS
    echo "\n--- ΡΥΘΜΙΣΕΙΣ SESSIONS ---\n";
    $bootstrapContent = file_get_contents(__DIR__ . '/src/bootstrap.php');
    if (strpos($bootstrapContent, 'USE_DB_SESSIONS') !== false) {
        echo "✓ Βρέθηκε ρύθμιση USE_DB_SESSIONS στο bootstrap.php\n";
        if (defined('USE_DB_SESSIONS')) {
            echo "USE_DB_SESSIONS = " . (USE_DB_SESSIONS ? 'TRUE' : 'FALSE') . "\n";
        } else {
            echo "USE_DB_SESSIONS δεν είναι defined\n";
        }
    } else {
        echo "⚠️ Δεν βρέθηκε ρύθμιση USE_DB_SESSIONS\n";
    }

    echo "\n=== Συμπεράσματα ===\n";
    echo "1. Sessions αποθηκεύονται: " . (in_array('sessions', $tables) ? "Βάση Δεδομένων" : "Filesystem") . "\n";
    echo "2. Πίνακες χρηστών: drivers, companies" . (in_array('users', $tables) ? ", users" : "") . "\n";
    echo "3. Σύνολο χρηστών: ";

    $stmt = $pdo->query("SELECT COUNT(*) FROM drivers");
    $driversCount = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM companies");
    $companiesCount = $stmt->fetchColumn();
    echo "$driversCount drivers + $companiesCount companies";

    if (in_array('users', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminsCount = $stmt->fetchColumn();
        echo " + $adminsCount admins";
    }
    echo "\n";
} catch (Exception $e) {
    echo "ΣΦΑΛΜΑ: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
