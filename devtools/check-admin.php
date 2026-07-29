<?php
require 'src/bootstrap.php';
$pdo = require 'config/database.php';

echo "=== Checking Admin User ===\n\n";

$stmt = $pdo->prepare('SELECT id, email, username, password, role FROM users WHERE email = ?');
$stmt->execute(['admin@drivejob.gr']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "✅ Admin found!\n";
    echo "ID: {$admin['id']}\n";
    echo "Email: {$admin['email']}\n";
    echo "Username: {$admin['username']}\n";
    echo "Role: {$admin['role']}\n";
    echo "Password hash length: " . strlen($admin['password']) . "\n\n";

    // Test password
    $testPassword = 'admin123';
    echo "Testing password: $testPassword\n";
    $result = password_verify($testPassword, $admin['password']);
    echo "Result: " . ($result ? "MATCH" : "NO MATCH") . "\n";
} else {
    echo "❌ Admin NOT found\n";
}
