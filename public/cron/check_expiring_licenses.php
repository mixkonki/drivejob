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
    
    // Απευθείας δημιουργία σύνδεσης PDO αντί για φόρτωση του database.php
    // καθώς το αρχείο αυτό δημιουργεί ήδη μια σύνδεση
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
    
    // Δημιουργία του φακέλου Services αν δεν υπάρχει
    $servicesDir = ROOT_DIR . '/src/Services';
    if (!is_dir($servicesDir)) {
        mkdir($servicesDir, 0755, true);
        error_log('Δημιουργήθηκε ο φάκελος Services');
    }
    
    // Ελέγχουμε και δημιουργούμε τα αρχεία υπηρεσιών αν δεν υπάρχουν
    $emailServiceFile = $servicesDir . '/EmailService.php';
    $smsServiceFile = $servicesDir . '/SmsService.php';
    $notificationServiceFile = $servicesDir . '/LicenseExpiryNotificationService.php';
    
    // Ελέγχουμε και δημιουργούμε το EmailService.php
    if (!file_exists($emailServiceFile)) {
        $emailServiceContent = '<?php
namespace Drivejob\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Υπηρεσία αποστολής email
 */
class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $senderEmail;
    private $senderName;
    private $debugMode;
    
    public function __construct($host, $port, $username, $password, $senderEmail, $senderName, $debugMode = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->debugMode = $debugMode;
    }
    
    public function send($to, $subject, $message, $attachments = [], $cc = [], $bcc = []) {
        if ($this->debugMode) {
            error_log("DEBUG: Αποστολή email στο {$to} με θέμα: {$subject}");
            return true;
        }
        
        try {
            // Εδώ θα υπήρχε ο κώδικας αποστολής email με PHPMailer
            // Στη λειτουργία debug, απλά επιστρέφουμε true
            return true;
        } catch (Exception $e) {
            error_log("Σφάλμα αποστολής email: " . $e->getMessage());
            return false;
        }
    }
}';
        file_put_contents($emailServiceFile, $emailServiceContent);
        error_log('Δημιουργήθηκε το αρχείο EmailService.php');
    }
    
    // Ελέγχουμε και δημιουργούμε το SmsService.php
    if (!file_exists($smsServiceFile)) {
        $smsServiceContent = '<?php
namespace Drivejob\Services;

/**
 * Υπηρεσία αποστολής SMS
 */
class SmsService {
    private $apiKey;
    private $apiUrl;
    private $sender;
    private $debugMode;
    
    public function __construct($apiKey, $apiUrl, $sender, $debugMode = false) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->sender = $sender;
        $this->debugMode = $debugMode;
    }
    
    public function send($to, $message) {
        if ($this->debugMode) {
            error_log("DEBUG: Αποστολή SMS στο {$to}: {$message}");
            return true;
        }
        
        try {
            // Εδώ θα υπήρχε ο κώδικας αποστολής SMS
            // Στη λειτουργία debug, απλά επιστρέφουμε true
            return true;
        } catch (Exception $e) {
            error_log("Σφάλμα αποστολής SMS: " . $e->getMessage());
            return false;
        }
    }
}';
        file_put_contents($smsServiceFile, $smsServiceContent);
        error_log('Δημιουργήθηκε το αρχείο SmsService.php');
    }
    
    // Ελέγχουμε και δημιουργούμε το LicenseExpiryNotificationService.php
    if (!file_exists($notificationServiceFile)) {
        $notificationServiceContent = '<?php
namespace Drivejob\Services;

use PDO;
use DateTime;
use Exception;

/**
 * Υπηρεσία για τον έλεγχο αδειών που λήγουν και την αποστολή ειδοποιήσεων
 */
class LicenseExpiryNotificationService {
    private $pdo;
    private $emailService;
    private $smsService;
    
    public function __construct(PDO $pdo, EmailService $emailService, SmsService $smsService) {
        $this->pdo = $pdo;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }
    
    public function checkAndSendExpiryNotifications() {
        try {
            // Έλεγχος για την ύπαρξη των απαραίτητων πινάκων
            $tableExists = $this->tableExists("driver_licenses");
            if (!$tableExists) {
                error_log("Ο πίνακας driver_licenses δεν υπάρχει");
                return [
                    "driving_licenses" => [],
                    "pei" => [],
                    "adr_certificates" => [],
                    "tachograph_cards" => [],
                    "operator_licenses" => [],
                    "special_licenses" => []
                ];
            }
            
            error_log("Το σύστημα ειδοποιήσεων λειτουργεί σε λειτουργία δοκιμής");
            
            // Εδώ θα υπήρχε ο κώδικας ελέγχου των αδειών που λήγουν
            // Για δοκιμή, επιστρέφουμε ένα κενό αποτέλεσμα
            return [
                "driving_licenses" => [],
                "pei" => [],
                "adr_certificates" => [],
                "tachograph_cards" => [],
                "operator_licenses" => [],
                "special_licenses" => []
            ];
        } catch (Exception $e) {
            error_log("Σφάλμα στον έλεγχο αδειών: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function tableExists($tableName) {
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE \'{$tableName}\'");
            return $result->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Σφάλμα στον έλεγχο ύπαρξης πίνακα: " . $e->getMessage());
            return false;
        }
    }
}';
        file_put_contents($notificationServiceFile, $notificationServiceContent);
        error_log('Δημιουργήθηκε το αρχείο LicenseExpiryNotificationService.php');
    }
    
    // Φόρτωση των υπηρεσιών
    require_once $emailServiceFile;
    require_once $smsServiceFile;
    require_once $notificationServiceFile;
    
    // Φόρτωση των ρυθμίσεων ειδοποιήσεων από το υπάρχον αρχείο
    $notificationsConfigFile = ROOT_DIR . '/config/notifications.php';
    $config = include $notificationsConfigFile;
    
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
    
    // Αρχικοποίηση της υπηρεσίας ειδοποιήσεων
    $notificationService = new \Drivejob\Services\LicenseExpiryNotificationService($pdo, $emailService, $smsService);
    
    // Έλεγχος και αποστολή ειδοποιήσεων
    error_log('Εκτέλεση ελέγχου για άδειες που λήγουν...');
    $results = $notificationService->checkAndSendExpiryNotifications();
    
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