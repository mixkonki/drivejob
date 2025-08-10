<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Check Company Login</h2>";

// Check company with email info@thessdrive.gr
$stmt = $pdo->prepare("SELECT * FROM companies WHERE email = ?");
$stmt->execute(['info@thessdrive.gr']);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if ($company) {
    echo "<h3>Company Found:</h3>";
    echo "<pre>";
    echo "ID: " . $company['id'] . "\n";
    echo "Name: " . $company['company_name'] . "\n";
    echo "Email: " . $company['email'] . "\n";
    echo "Is Active: " . ($company['is_active'] ? 'Yes' : 'No') . "\n";
    echo "Created: " . $company['created_at'] . "\n";
    echo "</pre>";

    // Test password
    $testPassword = '123456';
    if (password_verify($testPassword, $company['password'])) {
        echo "<p>✅ Password '123456' is correct</p>";
    } else {
        echo "<p>❌ Password '123456' is incorrect</p>";
        echo "<p>Stored hash: " . substr($company['password'], 0, 20) . "...</p>";
    }
} else {
    echo "<p>❌ Company not found with email: info@thessdrive.gr</p>";
}

// List all companies
echo "<h3>All Companies:</h3>";
$stmt = $pdo->query("SELECT id, company_name, email, is_active FROM companies");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Company Name</th><th>Email</th><th>Active</th></tr>";
foreach ($companies as $comp) {
    echo "<tr>";
    echo "<td>{$comp['id']}</td>";
    echo "<td>{$comp['company_name']}</td>";
    echo "<td>{$comp['email']}</td>";
    echo "<td>" . ($comp['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check users table too
echo "<h3>Check in Users Table:</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'company'");
$stmt->execute(['info@thessdrive.gr']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<p>✅ Found in users table</p>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    // Test password in users table
    if (password_verify($testPassword, $user['password'])) {
        echo "<p>✅ Password '123456' is correct in users table</p>";
    } else {
        echo "<p>❌ Password '123456' is incorrect in users table</p>";
    }
} else {
    echo "<p>❌ Not found in users table</p>";
}
