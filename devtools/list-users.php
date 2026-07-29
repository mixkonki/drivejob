<?php

/**
 * Script για εμφάνιση όλων των χρηστών στη βάση
 */

require_once __DIR__ . '/src/bootstrap.php';

try {
    $pdo = require __DIR__ . '/config/database.php';

    echo "=== Λίστα Χρηστών στη Βάση Δεδομένων ===\n\n";

    // Drivers
    echo "--- ΟΔΗΓΟΙ (Drivers) ---\n";
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, is_verified, created_at FROM drivers ORDER BY id LIMIT 10");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($drivers)) {
        echo "Δεν βρέθηκαν οδηγοί\n\n";
    } else {
        foreach ($drivers as $driver) {
            echo "ID: {$driver['id']}\n";
            echo "Email: {$driver['email']}\n";
            echo "Όνομα: {$driver['first_name']} {$driver['last_name']}\n";
            echo "Verified: " . ($driver['is_verified'] ? 'ΝΑΙ' : 'ΟΧΙ') . "\n";
            echo "Created: {$driver['created_at']}\n";
            echo "---\n";
        }
        echo "\n";
    }

    // Companies
    echo "--- ΕΤΑΙΡΕΙΕΣ (Companies) ---\n";
    $stmt = $pdo->query("SELECT id, email, company_name, is_verified, created_at FROM companies ORDER BY id LIMIT 10");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($companies)) {
        echo "Δεν βρέθηκαν εταιρείες\n\n";
    } else {
        foreach ($companies as $company) {
            echo "ID: {$company['id']}\n";
            echo "Email: {$company['email']}\n";
            echo "Όνομα: {$company['company_name']}\n";
            echo "Verified: " . ($company['is_verified'] ? 'ΝΑΙ' : 'ΟΧΙ') . "\n";
            echo "Created: {$company['created_at']}\n";
            echo "---\n";
        }
        echo "\n";
    }

    // Admins (από τον πίνακα users)
    echo "--- ΔΙΑΧΕΙΡΙΣΤΕΣ (Admins) ---\n";
    $stmt = $pdo->query("SELECT id, email, username, role, created_at FROM users WHERE role = 'admin' ORDER BY id LIMIT 10");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($admins)) {
        echo "Δεν βρέθηκαν διαχειριστές\n\n";
    } else {
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']}\n";
            echo "Email: {$admin['email']}\n";
            echo "Username: {$admin['username']}\n";
            echo "Role: {$admin['role']}\n";
            echo "Created: {$admin['created_at']}\n";
            echo "---\n";
        }
        echo "\n";
    }

    echo "=== Σύνολο ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM drivers");
    echo "Οδηγοί: " . $stmt->fetchColumn() . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM companies");
    echo "Εταιρείες: " . $stmt->fetchColumn() . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    echo "Διαχειριστές: " . $stmt->fetchColumn() . "\n";

    echo "\n=== Οδηγίες ===\n";
    echo "Για να δοκιμάσετε το login, χρησιμοποιήστε:\n";
    echo "php test-actual-login.php\n\n";
    echo "Και εισάγετε ένα από τα παραπάνω emails.\n";
    echo "Αν δεν ξέρετε το password, μπορείτε να το επαναφέρετε από τη σελίδα login.\n";
} catch (Exception $e) {
    echo "ΣΦΑΛΜΑ: " . $e->getMessage() . "\n";
}
