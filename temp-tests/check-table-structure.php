<?php

define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== CHECKING TABLE STRUCTURES ===\n\n";

    // Check drivers table
    echo "DRIVERS TABLE:\n";
    $stmt = $pdo->query('DESCRIBE drivers');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nUSERS TABLE:\n";
    $stmt = $pdo->query('DESCRIBE users');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nJOB_LISTINGS TABLE:\n";
    $stmt = $pdo->query('DESCRIBE job_listings');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nMATCHING_SCORES TABLE:\n";
    $stmt = $pdo->query('DESCRIBE matching_scores');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    // Check sample data
    echo "\n=== SAMPLE DATA ===\n\n";

    echo "DRIVERS (first 3):\n";
    $stmt = $pdo->query("SELECT id, first_name, last_name, city, available_for_work FROM drivers LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID {$row['id']}: {$row['first_name']} {$row['last_name']}, {$row['city']}, Available: " . ($row['available_for_work'] ? 'Yes' : 'No') . "\n";
    }

    echo "\nUSERS (first 3):\n";
    $stmt = $pdo->query("SELECT id, email, role FROM users LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID {$row['id']}: {$row['email']}, Role: {$row['role']}\n";
    }

    echo "\nJOB_LISTINGS (first 3):\n";
    $stmt = $pdo->query("SELECT id, title, location, vehicle_type, is_active FROM job_listings LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_active'] ? 'Active' : 'Inactive';
        echo "- ID {$row['id']}: {$row['title']}, {$row['location']}, Vehicle: {$row['vehicle_type']}, Status: {$status}\n";
    }

    // Check relationship between users and drivers
    echo "\n=== RELATIONSHIP CHECK ===\n\n";
    echo "Checking how users connect to drivers...\n";
    $stmt = $pdo->query("
        SELECT u.id as user_id, u.email, u.role, d.id as driver_id, d.first_name, d.last_name
        FROM users u
        LEFT JOIN drivers d ON u.id = d.id
        WHERE u.role = 'driver'
        LIMIT 5
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- User ID {$row['user_id']} ({$row['email']}) -> Driver ID {$row['driver_id']} ({$row['first_name']} {$row['last_name']})\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== COMPLETED ===\n";
