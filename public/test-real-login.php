<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Models\AuthModel;
use Drivejob\Core\Database;

// Δημιουργία database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Δημιουργία AuthModel instance με PDO
$authModel = new AuthModel($pdo);

// Test credentials
$email = 'admin@drivejob.gr';
$password = 'admin123';

echo "<h2>Testing Real Login Process</h2>";
echo "<pre>";
echo "Testing login with:\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// Προσπάθεια αυθεντικοποίησης
$user = $authModel->authenticate($email, $password);

if ($user) {
    echo "✅ Authentication successful!\n";
    echo "User data:\n";
    print_r($user);

    // Set session variables όπως κάνει ο AuthController
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role'] = $user['role']; // Για συμβατότητα
    $_SESSION['user_name'] = $user['name'];

    echo "\nSession set:\n";
    print_r($_SESSION);

    echo "\n<a href='/drivejob/public/admin/dashboard.php'>Go to Admin Dashboard</a>";
} else {
    echo "❌ Authentication failed!\n";
}

echo "</pre>";
