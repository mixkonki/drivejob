<?php

/**
 * Test password verification
 */

require_once __DIR__ . '/src/bootstrap.php';

$email = 'kostas.michailidis1@gmail.com';
$testPassword = 'gma3e4r#E$R';

try {
    $pdo = require __DIR__ . '/config/database.php';

    echo "=== Password Verification Test ===\n\n";
    echo "Email: $email\n";
    echo "Test Password: $testPassword\n\n";

    // Βρες τον χρήστη
    $stmt = $pdo->prepare('SELECT id, email, password FROM drivers WHERE email = ?');
    $stmt->execute([$email]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        echo "✅ Driver found!\n";
        echo "ID: {$driver['id']}\n";
        echo "Email: {$driver['email']}\n";
        echo "Password hash: " . substr($driver['password'], 0, 30) . "...\n";
        echo "Hash length: " . strlen($driver['password']) . " characters\n\n";

        // Δοκίμασε το password
        echo "Testing password verification...\n";
        $result = password_verify($testPassword, $driver['password']);

        if ($result) {
            echo "✅ PASSWORD MATCH! Authentication should work.\n";
        } else {
            echo "❌ PASSWORD DOES NOT MATCH!\n";
            echo "The password you entered is incorrect.\n";
            echo "Please check your password or reset it.\n";
        }
    } else {
        echo "❌ Driver NOT found with email: $email\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
