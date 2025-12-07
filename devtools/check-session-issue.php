<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\RBAC\DB;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "Testing session and RBAC integration:\n";
    echo "====================================\n";

    // Simulate what happens during login
    $userId = 1; // admin user ID

    // Set the RBAC actor (this is what _rbac_bootstrap.php does)
    $pdo->exec("SET @rbac_actor_user_id = $userId");

    // Test currentUserId() function
    $currentUser = currentUserId();
    echo "✅ currentUserId(): " . ($currentUser ?: 'NULL') . "\n";

    // Test the Guard::requirePermission function simulation
    echo "Testing admin.access permission check:\n";

    // Simulate the permission check from Guard::requirePermission
    $stmt = DB::pdo()->prepare("
        SELECT p.name
        FROM user_roles ur
        JOIN role_permissions rp ON rp.role_id = ur.role_id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE ur.user_id = ? AND p.name = ?
    ");
    $stmt->execute([$userId, 'admin.access']);
    $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "✅ Has admin.access permission: " . ($hasPermission ? 'Yes' : 'No') . "\n";

    if (!$hasPermission) {
        echo "❌ Permission check failed\n";
        exit(1);
    }

    // Test the admin API endpoint directly
    echo "\nTesting admin API access:\n";
    echo "=========================\n";

    // Simulate the users_overview API call
    $sql = "SELECT * FROM v_user_overview WHERE 1 ORDER BY id ASC LIMIT :lim";
    $params = [];
    $limit = 10;

    $st = DB::pdo()->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();

    $items = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ API query executed successfully\n";
    echo "✅ Found " . count($items) . " users in result\n";

    foreach ($items as $user) {
        echo "   - {$user['username']} ({$user['roles']})\n";
    }

    echo "\n🎉 All session and RBAC tests passed!\n";
    echo "The issue might be in the frontend routing or session management.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
