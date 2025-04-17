<?php
namespace Drivejob\Services;

use PDO;
use DateTime;
use Exception;

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
     * Constructor
     * 
     * @param PDO $pdo Σύνδεση με τη βάση δεδομένων
     * @param EmailService $emailService Υπηρεσία αποστολής email
     * @param SmsService $smsService Υπηρεσία αποστολής SMS
     */
    public function __construct(PDO $pdo, EmailService $emailService, SmsService $smsService) {
        $this->pdo = $pdo;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }
    
    /**
     * Έλεγχος για άδειες που λήγουν και αποστολή ειδοποιήσεων
     * 
     * @return array Αποτελέσματα ειδοποιήσεων ανά κατηγορία
     */
    public function checkAndSendExpiryNotifications() {
        try {
            $results = [];
            
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
        } catch (Exception $e) {
            error_log('Σφάλμα κατά τον έλεγχο αδειών: ' . $e->getMessage());
            throw $e;
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
        
        foreach ($this->notificationPeriods['driving_license'] as $daysBeforeExpiry) {
            $targetDate = clone $currentDate;
            $targetDate->modify("+{$daysBeforeExpiry} days");
            
            // Εύρεση αδειών που λήγουν
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
                    dl.expiry_date = :target_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_date' => $targetDate->format('Y-m-d')
            ]);
            
            $expiringLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiringLicenses as $license) {
                // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                if ($this->hasNotificationBeenSent($license['driver_id'], 'driving_license', $license['license_type'], $license['expiry_date'], $daysBeforeExpiry)) {
                    continue;
                }
                
                // Προετοιμασία του email
                $subject = "Ειδοποίηση λήξης άδειας οδήγησης - {$license['license_type']}";
                $message = $this->generateLicenseExpiryEmailTemplate(
                    $license['first_name'],
                    'άδεια οδήγησης',
                    $license['license_type'],
                    $license['expiry_date'],
                    $daysBeforeExpiry
                );
                
                // Αποστολή email
                $emailSent = $this->emailService->send($license['email'], $subject, $message);
                
                // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                $smsSent = false;
                if ($daysBeforeExpiry <= 15 && !empty($license['phone'])) {
                    $smsMessage = "DriveJob: Η άδεια οδήγησης κατηγορίας {$license['license_type']} λήγει σε {$daysBeforeExpiry} " . 
                                ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                    $smsSent = $this->smsService->send($license['phone'], $smsMessage);
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
        
        return $sentNotifications;
    }
    
    /**
     * Έλεγχος για ΠΕΙ που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkPeiCertificates() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
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
        
        foreach ($this->notificationPeriods['pei'] as $daysBeforeExpiry) {
            $targetDate = clone $currentDate;
            $targetDate->modify("+{$daysBeforeExpiry} days");
            
            // Εύρεση ΠΕΙ που λήγουν
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
                    dl.{$expiryDateField} = :target_date
                    AND dl.has_pei = 1
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_date' => $targetDate->format('Y-m-d')
            ]);
            
            $expiringPeis = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiringPeis as $pei) {
                // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                if ($this->hasNotificationBeenSent($pei['driver_id'], 'pei', "PEI-{$peiCategory}", $pei['expiry_date'], $daysBeforeExpiry)) {
                    continue;
                }
                
                // Προετοιμασία του email
                $subject = "Ειδοποίηση λήξης ΠΕΙ κατηγορίας {$peiCategory}";
                $message = $this->generateLicenseExpiryEmailTemplate(
                    $pei['first_name'],
                    'Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)',
                    "Κατηγορία {$peiCategory}",
                    $pei['expiry_date'],
                    $daysBeforeExpiry
                );
                
                // Αποστολή email
                $emailSent = $this->emailService->send($pei['email'], $subject, $message);
                
                // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                $smsSent = false;
                if ($daysBeforeExpiry <= 15 && !empty($pei['phone'])) {
                    $smsMessage = "DriveJob: Το ΠΕΙ κατηγορίας {$peiCategory} λήγει σε {$daysBeforeExpiry} " . 
                                ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε το έγκαιρα.";
                    $smsSent = $this->smsService->send($pei['phone'], $smsMessage);
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
        
        return $sentNotifications;
    }
    
    /**
     * Έλεγχος για πιστοποιητικά ADR που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkAdrCertificates() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        foreach ($this->notificationPeriods['adr_certificate'] as $daysBeforeExpiry) {
            $targetDate = clone $currentDate;
            $targetDate->modify("+{$daysBeforeExpiry} days");
            
            // Εύρεση πιστοποιητικών ADR που λήγουν
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
                    dac.expiry_date = :target_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_date' => $targetDate->format('Y-m-d')
            ]);
            
            $expiringAdrCerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiringAdrCerts as $cert) {
                // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                if ($this->hasNotificationBeenSent($cert['driver_id'], 'adr_certificate', $cert['adr_type'], $cert['expiry_date'], $daysBeforeExpiry)) {
                    continue;
                }
                
                // Προετοιμασία του email
                $subject = "Ειδοποίηση λήξης πιστοποιητικού ADR - {$cert['adr_type']}";
                $message = $this->generateLicenseExpiryEmailTemplate(
                    $cert['first_name'],
                    'πιστοποιητικό ADR',
                    $cert['adr_type'],
                    $cert['expiry_date'],
                    $daysBeforeExpiry
                );
                
                // Αποστολή email
                $emailSent = $this->emailService->send($cert['email'], $subject, $message);
                
                // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                $smsSent = false;
                if ($daysBeforeExpiry <= 15 && !empty($cert['phone'])) {
                    $smsMessage = "DriveJob: Το πιστοποιητικό ADR τύπου {$cert['adr_type']} λήγει σε {$daysBeforeExpiry} " . 
                                ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε το έγκαιρα.";
                    $smsSent = $this->smsService->send($cert['phone'], $smsMessage);
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
        
        return $sentNotifications;
    }
    
    /**
     * Έλεγχος για κάρτες ταχογράφου που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkTachographCards() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        foreach ($this->notificationPeriods['tachograph_card'] as $daysBeforeExpiry) {
            $targetDate = clone $currentDate;
            $targetDate->modify("+{$daysBeforeExpiry} days");
            
            // Εύρεση καρτών ταχογράφου που λήγουν
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
                    dtc.expiry_date = :target_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_date' => $targetDate->format('Y-m-d')
            ]);
            
            $expiringCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiringCards as $card) {
                // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                if ($this->hasNotificationBeenSent($card['driver_id'], 'tachograph_card', 'card', $card['expiry_date'], $daysBeforeExpiry)) {
                    continue;
                }
                
                // Προετοιμασία του email
                $subject = "Ειδοποίηση λήξης κάρτας ψηφιακού ταχογράφου";
                $message = $this->generateLicenseExpiryEmailTemplate(
                    $card['first_name'],
                    'κάρτα ψηφιακού ταχογράφου',
                    $card['card_number'],
                    $card['expiry_date'],
                    $daysBeforeExpiry
                );
                
                // Αποστολή email
                $emailSent = $this->emailService->send($card['email'], $subject, $message);
                
                // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                $smsSent = false;
                if ($daysBeforeExpiry <= 15 && !empty($card['phone'])) {
                    $smsMessage = "DriveJob: Η κάρτα ψηφιακού ταχογράφου σας λήγει σε {$daysBeforeExpiry} " . 
                                ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                    $smsSent = $this->smsService->send($card['phone'], $smsMessage);
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
        
        return $sentNotifications;
    }
    
    /**
     * Έλεγχος για άδειες χειριστή μηχανημάτων που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkOperatorLicenses() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        foreach ($this->notificationPeriods['operator_license'] as $daysBeforeExpiry) {
            $targetDate = clone $currentDate;
            $targetDate->modify("+{$daysBeforeExpiry} days");
            
            // Εύρεση αδειών χειριστή που λήγουν
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
                    dol.expiry_date = :target_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_date' => $targetDate->format('Y-m-d')
            ]);
            
            $expiringLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiringLicenses as $license) {
                // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                if ($this->hasNotificationBeenSent($license['driver_id'], 'operator_license', $license['speciality'], $license['expiry_date'], $daysBeforeExpiry)) {
                    continue;
                }
                
                // Μετατροπή του αριθμού ειδικότητας σε περιγραφή
                $specialityName = $this->getOperatorSpecialityName($license['speciality']);
                
                // Προετοιμασία του email
                $subject = "Ειδοποίηση λήξης άδειας χειριστή μηχανημάτων έργου";
                $message = $this->generateLicenseExpiryEmailTemplate(
                    $license['first_name'],
                    'άδεια χειριστή μηχανημάτων έργου',
                    $specialityName,
                    $license['expiry_date'],
                    $daysBeforeExpiry
                );
                
                // Αποστολή email
                $emailSent = $this->emailService->send($license['email'], $subject, $message);
                
                // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                $smsSent = false;
                if ($daysBeforeExpiry <= 15 && !empty($license['phone'])) {
                    $smsMessage = "DriveJob: Η άδεια χειριστή μηχανημάτων έργου {$specialityName} λήγει σε {$daysBeforeExpiry} " . 
                                ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                    $smsSent = $this->smsService->send($license['phone'], $smsMessage);
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
        
        return $sentNotifications;
    }
    
    /**
     * Έλεγχος για ειδικές άδειες που λήγουν
     * 
     * @return array Λίστα ειδοποιήσεων που στάλθηκαν
     */
    private function checkSpecialLicenses() {
        $sentNotifications = [];
        $currentDate = new DateTime();
        
        foreach ($this->notificationPeriods['special_license'] as $daysBeforeExpiry) {
            $targetDate = clone $currentDate;
            $targetDate->modify("+{$daysBeforeExpiry} days");
            
            // Εύρεση ειδικών αδειών που λήγουν
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
                    dsl.expiry_date = :target_date
                    AND d.is_verified = 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'target_date' => $targetDate->format('Y-m-d')
            ]);
            
            $expiringLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($expiringLicenses as $license) {
                // Έλεγχος αν έχει ήδη σταλεί ειδοποίηση για τη συγκεκριμένη περίοδο
                if ($this->hasNotificationBeenSent($license['driver_id'], 'special_license', $license['license_type'], $license['expiry_date'], $daysBeforeExpiry)) {
                    continue;
                }
                
                // Προετοιμασία του email
                $subject = "Ειδοποίηση λήξης ειδικής άδειας - {$license['license_type']}";
                $message = $this->generateLicenseExpiryEmailTemplate(
                    $license['first_name'],
                    'ειδική άδεια',
                    $license['license_type'],
                    $license['expiry_date'],
                    $daysBeforeExpiry
                );
                
                // Αποστολή email
                $emailSent = $this->emailService->send($license['email'], $subject, $message);
                
                // Αποστολή SMS αν είναι λιγότερο από 15 ημέρες πριν τη λήξη
                $smsSent = false;
                if ($daysBeforeExpiry <= 15 && !empty($license['phone'])) {
                    $smsMessage = "DriveJob: Η ειδική άδεια {$license['license_type']} λήγει σε {$daysBeforeExpiry} " . 
                                ($daysBeforeExpiry == 1 ? "ημέρα" : "ημέρες") . ". Παρακαλούμε ανανεώστε την έγκαιρα.";
                    $smsSent = $this->smsService->send($license['phone'], $smsMessage);
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
        
        return $sentNotifications;
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
            $sql = "
                INSERT INTO license_expiry_notifications
                (driver_id, license_category, license_type, expiry_date, days_before, sent_at)
                VALUES (:driver_id, :license_category, :license_type, :expiry_date, :days_before, NOW())
            ";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'driver_id' => $driverId,
                'license_category' => $licenseCategory,
                'license_type' => $licenseType,
                'expiry_date' => $expiryDate,
                'days_before' => $daysBefore
            ]);
        } catch (Exception $e) {
            error_log('Σφάλμα κατά την καταγραφή ειδοποίησης: ' . $e->getMessage());
            return false;
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
                
                <a href='https://drivejob.gr/drivers/edit-profile' class='button'>Ενημέρωση Προφίλ</a>
                
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
}