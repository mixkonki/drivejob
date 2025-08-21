<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "Users table structure:\n";
    echo "======================\n";

    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\nSample users data:\n";
    echo "==================\n";

    $stmt = $pdo->query('SELECT * FROM users LIMIT 3');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        echo "User ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        foreach ($user as $key => $value) {
            if ($key !== 'id' && $key !== 'email') {
                echo "$key: $value\n";
            }
        }
        echo "---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
