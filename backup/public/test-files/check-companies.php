<?php
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Companies in Database ===\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Get all companies
    $stmt = $pdo->query("SELECT id, company_name, email, is_active FROM companies ORDER BY id");
    $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo "Total companies: " . count($companies) . "\n\n";

    foreach ($companies as $company) {
        echo "ID: " . $company['id'] . "\n";
        echo "Name: " . $company['company_name'] . "\n";
        echo "Email: " . $company['email'] . "\n";
        echo "Active: " . ($company['is_active'] ? 'Yes' : 'No') . "\n";
        echo "---\n";
    }

    // Check users table for companies
    echo "\n=== Company Users ===\n\n";
    $stmt = $pdo->query("SELECT id, email, user_role, is_active FROM users WHERE user_role = 'company' ORDER BY id");
    $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo "Total company users: " . count($users) . "\n\n";

    foreach ($users as $user) {
        echo "User ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['user_role'] . "\n";
        echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        echo "---\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
