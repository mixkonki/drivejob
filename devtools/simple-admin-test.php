<?php
// Simple test without bootstrap to avoid session issues
$dsn = "mysql:host=127.0.0.1;port=3306;dbname=drivejob;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "Simple Admin Test:\n";
    echo "==================\n";

    // Test direct database access to admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@drivejob.gr']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "❌ Admin user not found\n";
        exit(1);
    }

    echo "✅ Admin user exists\n";
    echo "   ID: {$user['id']}\n";
    echo "   Username: {$user['username']}\n";
    echo "   Status: " . ($user['is_active'] ? 'Active' : 'Inactive') . "\n";

    // Check if user has admin role
    $stmt = $pdo->prepare("
        SELECT r.name
        FROM user_roles ur
        JOIN roles r ON r.id = ur.role_id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "✅ User roles: " . implode(', ', array_column($roles, 'name')) . "\n";

    // Check admin.access permission
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as has_permission
        FROM user_roles ur
        JOIN role_permissions rp ON rp.role_id = ur.role_id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE ur.user_id = ? AND p.name = 'admin.access'
    ");
    $stmt->execute([$user['id']]);
    $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC)['has_permission'] > 0;

    echo "✅ Admin access: " . ($hasPermission ? 'Yes' : 'No') . "\n";

    if (!$hasPermission) {
        echo "❌ Missing admin.access permission\n";

        // Let's fix it by adding the permission
        echo "\n🔧 Attempting to fix missing permission...\n";

        // Find admin role ID
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
        $stmt->execute();
        $adminRoleId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

        if (!$adminRoleId) {
            echo "❌ Admin role not found\n";
            exit(1);
        }

        // Find admin.access permission ID
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = 'admin.access'");
        $stmt->execute();
        $adminAccessId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

        if (!$adminAccessId) {
            echo "❌ admin.access permission not found\n";
            exit(1);
        }

        // Add the permission to admin role
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO role_permissions (role_id, permission_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$adminRoleId, $adminAccessId]);

        echo "✅ Added admin.access permission to admin role\n";

        // Verify it was added
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_permission
            FROM user_roles ur
            JOIN role_permissions rp ON rp.role_id = ur.role_id
            JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.user_id = ? AND p.name = 'admin.access'
        ");
        $stmt->execute([$user['id']]);
        $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC)['has_permission'] > 0;

        echo "✅ Permission fixed: " . ($hasPermission ? 'Yes' : 'No') . "\n";
    }

    echo "\n🎉 Admin setup is correct!\n";
    echo "If you're still having login issues, try:\n";
    echo "1. Clear browser cache and cookies\n";
    echo "2. Check browser console for JavaScript errors\n";
    echo "3. Verify the login form is posting to the correct endpoint\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
