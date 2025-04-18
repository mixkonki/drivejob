<?php
namespace Drivejob\Services;

use PDO;
use DateTime;
use Exception;

/**
 * Υπηρεσία διαχείρισης όλων των ειδοποιήσεων στην εφαρμογή DriveJob
 */
class NotificationServices {
    /**
     * @var PDO $pdo Σύνδεση με τη βάση δεδομένων
     */
    private $pdo;
    
    /**
     * @var EmailService $emailService Υπηρεσία αποστολής email
     */
    private $emailService;
    
    /**
     * @var SmsService $smsService Υπηρεσία αποστολής SMS
     */
    private $smsService;
    
    /**
     * @var array $config Ρυθμίσεις ειδοποιήσεων
     */
    private $config;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Σύνδεση με τη βάση δεδομένων
     * @param EmailService $emailService Υπηρεσία αποστολής email
     * @param SmsService $smsService Υπηρεσία αποστολής SMS
     * @param array $config Ρυθμίσεις ειδοποιήσεων
     */
    public function __construct(PDO $pdo, EmailService $emailService, SmsService $smsService, array $config = []) {
        $this->pdo = $pdo;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->config = $config;
        
        // Μετάδοση της λειτουργίας debug από τις ρυθμίσεις στις υπηρεσίες
        if (isset($this->config['debug_mode'])) {
            $this->emailService->setDebugMode($this->config['debug_mode']);
        }
    }
    
    /**
     * Έλεγχος και αποστολή ειδοποιήσεων για άδειες που λήγουν
     * 
     * @return array Αποτελέσματα ειδοποιήσεων
     */
    public function checkAndSendLicenseExpiryNotifications() {
        try {
            $this->log("Έναρξη ελέγχου για άδειες που λήγουν", 'INFO');
            
            // Δημιουργία της υπηρεσίας ειδοποιήσεων λήξης αδειών
            $expiryService = new LicenseExpiryNotificationService($this->pdo, $this->emailService, $this->smsService, $this->config);
            
            // Εκτέλεση του ελέγχου και αποστολή των ειδοποιήσεων
            $results = $expiryService->checkAndSendExpiryNotifications();
            
            // Καταγραφή των συνολικών αποτελεσμάτων
            $totalNotifications = 0;
            foreach ($results as $category => $notifications) {
                $totalNotifications += count($notifications);
                $this->log("Κατηγορία {$category}: " . count($notifications) . " ειδοποιήσεις", 'INFO');
            }
            
            $this->log("Συνολικές ειδοποιήσεις που στάλθηκαν: {$totalNotifications}", 'INFO');
            
            // Αποστολή συγκεντρωτικής αναφοράς στους διαχειριστές αν έχει ρυθμιστεί
            if (isset($this->config['daily_report_enabled']) && $this->config['daily_report_enabled'] && $totalNotifications > 0) {
                $this->sendDailyReport($results);
            }
            
            return $results;
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά τον έλεγχο λήξης αδειών: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Αποστολή καθημερινής αναφοράς στους διαχειριστές
     * 
     * @param array $results Αποτελέσματα των ειδοποιήσεων που στάλθηκαν
     * @return bool Επιτυχία/αποτυχία
     */
    private function sendDailyReport(array $results) {
        try {
            // Έλεγχος αν υπάρχουν διαχειριστές για αποστολή
            if (empty($this->config['admin_emails'])) {
                $this->log("Δεν υπάρχουν διαχειριστές για αποστολή της καθημερινής αναφοράς", 'WARNING');
                return false;
            }
            
            // Υπολογισμός συνολικών ειδοποιήσεων
            $totalNotifications = 0;
            foreach ($results as $category => $notifications) {
                $totalNotifications += count($notifications);
            }
            
            // Δημιουργία περιεχομένου email
            $subject = "DriveJob - Καθημερινή Αναφορά Ειδοποιήσεων Λήξης Αδειών";
            $message = $this->generateDailyReportEmail($results, $totalNotifications);
            
            // Αποστολή email στους διαχειριστές
            return $this->emailService->send(
                $this->config['admin_emails'],
                $subject,
                $message
            );
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά την αποστολή καθημερινής αναφοράς: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Δημιουργία του περιεχομένου email για την καθημερινή αναφορά
     * 
     * @param array $results Αποτελέσματα των ειδοποιήσεων που στάλθηκαν
     * @param int $totalNotifications Συνολικός αριθμός ειδοποιήσεων
     * @return string HTML περιεχόμενο του email
     */
    private function generateDailyReportEmail(array $results, int $totalNotifications) {
        $date = date('d/m/Y');
        
        // Μετάφραση των κατηγοριών σε ανθρώπινα αναγνώσιμη μορφή
        $categoryNames = [
            'driving_licenses' => 'Άδειες Οδήγησης',
            'pei' => 'Πιστοποιητικά Επαγγελματικής Ικανότητας (ΠΕΙ)',
            'adr_certificates' => 'Πιστοποιητικά ADR',
            'tachograph_cards' => 'Κάρτες Ψηφιακού Ταχογράφου',
            'operator_licenses' => 'Άδειες Χειριστή Μηχανημάτων',
            'special_licenses' => 'Ειδικές Άδειες'
        ];
        
        // Δημιουργία του HTML μηνύματος
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>DriveJob - Καθημερινή Αναφορά Ειδοποιήσεων</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .summary { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-left: 4px solid #3498db; }
                .category { margin-bottom: 30px; }
                .category h3 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; font-weight: bold; }
                tr:hover { background-color: #f5f5f5; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .no-data { color: #999; font-style: italic; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>DriveJob - Καθημερινή Αναφορά Ειδοποιήσεων</h1>
                <p>Ημερομηνία: {$date}</p>
            </div>
            <div class='content'>
                <div class='summary'>
                    <h2>Σύνοψη</h2>
                    <p>Συνολικές ειδοποιήσεις που στάλθηκαν: <strong>{$totalNotifications}</strong></p>";
        
        // Προσθήκη σύνοψης ανά κατηγορία
        foreach ($results as $category => $notifications) {
            $categoryName = $categoryNames[$category] ?? $category;
            $count = count($notifications);
            $html .= "<p>{$categoryName}: <strong>{$count}</strong> ειδοποιήσεις</p>";
        }
        
        $html .= "
                </div>";
        
        // Προσθήκη αναλυτικών πληροφοριών ανά κατηγορία
        foreach ($results as $category => $notifications) {
            $categoryName = $categoryNames[$category] ?? $category;
            $html .= "
                <div class='category'>
                    <h3>{$categoryName}</h3>";
            
            if (empty($notifications)) {
                $html .= "<p class='no-data'>Δεν στάλθηκαν ειδοποιήσεις για αυτή την κατηγορία.</p>";
            } else {
                $html .= "
                    <table>
                        <thead>
                            <tr>
                                <th>Οδηγός</th>
                                <th>Τύπος Άδειας</th>
                                <th>Ημερομηνία Λήξης</th>
                                <th>Ημέρες πριν τη λήξη</th>
                                <th>Ειδοποιήσεις</th>
                            </tr>
                        </thead>
                        <tbody>";
                
                foreach ($notifications as $notification) {
                    $driverName = $notification['driver_name'] ?? 'Άγνωστος';
                    $licenseType = $notification['license_type'] ?? 'Άγνωστος τύπος';
                    $expiryDate = isset($notification['expiry_date']) ? date('d/m/Y', strtotime($notification['expiry_date'])) : 'Άγνωστη';
                    $daysBefore = $notification['days_before'] ?? '-';
                    
                    $notificationTypes = [];
                    if (isset($notification['email_sent']) && $notification['email_sent']) {
                        $notificationTypes[] = 'Email';
                    }
                    if (isset($notification['sms_sent']) && $notification['sms_sent']) {
                        $notificationTypes[] = 'SMS';
                    }
                    $notificationsText = !empty($notificationTypes) ? implode(', ', $notificationTypes) : 'Καμία';
                    
                    $html .= "
                            <tr>
                                <td>{$driverName}</td>
                                <td>{$licenseType}</td>
                                <td>{$expiryDate}</td>
                                <td>{$daysBefore}</td>
                                <td>{$notificationsText}</td>
                            </tr>";
                }
                
                $html .= "
                        </tbody>
                    </table>";
            }
            
            $html .= "
                </div>";
        }
        
        $html .= "
            </div>
            <div class='footer'>
                <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
                <p>&copy; " . date('Y') . " DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Αποστολή ειδοποίησης για λήξη συμβολαίου
     * 
     * @param int $userId ID χρήστη
     * @param string $userType Τύπος χρήστη (driver, company)
     * @param string $contractType Τύπος συμβολαίου
     * @param string $expiryDate Ημερομηνία λήξης
     * @return bool Επιτυχία/αποτυχία
     */
    public function sendContractExpiryNotification($userId, $userType, $contractType, $expiryDate) {
        try {
            // Λήψη στοιχείων χρήστη
            $userModel = $userType === 'driver' 
                ? new \Drivejob\Models\DriversModel($this->pdo)
                : new \Drivejob\Models\CompaniesModel($this->pdo);
            
            $user = $userType === 'driver'
                ? $userModel->getDriverById($userId)
                : $userModel->getCompanyById($userId);
            
            if (!$user) {
                $this->log("Δεν βρέθηκε ο χρήστης με ID: {$userId} και τύπο: {$userType}", 'ERROR');
                return false;
            }
            
            // Προετοιμασία των δεδομένων
            $firstName = $user['first_name'] ?? $user['name'] ?? 'Συνεργάτη';
            $email = $user['email'] ?? null;
            $phone = $user['phone'] ?? null;
            
            if (!$email && !$phone) {
                $this->log("Δεν υπάρχουν στοιχεία επικοινωνίας για τον χρήστη με ID: {$userId}", 'ERROR');
                return false;
            }
            
            // Υπολογισμός ημερών μέχρι τη λήξη
            $expiryDateTime = new DateTime($expiryDate);
            $currentDateTime = new DateTime();
            $interval = $currentDateTime->diff($expiryDateTime);
            $daysUntilExpiry = $interval->days;
            
            // Αποστολή email
            $success = false;
            if ($email) {
                $subject = "Ειδοποίηση λήξης συμβολαίου - {$contractType}";
                $message = $this->generateContractExpiryEmailTemplate(
                    $firstName,
                    $contractType,
                    $expiryDate,
                    $daysUntilExpiry
                );
                
                $success = $this->emailService->send($email, $subject, $message);
            }
            
            // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη και υπάρχει αριθμός τηλεφώνου
            if ($phone && $daysUntilExpiry <= 15) {
                $smsMessage = "DriveJob: Το συμβόλαιο {$contractType} λήγει σε {$daysUntilExpiry} " . 
                            ($daysUntilExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε το έγκαιρα.";
                $success = $this->smsService->sendSms($phone, $smsMessage) || $success;
            }
            
            return $success;
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά την αποστολή ειδοποίησης λήξης συμβολαίου: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Δημιουργία του προτύπου email για ειδοποίηση λήξης συμβολαίου
     * 
     * @param string $firstName Όνομα παραλήπτη
     * @param string $contractType Τύπος συμβολαίου
     * @param string $expiryDate Ημερομηνία λήξης
     * @param int $daysUntilExpiry Ημέρες μέχρι τη λήξη
     * @return string HTML περιεχόμενο του email
     */
    private function generateContractExpiryEmailTemplate($firstName, $contractType, $expiryDate, $daysUntilExpiry) {
        // Μετατροπή της ημερομηνίας σε αναγνώσιμη μορφή
        $expiryDateObj = new DateTime($expiryDate);
        $formattedDate = $expiryDateObj->format('d/m/Y');
        
        // Επιλογή του κατάλληλου κειμένου για τις ημέρες
        $daysText = ($daysUntilExpiry == 1) ? 'μία ημέρα' : $daysUntilExpiry . ' ημέρες';
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ειδοποίηση Λήξης Συμβολαίου - DriveJob</title>
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
            <div class='header'>
                <h1>DriveJob - Ειδοποίηση Λήξης Συμβολαίου</h1>
            </div>
            <div class='content'>
                <p>Αγαπητέ/ή {$firstName},</p>
                
                <p>Σας ενημερώνουμε ότι το συμβόλαιο <strong>{$contractType}</strong> 
                πρόκειται να λήξει σε <span class='warning'>{$daysText}</span>, στις <strong>{$formattedDate}</strong>.</p>
                
                <div class='info-box'>
                    <h3>Στοιχεία Συμβολαίου</h3>
                    <p><strong>Τύπος:</strong> {$contractType}<br>
                    <strong>Ημερομηνία Λήξης:</strong> {$formattedDate}<br>
                    <strong>Υπολειπόμενες ημέρες:</strong> {$daysUntilExpiry}</p>
                </div>
                
                <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα το συμβόλαιό σας για να συνεχίσετε να απολαμβάνετε τις υπηρεσίες μας χωρίς διακοπή.</p>
                
                <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
                
                <a href='https://drivejob.gr/dashboard' class='button'>Μετάβαση στον Πίνακα Ελέγχου</a>
                
                <p style='margin-top: 20px;'>Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
                
                <p>Με εκτίμηση,<br>
                Η ομάδα του DriveJob</p>
            </div>
            <div class='footer'>
                <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
                <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href='mailto:info@drivejob.gr'>info@drivejob.gr</a>.</p>
                <p>&copy; " . date('Y') . " DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Καταγραφή της ειδοποίησης στη βάση δεδομένων
     * 
     * @param string $type Τύπος ειδοποίησης (license_expiry, contract_expiry, etc.)
     * @param int $userId ID χρήστη
     * @param string $userType Τύπος χρήστη (driver, company)
     * @param array $data Δεδομένα ειδοποίησης
     * @param string $method Μέθοδος αποστολής (email, sms, both)
     * @return bool Επιτυχία/αποτυχία
     */
    public function recordNotification($type, $userId, $userType, $data, $method) {
        try {
            // Έλεγχος για την ύπαρξη του πίνακα ειδοποιήσεων
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'notifications'");
            
            // Αν ο πίνακας δεν υπάρχει, τον δημιουργούμε
            if ($tableCheck->rowCount() == 0) {
                $this->createNotificationsTable();
            }
            
            // Μετατροπή του πίνακα data σε JSON
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            // Εισαγωγή της εγγραφής στη βάση
            $sql = "INSERT INTO notifications (type, user_id, user_type, data, method, sent_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$type, $userId, $userType, $jsonData, $method]);
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά την καταγραφή ειδοποίησης: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Δημιουργία του πίνακα ειδοποιήσεων αν δεν υπάρχει
     * 
     * @return bool Επιτυχία/αποτυχία
     */
    private function createNotificationsTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    type VARCHAR(50) NOT NULL,
                    user_id INT NOT NULL,
                    user_type VARCHAR(20) NOT NULL,
                    data JSON,
                    method VARCHAR(10) NOT NULL,
                    sent_at DATETIME NOT NULL,
                    read_at DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (user_id, user_type),
                    INDEX (type),
                    INDEX (sent_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            return $this->pdo->exec($sql) !== false;
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά τη δημιουργία του πίνακα ειδοποιήσεων: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Επιστρέφει τις ρυθμίσεις ειδοποιήσεων
     * 
     * @return array Ρυθμίσεις ειδοποιήσεων
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Ορίζει τις ρυθμίσεις ειδοποιήσεων
     * 
     * @param array $config Νέες ρυθμίσεις
     * @return void
     */
    public function setConfig(array $config) {
        $this->config = $config;
    }
    
    /**
     * Ενημερώνει μια συγκεκριμένη ρύθμιση
     * 
     * @param string $key Κλειδί ρύθμισης
     * @param mixed $value Τιμή ρύθμισης
     * @return void
     */
    public function updateConfig($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Καταγραφή μηνύματος στο αρχείο καταγραφής
     *
     * @param string $message Μήνυμα προς καταγραφή
     * @param string $level Επίπεδο καταγραφής (INFO, WARNING, ERROR, DEBUG)
     * @return void
     */
    private function log($message, $level = 'INFO') {
        // Έλεγχος αν υπάρχει η κλάση Logger
        if (class_exists('Drivejob\Core\Logger')) {
            // Έλεγχος αν υπάρχει η μέθοδος log
            if (method_exists('Drivejob\Core\Logger', 'log')) {
                // Χρήση της μεθόδου της κλάσης Logger
                \Drivejob\Core\Logger::log($level, $message, 'NotificationServices');
            } else {
                // Εναλλακτικά, χρήση της error_log
                error_log("[{$level}] NotificationServices: {$message}");
            }
        } else {
            // Χρήση της error_log αν δεν υπάρχει η κλάση Logger
            error_log("[{$level}] NotificationServices: {$message}");
        }
    }
}