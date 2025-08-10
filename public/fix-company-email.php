<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Fix Company Email</h2>";

// Update Thessdrive IKE email
$stmt = $pdo->prepare("UPDATE companies SET email = ? WHERE id = ? AND company_name = ?");
$result = $stmt->execute(['info@thessdrive.gr', 2, 'Thessdrive IKE']);

if ($result) {
    echo "<p>✅ Updated Thessdrive IKE email to info@thessdrive.gr</p>";
} else {
    echo "<p>❌ Failed to update email</p>";
}

// Also ensure it exists in users table
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['info@thessdrive.gr']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Create user record
    $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, role, is_active, created_at) 
        VALUES (?, ?, 'company', 1, NOW())
    ");
    $result = $stmt->execute(['info@thessdrive.gr', $hashedPassword]);

    if ($result) {
        echo "<p>✅ Created user record for info@thessdrive.gr</p>";
    } else {
        echo "<p>❌ Failed to create user record</p>";
    }
} else {
    echo "<p>ℹ️ User record already exists</p>";

    // Update password to ensure it's correct
    $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, 'info@thessdrive.gr']);
    echo "<p>✅ Updated password for info@thessdrive.gr</p>";
}

// Verify the fix
echo "<h3>Verification:</h3>";
$stmt = $pdo->prepare("SELECT * FROM companies WHERE email = ?");
$stmt->execute(['info@thessdrive.gr']);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if ($company) {
    echo "<p>✅ Company found with email: " . $company['email'] . "</p>";
    echo "<p>Company Name: " . $company['company_name'] . "</p>";
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['info@thessdrive.gr']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify('123456', $user['password'])) {
    echo "<p>✅ User login verified successfully</p>";
} else {
    echo "<p>❌ User login verification failed</p>";
}

echo "<p><a href='/drivejob/public/login.php'>Go to Login</a></p>";
