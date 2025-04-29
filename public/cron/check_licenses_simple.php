<?php
/**
 * Απλοποιημένο cron job για τον έλεγχο αδειών που λήγουν
 */

// Ορισμός του βασικού φακέλου
$rootDir = dirname(dirname(__DIR__));

// Ρύθμιση καταγραφής σφαλμάτων
$logFile = $rootDir . '/logs/license_notifications_' . date('Y-m-d') . '.log';
ini_set('error_log', $logFile);
ini_set('log_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Δημιουργία φακέλου logs αν δεν υπάρχει
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Καταγραφή έναρξης
error_log('--- Έναρξη απλοποιημένου ελέγχου αδειών: ' . date('Y-m-d H:i:s') . ' ---');

try {
    // Ρύθμιση ζώνης ώρας
    date_default_timezone_set('Europe/Athens');
    
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
        $filePath = $rootDir . $file;
        if (!file_exists($filePath)) {
            error_log("Προειδοποίηση: Το αρχείο {$file} δεν βρέθηκε.");
            continue;
        }
        require_once $filePath;
    }
    
    // Φόρτωση των ρυθμίσεων ειδοποιήσεων
    $notificationsConfigFile = $rootDir . '/config/notifications.php';
    $config = [];
    
    if (file_exists($notificationsConfigFile)) {
        $config = include $notificationsConfigFile;
        error_log('Φόρτωση ρυθμίσεων ειδοποιήσεων από: ' . $notificationsConfigFile);
    } else {
        error_log('Το αρχείο ρυθμίσεων δεν βρέθηκε. Χρήση προεπιλεγμένων ρυθμίσεων.');
        // Προεπιλεγμένες ρυθμίσεις αν δεν υπάρχει το αρχείο config
        $config = [
            'smtp_host' => 'localhost',
            'smtp_port' => 25,
            'smtp_username' => '',
            'smtp_password' => '',
            'sender_email' => 'noreply@drivejob.gr',
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
    
    // Δημιουργία του φακέλου templates/emails αν δεν υπάρχει
    $templatesPath = $rootDir . '/templates/emails/';
    if (!is_dir($templatesPath)) {
        mkdir($templatesPath, 0755, true);
        error_log("Δημιουργήθηκε ο φάκελος προτύπων: {$templatesPath}");
    }
    
    // Δημιουργία ενός βασικού προτύπου email αν δεν υπάρχει
    $generalTemplateFile = $templatesPath . 'license_expiry_general.php';
    if (!file_exists($generalTemplateFile)) {
        $templateContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Άδειας - DriveJob</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .warning { color: #e74c3c; font-weight: bold; }
        .button { display: inline-block; background-color: #3498db; color: white; padding: 10px 20px; 
                  text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>DriveJob - Ειδοποίηση Λήξης Άδειας</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo isset($first_name) ? $first_name : "Συνεργάτη"; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι μια άδεια/πιστοποιητικό σας πρόκειται να λήξει σύντομα.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Άδειας/Πιστοποιητικού</h3>
            <p>
            <?php if (isset($license_type)): ?>
            <strong>Τύπος:</strong> <?php echo $license_type; ?><br>
            <?php endif; ?>
            <?php if (isset($expiry_date)): ?>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <?php endif; ?>
            <?php if (isset($days_before_expiry)): ?>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?><br>
            <?php endif; ?>
            </p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδεια/πιστοποιητικό σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url ?? "https://drivejob.gr"; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year ?? date("Y"); ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
        
        file_put_contents($generalTemplateFile, $templateContent);
        error_log("Δημιουργήθηκε το βασικό πρότυπο email: {$generalTemplateFile}");
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
            $resultsFile = $rootDir . '/logs/notifications_results_' . date('Y-m-d_H-i-s') . '.json';
            file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            error_log('Αποθήκευση αποτελεσμάτων στο αρχείο: ' . $resultsFile);
        }
    } else {
        error_log('Δεν επιστράφηκαν αποτελέσματα από την υπηρεσία ειδοποιήσεων');
    }
    
    // Επιτυχής ολοκλήρωση
    error_log('--- Επιτυχής ολοκλήρωση απλοποιημένου ελέγχου: ' . date('Y-m-d H:i:s') . ' ---');
    exit(0);
} catch (Exception $e) {
    // Καταγραφή σφάλματος
    error_log('ΣΦΑΛΜΑ: ' . $e->getMessage());
    error_log('Stack Trace: ' . $e->getTraceAsString());
    error_log('--- Αποτυχία απλοποιημένου ελέγχου αδειών: ' . date('Y-m-d H:i:s') . ' ---');
    exit(1);
}