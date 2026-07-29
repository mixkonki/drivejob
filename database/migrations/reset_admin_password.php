<?php

/**
 * Migration: reset_admin_password
 * 
 * Επαναφορά κωδικού πρόσβασης για τον admin χρήστη
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    // Εύρεση του admin χρήστη
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Επαναφορά του κωδικού
        $newPassword = 'admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = :password, login_attempts = 0, locked_until = NULL WHERE id = :id");
        $stmt->execute([
            'password' => $hashedPassword,
            'id' => $admin['id']
        ]);

        // Ενημέρωση του email αν χρειάζεται
        $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id AND (email IS NULL OR email = '')");
        $stmt->execute([
            'email' => $admin['username'],
            'id' => $admin['id']
        ]);

        echo "Ο κωδικός πρόσβασης επαναφέρθηκε επιτυχώς!\n\n";
        echo "Στοιχεία σύνδεσης:\n";
        echo "Username: {$admin['username']}\n";
        echo "Password: {$newPassword}\n";
        echo "\nΠΑΡΑΚΑΛΩ ΑΛΛΑΞΤΕ ΤΟΝ ΚΩΔΙΚΟ ΜΕΤΑ ΤΗΝ ΠΡΩΤΗ ΣΥΝΔΕΣΗ!\n";
        echo "\nURL σύνδεσης: http://localhost/drivejob/public/auth/login\n";
    } else {
        echo "Δεν βρέθηκε admin χρήστης στο σύστημα.\n";
    }
} catch (PDOException $e) {
    die("Σφάλμα: " . $e->getMessage() . "\n");
}
