<?php
require_once __DIR__ . '/src/bootstrap.php';

$pdo = require __DIR__ . '/config/database.php';

echo "=== Fixing User Passwords ===\n\n";

// Fix driver password
$email = 'kostas.michailidis1@gmail.com';
$newPassword = '123456';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

echo "Updating password for driver: $email\n";
$stmt = $pdo->prepare("UPDATE drivers SET password = ? WHERE email = ?");
$result = $stmt->execute([$hashedPassword, $email]);

if ($result) {
    echo "✅ Password updated successfully!\n";

    // Verify the update
    $stmt = $pdo->prepare("SELECT password FROM drivers WHERE email = ?");
    $stmt->execute([$email]);
    $pwd = $stmt->fetch(PDO::FETCH_ASSOC);

    $verify = password_verify($newPassword, $pwd['password']);
    echo "Verification: " . ($verify ? "✅ SUCCESS" : "❌ FAILED") . "\n";
} else {
    echo "❌ Failed to update password\n";
}

echo "\n";

// Check company password (should be working)
$companyEmail = 'info@thessdrive.gr';
echo "Checking company password: $companyEmail\n";
$stmt = $pdo->prepare("SELECT password FROM companies WHERE email = ?");
$stmt->execute([$companyEmail]);
$pwd = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pwd) {
    $verify = password_verify('123456', $pwd['password']);
    echo "Password '123456' verification: " . ($verify ? "✅ CORRECT" : "❌ WRONG") . "\n";
} else {
    echo "❌ Company not found\n";
}

echo "\n=== Done ===\n";
