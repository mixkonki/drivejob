<?php

/**
 * Migration: add_test_data_to_monitoring_tables
 * 
 * Προσθήκη δοκιμαστικών δεδομένων στους πίνακες του συστήματος παρακολούθησης
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    // Προσθήκη δοκιμαστικών σφαλμάτων
    $errorTypes = ['error', 'warning', 'notice', 'deprecated'];
    $errorMessages = [
        'Undefined variable: user_data',
        'Division by zero',
        'File not found: config.php',
        'Invalid argument supplied for foreach()',
        'Cannot modify header information - headers already sent',
        'Call to undefined function test_function()',
        'Trying to access array offset on value of type null',
        'Use of undefined constant TEST - assumed \'TEST\'',
        'mysqli_connect(): (HY000/2002): Connection refused',
        'Maximum execution time of 30 seconds exceeded'
    ];

    for ($i = 0; $i < 50; $i++) {
        $type = $errorTypes[array_rand($errorTypes)];
        $message = $errorMessages[array_rand($errorMessages)];
        $file = 'src/Controllers/TestController.php';
        $line = rand(10, 500);
        $daysAgo = rand(0, 30);
        $created_at = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

        $sql = "INSERT INTO error_logs (type, message, file, line, created_at) 
                VALUES (:type, :message, :file, :line, :created_at)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'type' => $type,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'created_at' => $created_at
        ]);
    }

    echo "Προστέθηκαν 50 δοκιμαστικά σφάλματα.\n";

    // Προσθήκη δοκιμαστικών logs απόδοσης
    $pages = ['/home', '/drivers/profile', '/companies/search', '/job-listings', '/admin/dashboard'];
    $methods = ['GET', 'POST'];

    for ($i = 0; $i < 100; $i++) {
        $request_uri = $pages[array_rand($pages)];
        $method = $methods[array_rand($methods)];
        $response_time = rand(50, 2000) / 1000; // 0.05 - 2 seconds
        $memory_usage = rand(1000000, 10000000); // 1MB - 10MB
        $daysAgo = rand(0, 7);
        $created_at = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

        $sql = "INSERT INTO performance_logs (request_uri, method, response_time, memory_usage, created_at) 
                VALUES (:request_uri, :method, :response_time, :memory_usage, :created_at)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'request_uri' => $request_uri,
            'method' => $method,
            'response_time' => $response_time,
            'memory_usage' => $memory_usage,
            'created_at' => $created_at
        ]);
    }

    echo "Προστέθηκαν 100 δοκιμαστικά logs απόδοσης.\n";

    // Προσθήκη δοκιμαστικών logs χρήσης
    $actions = ['view', 'search', 'login', 'logout', 'register', 'update'];

    for ($i = 0; $i < 200; $i++) {
        $user_id = rand(1, 50);
        $page = $pages[array_rand($pages)];
        $action = $actions[array_rand($actions)];
        $is_mobile = rand(0, 1);
        $ip_address = '192.168.1.' . rand(1, 255);
        $daysAgo = rand(0, 30);
        $created_at = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

        $sql = "INSERT INTO usage_logs (user_id, page, action, is_mobile, ip_address, created_at) 
                VALUES (:user_id, :page, :action, :is_mobile, :ip_address, :created_at)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'page' => $page,
            'action' => $action,
            'is_mobile' => $is_mobile,
            'ip_address' => $ip_address,
            'created_at' => $created_at
        ]);
    }

    echo "Προστέθηκαν 200 δοκιμαστικά logs χρήσης.\n";

    // Προσθήκη δοκιμαστικών γενικών logs
    $logTypes = ['info', 'warning', 'error', 'debug'];
    $logMessages = [
        'User login successful',
        'New driver registration',
        'Job listing created',
        'Search performed',
        'Profile updated',
        'Password changed',
        'Email sent successfully',
        'File uploaded',
        'Database backup completed',
        'Cache cleared'
    ];

    for ($i = 0; $i < 150; $i++) {
        $type = $logTypes[array_rand($logTypes)];
        $message = $logMessages[array_rand($logMessages)];
        $user_id = rand(1, 50);
        $ip_address = '192.168.1.' . rand(1, 255);
        $daysAgo = rand(0, 30);
        $created_at = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

        $sql = "INSERT INTO system_logs (type, message, user_id, ip_address, created_at) 
                VALUES (:type, :message, :user_id, :ip_address, :created_at)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'type' => $type,
            'message' => $message,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'created_at' => $created_at
        ]);
    }

    echo "Προστέθηκαν 150 δοκιμαστικά γενικά logs.\n";

    // Προσθήκη δοκιμαστικών αντιγράφων ασφαλείας
    for ($i = 0; $i < 10; $i++) {
        $daysAgo = $i * 3;
        $filename = 'backup_' . date('Y-m-d_H-i-s', strtotime("-{$daysAgo} days")) . '.sql';
        $size = rand(5000000, 20000000); // 5MB - 20MB
        $created_by = 1; // Admin user
        $created_at = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

        $sql = "INSERT INTO database_backups (filename, size, created_by, created_at) 
                VALUES (:filename, :size, :created_by, :created_at)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'filename' => $filename,
            'size' => $size,
            'created_by' => $created_by,
            'created_at' => $created_at
        ]);
    }

    echo "Προστέθηκαν 10 δοκιμαστικά αντίγραφα ασφαλείας.\n";

    echo "\nΌλα τα δοκιμαστικά δεδομένα προστέθηκαν επιτυχώς!\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά την προσθήκη δοκιμαστικών δεδομένων: " . $e->getMessage() . "\n");
}
