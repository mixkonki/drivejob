<?php
require 'src/bootstrap.php';
$pdo = require 'config/database.php';

echo "=== Checking Failing Users ===\n\n";

// Check admin
echo "--- ADMIN ---\n";
$stmt = $pdo->prepare('SELECT id, email, username, password, role, is_active FROM users WHERE email = ?');
$stmt->execute(['admin@drivejob.gr']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "✅ Admin found!\n";
    echo "ID: {$admin['id']}\n";
    echo "Email: {$admin['email']}\n";
    echo "Username: {$admin['username']}\n";
    echo "Role: {$admin['role']}\n";
    echo "Is Active: {$admin['is_active']}\n";
    echo "Password hash: " . substr($admin['password'], 0, 20) . "...\n";

    // Test common passwords
    $testPasswords = ['admin123', 'admin', 'password', '123456'];
    foreach ($testPasswords as $testPass) {
        $result = password_verify($testPass, $admin['password']);
        echo "Password '$testPass': " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "\n";
    }
} else {
    echo "❌ Admin NOT found\n";
}

echo "\n--- DRIVER (hotmail) ---\n";
$stmt = $pdo->prepare('SELECT id, email, first_name, last_name, password, is_verified, is_active FROM drivers WHERE email = ?');
$stmt->execute(['kostas.michailidis@hotmail.gr']);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if ($driver) {
    echo "✅ Driver found!\n";
    echo "ID: {$driver['id']}\n";
    echo "Email: {$driver['email']}\n";
    echo "Name: {$driver['first_name']} {$driver['last_name']}\n";
    echo "Is Verified: {$driver['is_verified']}\n";
    echo "Is Active: {$driver['is_active']}\n";
    echo "Password hash: " . substr($driver['password'], 0, 20) . "...\n";

    // Test common passwords
    $testPasswords = ['123456', 'password', 'hotmail', 'kostas'];
    foreach ($testPasswords as $testPass) {
        $result = password_verify($testPass, $driver['password']);
        echo "Password '$testPass': " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "\n";
    }
} else {
    echo "❌ Driver NOT found\n";
}

echo "\n=== Σημείωση ===\n";
echo "Αν δεν ξέρετε τα passwords, μπορείτε να τα επαναφέρετε από τη σελίδα login.\n";
