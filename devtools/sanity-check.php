<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    // Check FKs
    $stmt = $pdo->query('SELECT "companies_fk" AS check_name, COUNT(*)>0 ok FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME="fk_companies_user" UNION ALL SELECT "drivers_fk", COUNT(*)>0 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME="fk_drivers_user" UNION ALL SELECT "v_user_overview", COUNT(*)>0 FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="v_user_overview";');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Sanity Check Results:\n";
    echo "===================\n";
    foreach ($results as $result) {
        echo $result['check_name'] . ': ' . ($result['ok'] ? 'OK' : 'FAIL') . "\n";
    }

    // Test the view
    echo "\nTesting v_user_overview:\n";
    echo "=======================\n";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM v_user_overview');
    $count = $stmt->fetch()['total'];
    echo "Total users in view: $count\n";

    $stmt = $pdo->query('SELECT * FROM v_user_overview LIMIT 2');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "User: {$user['username']} - Roles: {$user['roles']} - Company ID: {$user['company_id']} - Driver ID: {$user['driver_id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
