<?php

/**
 * Migration για προσθήκη του ρόλου admin στους χρήστες
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = require __DIR__ . '/../../config/database.php';

    echo "Έναρξη migration για προσθήκη admin role...\n";

    // Έλεγχος αν υπάρχει ήδη admin στον πίνακα users
    $checkSql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $stmt = $pdo->query($checkSql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        // Δημιουργία admin χρήστη
        $insertSql = "INSERT INTO users (username, password, role, created_at) 
                      VALUES (:username, :password, :role, NOW())";

        $stmt = $pdo->prepare($insertSql);

        // Hash του κωδικού admin123
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt->execute([
            ':username' => 'admin@drivejob.gr',
            ':password' => $hashedPassword,
            ':role' => 'admin'
        ]);

        echo "✓ Admin χρήστης δημιουργήθηκε επιτυχώς!\n";
        echo "  Username: admin@drivejob.gr\n";
        echo "  Password: admin123\n";
    } else {
        echo "✓ Υπάρχει ήδη admin χρήστης στη βάση.\n";
    }

    // Έλεγχος αν το πεδίο role υποστηρίζει την τιμή 'admin'
    $checkEnumSql = "SHOW COLUMNS FROM users WHERE Field = 'role'";
    $stmt = $pdo->query($checkEnumSql);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        $type = $column['Type'];
        if (strpos($type, 'admin') === false) {
            // Ενημέρωση του ENUM για να συμπεριλάβει το 'admin'
            $alterSql = "ALTER TABLE users MODIFY COLUMN role ENUM('driver', 'company', 'admin') NOT NULL DEFAULT 'driver'";
            $pdo->exec($alterSql);
            echo "✓ Το πεδίο role ενημερώθηκε για να υποστηρίζει admin.\n";
        }
    }

    echo "\nΤο migration ολοκληρώθηκε επιτυχώς!\n";
} catch (PDOException $e) {
    echo "Σφάλμα κατά το migration: " . $e->getMessage() . "\n";
    exit(1);
}
