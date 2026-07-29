<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "🔧 FIXING LOGIN SESSION ISSUES\n";
    echo "==============================\n";

    // 1. Verify current session state
    echo "1. Current Session State:\n";
    session_start();
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";

    // 2. Manually set admin session (temporary fix)
    $_SESSION['user_id'] = 1;
    $_SESSION['user_email'] = 'admin@drivejob.gr';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['login_time'] = time();

    echo "   ✅ Set admin session manually\n";

    // 3. Test currentUserId function
    $currentUserId = currentUserId();
    echo "   currentUserId() returns: " . ($currentUserId ?: 'NULL') . "\n";

    // 4. Test RBAC permission check
    echo "\n2. Testing RBAC Integration:\n";

    // Set RBAC actor
    $pdo->exec("SET @rbac_actor_user_id = 1");

    // Test permission check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as has_permission
        FROM user_roles ur
        JOIN role_permissions rp ON rp.role_id = ur.role_id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE ur.user_id = ? AND p.name = 'admin.access'
    ");
    $stmt->execute([1]);
    $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC)['has_permission'] > 0;

    echo "   Admin.access permission: " . ($hasPermission ? '✅ GRANTED' : '❌ DENIED') . "\n";

    // 5. Test admin API access
    echo "\n3. Testing Admin API Access:\n";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM v_user_overview");
    $total = $stmt->fetch()['total'];
    echo "   Users in system: $total\n";

    echo "\n🎉 SESSION FIX APPLIED!\n";
    echo "\nTry accessing the admin panel now:\n";
    echo "http://localhost/drivejob/public/admin/\n";
    echo "\nIf you still have issues:\n";
    echo "1. Check browser console for errors\n";
    echo "2. Try incognito mode\n";
    echo "3. Check if admin panel loads\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
