<?php
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/RBAC/DB.php';

use Drivejob\RBAC\DB;

try {
    $pdo = DB::pdo();

    // Simulate admin access by setting the actor
    $pdo->exec('SET @rbac_actor_user_id = 1');

    // Test the users_overview API logic
    $q = '';
    $limit = 10;

    $sql = "SELECT * FROM v_user_overview WHERE 1";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (username LIKE :q OR email LIKE :q)";
        $params[':q'] = "%$q%";
    }
    $sql .= " ORDER BY id ASC LIMIT :lim";

    $st = $pdo->prepare($sql);
    foreach ($params as $k => $v) $st->bindValue($k, $v);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();

    $items = $st->fetchAll(PDO::FETCH_ASSOC);
    echo "API Test Results:\n";
    echo "================\n";
    echo "Found " . count($items) . " users\n\n";

    foreach ($items as $user) {
        echo "ID: {$user['id']} | Username: {$user['username']} | Roles: {$user['roles']} | Company: {$user['company_id']} | Driver: {$user['driver_id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
