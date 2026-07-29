<?php
// Απλό test για το admin users query
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== Testing Users Query Directly ===\n\n";

    // Test το ίδιο query που χρησιμοποιεί το admin/users.php
    $sql = "
        SELECT 
            u.id,
            u.email,
            u.role,
            u.is_active,
            u.is_verified,
            u.created_at,
            c.id   AS company_id,
            c.company_name,
            c.phone AS company_phone,
            d.id   AS driver_id,
            d.first_name,
            d.last_name,
            d.phone AS driver_phone,
            CASE 
                WHEN u.role = 'driver' THEN CONCAT(d.first_name, ' ', d.last_name)
                WHEN u.role = 'company' THEN c.company_name
                ELSE u.email
            END as name
        FROM users u
        LEFT JOIN companies c ON c.user_id = u.id
        LEFT JOIN drivers d ON d.user_id = u.id
        ORDER BY u.created_at DESC
        LIMIT 5
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Query executed successfully!\n";
    echo "Users found: " . count($users) . "\n\n";

    foreach ($users as $i => $user) {
        echo "User " . ($i + 1) . ":\n";
        echo "  - ID: {$user['id']}\n";
        echo "  - Email: {$user['email']}\n";
        echo "  - Role: {$user['role']}\n";
        echo "  - Name: {$user['name']}\n";
        echo "  - Company ID: " . ($user['company_id'] ?? 'NULL') . "\n";
        echo "  - Driver ID: " . ($user['driver_id'] ?? 'NULL') . "\n";
        echo "\n";
    }

    echo "=== Test Complete - Query Works! ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
