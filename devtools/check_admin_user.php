<?php
require_once 'src/RBAC/DB.php';

use DriveJob\RBAC\DB;

try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('SELECT id, email, username, password, is_active FROM users WHERE email = ? LIMIT 1');
    $stmt->execute(['admin@drivejob.gr']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo 'User found:' . PHP_EOL;
        echo 'ID: ' . $user['id'] . PHP_EOL;
        echo 'Email: ' . $user['email'] . PHP_EOL;
        echo 'Username: ' . ($user['username'] ?? 'NULL') . PHP_EOL;
        echo 'Password hash: ' . substr($user['password'], 0, 20) . '...' . PHP_EOL;
        echo 'Is Active: ' . ($user['is_active'] ?? 'NULL') . PHP_EOL;
        echo 'Password verify test: ' . (password_verify('admin123', $user['password']) ? 'PASS' : 'FAIL') . PHP_EOL;

        // Check if there's a role column
        $stmt2 = $pdo->prepare('SHOW COLUMNS FROM users LIKE "role"');
        $stmt2->execute();
        $roleColumn = $stmt2->fetch();

        if ($roleColumn) {
            $stmt3 = $pdo->prepare('SELECT role FROM users WHERE email = ? LIMIT 1');
            $stmt3->execute(['admin@drivejob.gr']);
            $roleData = $stmt3->fetch(PDO::FETCH_ASSOC);
            echo 'Role: ' . ($roleData['role'] ?? 'NULL') . PHP_EOL;
        } else {
            echo 'Role column does not exist' . PHP_EOL;
        }
    } else {
        echo 'User not found!' . PHP_EOL;

        // Let's see what users exist
        echo 'Existing users:' . PHP_EOL;
        $stmt = $pdo->query('SELECT id, email, username FROM users LIMIT 10');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '- ID: ' . $row['id'] . ', Email: ' . $row['email'] . ', Username: ' . ($row['username'] ?? 'NULL') . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
