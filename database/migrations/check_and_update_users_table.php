<?php

/**
 * Migration: check_and_update_users_table
 * 
 * Έλεγχος και ενημέρωση του πίνακα users
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    // Έλεγχος της δομής του πίνακα users
    echo "Έλεγχος δομής πίνακα users...\n";

    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nΥπάρχουσες στήλες:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }

    // Έλεγχος αν υπάρχει η στήλη email
    $hasEmail = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'email') {
            $hasEmail = true;
            break;
        }
    }

    if (!$hasEmail) {
        echo "\nΗ στήλη email δεν υπάρχει. Προσθήκη στήλης...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username");
        echo "Η στήλη email προστέθηκε επιτυχώς.\n";
    }

    // Έλεγχος για άλλες απαραίτητες στήλες
    $requiredColumns = [
        'role' => "VARCHAR(20) NOT NULL DEFAULT 'user'",
        'is_active' => "TINYINT(1) DEFAULT 1",
        'last_login' => "DATETIME",
        'login_attempts' => "INT DEFAULT 0",
        'locked_until' => "DATETIME"
    ];

    foreach ($requiredColumns as $columnName => $columnDef) {
        $hasColumn = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $columnName) {
                $hasColumn = true;
                break;
            }
        }

        if (!$hasColumn) {
            echo "\nΗ στήλη {$columnName} δεν υπάρχει. Προσθήκη στήλης...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN {$columnName} {$columnDef}");
            echo "Η στήλη {$columnName} προστέθηκε επιτυχώς.\n";
        }
    }

    // Εμφάνιση των admin χρηστών
    echo "\n\nΈλεγχος για admin χρήστες...\n";
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($admins) > 0) {
        echo "\nΥπάρχοντες admin χρήστες:\n";
        foreach ($admins as $admin) {
            echo "- ID: {$admin['id']}, Username: {$admin['username']}, Role: {$admin['role']}\n";
        }

        echo "\nΓια να συνδεθείτε, χρησιμοποιήστε το username και τον κωδικό που έχετε ήδη.\n";
    } else {
        echo "\nΔεν βρέθηκαν admin χρήστες.\n";
        echo "Εκτελέστε το script create_admin_user.php για να δημιουργήσετε έναν.\n";
    }
} catch (PDOException $e) {
    die("Σφάλμα: " . $e->getMessage() . "\n");
}
