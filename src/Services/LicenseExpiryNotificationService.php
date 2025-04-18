<?php
namespace Drivejob\Services;

use PDO;
use DateTime;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Υπηρεσία για τον έλεγχο αδειών που λήγουν και την αποστολή ειδοποιήσεων
 */
class LicenseExpiryNotificationService {
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
     * @var array $notificationPeriods Περίοδοι ειδοποίησης σε ημέρες πριν τη λήξη
     */
    private $notificationPeriods = [
        'driving_license' => [60, 30, 15, 7, 1], // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'pei' => [60, 30, 15, 7, 1],              // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'adr_certificate' => [60, 30, 15, 7, 1],  // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'tachograph_card' => [60, 30, 15, 7, 1],  // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'operator_license' => [180, 90, 30, 15],  // 6 μήνες, 3 μήνες, 1 μήνας, 15 ημέρες πριν
        'special_license' => [60, 30, 15, 7, 1]   // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
    ];
    
    /**
     * @var int $maxCheckDays Μέγιστος αριθμός ημερών ελέγχου για λήξεις
     */
    private $maxCheckDays = 180; // 6 μήνες
    
    /**
     * @var string $templatesPath Διαδρομή προς τα πρότυπα email
     */
    private $templatesPath;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Σύνδεση με τη βάση δεδομένων
     * @param EmailService $emailService Υπηρεσία αποστολής email
     * @param SmsService $smsService Υπηρεσία αποστολής SMS
     * @param array $config Προαιρετικές ρυθμίσεις περιόδων ειδοποίησης
     */
    public function __construct(PDO $pdo, EmailService $emailService, SmsService $smsService, array $config = null) {
        $this->pdo = $pdo;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        
        // Ορισμός της διαδρομής προτύπων
        $this->templatesPath = ROOT_DIR . '/templates/emails/';
        
        // Αρχικοποίηση του Logger
        if (!class_exists('Drivejob\Core\Logger') || !method_exists('Drivejob\Core\Logger', 'isInitialized')) {
            // Αν ο Logger δεν είναι διαθέσιμος, χρησιμοποιούμε την error_log
            if (!function_exists('simple_log')) {
                function simple_log($message, $level = 'INFO') {
                    error_log("[{$level}] {$message}");
                }
            }
        } else if (!Logger::isInitialized()) {
            Logger::init();
        }
        
        // Χρήση προσαρμοσμένων περιόδων ειδοποίησης αν έχουν παρασχεθεί
        if ($config !== null) {
            if (isset($config['notification_periods'])) {
                $this->notificationPeriods = array_merge($this->notificationPeriods, $config['notification_periods']);
                $this->log("Φόρτωση προσαρμοσμένων περιόδων ειδοποίησης: " . json_encode($this->notificationPeriods), 'INFO');
            }
            
            // Υπολογισμός του μέγιστου αριθμού ημερών ελέγχου από τις περιόδους ειδοποίησης
            $maxDays = 0;
            foreach ($this->notificationPeriods as $periods) {
                $maxDays = max($maxDays, max($periods));
            }
            
            // Προσθήκη 5 ημερών περιθωρίου
            $this->maxCheckDays = $maxDays + 5;
            
            if (isset($config['max_check_days']) && $config['max_check_days'] > $this->maxCheckDays) {
                $this->maxCheckDays = $config['max_check_days'];
            }
            
            $this->log("Μέγιστος αριθμός ημερών ελέγχου: {$this->maxCheckDays}", 'INFO');
            
            // Έλεγχος ύπαρξης φακέλου προτύπων
            if (isset($config['templates_path'])) {
                $customPath = $config['templates_path'];
                if (is_dir($customPath)) {
                    $this->templatesPath = rtrim($customPath, '/\\') . '/';
                    $this->log("Χρήση προσαρμοσμένης διαδρομής προτύπων: {$this->templatesPath}", 'INFO');
                }
            }
        }
        
        // Έλεγχος ύπαρξης του φακέλου προτύπων
        if (!is_dir($this->templatesPath)) {
            try {
                mkdir($this->templatesPath, 0755, true);
                $this->log("Δημιουργήθηκε ο φάκελος προτύπων: {$this->templatesPath}", 'INFO');
            } catch (Exception $e) {
                $this->log("Αδυναμία δημιουργίας φακέλου προτύπων: {$e->getMessage()}", 'ERROR');
            }
        }
    }
    
    /**
     * Έλεγχος για άδειες που λήγουν και αποστολή ειδοποιήσεων
     * 
     * @return array Αποτελέσματα ειδοποιήσεων ανά κατηγορία
     */
    public function checkAndSendExpiryNotifications() {
        try {
            $results = [];
            
            // Διαγνωστικός έλεγχος των πινάκων στη βάση
            $this->logDatabaseTables();
            
            // Έλεγχος για άδειες οδήγησης που λήγουν
            $results['driving_licenses'] = $this->checkDrivingLicenses();
            
            // Έλεγχος για ΠΕΙ που λήγουν
            $results['pei'] = $this->checkPeiCertificates();
            
            // Έλεγχος για πιστοποιητικά ADR που λήγουν
            $results['adr_certificates'] = $this->checkAdrCertificates();
            
            // Έλεγχος για κάρτες ταχογράφου που λήγουν
            $results['tachograph_cards'] = $this->checkTachographCards();
            
            // Έλεγχος για άδειες χειριστή μηχανημάτων που λήγουν
            $results['operator_licenses'] = $this->checkOperatorLicenses();
            
            // Έλεγχος για ειδικές άδειες που λήγουν
            $results['special_licenses'] = $this->checkSpecialLicenses();
            
            return $results;
        } catch (PDOException $e) {
            $this->log('Σφάλμα PDO κατά τον έλεγχο αδειών: ' . $e->getMessage() . ' (Κωδικός: ' . $e->getCode() . ')', 'ERROR');
            $this->log('SQL State: ' . $e->errorInfo[0] . ', Driver error code: ' . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log('Ίχνος στοίβας: ' . $e->getTraceAsString(), 'DEBUG');
            throw $e;
        } catch (Exception $e) {
            $this->log('Γενικό σφάλμα κατά τον έλεγχο αδειών: ' . $e->getMessage() . ' (Τύπος: ' . get_class($e) . ')', 'ERROR');
            $this->log('Ίχνος στοίβας: ' . $e->getTraceAsString(), 'DEBUG');
            throw $e;
        }
    }
    
    /**
     * Καταγράφει πληροφορίες για τους πίνακες στη βάση δεδομένων
     */
    private function logDatabaseTables() {
        try {
            $tables = [
                'drivers',
                'driver_licenses',
                'driver_adr_certificates',
                'driver_tachograph_cards',
                'driver_operator_licenses',
                'driver_special_licenses',
                'license_expiry_notifications'
            ];
            
            foreach ($tables as $table) {
                $exists = $this->tableExists($table);
                $this->log("Πίνακας {$table}: " . ($exists ? "Υπάρχει" : "Δεν υπάρχει"), 'INFO');
                
                if ($exists) {
                    // Μέτρηση εγγραφών
                    $countSql = "SELECT COUNT(*) FROM {$table}";
                    $count = $this->pdo->query($countSql)->fetchColumn();
                    $this->log("  Αριθμός εγγραφών: {$count}", 'INFO');
                    
                    // Έλεγχος αν υπάρχουν επερχόμενες λήξεις
                    if (in_array($table, ['driver_licenses', 'driver_adr_certificates', 'driver_tachograph_cards', 'driver_operator_licenses', 'driver_special_licenses'])) {
                        $expiryField = 'expiry_date';
                        
                        // Έλεγχος αν υπάρχει η στήλη expiry_date
                        $columnCheck = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE 'expiry_date'");
                        if ($columnCheck->rowCount() > 0) {
                            // Μέτρηση επερχόμενων λήξεων (επόμενες 60 ημέρες)
                            $expiryCheckSql = "SELECT COUNT(*) FROM {$table} WHERE {$expiryField} BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
                            $expiryCount = $this->pdo->query($expiryCheckSql)->fetchColumn();
                            $this->log("  Επερχόμενες λήξεις (60 ημέρες): {$expiryCount}", 'INFO');
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $this->log("Σφάλμα κατά την καταγραφή πληροφοριών πινάκων: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά την καταγραφή πληροφοριών πινάκων: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Έλεγχος για άδειες οδήγησης που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkDrivingLicenses() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        // Έλεγχος αν υπάρχει ο πίνακας
        if (!$this->tableExists('driver_licenses')) {
            $this->log("Ο πίνακας driver_licenses δεν υπάρχει", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Υπολογισμός της μέγιστης ημερομηνίας ελέγχου
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$this->maxCheckDays} days");
            $maxDateString = $maxDate->format('Y-m-d');
            
            // Εύρεση όλων των αδειών που λήγουν στο επόμενο διάστημα
            $sql = "
                SELECT 
                    d.id as driver_id, 
                    d.first_name, 
                    d.last_name, 
                    d.email, 
                    d.phone,
                    dl.license_type, 
                    dl.expiry_date
                FROM 
                    drivers d
                JOIN 
                    driver_licenses dl ON d.id = dl.driver_id
                WHERE 
                    dl.expiry_date BETWEEN CURDATE() AND :max_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'max_date' => $maxDateString
            ]);
            
            $expiringLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Βρέθηκαν " . count($expiringLicenses) . " άδειες οδήγησης που λήγουν στο επόμενο διάστημα", 'INFO');
            
            // Έλεγχος για κάθε άδεια
            foreach ($expiringLicenses as $license) {
                $expiryDate = new DateTime($license['expiry_date']);
                $interval = $currentDate->diff($expiryDate);
                $daysUntilExpiry = $interval->days;
                
                // Έλεγχος αν η άδεια πρέπει να ειδοποιηθεί με βάση τις περιόδους
                foreach ($this->notificationPeriods['driving_license'] as $daysBeforeExpiry) {
                    if ($daysUntilExpiry == $daysBeforeExpiry) {
                        // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                        if ($this->hasNotificationBeenSent($license['driver_id'], 'driving_license', $license['license_type'], $license['expiry_date'], $daysBeforeExpiry)) {
                            $this->log("Η ειδοποίηση για τον οδηγό {$license['driver_id']} και άδεια {$license['license_type']} έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες", 'INFO');
                            continue;
                        }
                        
                        // Προετοιμασία του email
                        $subject = "Ειδοποίηση λήξης άδειας οδήγησης - {$license['license_type']}";
                        $message = $this->getEmailTemplate(
                            'driving_license',
                            [
                                'first_name' => $license['first_name'],
                                'license_type' => $license['license_type'],
                                'expiry_date' => $license['expiry_date'],
                                'days_before_expiry' => $daysBeforeExpiry
                            ]
                        );
                        
                        // Αποστολή email
                        $emailSent = false;
                        if (!empty($license['email'])) {
                            $emailSent = $this->emailService->send($license['email'], $subject, $message);
                            $this->log("Αποστολή email στον οδηγό {$license['driver_id']} για άδεια {$license['license_type']}: " . ($emailSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        } else {
                            $this->log("Ο οδηγός {$license['driver_id']} δεν έχει email", 'WARNING');
                        }
                        
                        // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                        $smsSent = false;
                        if ($daysBeforeExpiry <= 15 && !empty($license['phone'])) {
                            $smsMessage = "DriveJob: Η άδεια οδήγησης κατηγορίας {$license['license_type']} λήγει σε {$daysBeforeExpiry} " . 
                                        ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                            $smsSent = $this->smsService->sendSms($license['phone'], $smsMessage);
                            $this->log("Αποστολή SMS στον οδηγό {$license['driver_id']} για άδεια {$license['license_type']}: " . ($smsSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        }
                        
                        // Καταγραφή της ειδοποίησης
                        if ($emailSent || $smsSent) {
                            $this->recordNotification($license['driver_id'], 'driving_license', $license['license_type'], $license['expiry_date'], $daysBeforeExpiry);
                            $sentNotifications[] = [
                                'driver_id' => $license['driver_id'],
                                'driver_name' => $license['first_name'] . ' ' . $license['last_name'],
                                'license_type' => $license['license_type'],
                                'expiry_date' => $license['expiry_date'],
                                'days_before' => $daysBeforeExpiry,
                                'email_sent' => $emailSent,
                                'sms_sent' => $smsSent
                            ];
                        }
                    }
                }
            }
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο αδειών οδήγησης: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο αδειών οδήγησης: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος για ΠΕΙ που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkPeiCertificates() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        // Έλεγχος αν υπάρχει ο πίνακας
        if (!$this->tableExists('driver_licenses')) {
            $this->log("Ο πίνακας driver_licenses δεν υπάρχει", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Έλεγχος για ΠΕΙ Κατηγορίας C
            $sentNotifications = array_merge(
                $sentNotifications, 
                $this->checkSpecificPeiType('C', 'pei_expiry_c', $currentDate)
            );
            
            // Έλεγχος για ΠΕΙ Κατηγορίας D
            $sentNotifications = array_merge(
                $sentNotifications, 
                $this->checkSpecificPeiType('D', 'pei_expiry_d', $currentDate)
            );
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο ΠΕΙ: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο ΠΕΙ: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος για συγκεκριμένο τύπο ΠΕΙ
     * 
     * @param string $peiCategory Κατηγορία ΠΕΙ (C ή D)
     * @param string $expiryDateField Όνομα πεδίου ημερομηνίας λήξης στη βάση
     * @param DateTime $currentDate Τρέχουσα ημερομηνία
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkSpecificPeiType($peiCategory, $expiryDateField, $currentDate) {
        $sentNotifications = [];
        
        // Βεβαιωνόμαστε ότι υπάρχει η στήλη στον πίνακα
        if (!$this->columnExists('driver_licenses', $expiryDateField)) {
            $this->log("Η στήλη {$expiryDateField} δεν υπάρχει στον πίνακα driver_licenses", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Υπολογισμός της μέγιστης ημερομηνίας ελέγχου
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$this->maxCheckDays} days");
            $maxDateString = $maxDate->format('Y-m-d');
            
            // Εύρεση όλων των ΠΕΙ που λήγουν στο επόμενο διάστημα
            $sql = "
                SELECT 
                    d.id as driver_id, 
                    d.first_name, 
                    d.last_name, 
                    d.email, 
                    d.phone,
                    dl.license_type, 
                    dl.{$expiryDateField} as expiry_date
                FROM 
                    drivers d
                JOIN 
                    driver_licenses dl ON d.id = dl.driver_id
                WHERE 
                    dl.{$expiryDateField} BETWEEN CURDATE() AND :max_date
                    AND dl.has_pei = 1
                    AND d.is_verified = 1
                GROUP BY 
                    d.id, dl.{$expiryDateField}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'max_date' => $maxDateString
            ]);
            
            $expiringPeis = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Βρέθηκαν " . count($expiringPeis) . " ΠΕΙ κατηγορίας {$peiCategory} που λήγουν στο επόμενο διάστημα", 'INFO');
            
            // Έλεγχος για κάθε ΠΕΙ
            foreach ($expiringPeis as $pei) {
                $expiryDate = new DateTime($pei['expiry_date']);
                $interval = $currentDate->diff($expiryDate);
                $daysUntilExpiry = $interval->days;
                
                // Έλεγχος αν το ΠΕΙ πρέπει να ειδοποιηθεί με βάση τις περιόδους
                foreach ($this->notificationPeriods['pei'] as $daysBeforeExpiry) {
                    if ($daysUntilExpiry == $daysBeforeExpiry) {
                        // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                        if ($this->hasNotificationBeenSent($pei['driver_id'], 'pei', "PEI-{$peiCategory}", $pei['expiry_date'], $daysBeforeExpiry)) {
                            $this->log("Η ειδοποίηση για τον οδηγό {$pei['driver_id']} και ΠΕΙ κατηγορίας {$peiCategory} έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες", 'INFO');
                            continue;
                        }
                        
                        // Προετοιμασία του email
                        $subject = "Ειδοποίηση λήξης ΠΕΙ κατηγορίας {$peiCategory}";
                        $message = $this->getEmailTemplate(
                            'pei', 
                            [
                                'first_name' => $pei['first_name'],
                                'pei_category' => $peiCategory,
                                'expiry_date' => $pei['expiry_date'],
                                'days_before_expiry' => $daysBeforeExpiry
                            ]
                        );
                        
                        // Αποστολή email
                        $emailSent = false;
                        if (!empty($pei['email'])) {
                            $emailSent = $this->emailService->send($pei['email'], $subject, $message);
                            $this->log("Αποστολή email στον οδηγό {$pei['driver_id']} για ΠΕΙ κατηγορίας {$peiCategory}: " . ($emailSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        } else {
                            $this->log("Ο οδηγός {$pei['driver_id']} δεν έχει email", 'WARNING');
                        }
                        
                        // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                        $smsSent = false;
                        if ($daysBeforeExpiry <= 15 && !empty($pei['phone'])) {
                            $smsMessage = "DriveJob: Το ΠΕΙ κατηγορίας {$peiCategory} λήγει σε {$daysBeforeExpiry} " . 
                                        ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε το έγκαιρα.";
                            $smsSent = $this->smsService->sendSms($pei['phone'], $smsMessage);
                            $this->log("Αποστολή SMS στον οδηγό {$pei['driver_id']} για ΠΕΙ κατηγορίας {$peiCategory}: " . ($smsSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        }
                        
                        // Καταγραφή της ειδοποίησης
                        if ($emailSent || $smsSent) {
                            $this->recordNotification($pei['driver_id'], 'pei', "PEI-{$peiCategory}", $pei['expiry_date'], $daysBeforeExpiry);
                            $sentNotifications[] = [
                                'driver_id' => $pei['driver_id'],
                                'driver_name' => $pei['first_name'] . ' ' . $pei['last_name'],
                                'license_type' => "PEI-{$peiCategory}",
                                'expiry_date' => $pei['expiry_date'],
                                'days_before' => $daysBeforeExpiry,
                                'email_sent' => $emailSent,
                                'sms_sent' => $smsSent
                            ];
                        }
                    }
                }
            }
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο ΠΕΙ κατηγορίας {$peiCategory}: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο ΠΕΙ κατηγορίας {$peiCategory}: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος για πιστοποιητικά ADR που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkAdrCertificates() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        // Έλεγχος αν υπάρχει ο πίνακας
        if (!$this->tableExists('driver_adr_certificates')) {
            $this->log("Ο πίνακας driver_adr_certificates δεν υπάρχει", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Υπολογισμός της μέγιστης ημερομηνίας ελέγχου
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$this->maxCheckDays} days");
            $maxDateString = $maxDate->format('Y-m-d');
            
            // Εύρεση όλων των πιστοποιητικών ADR που λήγουν στο επόμενο διάστημα
            $sql = "
                SELECT 
                    d.id as driver_id, 
                    d.first_name, 
                    d.last_name, 
                    d.email, 
                    d.phone,
                    dac.adr_type, 
                    dac.expiry_date
                FROM 
                    drivers d
                JOIN 
                    driver_adr_certificates dac ON d.id = dac.driver_id
                WHERE 
                    dac.expiry_date BETWEEN CURDATE() AND :max_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'max_date' => $maxDateString
            ]);
            
            $expiringAdrCerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Βρέθηκαν " . count($expiringAdrCerts) . " πιστοποιητικά ADR που λήγουν στο επόμενο διάστημα", 'INFO');
            
            // Έλεγχος για κάθε πιστοποιητικό ADR
            foreach ($expiringAdrCerts as $cert) {
                $expiryDate = new DateTime($cert['expiry_date']);
                $interval = $currentDate->diff($expiryDate);
                $daysUntilExpiry = $interval->days;
                
                // Έλεγχος αν το πιστοποιητικό πρέπει να ειδοποιηθεί με βάση τις περιόδους
                foreach ($this->notificationPeriods['adr_certificate'] as $daysBeforeExpiry) {
                    if ($daysUntilExpiry == $daysBeforeExpiry) {
                        // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                        if ($this->hasNotificationBeenSent($cert['driver_id'], 'adr_certificate', $cert['adr_type'], $cert['expiry_date'], $daysBeforeExpiry)) {
                            $this->log("Η ειδοποίηση για τον οδηγό {$cert['driver_id']} και ADR τύπου {$cert['adr_type']} έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες", 'INFO');
                            continue;
                        }
                        
                        // Προετοιμασία του email
                        $subject = "Ειδοποίηση λήξης πιστοποιητικού ADR - {$cert['adr_type']}";
                        $message = $this->getEmailTemplate(
                            'adr_certificate',
                            [
                                'first_name' => $cert['first_name'],
                                'adr_type' => $cert['adr_type'],
                                'expiry_date' => $cert['expiry_date'],
                                'days_before_expiry' => $daysBeforeExpiry
                            ]
                        );
                        
                        // Αποστολή email
                        $emailSent = false;
                        if (!empty($cert['email'])) {
                            $emailSent = $this->emailService->send($cert['email'], $subject, $message);
                            $this->log("Αποστολή email στον οδηγό {$cert['driver_id']} για ADR τύπου {$cert['adr_type']}: " . ($emailSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        } else {
                            $this->log("Ο οδηγός {$cert['driver_id']} δεν έχει email", 'WARNING');
                        }
                        
                        // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                        $smsSent = false;
                        if ($daysBeforeExpiry <= 15 && !empty($cert['phone'])) {
                            $smsMessage = "DriveJob: Το πιστοποιητικό ADR τύπου {$cert['adr_type']} λήγει σε {$daysBeforeExpiry} " . 
                                        ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε το έγκαιρα.";
                            $smsSent = $this->smsService->sendSms($cert['phone'], $smsMessage);
                            $this->log("Αποστολή SMS στον οδηγό {$cert['driver_id']} για ADR τύπου {$cert['adr_type']}: " . ($smsSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        }
                        
                        // Καταγραφή της ειδοποίησης
                        if ($emailSent || $smsSent) {
                            $this->recordNotification($cert['driver_id'], 'adr_certificate', $cert['adr_type'], $cert['expiry_date'], $daysBeforeExpiry);
                            $sentNotifications[] = [
                                'driver_id' => $cert['driver_id'],
                                'driver_name' => $cert['first_name'] . ' ' . $cert['last_name'],
                                'license_type' => $cert['adr_type'],
                                'expiry_date' => $cert['expiry_date'],
                                'days_before' => $daysBeforeExpiry,
                                'email_sent' => $emailSent,
                                'sms_sent' => $smsSent
                            ];
                        }
                    }
                }
            }
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο πιστοποιητικών ADR: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο πιστοποιητικών ADR: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος για κάρτες ταχογράφου που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkTachographCards() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        // Έλεγχος αν υπάρχει ο πίνακας
        if (!$this->tableExists('driver_tachograph_cards')) {
            $this->log("Ο πίνακας driver_tachograph_cards δεν υπάρχει", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Υπολογισμός της μέγιστης ημερομηνίας ελέγχου
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$this->maxCheckDays} days");
            $maxDateString = $maxDate->format('Y-m-d');
            
            // Εύρεση όλων των καρτών ταχογράφου που λήγουν στο επόμενο διάστημα
            $sql = "
                SELECT 
                    d.id as driver_id, 
                    d.first_name, 
                    d.last_name, 
                    d.email, 
                    d.phone,
                    dtc.card_number, 
                    dtc.expiry_date
                FROM 
                    drivers d
                JOIN 
                    driver_tachograph_cards dtc ON d.id = dtc.driver_id
                WHERE 
                    dtc.expiry_date BETWEEN CURDATE() AND :max_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'max_date' => $maxDateString
            ]);
            
            $expiringCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Βρέθηκαν " . count($expiringCards) . " κάρτες ταχογράφου που λήγουν στο επόμενο διάστημα", 'INFO');
            
            // Έλεγχος για κάθε κάρτα ταχογράφου
            foreach ($expiringCards as $card) {
                $expiryDate = new DateTime($card['expiry_date']);
                $interval = $currentDate->diff($expiryDate);
                $daysUntilExpiry = $interval->days;
                
                // Έλεγχος αν η κάρτα πρέπει να ειδοποιηθεί με βάση τις περιόδους
                foreach ($this->notificationPeriods['tachograph_card'] as $daysBeforeExpiry) {
                    if ($daysUntilExpiry == $daysBeforeExpiry) {
                        // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                        if ($this->hasNotificationBeenSent($card['driver_id'], 'tachograph_card', 'card', $card['expiry_date'], $daysBeforeExpiry)) {
                            $this->log("Η ειδοποίηση για τον οδηγό {$card['driver_id']} και την κάρτα ταχογράφου έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες", 'INFO');
                            continue;
                        }
                        
                        // Προετοιμασία του email
                        $subject = "Ειδοποίηση λήξης κάρτας ψηφιακού ταχογράφου";
                        $message = $this->getEmailTemplate(
                            'tachograph_card',
                            [
                                'first_name' => $card['first_name'],
                                'card_number' => $card['card_number'],
                                'expiry_date' => $card['expiry_date'],
                                'days_before_expiry' => $daysBeforeExpiry
                            ]
                        );
                        
                        // Αποστολή email
                        $emailSent = false;
                        if (!empty($card['email'])) {
                            $emailSent = $this->emailService->send($card['email'], $subject, $message);
                            $this->log("Αποστολή email στον οδηγό {$card['driver_id']} για κάρτα ταχογράφου: " . ($emailSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        } else {
                            $this->log("Ο οδηγός {$card['driver_id']} δεν έχει email", 'WARNING');
                        }
                        
                        // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                        $smsSent = false;
                        if ($daysBeforeExpiry <= 15 && !empty($card['phone'])) {
                            $smsMessage = "DriveJob: Η κάρτα ψηφιακού ταχογράφου σας λήγει σε {$daysBeforeExpiry} " . 
                                        ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                            $smsSent = $this->smsService->sendSms($card['phone'], $smsMessage);
                            $this->log("Αποστολή SMS στον οδηγό {$card['driver_id']} για κάρτα ταχογράφου: " . ($smsSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        }
                        
                        // Καταγραφή της ειδοποίησης
                        if ($emailSent || $smsSent) {
                            $this->recordNotification($card['driver_id'], 'tachograph_card', 'card', $card['expiry_date'], $daysBeforeExpiry);
                            $sentNotifications[] = [
                                'driver_id' => $card['driver_id'],
                                'driver_name' => $card['first_name'] . ' ' . $card['last_name'],
                                'license_type' => 'Ταχογράφος',
                                'expiry_date' => $card['expiry_date'],
                                'days_before' => $daysBeforeExpiry,
                                'email_sent' => $emailSent,
                                'sms_sent' => $smsSent
                            ];
                        }
                    }
                }
            }
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο καρτών ταχογράφου: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο καρτών ταχογράφου: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος για άδειες χειριστή μηχανημάτων που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkOperatorLicenses() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        // Έλεγχος αν υπάρχει ο πίνακας
        if (!$this->tableExists('driver_operator_licenses')) {
            $this->log("Ο πίνακας driver_operator_licenses δεν υπάρχει", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Υπολογισμός της μέγιστης ημερομηνίας ελέγχου
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$this->maxCheckDays} days");
            $maxDateString = $maxDate->format('Y-m-d');
            
            // Εύρεση όλων των αδειών χειριστή που λήγουν στο επόμενο διάστημα
            $sql = "
                SELECT 
                    d.id as driver_id, 
                    d.first_name, 
                    d.last_name, 
                    d.email, 
                    d.phone,
                    dol.speciality, 
                    dol.license_number, 
                    dol.expiry_date
                FROM 
                    drivers d
                JOIN 
                    driver_operator_licenses dol ON d.id = dol.driver_id
                WHERE 
                    dol.expiry_date BETWEEN CURDATE() AND :max_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'max_date' => $maxDateString
            ]);
            
            $expiringLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Βρέθηκαν " . count($expiringLicenses) . " άδειες χειριστή που λήγουν στο επόμενο διάστημα", 'INFO');
            
            // Έλεγχος για κάθε άδεια χειριστή
            foreach ($expiringLicenses as $license) {
                $expiryDate = new DateTime($license['expiry_date']);
                $interval = $currentDate->diff($expiryDate);
                $daysUntilExpiry = $interval->days;
                
                // Έλεγχος αν η άδεια πρέπει να ειδοποιηθεί με βάση τις περιόδους
                foreach ($this->notificationPeriods['operator_license'] as $daysBeforeExpiry) {
                    if ($daysUntilExpiry == $daysBeforeExpiry) {
                        // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                        if ($this->hasNotificationBeenSent($license['driver_id'], 'operator_license', $license['speciality'], $license['expiry_date'], $daysBeforeExpiry)) {
                            $this->log("Η ειδοποίηση για τον οδηγό {$license['driver_id']} και την άδεια χειριστή ειδικότητας {$license['speciality']} έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες", 'INFO');
                            continue;
                        }
                        
                        // Μετατροπή του αριθμού ειδικότητας σε περιγραφή
                        $specialityName = $this->getOperatorSpecialityName($license['speciality']);
                        
                        // Προετοιμασία του email
                        $subject = "Ειδοποίηση λήξης άδειας χειριστή μηχανημάτων έργου";
                        $message = $this->getEmailTemplate(
                            'operator_license',
                            [
                                'first_name' => $license['first_name'],
                                'speciality' => $license['speciality'],
                                'speciality_name' => $specialityName,
                                'license_number' => $license['license_number'],
                                'expiry_date' => $license['expiry_date'],
                                'days_before_expiry' => $daysBeforeExpiry
                            ]
                        );
                        
                        // Αποστολή email
                        $emailSent = false;
                        if (!empty($license['email'])) {
                            $emailSent = $this->emailService->send($license['email'], $subject, $message);
                            $this->log("Αποστολή email στον οδηγό {$license['driver_id']} για άδεια χειριστή ειδικότητας {$license['speciality']}: " . ($emailSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        } else {
                            $this->log("Ο οδηγός {$license['driver_id']} δεν έχει email", 'WARNING');
                        }
                        
                        // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                        $smsSent = false;
                        if ($daysBeforeExpiry <= 15 && !empty($license['phone'])) {
                            $smsMessage = "DriveJob: Η άδεια χειριστή μηχανημάτων έργου {$specialityName} λήγει σε {$daysBeforeExpiry} " . 
                                        ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                            $smsSent = $this->smsService->sendSms($license['phone'], $smsMessage);
                            $this->log("Αποστολή SMS στον οδηγό {$license['driver_id']} για άδεια χειριστή ειδικότητας {$license['speciality']}: " . ($smsSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        }
                        
                        // Καταγραφή της ειδοποίησης
                        if ($emailSent || $smsSent) {
                            $this->recordNotification($license['driver_id'], 'operator_license', $license['speciality'], $license['expiry_date'], $daysBeforeExpiry);
                            $sentNotifications[] = [
                                'driver_id' => $license['driver_id'],
                                'driver_name' => $license['first_name'] . ' ' . $license['last_name'],
                                'license_type' => $specialityName,
                                'expiry_date' => $license['expiry_date'],
                                'days_before' => $daysBeforeExpiry,
                                'email_sent' => $emailSent,
                                'sms_sent' => $smsSent
                            ];
                        }
                    }
                }
            }
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο αδειών χειριστή: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο αδειών χειριστή: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος για ειδικές άδειες που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkSpecialLicenses() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        // Έλεγχος αν υπάρχει ο πίνακας
        if (!$this->tableExists('driver_special_licenses')) {
            $this->log("Ο πίνακας driver_special_licenses δεν υπάρχει", 'WARNING');
            return $sentNotifications;
        }
        
        try {
            // Υπολογισμός της μέγιστης ημερομηνίας ελέγχου
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$this->maxCheckDays} days");
            $maxDateString = $maxDate->format('Y-m-d');
            
            // Εύρεση όλων των ειδικών αδειών που λήγουν στο επόμενο διάστημα
            $sql = "
                SELECT 
                    d.id as driver_id, 
                    d.first_name,
                    d.last_name, 
                    d.email, 
                    d.phone,
                    dsl.license_type, 
                    dsl.license_number, 
                    dsl.expiry_date,
                    dsl.details
                FROM 
                    drivers d
                JOIN 
                    driver_special_licenses dsl ON d.id = dsl.driver_id
                WHERE 
                    dsl.expiry_date BETWEEN CURDATE() AND :max_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'max_date' => $maxDateString
            ]);
            
            $expiringLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Βρέθηκαν " . count($expiringLicenses) . " ειδικές άδειες που λήγουν στο επόμενο διάστημα", 'INFO');
            
            // Έλεγχος για κάθε ειδική άδεια
            foreach ($expiringLicenses as $license) {
                $expiryDate = new DateTime($license['expiry_date']);
                $interval = $currentDate->diff($expiryDate);
                $daysUntilExpiry = $interval->days;
                
                // Έλεγχος αν η άδεια πρέπει να ειδοποιηθεί με βάση τις περιόδους
                foreach ($this->notificationPeriods['special_license'] as $daysBeforeExpiry) {
                    if ($daysUntilExpiry == $daysBeforeExpiry) {
                        // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                        if ($this->hasNotificationBeenSent($license['driver_id'], 'special_license', $license['license_type'], $license['expiry_date'], $daysBeforeExpiry)) {
                            $this->log("Η ειδοποίηση για τον οδηγό {$license['driver_id']} και την ειδική άδεια {$license['license_type']} έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες", 'INFO');
                            continue;
                        }
                        
                        // Προετοιμασία του email
                        $subject = "Ειδοποίηση λήξης ειδικής άδειας - {$license['license_type']}";
                        $message = $this->getEmailTemplate(
                            'special_license',
                            [
                                'first_name' => $license['first_name'],
                                'license_type' => $license['license_type'],
                                'license_number' => $license['license_number'],
                                'expiry_date' => $license['expiry_date'],
                                'days_before_expiry' => $daysBeforeExpiry,
                                'details' => $license['details']
                            ]
                        );
                        
                        // Αποστολή email
                        $emailSent = false;
                        if (!empty($license['email'])) {
                            $emailSent = $this->emailService->send($license['email'], $subject, $message);
                            $this->log("Αποστολή email στον οδηγό {$license['driver_id']} για ειδική άδεια {$license['license_type']}: " . ($emailSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        } else {
                            $this->log("Ο οδηγός {$license['driver_id']} δεν έχει email", 'WARNING');
                        }
                        
                        // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                        $smsSent = false;
                        if ($daysBeforeExpiry <= 15 && !empty($license['phone'])) {
                            $smsMessage = "DriveJob: Η ειδική άδεια {$license['license_type']} λήγει σε {$daysBeforeExpiry} " . 
                                        ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                            $smsSent = $this->smsService->sendSms($license['phone'], $smsMessage);
                            $this->log("Αποστολή SMS στον οδηγό {$license['driver_id']} για ειδική άδεια {$license['license_type']}: " . ($smsSent ? "Επιτυχής" : "Αποτυχία"), 'INFO');
                        }
                        
                        // Καταγραφή της ειδοποίησης
                        if ($emailSent || $smsSent) {
                            $this->recordNotification($license['driver_id'], 'special_license', $license['license_type'], $license['expiry_date'], $daysBeforeExpiry);
                            $sentNotifications[] = [
                                'driver_id' => $license['driver_id'],
                                'driver_name' => $license['first_name'] . ' ' . $license['last_name'],
                                'license_type' => $license['license_type'],
                                'expiry_date' => $license['expiry_date'],
                                'days_before' => $daysBeforeExpiry,
                                'email_sent' => $emailSent,
                                'sms_sent' => $smsSent
                            ];
                        }
                    }
                }
            }
            
            return $sentNotifications;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο ειδικών αδειών: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο ειδικών αδειών: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            $this->log("Ίχνος στοίβας: " . $e->getTraceAsString(), 'DEBUG');
            return $sentNotifications;
        }
    }
    
    /**
     * Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseCategory Κατηγορία άδειας
     * @param string $licenseType Τύπος άδειας
     * @param string $expiryDate Ημερομηνία λήξης
     * @param int $daysBefore Ημέρες πριν τη λήξη
     * @return bool True αν έχει ήδη σταλεί ειδοποίηση
     */
    private function hasNotificationBeenSent($driverId, $licenseCategory, $licenseType, $expiryDate, $daysBefore) {
        try {
            // Έλεγχος αν υπάρχει ο πίνακας
            if (!$this->tableExists('license_expiry_notifications')) {
                $this->log("Ο πίνακας license_expiry_notifications δεν υπάρχει", 'WARNING');
                return false;
            }
            
            $sql = "
                SELECT COUNT(*) as count
                FROM license_expiry_notifications
                WHERE driver_id = :driver_id
                  AND license_category = :license_category
                  AND license_type = :license_type
                  AND expiry_date = :expiry_date
                  AND days_before = :days_before
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'driver_id' => $driverId,
                'license_category' => $licenseCategory,
                'license_type' => $licenseType,
                'expiry_date' => $expiryDate,
                'days_before' => $daysBefore
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο προηγούμενων ειδοποιήσεων: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            // Σε περίπτωση σφάλματος, επιστρέφουμε false για να επιτρέψουμε την αποστολή
            return false;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο προηγούμενων ειδοποιήσεων: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            // Σε περίπτωση σφάλματος, επιστρέφουμε false για να επιτρέψουμε την αποστολή
            return false;
        }
    }
    
    /**
     * Καταγραφή της ειδοποίησης στη βάση δεδομένων
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseCategory Κατηγορία άδειας
     * @param string $licenseType Τύπος άδειας
     * @param string $expiryDate Ημερομηνία λήξης
     * @param int $daysBefore Ημέρες πριν τη λήξη
     * @return bool Επιτυχία/αποτυχία
     */
    private function recordNotification($driverId, $licenseCategory, $licenseType, $expiryDate, $daysBefore) {
        try {
            // Έλεγχος αν υπάρχει ο πίνακας
            if (!$this->tableExists('license_expiry_notifications')) {
                // Δημιουργία του πίνακα αν δεν υπάρχει
                $this->createNotificationsTable();
            }
            
            $sql = "
                INSERT INTO license_expiry_notifications
                (driver_id, license_category, license_type, expiry_date, days_before, sent_at)
                VALUES (:driver_id, :license_category, :license_type, :expiry_date, :days_before, NOW())
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                'driver_id' => $driverId,
                'license_category' => $licenseCategory,
                'license_type' => $licenseType,
                'expiry_date' => $expiryDate,
                'days_before' => $daysBefore
            ]);
            
            if ($result) {
                $this->log("Καταγραφή ειδοποίησης για τον οδηγό {$driverId}, κατηγορία {$licenseCategory}, τύπο {$licenseType}", 'INFO');
            } else {
                $this->log("Αποτυχία καταγραφής ειδοποίησης για τον οδηγό {$driverId}", 'WARNING');
            }
            
            return $result;
        } catch (PDOException $e) {
            // Έλεγχος για duplicate key error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || $e->getCode() == 23000) {
                $this->log("Η ειδοποίηση για τον οδηγό {$driverId}, κατηγορία {$licenseCategory}, τύπο {$licenseType} υπάρχει ήδη", 'WARNING');
                return true; // Θεωρούμε επιτυχία καθώς η εγγραφή υπάρχει ήδη
            }
            
            $this->log("Σφάλμα PDO κατά την καταγραφή ειδοποίησης: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            return false;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά την καταγραφή ειδοποίησης: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            return false;
        }
    }
    
    /**
     * Δημιουργία του πίνακα ειδοποιήσεων
     * 
     * @return bool Επιτυχία/αποτυχία
     */
    private function createNotificationsTable() {
        try {
            $sql = "
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
            
            $this->pdo->exec($sql);
            $this->log("Ο πίνακας license_expiry_notifications δημιουργήθηκε επιτυχώς", 'INFO');
            return true;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τη δημιουργία του πίνακα ειδοποιήσεων: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            return false;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τη δημιουργία του πίνακα ειδοποιήσεων: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            return false;
        }
    }
    
    /**
     * Παίρνει το κατάλληλο πρότυπο email ανάλογα με τον τύπο άδειας
     * 
     * @param string $licenseCategory Κατηγορία άδειας
     * @param array $data Δεδομένα για το πρότυπο
     * @return string HTML του email
     */
    private function getEmailTemplate($licenseCategory, $data) {
        // Προσθέτουμε βασικά δεδομένα στο template
        $data['base_url'] = isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] : 'https://drivejob.gr';
        $data['year'] = date('Y');
        
        // Έλεγχος αν υπάρχει ειδικό πρότυπο για την κατηγορία
        $templateFile = $this->templatesPath . "license_expiry_{$licenseCategory}.php";
        
        // Αν δεν υπάρχει το συγκεκριμένο πρότυπο, χρησιμοποιούμε το γενικό
        if (!file_exists($templateFile)) {
            // Δημιουργία του ειδικού προτύπου αν δεν υπάρχει
            $this->createTemplateFile($licenseCategory);
            
            // Αν ακόμα δεν υπάρχει, χρησιμοποιούμε το γενικό
            if (!file_exists($templateFile)) {
                $templateFile = $this->templatesPath . "license_expiry_general.php";
                
                // Αν δεν υπάρχει ούτε το γενικό, το δημιουργούμε
                if (!file_exists($templateFile)) {
                    $this->createTemplateFile('general');
                }
            }
        }
        
        // Αν υπάρχει το πρότυπο, το φορτώνουμε και αντικαθιστούμε τις μεταβλητές
        if (file_exists($templateFile)) {
            ob_start();
            extract($data);
            include($templateFile);
            $html = ob_get_clean();
            return $html;
        }
        
        // Αν όλα αποτύχουν, χρησιμοποιούμε το προεπιλεγμένο πρότυπο
        return $this->generateLicenseExpiryEmailTemplate(
            $data['first_name'],
            $this->getLicenseCategoryDesc($licenseCategory),
            $data['license_type'] ?? ($data['adr_type'] ?? ($data['pei_category'] ?? 'Γενική')),
            $data['expiry_date'],
            $data['days_before_expiry']
        );
    }
    
    /**
     * Δημιουργεί ένα αρχείο προτύπου email αν δεν υπάρχει
     * 
     * @param string $licenseCategory Κατηγορία άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    private function createTemplateFile($licenseCategory) {
        $templateFile = $this->templatesPath . "license_expiry_{$licenseCategory}.php";
        
        // Αν υπάρχει ήδη το αρχείο, δεν κάνουμε τίποτα
        if (file_exists($templateFile)) {
            return true;
        }
        
        try {
            // Δημιουργία του φακέλου αν δεν υπάρχει
            if (!is_dir($this->templatesPath)) {
                mkdir($this->templatesPath, 0755, true);
            }
            
            // Περιεχόμενο του προτύπου ανάλογα με την κατηγορία
            $content = $this->getTemplateContent($licenseCategory);
            
            // Αποθήκευση του αρχείου
            file_put_contents($templateFile, $content);
            $this->log("Δημιουργήθηκε το πρότυπο email για την κατηγορία {$licenseCategory}: {$templateFile}", 'INFO');
            
            return true;
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά τη δημιουργία του προτύπου email για την κατηγορία {$licenseCategory}: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Επιστρέφει το περιεχόμενο προτύπου για μια συγκεκριμένη κατηγορία άδειας
     * 
     * @param string $licenseCategory Κατηγορία άδειας
     * @return string Περιεχόμενο του προτύπου
     */
    private function getTemplateContent($licenseCategory) {
        switch ($licenseCategory) {
            case 'driving_license':
                return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Άδειας Οδήγησης - DriveJob</title>
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
        <h1>DriveJob - Ειδοποίηση Λήξης Άδειας Οδήγησης</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι η <strong>άδεια οδήγησής</strong> σας <strong>κατηγορίας <?php echo $license_type; ?></strong> 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Άδειας</h3>
            <p><strong>Τύπος:</strong> Άδεια οδήγησης κατηγορίας <?php echo $license_type; ?><br>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?></p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδειά σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style='margin-top: 20px;'>Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
            
            case 'pei':
                return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης ΠΕΙ - DriveJob</title>
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
        <h1>DriveJob - Ειδοποίηση Λήξης ΠΕΙ</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι το <strong>Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)</strong> σας <strong>κατηγορίας <?php echo $pei_category; ?></strong> 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Πιστοποιητικού</h3>
            <p><strong>Τύπος:</strong> ΠΕΙ κατηγορίας <?php echo $pei_category; ?><br>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?></p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα το ΠΕΙ σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα. Η ανανέωση του ΠΕΙ μπορεί να γίνει κατά τους 12 μήνες πριν τη λήξη του.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
            
            case 'adr_certificate':
                return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Πιστοποιητικού ADR - DriveJob</title>
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
        <h1>DriveJob - Ειδοποίηση Λήξης Πιστοποιητικού ADR</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι το <strong>Πιστοποιητικό ADR</strong> σας <strong>τύπου <?php echo $adr_type; ?></strong> 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Πιστοποιητικού</h3>
            <p><strong>Τύπος:</strong> ADR τύπου <?php echo $adr_type; ?><br>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?></p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα το πιστοποιητικό ADR σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα. Το πιστοποιητικό ADR ανανεώνεται κάθε 5 έτη και η ανανέωση μπορεί 
        να γίνει κατά τον τελευταίο χρόνο πριν τη λήξη του.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
            
            case 'tachograph_card':
                return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Κάρτας Ψηφιακού Ταχογράφου - DriveJob</title>
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
        <h1>DriveJob - Ειδοποίηση Λήξης Κάρτας Ψηφιακού Ταχογράφου</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι η <strong>κάρτα ψηφιακού ταχογράφου</strong> σας με αριθμό <strong><?php echo $card_number; ?></strong> 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Κάρτας</h3>
            <p><strong>Αριθμός:</strong> <?php echo $card_number; ?><br>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?></p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την κάρτα ψηφιακού ταχογράφου σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα. Η κάρτα ταχογράφου έχει ισχύ 5 έτη και η ανανέωση μπορεί 
        να γίνει το νωρίτερο 2 μήνες πριν τη λήξη της.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
            
            case 'operator_license':
                return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Άδειας Χειριστή Μηχανημάτων Έργου - DriveJob</title>
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
        <h1>DriveJob - Ειδοποίηση Λήξης Άδειας Χειριστή Μηχανημάτων Έργου</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι η <strong>άδεια χειριστή μηχανημάτων έργου</strong> σας <strong>ειδικότητας <?php echo $speciality; ?></strong> 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Άδειας</h3>
            <p><strong>Τύπος:</strong> Άδεια χειριστή μηχανημάτων έργου<br>
            <strong>Ειδικότητα:</strong> <?php echo $speciality; ?> - <?php echo $speciality_name; ?><br>
            <strong>Αριθμός άδειας:</strong> <?php echo $license_number; ?><br>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?></p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδεια χειριστή μηχανημάτων έργου σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα. Οι άδειες χειριστή μηχανημάτων έργου είναι αορίστου διάρκειας και θεωρούνται κάθε οκτώ έτη. 
        Με την παράγραφο 1 του άρθρου 145 Νόμος 4887 η προθεσμία θεώρησής των αδειών χειριστή μηχανημάτων έργου, 
        μετά την παρέλευση οκτώ (8) ετών, παρατείνεται κατά τρία (3) έτη και άρα η θεώρηση πραγματοποιείτε στα έντεκα (11) έτη.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
            
            case 'special_license':
                return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ειδοποίηση Λήξης Ειδικής Άδειας - DriveJob</title>
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
        <h1>DriveJob - Ειδοποίηση Λήξης Ειδικής Άδειας</h1>
    </div>
    <div class="content">
        <p>Αγαπητέ/ή <?php echo $first_name; ?>,</p>
        
        <p>Σας ενημερώνουμε ότι η <strong>ειδική άδεια <?php echo $license_type; ?></strong> σας 
        πρόκειται να λήξει σε <span class="warning"><?php echo $days_before_expiry == 1 ? "μία ημέρα" : $days_before_expiry . " ημέρες"; ?></span>, 
        στις <strong><?php echo date("d/m/Y", strtotime($expiry_date)); ?></strong>.</p>
        
        <div class="info-box">
            <h3>Στοιχεία Άδειας</h3>
            <p><strong>Τύπος:</strong> <?php echo $license_type; ?><br>
            <?php if(!empty($license_number)): ?>
            <strong>Αριθμός άδειας:</strong> <?php echo $license_number; ?><br>
            <?php endif; ?>
            <strong>Ημερομηνία Λήξης:</strong> <?php echo date("d/m/Y", strtotime($expiry_date)); ?><br>
            <strong>Υπολειπόμενες ημέρες:</strong> <?php echo $days_before_expiry; ?><br>
            <?php if(!empty($details)): ?>
            <strong>Λεπτομέρειες:</strong> <?php echo $details; ?><br>
            <?php endif; ?>
            </p>
        </div>
        
        <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδειά σας για να αποφύγετε τυχόν προβλήματα 
        στην επαγγελματική σας δραστηριότητα.</p>
        
        <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
        <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
        
        <p>Με εκτίμηση,<br>
        Η ομάδα του DriveJob</p>
    </div>
    <div class="footer">
        <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
        <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
        <p>&copy; <?php echo $year; ?> DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
    </div>
</body>
</html>';
            
            default:
                // Γενικό πρότυπο
                return '<!DOCTYPE html>
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
        
        <a href="<?php echo $base_url; ?>/drivers/edit-profile" class="button">Ενημέρωση Προφίλ</a>
        
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
        }
    }
    
    /**
     * Παράγει το πρότυπο email για την ειδοποίηση λήξης άδειας
     * 
     * @param string $firstName Όνομα οδηγού
     * @param string $licenseCategory Κατηγορία άδειας (π.χ. "άδεια οδήγησης", "πιστοποιητικό ADR")
     * @param string $licenseType Τύπος άδειας (π.χ. "C", "CE", "Π5")
     * @param string $expiryDate Ημερομηνία λήξης
     * @param int $daysBeforeExpiry Ημέρες πριν τη λήξη
     * @return string HTML μήνυμα email
     */
    private function generateLicenseExpiryEmailTemplate($firstName, $licenseCategory, $licenseType, $expiryDate, $daysBeforeExpiry) {
        // Μετατροπή της ημερομηνίας σε αναγνώσιμη μορφή
        $expiryDateObj = new DateTime($expiryDate);
        $formattedDate = $expiryDateObj->format('d/m/Y');
        
        // Επιλογή του κατάλληλου κειμένου για τις ημέρες
        $daysText = ($daysBeforeExpiry == 1) ? 'μία ημέρα' : $daysBeforeExpiry . ' ημέρες';
        
        // Δημιουργία του HTML μηνύματος
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
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
            <div class='header'>
                <h1>DriveJob - Ειδοποίηση Λήξης Άδειας</h1>
            </div>
            <div class='content'>
                <p>Αγαπητέ/ή {$firstName},</p>
                
                <p>Σας ενημερώνουμε ότι η <strong>{$licenseCategory}</strong> σας <strong>{$licenseType}</strong> 
                πρόκειται να λήξει σε <span class='warning'>{$daysText}</span>, στις <strong>{$formattedDate}</strong>.</p>
                
                <div class='info-box'>
                    <h3>Στοιχεία Άδειας</h3>
                    <p><strong>Τύπος:</strong> {$licenseCategory} {$licenseType}<br>
                    <strong>Ημερομηνία Λήξης:</strong> {$formattedDate}<br>
                    <strong>Υπολειπόμενες ημέρες:</strong> {$daysBeforeExpiry}</p>
                </div>
                
                <p>Παρακαλούμε φροντίστε να ανανεώσετε έγκαιρα την άδειά σας για να αποφύγετε τυχόν προβλήματα 
                στην επαγγελματική σας δραστηριότητα.</p>
                
                <p>Για να ενημερώσετε τα στοιχεία σας στο προφίλ σας στο DriveJob, πατήστε το παρακάτω κουμπί:</p>
                
                   <a href=\"https://drivejob.gr/drivers/edit-profile\" class=\"button\">Ενημέρωση Προφίλ</a>
                
                <p style="margin-top: 20px;">Σας ευχαριστούμε που χρησιμοποιείτε την πλατφόρμα DriveJob.</p>
                
                <p>Με εκτίμηση,<br>
                Η ομάδα του DriveJob</p>
            </div>
            <div class="footer">
                <p>Αυτό το email είναι αυτοματοποιημένο. Παρακαλούμε μην απαντήσετε σε αυτό το μήνυμα.</p>
                <p>Αν έχετε οποιαδήποτε απορία, επικοινωνήστε μαζί μας στο <a href="mailto:info@drivejob.gr">info@drivejob.gr</a>.</p>
                <p>&copy; " . date('Y') . " DriveJob. Με επιφύλαξη παντός δικαιώματος.</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Επιστρέφει την περιγραφή της κατηγορίας άδειας
     * 
     * @param string $licenseCategory Κωδικός κατηγορίας άδειας
     * @return string Περιγραφή κατηγορίας
     */
    private function getLicenseCategoryDesc($licenseCategory) {
        $categories = [
            'driving_license' => 'άδεια οδήγησης',
            'pei' => 'Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)',
            'adr_certificate' => 'πιστοποιητικό ADR',
            'tachograph_card' => 'κάρτα ψηφιακού ταχογράφου',
            'operator_license' => 'άδεια χειριστή μηχανημάτων έργου',
            'special_license' => 'ειδική άδεια'
        ];
        
        return isset($categories[$licenseCategory]) ? $categories[$licenseCategory] : 'άδεια';
    }
    
    /**
     * Επιστρέφει την περιγραφή της ειδικότητας χειριστή με βάση τον αριθμό της
     * 
     * @param string $specialityId Αριθμός ειδικότητας
     * @return string Περιγραφή ειδικότητας
     */
    private function getOperatorSpecialityName($specialityId) {
        $specialities = [
            '1' => 'Εργασίες εκσκαφής και χωματουργικές',
            '2' => 'Εργασίες ανύψωσης και μεταφοράς φορτίων',
            '3' => 'Εργασίες οδοστρωσίας',
            '4' => 'Εργασίες εξυπηρέτησης οδών και αεροδρομίων',
            '5' => 'Εργασίες υπόγειων έργων και μεταλλείων',
            '6' => 'Εργασίες έλξης',
            '7' => 'Εργασίες διάτρησης και κοπής εδαφών',
            '8' => 'Ειδικές εργασίες ανύψωσης'
        ];
        
        return isset($specialities[$specialityId]) 
            ? $specialities[$specialityId] 
            : "Ειδικότητα {$specialityId}";
    }
    
    /**
     * Έλεγχος αν υπάρχει ο πίνακας
     * 
     * @param string $tableName Όνομα πίνακα
     * @return bool
     */
    private function tableExists($tableName) {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο του πίνακα {$tableName}: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            return false;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο του πίνακα {$tableName}: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            return false;
        }
    }
    
    /**
     * Έλεγχος αν υπάρχει η στήλη στον πίνακα
     * 
     * @param string $tableName Όνομα πίνακα
     * @param string $columnName Όνομα στήλης
     * @return bool
     */
    private function columnExists($tableName, $columnName) {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
            $stmt->execute([$columnName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->log("Σφάλμα PDO κατά τον έλεγχο της στήλης {$columnName} στον πίνακα {$tableName}: " . $e->getMessage() . " (Κωδικός: " . $e->getCode() . ")", 'ERROR');
            $this->log("SQL State: " . $e->errorInfo[0] . ", Driver error code: " . (isset($e->errorInfo[1]) ? $e->errorInfo[1] : 'N/A'), 'ERROR');
            return false;
        } catch (Exception $e) {
            $this->log("Γενικό σφάλμα κατά τον έλεγχο της στήλης {$columnName} στον πίνακα {$tableName}: " . $e->getMessage() . " (Τύπος: " . get_class($e) . ")", 'ERROR');
            return false;
        }
    }
    
    /**
     * Καταγραφή μηνύματος χρησιμοποιώντας το Logger αν είναι διαθέσιμο, διαφορετικά χρησιμοποιεί την error_log
     * 
     * @param string $message Το μήνυμα προς καταγραφή
     * @param string $level Το επίπεδο καταγραφής (INFO, WARNING, ERROR, DEBUG)
     */
    private function log($message, $level = 'INFO') {
        // Έλεγχος αν υπάρχει η κλάση Logger και αν είναι διαθέσιμη
        if (class_exists('Drivejob\Core\Logger') && method_exists('Drivejob\Core\Logger', 'log')) {
            // Χρήση της κλάσης Logger με το σωστό επίπεδο καταγραφής
            \Drivejob\Core\Logger::log($level, $message, 'LicenseExpiryNotification');
        } else {
            // Εφεδρική καταγραφή με την error_log με σαφή ένδειξη του επιπέδου
            error_log("[" . date('Y-m-d H:i:s') . "] {$level} [LicenseExpiryNotification]: {$message}");
        }
    }
}