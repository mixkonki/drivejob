<?php

/**
 * Migration: create_admin_user
 * 
 * Δημιουργία admin χρήστη για το σύστημα
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    // Έλεγχος αν υπάρχει ήδη admin χρήστης
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo "Υπάρχει ήδη admin χρήστης στο σύστημα.\n";

        // Εμφάνιση των υπαρχόντων admin χρηστών
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\nΥπάρχοντες admin χρήστες:\n";
        foreach ($admins as $admin) {
            echo "- ID: {$admin['id']}, Username: {$admin['username']}, Email: {$admin['email']}\n";
        }
    } else {
        // Δημιουργία νέου admin χρήστη
        $username = 'admin';
        $email = 'admin@drivejob.gr';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $role = 'admin';

        $sql = "INSERT INTO users (username, email, password, role, created_at) 
                VALUES (:username, :email, :password, :role, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role
        ]);

        echo "Δημιουργήθηκε νέος admin χρήστης επιτυχώς!\n";
        echo "\nΣτοιχεία σύνδεσης:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "\nΠΑΡΑΚΑΛΩ ΑΛΛΑΞΤΕ ΤΟΝ ΚΩΔΙΚΟ ΜΕΤΑ ΤΗΝ ΠΡΩΤΗ ΣΥΝΔΕΣΗ!\n";
    }
} catch (PDOException $e) {
    // Αν ο πίνακας users δεν υπάρχει, δημιουργούμε τον
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "Ο πίνακας users δεν υπάρχει. Δημιουργία πίνακα...\n";

        $createTableSql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME,
            login_attempts INT DEFAULT 0,
            locked_until DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($createTableSql);
        echo "Πίνακας users δημιουργήθηκε επιτυχώς.\n";

        // Προσπάθεια δημιουργίας admin ξανά
        $username = 'admin';
        $email = 'admin@drivejob.gr';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $role = 'admin';

        $sql = "INSERT INTO users (username, email, password, role, created_at) 
                VALUES (:username, :email, :password, :role, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role
        ]);

        echo "\nΔημιουργήθηκε νέος admin χρήστης επιτυχώς!\n";
        echo "\nΣτοιχεία σύνδεσης:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "\nΠΑΡΑΚΑΛΩ ΑΛΛΑΞΤΕ ΤΟΝ ΚΩΔΙΚΟ ΜΕΤΑ ΤΗΝ ΠΡΩΤΗ ΣΥΝΔΕΣΗ!\n";
    } else {
        die("Σφάλμα: " . $e->getMessage() . "\n");
    }
}
