<?php
require_once __DIR__ . '/src/bootstrap.php';

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check if driver already exists
    $stmt = $pdo->prepare('SELECT id FROM drivers WHERE email = ?');
    $stmt->execute(['driver@example.com']);
    if ($stmt->fetch()) {
        echo "✓ Driver already exists with email: driver@example.com\n";
        exit(0);
    }

    // Create test driver
    $password = password_hash('password123', PASSWORD_DEFAULT);

    $sql = "INSERT INTO drivers (
        email, password, first_name, last_name, phone,
        is_verified, is_active, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'driver@example.com',
        $password,
        'Test',
        'Driver',
        '1234567890',
        1, // is_verified = true
        1  // is_active = true
    ]);

    if ($result) {
        $driverId = $pdo->lastInsertId();
        echo "✓ Test driver created successfully!\n";
        echo "  ID: $driverId\n";
        echo "  Email: driver@example.com\n";
        echo "  Password: password123\n";
        echo "  Name: Test Driver\n";
        echo "\n✓ You can now login at: http://localhost:8000/login.php\n";
    } else {
        echo "✗ Failed to create driver\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
