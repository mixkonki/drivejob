<?php
/**
 * Cron job για τον έλεγχο αδειών που λήγουν και την αποστολή ειδοποιήσεων
 * 
 * Αυτό το script πρέπει να εκτελείται καθημερινά μέσω του crontab
 * Παράδειγμα εντολής crontab:
 * 0 9 * * * php /path/to/drivejob/public/cron/check_expiring_licenses.php
 */

// Αρχικοποίηση του συστήματος καταγραφής
$baseDir = dirname(dirname(__DIR__)); // Αυτό είναι το Root Directory
$logFile = $baseDir . '/logs/license_notifications_' . date('Y-m-d') . '.log';
ini_set('error_log', $logFile);
ini_set('log_errors', 1);

// Δημιουργία φακέλου logs αν δεν υπάρχει
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Καταγραφή έναρξης της εκτέλεσης
error_log('--- Έναρξη ελέγχου αδειών που λήγουν: ' . date('Y-m-d H:i:s') . ' ---');

try {
    // Ορισμός της ζώνης ώρας
    date_default_timezone_set('Europe/Athens');
    
    // Φόρτωση του bootstrap αν υπάρχει (προαιρετικά)
    if (file_exists($baseDir . '/src/bootstrap.php')) {
        require_once $baseDir . '/src/bootstrap.php';
        error_log('Φόρτωση του bootstrap.php');
    } else {
        // Αν δεν υπάρχει bootstrap, ορίζουμε τη σταθερά ROOT_DIR
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', $baseDir);
        }
    }
    
    // Σύνδεση με τη βάση δεδομένων
    $pdo = null;
    
    try {
        $host = 'localhost';
        $db = 'drivejob';
        $user = 'root';
        $pass = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        error_log('Επιτυχής σύνδεση με τη βάση δεδομένων');
    } catch (PDOException $e) {
        error_log('Σφάλμα σύνδεσης με τη βάση δεδομένων: ' . $e->getMessage());
        throw $e;
    }
    
    // Φόρτωση των απαραίτητων αρχείων για τις υπηρεσίες
    $requiredFiles = [
        '/src/Services/EmailService.php',
        '/src/Services/SmsService.php',
        '/src/Services/LicenseExpiryNotificationService.php',
        '/src/Services/NotificationServices.php'
    ];
    
    foreach ($requiredFiles as $file) {
        $filePath = ROOT_DIR . $file;
        if (!file_exists($filePath)) {
            error_log("Προειδοποίηση: Το αρχείο {$file} δεν βρέθηκε.");
            continue;
        }
        require_once $filePath;
    }
    
    // Φόρτωση των ρυθμίσεων ειδοποιήσεων
    $notificationsConfigFile = ROOT_DIR . '/config/notifications.php';
    $config = [];
    
    if (file_exists($notificationsConfigFile)) {
        $config = include $notificationsConfigFile;
        error_log('Φόρτωση ρυθμίσεων ειδοποιήσεων από: ' . $notificationsConfigFile);
    } else {
        error_log('Το αρχείο ρυθμίσεων δεν βρέθηκε. Χρήση προεπιλεγμένων ρυθμίσεων.');
        // Προεπιλεγμένες ρυθμίσεις αν δεν υπάρχει το αρχείο config
        $config = [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'user@example.com',
            'smtp_password' => 'password',
            'sender_email' => 'notifications@example.com',
            'sender_name' => 'DriveJob Ειδοποιήσεις',
            'sms_api_key' => 'your_sms_api_key_here',
            'sms_api_url' => 'https://api.smsservice.example/send',
            'sms_sender' => 'DriveJob',
            'debug_mode' => true,
            'notification_periods' => [
                'driving_license' => [60, 30, 15, 7, 1],
                'pei' => [60, 30, 15, 7, 1],
                'adr_certificate' => [60, 30, 15, 7, 1],
                'tachograph_card' => [60, 30, 15, 7, 1],
                'operator_license' => [180, 90, 30, 15],
                'special_license' => [60, 30, 15, 7, 1]
            ],
            'max_notifications_per_run' => 100,
            'admin_emails' => ['admin@example.com'],
            'daily_report_enabled' => false
        ];
    }
    
    // Έλεγχος και δημιουργία του πίνακα ειδοποιήσεων αν δεν υπάρχει
    $tableCheckSQL = "SHOW TABLES LIKE 'license_expiry_notifications'";
    $tableExists = $pdo->query($tableCheckSQL)->rowCount() > 0;
    
    if (!$tableExists) {
        error_log('Δημιουργία πίνακα license_expiry_notifications...');
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS license_expiry_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                driver_id INT NOT NULL,
                license_category VARCHAR(50) NOT NULL,
                license_type VARCHAR(50) NOT NULL,
                expiry_date DATE NOT NULL,
                days_before INT NOT NULL,
                sent_at DATETIME NOT NULL,
                INDEX (driver_id),
                INDEX (license_category),
                INDEX (expiry_date),
                UNIQUE KEY unique_notification (driver_id, license_type, expiry_date, days_before)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($createTableSQL);
        error_log('Ο πίνακας δημιουργήθηκε επιτυχώς');
    }
    
    // Αρχικοποίηση των υπηρεσιών
    $emailService = new \Drivejob\Services\EmailService(
        $config['smtp_host'],
        $config['smtp_port'],
        $config['smtp_username'],
        $config['smtp_password'],
        $config['sender_email'],
        $config['sender_name'],
        $config['debug_mode']
    );
    
    $smsService = new \Drivejob\Services\SmsService(
        $config['sms_api_key'],
        $config['sms_api_url'],
        $config['sms_sender'],
        $config['debug_mode']
    );
    
    // Αρχικοποίηση της κεντρικής υπηρεσίας ειδοποιήσεων
    $notificationService = new \Drivejob\Services\NotificationServices($pdo, $emailService, $smsService, $config);
    
    // Έλεγχος και αποστολή ειδοποιήσεων
    error_log('Εκτέλεση ελέγχου για άδειες που λήγουν...');
    $results = $notificationService->checkAndSendLicenseExpiryNotifications();
    
    // Καταγραφή των αποτελεσμάτων
    error_log('Ολοκλήρωση ελέγχου.');
    
    if (is_array($results)) {
        $totalNotifications = 0;
        foreach ($results as $category => $notifications) {
            $totalNotifications += count($notifications);
        }
        
        error_log('Συνολικές ειδοποιήσεις που στάλθηκαν: ' . $totalNotifications);
        
        foreach ($results as $category => $notifications) {
            error_log("Κατηγορία {$category}: " . count($notifications) . " ειδοποιήσεις");
        }
        
        // Αποθήκευση των αποτελεσμάτων για διαγνωστικούς σκοπούς αν είναι ενεργοποιημένη η λειτουργία debug
        if ($config['debug_mode'] && $totalNotifications > 0) {
            $resultsFile = ROOT_DIR . '/logs/notifications_results_' . date('Y-m-d_H-i-s') . '.json';
            file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            error_log('Αποθήκευση αποτελεσμάτων στο αρχείο: ' . $resultsFile);
        }
    } else {
        error_log('Δεν επιστράφηκαν αποτελέσματα από την υπηρεσία ειδοποιήσεων');
    }
    
    // Επιτυχής ολοκλήρωση
    error_log('--- Επιτυχής ολοκλήρωση ελέγχου: ' . date('Y-m-d H:i:s') . ' ---');
    exit(0);
} catch (Exception $e) {
    // Καταγραφή σφάλματος
    error_log('ΣΦΑΛΜΑ: ' . $e->getMessage());
    error_log('Stack Trace: ' . $e->getTraceAsString());
    error_log('--- Αποτυχία ελέγχου αδειών: ' . date('Y-m-d H:i:s') . ' ---');
    exit(1);
}