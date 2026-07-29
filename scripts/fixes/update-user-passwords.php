<?php

/**
 * Ενημέρωση κωδικών χρηστών με τα σωστά διαπιστευτήρια
 */

require_once __DIR__ . '/../../config/database.php';

echo "\n=== ΕΝΗΜΕΡΩΣΗ ΚΩΔΙΚΩΝ ΧΡΗΣΤΩΝ ===\n\n";

// Οι σωστοί κωδικοί που δόθηκαν
$users = [
    'info@thessdrive.gr' => '123456',
    'kostas.michailidis@hotmail.gr' => '123456',
    'admin@drivejob.gr' => 'admin123',
    'kostas.michailidis1@gmail.com' => 'gma3e4r#E$R'
];

foreach ($users as $email => $password) {
    echo "• Ενημέρωση κωδικού για $email... ";

    try {
        // Δημιουργία hash για τον κωδικό
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Ενημέρωση στη βάση
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        if ($stmt->rowCount() > 0) {
            echo "✓\n";

            // Επιβεβαίωση ότι ο κωδικός αποθηκεύτηκε σωστά
            $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                echo "  Επιβεβαίωση: Ο κωδικός επαληθεύεται σωστά ✓\n";
            } else {
                echo "  ΠΡΟΣΟΧΗ: Πρόβλημα με την επαλήθευση του κωδικού!\n";
            }
        } else {
            echo "✗ (δεν βρέθηκε ο χρήστης)\n";

            // Αν δεν υπάρχει ο χρήστης kostas.michailidis@hotmail.gr, ας τον δημιουργήσουμε
            if ($email === 'kostas.michailidis@hotmail.gr') {
                echo "  Δημιουργία νέου χρήστη... ";
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, is_active, created_at) 
                    VALUES (?, ?, ?, 'driver', 1, NOW())
                ");
                $stmt->execute(['kostas_hotmail', $email, $hashedPassword]);
                echo "✓\n";
            }
        }
    } catch (PDOException $e) {
        echo "✗ Σφάλμα: " . $e->getMessage() . "\n";
    }
}

// Έλεγχος όλων των χρηστών στη βάση
echo "\n=== ΚΑΤΑΣΤΑΣΗ ΧΡΗΣΤΩΝ ===\n\n";

$stmt = $pdo->query("
    SELECT id, username, email, role, is_active, 
           LENGTH(password) as pwd_len,
           created_at
    FROM users 
    ORDER BY id
");

$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Σύνολο χρηστών: " . count($allUsers) . "\n\n";

foreach ($allUsers as $user) {
    echo "ID: {$user['id']}\n";
    echo "  Username: " . ($user['username'] ?: '(κενό)') . "\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: " . ($user['role'] ?: '(κενό)') . "\n";
    echo "  Active: " . ($user['is_active'] ? 'Ναι' : 'Όχι') . "\n";
    echo "  Password Hash Length: {$user['pwd_len']}\n";
    echo "  Created: {$user['created_at']}\n";
    echo "  ---\n";
}

echo "\n=== ΔΙΑΠΙΣΤΕΥΤΗΡΙΑ ΓΙΑ ΔΟΚΙΜΗ ===\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Admin:\n";
echo "  Email: admin@drivejob.gr\n";
echo "  Password: admin123\n\n";

echo "Company:\n";
echo "  Email: info@thessdrive.gr\n";
echo "  Password: 123456\n\n";

echo "Drivers:\n";
echo "  Email: kostas.michailidis@hotmail.gr\n";
echo "  Password: 123456\n\n";
echo "  Email: kostas.michailidis1@gmail.com\n";
echo "  Password: gma3e4r#E\$R\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "URL Σύνδεσης: http://localhost:8000/login.php\n";
