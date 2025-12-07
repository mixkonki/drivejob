<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "Checking admin user permissions:\n";
    echo "================================\n";

    // Check the admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@drivejob.gr']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "Admin user not found!\n";
        exit(1);
    }

    echo "Admin User ID: {$admin['id']}\n";
    echo "Email: {$admin['email']}\n";
    echo "Username: {$admin['username']}\n";
    echo "Status: " . ($admin['is_active'] ? 'Active' : 'Inactive') . "\n";
    echo "Verified: " . ($admin['is_verified'] ? 'Yes' : 'No') . "\n\n";

    // Check user roles
    echo "User Roles:\n";
    echo "===========\n";
    $stmt = $pdo->prepare("
        SELECT ur.*, r.name as role_name, r.description
        FROM user_roles ur
        JOIN roles r ON r.id = ur.role_id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$admin['id']]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($userRoles)) {
        echo "No roles assigned to admin user!\n\n";
        echo "Available roles:\n";
        echo "================\n";
        $stmt = $pdo->query("SELECT * FROM roles");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($roles as $role) {
            echo "- ID: {$role['id']}, Name: {$role['name']}\n";
        }
        echo "\n";
    } else {
        foreach ($userRoles as $ur) {
            echo "- Role: {$ur['role_name']} (ID: {$ur['role_id']})\n";
            echo "  Primary: " . ($ur['is_primary'] ? 'Yes' : 'No') . "\n";
        }
    }

    // Check permissions for admin role
    echo "\nAdmin Role Permissions:\n";
    echo "======================\n";

    // First find the admin role
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminRole) {
        echo "Admin Role ID: {$adminRole['id']}\n";

        $stmt = $pdo->prepare("
            SELECT p.name, p.description
            FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$adminRole['id']]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($permissions)) {
            echo "No permissions assigned to admin role!\n\n";
            echo "Available permissions:\n";
            echo "=====================\n";
            $stmt = $pdo->query("SELECT * FROM permissions");
            $allPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allPerms as $perm) {
                echo "- ID: {$perm['id']}, Name: {$perm['name']}\n";
            }
        } else {
            foreach ($permissions as $perm) {
                echo "- {$perm['name']}: {$perm['description']}\n";
            }
        }
    } else {
        echo "Admin role not found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
