<?php

/**
 * Δημιουργία χρήστη οδηγού με όλα τα απαραίτητα πεδία
 */

require_once __DIR__ . '/../../config/database.php';

echo "\n=== ΔΗΜΙΟΥΡΓΙΑ ΧΡΗΣΤΗ ΟΔΗΓΟΥ ===\n\n";

$driverEmail = 'kostas.michailidis1@gmail.com';

// Έλεγχος αν υπάρχει ήδη
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$driverEmail]);

if ($existingUser = $stmt->fetch()) {
    echo "• Ο χρήστης υπάρχει ήδη με ID: {$existingUser['id']}\n";

    // Ενημέρωση του role
    $stmt = $pdo->prepare("UPDATE users SET role = 'driver' WHERE id = ?");
    $stmt->execute([$existingUser['id']]);
    echo "• Ενημερώθηκε το role σε 'driver'\n";

    // Έλεγχος αν υπάρχει στον πίνακα drivers
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
    $stmt->execute([$existingUser['id']]);

    if (!$stmt->fetch()) {
        // Δημιουργία εγγραφής στον πίνακα drivers
        $stmt = $pdo->prepare("
            INSERT INTO drivers (user_id, first_name, last_name, phone, created_at) 
            VALUES (?, 'Κώστας', 'Μιχαηλίδης', '6900000000', NOW())
        ");
        $stmt->execute([$existingUser['id']]);
        echo "• Δημιουργήθηκε εγγραφή στον πίνακα drivers\n";
    }
} else {
    // Δημιουργία νέου χρήστη με username
    echo "• Δημιουργία νέου χρήστη οδηγού...\n";

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, is_active, email_verified, created_at) 
            VALUES (?, ?, ?, 'driver', 1, 1, NOW())
        ");

        $username = 'kostas_michailidis';
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);

        $stmt->execute([
            $username,
            $driverEmail,
            $hashedPassword
        ]);

        $userId = $pdo->lastInsertId();
        echo "• Δημιουργήθηκε χρήστης με ID: $userId\n";

        // Δημιουργία εγγραφής στον πίνακα drivers
        $stmt = $pdo->prepare("
            INSERT INTO drivers (user_id, first_name, last_name, phone, created_at) 
            VALUES (?, 'Κώστας', 'Μιχαηλίδης', '6900000000', NOW())
        ");
        $stmt->execute([$userId]);
        echo "• Δημιουργήθηκε εγγραφή στον πίνακα drivers\n";
    } catch (PDOException $e) {
        echo "✗ Σφάλμα: " . $e->getMessage() . "\n";
    }
}

// Ενημέρωση username για τους άλλους χρήστες αν χρειάζεται
echo "\n=== ΕΝΗΜΕΡΩΣΗ USERNAME ΓΙΑ ΑΛΛΟΥΣ ΧΡΗΣΤΕΣ ===\n\n";

$usersToUpdate = [
    'admin@drivejob.gr' => 'admin_drivejob',
    'info@thessdrive.gr' => 'thessdrive_company'
];

foreach ($usersToUpdate as $email => $username) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (empty($user['username']) || $user['username'] === '') {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$username, $user['id']]);
            echo "• Ενημερώθηκε username για $email: $username\n";
        } else {
            echo "• Ο χρήστης $email έχει ήδη username: {$user['username']}\n";
        }
    }
}

echo "\n=== ΟΛΟΚΛΗΡΩΣΗ ===\n\n";
echo "✓ Όλοι οι χρήστες έχουν ρυθμιστεί!\n\n";

echo "ΣΤΟΙΧΕΙΑ ΣΥΝΔΕΣΗΣ:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Admin:\n";
echo "  Email: admin@drivejob.gr\n";
echo "  Username: admin_drivejob\n";
echo "  Password: password123\n\n";

echo "Company:\n";
echo "  Email: info@thessdrive.gr\n";
echo "  Username: thessdrive_company\n";
echo "  Password: password123\n\n";

echo "Driver:\n";
echo "  Email: kostas.michailidis1@gmail.com\n";
echo "  Username: kostas_michailidis\n";
echo "  Password: password123\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
