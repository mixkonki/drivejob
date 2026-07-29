<?php
require_once __DIR__ . '/src/bootstrap.php';

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check table structures
    echo "=== Users Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE users');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n=== Drivers Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE drivers');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n=== Companies Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE companies');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    // List all users
    echo "\n=== All Users ===\n";
    $stmt = $pdo->query('SELECT id, username, email FROM users LIMIT 10');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        echo "  - {$u['email']} (username: {$u['username']})\n";
    }

    // Check drivers with user_id
    echo "\n=== Drivers with user_id ===\n";
    $stmt = $pdo->query('SELECT id, name, email, user_id FROM drivers WHERE user_id IS NOT NULL LIMIT 5');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        echo "  - {$d['name']} ({$d['email']}) -> user_id: {$d['user_id']}\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
