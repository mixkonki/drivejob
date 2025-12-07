<?php
// test-admin-users.php - Quick test for admin/users endpoint

require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "Testing admin/users endpoint query...\n";
    echo "=====================================\n";

    // Test the exact query from admin/users.php (with RBAC)
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.is_active,
            u.is_verified,
            u.created_at,
            GROUP_CONCAT(r.name ORDER BY ur.is_primary DESC, r.name) AS roles,
            GROUP_CONCAT(r.id ORDER BY ur.is_primary DESC, r.name) AS role_ids,
            c.id   AS company_id,
            c.company_name,
            c.phone AS company_phone,
            d.id   AS driver_id,
            d.first_name,
            d.last_name,
            d.phone AS driver_phone,
            CASE 
                WHEN d.first_name IS NOT NULL THEN CONCAT(d.first_name, ' ', d.last_name)
                WHEN c.company_name IS NOT NULL THEN c.company_name
                ELSE u.username
            END as name
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        LEFT JOIN companies c ON c.user_id = u.id
        LEFT JOIN drivers d ON d.user_id = u.id
        GROUP BY u.id, u.username, u.email, u.is_active, u.is_verified, u.created_at,
                 c.id, c.company_name, c.phone, d.id, d.first_name, d.last_name, d.phone
        ORDER BY u.created_at DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Query executed successfully!\n";
    echo "Found " . count($users) . " users\n\n";

    foreach ($users as $user) {
        echo "User ID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Email: {$user['email']}\n";
        echo "Roles: {$user['roles']}\n";
        echo "Name: {$user['name']}\n";

        if ($user['company_id']) {
            echo "Company: {$user['company_name']} (ID: {$user['company_id']})\n";
        }

        if ($user['driver_id']) {
            echo "Driver: {$user['first_name']} {$user['last_name']} (ID: {$user['driver_id']})\n";
        }

        echo "---\n";
    }

    // Test backfill results
    echo "\nBackfill verification:\n";
    echo "======================\n";

    $backfillSql = "
        SELECT 
            'companies' as table_name,
            COUNT(*) as total_records,
            COUNT(user_id) as linked_records,
            COUNT(*) - COUNT(user_id) as unlinked_records
        FROM companies
        UNION ALL
        SELECT 
            'drivers' as table_name,
            COUNT(*) as total_records,
            COUNT(user_id) as linked_records,
            COUNT(*) - COUNT(user_id) as unlinked_records
        FROM drivers
    ";

    $stmt = $pdo->prepare($backfillSql);
    $stmt->execute();
    $backfillResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($backfillResults as $result) {
        echo "{$result['table_name']}: {$result['linked_records']}/{$result['total_records']} linked ({$result['unlinked_records']} unlinked)\n";
    }

    echo "\nTest completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
