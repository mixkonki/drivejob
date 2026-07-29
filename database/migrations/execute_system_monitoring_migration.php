<?php

/**
 * Execute System Monitoring Migration
 * 
 * Εκτέλεση του migration για τη δημιουργία των πινάκων του συστήματος παρακολούθησης
 */

// Εκτέλεση του migration
require_once __DIR__ . '/create_system_monitoring_tables.php';

// Προσθήκη δεδομένων δοκιμής (μόνο για development)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    try {
        // Σύνδεση στη βάση δεδομένων
        $pdo = require __DIR__ . '/../../config/database.php';

        // Προσθήκη δοκιμαστικών σφαλμάτων
        $errorTypes = ['error', 'warning', 'notice', 'deprecated', 'exception'];
        $messages = [
            'Undefined variable: user',
            'Division by zero',
            'Call to undefined method',
            'Method is deprecated',
            'Database connection failed',
            'Invalid argument supplied',
            'File not found',
            'Permission denied',
            'Memory limit exceeded',
            'Maximum execution time exceeded'
        ];
        $files = [
            '/src/Controllers/AuthController.php',
            '/src/Models/UserModel.php',
            '/src/Services/EmailService.php',
            '/src/Core/Router.php',
            '/src/Views/users/profile.php'
        ];

        // Προσθήκη 50 τυχαίων σφαλμάτων
        for ($i = 0; $i < 50; $i++) {
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            $type = $errorTypes[array_rand($errorTypes)];
            $message = $messages[array_rand($messages)];
            $file = $files[array_rand($files)];
            $line = rand(10, 500);
            $userId = rand(1, 20);
            $ipAddress = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);

            $sql = "INSERT INTO error_logs (type, message, file, line, user_id, ip_address, created_at) 
                    VALUES (:type, :message, :file, :line, :user_id, :ip_address, :created_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':file', $file);
            $stmt->bindParam(':line', $line);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();
        }

        // Προσθήκη δοκιμαστικών δεδομένων απόδοσης
        $pages = [
            '/',
            '/login',
            '/register',
            '/profile',
            '/job-listings',
            '/drivers/search',
            '/companies/search',
            '/admin/dashboard',
            '/admin/users',
            '/admin/settings'
        ];
        $methods = ['GET', 'POST'];

        // Προσθήκη 100 τυχαίων καταγραφών απόδοσης
        for ($i = 0; $i < 100; $i++) {
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            $requestUri = $pages[array_rand($pages)];
            $method = $methods[array_rand($methods)];
            $responseTime = rand(50, 2000) / 1000; // 0.05 - 2 seconds
            $memoryUsage = rand(1000000, 10000000); // 1MB - 10MB
            $userId = rand(1, 20);
            $ipAddress = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);

            $sql = "INSERT INTO performance_logs (request_uri, method, response_time, memory_usage, user_id, ip_address, created_at) 
                    VALUES (:request_uri, :method, :response_time, :memory_usage, :user_id, :ip_address, :created_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':request_uri', $requestUri);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':response_time', $responseTime);
            $stmt->bindParam(':memory_usage', $memoryUsage);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();
        }

        // Προσθήκη δοκιμαστικών δεδομένων χρήσης
        $actions = ['view', 'search', 'login', 'logout', 'register', 'update', 'delete', 'create'];

        // Προσθήκη 200 τυχαίων καταγραφών χρήσης
        for ($i = 0; $i < 200; $i++) {
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            $page = $pages[array_rand($pages)];
            $action = $actions[array_rand($actions)];
            $isMobile = rand(0, 1);
            $userId = rand(1, 20);
            $ipAddress = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);

            $sql = "INSERT INTO usage_logs (user_id, page, action, is_mobile, ip_address, created_at) 
                    VALUES (:user_id, :page, :action, :is_mobile, :ip_address, :created_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':page', $page);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':is_mobile', $isMobile);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();
        }

        // Προσθήκη δοκιμαστικών αντιγράφων ασφαλείας
        $backupSizes = [5000000, 7500000, 10000000, 12500000, 15000000]; // 5MB - 15MB

        // Προσθήκη 10 τυχαίων αντιγράφων ασφαλείας
        for ($i = 0; $i < 10; $i++) {
            $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
            $filename = 'backup_' . date('Y-m-d_H-i-s', strtotime($date)) . '.sql';
            $size = $backupSizes[array_rand($backupSizes)];
            $createdBy = 1; // Admin user

            $sql = "INSERT INTO database_backups (filename, size, created_by, created_at) 
                    VALUES (:filename, :size, :created_by, :created_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':size', $size);
            $stmt->bindParam(':created_by', $createdBy);
            $stmt->bindParam(':created_at', $date);
            $stmt->execute();
        }

        echo "Τα δοκιμαστικά δεδομένα για το σύστημα παρακολούθησης προστέθηκαν επιτυχώς.\n";
    } catch (PDOException $e) {
        die("Σφάλμα κατά την προσθήκη δοκιμαστικών δεδομένων: " . $e->getMessage() . "\n");
    }
}

echo "Η εγκατάσταση του συστήματος παρακολούθησης ολοκληρώθηκε επιτυχώς.\n";
