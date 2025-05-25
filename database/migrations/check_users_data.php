<?php

/**
 * Script για έλεγχο των δεδομένων χρηστών
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    echo "=== ΕΛΕΓΧΟΣ ΠΙΝΑΚΑ DRIVERS ===\n\n";

    // Έλεγχος drivers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM drivers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Σύνολο οδηγών: {$result['total']}\n\n";

    if ($result['total'] > 0) {
        echo "Πρώτοι 5 οδηγοί:\n";
        $stmt = $pdo->query("SELECT id, email, first_name, last_name, phone, is_verified, created_at FROM drivers LIMIT 5");
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($drivers as $driver) {
            echo "ID: {$driver['id']}, Email: {$driver['email']}, Όνομα: {$driver['first_name']} {$driver['last_name']}, ";
            echo "Τηλ: {$driver['phone']}, Επαληθευμένος: " . ($driver['is_verified'] ? 'ΝΑΙ' : 'ΟΧΙ') . ", ";
            echo "Εγγραφή: {$driver['created_at']}\n";
        }
    }

    echo "\n\n=== ΕΛΕΓΧΟΣ ΠΙΝΑΚΑ COMPANIES ===\n\n";

    // Έλεγχος companies
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM companies");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Σύνολο εταιρειών: {$result['total']}\n\n";

    if ($result['total'] > 0) {
        echo "Πρώτες 5 εταιρείες:\n";
        $stmt = $pdo->query("SELECT id, email, company_name, phone, is_verified, created_at FROM companies LIMIT 5");
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($companies as $company) {
            echo "ID: {$company['id']}, Email: {$company['email']}, Όνομα: {$company['company_name']}, ";
            echo "Τηλ: {$company['phone']}, Επαληθευμένη: " . ($company['is_verified'] ? 'ΝΑΙ' : 'ΟΧΙ') . ", ";
            echo "Εγγραφή: {$company['created_at']}\n";
        }
    }

    echo "\n\n=== ΕΛΕΓΧΟΣ ΠΙΝΑΚΑ USERS ===\n\n";

    // Έλεγχος users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Σύνολο χρηστών στον πίνακα users: {$result['total']}\n\n";

    if ($result['total'] > 0) {
        echo "Χρήστες:\n";
        $stmt = $pdo->query("SELECT id, username, email, role, is_active FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            echo "ID: {$user['id']}, Username: {$user['username']}, Email: " . ($user['email'] ?? 'N/A') . ", ";
            echo "Role: {$user['role']}, Active: " . ($user['is_active'] ? 'ΝΑΙ' : 'ΟΧΙ') . "\n";
        }
    }

    echo "\n\n=== ΕΛΕΓΧΟΣ ΔΟΜΗΣ ΠΙΝΑΚΩΝ ===\n\n";

    // Έλεγχος δομής πίνακα drivers
    echo "Στήλες πίνακα drivers:\n";
    $stmt = $pdo->query("DESCRIBE drivers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n";
} catch (PDOException $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}
