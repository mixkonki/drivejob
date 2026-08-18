<?php

namespace Drivejob\Services;

use DateTime;
use Exception;
use PDO;
use PDOException;
use Drivejob\Services\Expiry\LicenseExpiryRepository;
use Drivejob\Services\Expiry\LicenseExpiryMessageComposer;

/**
 * Ενορχήστρωση ελέγχου αδειών που λήγουν & αποστολής ειδοποιήσεων (Πακέτο 5).
 *
 * Σπάστηκε από 1.812 γραμμές σε 3 εστιασμένες κλάσεις:
 *   - Expiry\LicenseExpiryRepository       → όλα τα SQL (εύρεση/ιστορικό)
 *   - Expiry\LicenseExpiryMessageComposer  → σύνθεση email/SMS από πρότυπα
 *   - LicenseExpiryNotificationService     → η ροή: ποιος, πότε, αποστολή, καταγραφή
 *
 * Το δημόσιο API παραμένει ΙΔΙΟ (constructor + checkAndSendExpiryNotifications),
 * οπότε NotificationServices και cron scripts δεν χρειάστηκαν καμία αλλαγή.
 *
 * Σημείωση Πακέτου 5: οι άδειες χειριστή μηχανημάτων χρησιμοποιούν πλέον τις
 * ΔΙΚΕΣ τους περιόδους ειδοποίησης (180/90/30/15 ημέρες) — ο παλιός κώδικας
 * τις όριζε αλλά κατά λάθος χρησιμοποιούσε τις περιόδους της άδειας οδήγησης.
 */
class LicenseExpiryNotificationService
{
    private PDO $pdo;
    private EmailService $emailService;
    private SmsService $smsService;
    private LicenseExpiryRepository $repository;
    private LicenseExpiryMessageComposer $composer;

    /** @var array<string,int[]> Περίοδοι ειδοποίησης (ημέρες πριν τη λήξη) ανά κατηγορία */
    private array $notificationPeriods = [
        'driving_license'  => [60, 30, 15, 7, 1],
        'pei'              => [60, 30, 15, 7, 1],
        'adr_certificate'  => [60, 30, 15, 7, 1],
        'tachograph_card'  => [60, 30, 15, 7, 1],
        'operator_license' => [180, 90, 30, 15],
        'special_license'  => [60, 30, 15, 7, 1],
    ];

    /** @var int Μέγιστο βάθος ελέγχου σε ημέρες */
    private int $maxCheckDays = 180;

    public function __construct(PDO $pdo, EmailService $emailService, SmsService $smsService, ?array $config = null)
    {
        $this->pdo = $pdo;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->repository = new LicenseExpiryRepository($pdo);

        $templatesPath = dirname(__DIR__, 2) . '/templates/emails/';

        if ($config !== null) {
            if (isset($config['notification_periods'])) {
                $this->notificationPeriods = array_merge($this->notificationPeriods, $config['notification_periods']);
                $this->log('Φόρτωση προσαρμοσμένων περιόδων ειδοποίησης: ' . json_encode($this->notificationPeriods));
            }

            // Το βάθος ελέγχου ακολουθεί τη μεγαλύτερη περίοδο (+5 ημέρες περιθώριο)
            $maxDays = 0;
            foreach ($this->notificationPeriods as $periods) {
                $maxDays = max($maxDays, max($periods));
            }
            $this->maxCheckDays = max($maxDays + 5, (int) ($config['max_check_days'] ?? 0));

            if (isset($config['templates_path']) && is_dir($config['templates_path'])) {
                $templatesPath = $config['templates_path'];
            }
        }

        $this->composer = new LicenseExpiryMessageComposer($templatesPath);
    }

    /**
     * Έλεγχος για άδειες που λήγουν και αποστολή ειδοποιήσεων.
     *
     * @return array Αποτελέσματα ανά κατηγορία (ίδιο σχήμα με πριν)
     */
    public function checkAndSendExpiryNotifications(): array
    {
        try {
            $this->log('Έναρξη ελέγχου για άδειες που λήγουν...');
            $this->logDiagnostics();

            return [
                'driving_licenses'  => $this->checkDrivingLicenses(),
                'pei'               => $this->checkPeiCertificates(),
                'adr_certificates'  => $this->checkAdrCertificates(),
                'tachograph_cards'  => $this->checkTachographCards(),
                'operator_licenses' => $this->checkOperatorLicenses(),
                'special_licenses'  => $this->checkSpecialLicenses(),
            ];
        } catch (PDOException $e) {
            $this->log('Σφάλμα PDO κατά τον έλεγχο αδειών: ' . $e->getMessage(), 'ERROR');
            throw $e;
        } catch (Exception $e) {
            $this->log('Γενικό σφάλμα κατά τον έλεγχο αδειών: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    // ---- Έλεγχοι ανά κατηγορία ------------------------------------------

    private function checkDrivingLicenses(): array
    {
        if (!$this->repository->tableExists('driver_licenses')) {
            $this->log('Ο πίνακας driver_licenses δεν υπάρχει', 'WARNING');
            return [];
        }

        $sent = [];
        try {
            $rows = $this->repository->findExpiringDrivingLicenses($this->maxDateString());
            $this->log('Βρέθηκαν ' . count($rows) . ' οδηγοί με άδειες οδήγησης που λήγουν στο επόμενο διάστημα');

            foreach ($rows as $row) {
                $this->notifyIfDue($row, [
                    'category'    => 'driving_license',
                    'licenseType' => 'all', // μία συγκεντρωτική ειδοποίηση ανά οδηγό
                    'emailData'   => ['license_type' => $row['license_types']],
                    'smsCtx'      => [],
                    'resultLabel' => $row['license_types'],
                ], $sent);
            }
        } catch (Exception $e) {
            $this->log('Σφάλμα κατά τον έλεγχο αδειών οδήγησης: ' . $e->getMessage(), 'ERROR');
        }
        return $sent;
    }

    private function checkPeiCertificates(): array
    {
        if (!$this->repository->tableExists('driver_licenses')) {
            $this->log('Ο πίνακας driver_licenses δεν υπάρχει', 'WARNING');
            return [];
        }

        return array_merge(
            $this->checkSpecificPeiType('C', 'pei_expiry_c'),
            $this->checkSpecificPeiType('D', 'pei_expiry_d')
        );
    }

    private function checkSpecificPeiType(string $peiCategory, string $expiryDateField): array
    {
        if (!$this->repository->columnExists('driver_licenses', $expiryDateField)) {
            $this->log("Η στήλη {$expiryDateField} δεν υπάρχει στον πίνακα driver_licenses", 'WARNING');
            return [];
        }

        $sent = [];
        try {
            $rows = $this->repository->findExpiringPei($expiryDateField, $this->maxDateString());
            $this->log('Βρέθηκαν ' . count($rows) . " ΠΕΙ κατηγορίας {$peiCategory} που λήγουν στο επόμενο διάστημα");

            foreach ($rows as $row) {
                $this->notifyIfDue($row, [
                    'category'    => 'pei',
                    'licenseType' => "PEI-{$peiCategory}",
                    'emailData'   => ['pei_category' => $peiCategory],
                    'smsCtx'      => ['pei_category' => $peiCategory],
                    'resultLabel' => "PEI-{$peiCategory}",
                ], $sent);
            }
        } catch (Exception $e) {
            $this->log("Σφάλμα κατά τον έλεγχο ΠΕΙ κατηγορίας {$peiCategory}: " . $e->getMessage(), 'ERROR');
        }
        return $sent;
    }

    private function checkAdrCertificates(): array
    {
        if (!$this->repository->tableExists('driver_adr_certificates')) {
            $this->log('Ο πίνακας driver_adr_certificates δεν υπάρχει', 'WARNING');
            return [];
        }

        $sent = [];
        try {
            $rows = $this->repository->findExpiringAdrCertificates($this->maxDateString());
            $this->log('Βρέθηκαν ' . count($rows) . ' πιστοποιητικά ADR που λήγουν στο επόμενο διάστημα');

            foreach ($rows as $row) {
                $this->notifyIfDue($row, [
                    'category'    => 'adr_certificate',
                    'licenseType' => $row['adr_type'],
                    'emailData'   => ['adr_type' => $row['adr_type']],
                    'smsCtx'      => ['adr_type' => $row['adr_type']],
                    'resultLabel' => $row['adr_type'],
                ], $sent);
            }
        } catch (Exception $e) {
            $this->log('Σφάλμα κατά τον έλεγχο πιστοποιητικών ADR: ' . $e->getMessage(), 'ERROR');
        }
        return $sent;
    }

    private function checkTachographCards(): array
    {
        if (!$this->repository->tableExists('driver_tachograph_cards')) {
            $this->log('Ο πίνακας driver_tachograph_cards δεν υπάρχει', 'WARNING');
            return [];
        }

        $sent = [];
        try {
            $rows = $this->repository->findExpiringTachographCards($this->maxDateString());
            $this->log('Βρέθηκαν ' . count($rows) . ' κάρτες ταχογράφου που λήγουν στο επόμενο διάστημα');

            foreach ($rows as $row) {
                $this->notifyIfDue($row, [
                    'category'    => 'tachograph_card',
                    'licenseType' => 'card',
                    'emailData'   => ['card_number' => $row['card_number']],
                    'smsCtx'      => [],
                    'resultLabel' => 'Ταχογράφος',
                ], $sent);
            }
        } catch (Exception $e) {
            $this->log('Σφάλμα κατά τον έλεγχο καρτών ταχογράφου: ' . $e->getMessage(), 'ERROR');
        }
        return $sent;
    }

    private function checkOperatorLicenses(): array
    {
        if (!$this->repository->tableExists('driver_operator_licenses')) {
            $this->log('Ο πίνακας driver_operator_licenses δεν υπάρχει', 'WARNING');
            return [];
        }

        $sent = [];
        try {
            $rows = $this->repository->findExpiringOperatorLicenses($this->maxDateString());
            $this->log('Βρέθηκαν ' . count($rows) . ' άδειες χειριστή που λήγουν στο επόμενο διάστημα');

            foreach ($rows as $row) {
                $specialityName = $this->composer->operatorSpecialityName((string) $row['speciality']);
                $this->notifyIfDue($row, [
                    'category'    => 'operator_license',
                    'licenseType' => (string) $row['speciality'],
                    'emailData'   => [
                        'speciality'      => $row['speciality'],
                        'speciality_name' => $specialityName,
                        'license_number'  => $row['license_number'],
                    ],
                    'smsCtx'      => ['speciality_name' => $specialityName],
                    'resultLabel' => $specialityName,
                ], $sent);
            }
        } catch (Exception $e) {
            $this->log('Σφάλμα κατά τον έλεγχο αδειών χειριστή: ' . $e->getMessage(), 'ERROR');
        }
        return $sent;
    }

    private function checkSpecialLicenses(): array
    {
        if (!$this->repository->tableExists('driver_special_licenses')) {
            $this->log('Ο πίνακας driver_special_licenses δεν υπάρχει', 'WARNING');
            return [];
        }

        $sent = [];
        try {
            $rows = $this->repository->findExpiringSpecialLicenses($this->maxDateString());
            $this->log('Βρέθηκαν ' . count($rows) . ' ειδικές άδειες που λήγουν στο επόμενο διάστημα');

            foreach ($rows as $row) {
                $this->notifyIfDue($row, [
                    'category'    => 'special_license',
                    'licenseType' => $row['license_type'],
                    'emailData'   => [
                        'license_type'   => $row['license_type'],
                        'license_number' => $row['license_number'],
                        'details'        => $row['details'],
                    ],
                    'smsCtx'      => ['license_type' => $row['license_type']],
                    'resultLabel' => $row['license_type'],
                ], $sent);
            }
        } catch (Exception $e) {
            $this->log('Σφάλμα κατά τον έλεγχο ειδικών αδειών: ' . $e->getMessage(), 'ERROR');
        }
        return $sent;
    }

    // ---- Κοινή ροή ειδοποίησης ------------------------------------------

    /**
     * Ελέγχει αν η εγγραφή $row εμπίπτει σε περίοδο ειδοποίησης και, αν ναι,
     * στέλνει email/SMS, καταγράφει την αποστολή και προσθέτει στο $sent.
     *
     * Απαιτεί στο $row: driver_id, first_name, last_name, email, phone, expiry_date.
     * Το $spec: category, licenseType, emailData, smsCtx, resultLabel.
     */
    private function notifyIfDue(array $row, array $spec, array &$sent): void
    {
        $category = $spec['category'];
        $daysUntilExpiry = (new DateTime())->diff(new DateTime($row['expiry_date']))->days;

        foreach ($this->notificationPeriods[$category] as $daysBeforeExpiry) {
            // Ανοχή ±1 ημέρας για διαφορές ώρας εκτέλεσης του cron
            if (abs($daysUntilExpiry - $daysBeforeExpiry) > 1) {
                continue;
            }

            $this->log("Βρέθηκε άδεια που χρειάζεται ειδοποίηση: driver_id={$row['driver_id']}, "
                . "category={$category}, type={$spec['licenseType']}, days_before={$daysBeforeExpiry}, actual_days={$daysUntilExpiry}");

            if ($this->repository->hasNotificationBeenSent(
                (int) $row['driver_id'], $category, $spec['licenseType'], $row['expiry_date'], $daysBeforeExpiry
            )) {
                $this->log("Η ειδοποίηση για τον οδηγό {$row['driver_id']} ({$category}/{$spec['licenseType']}) "
                    . "έχει ήδη σταλεί για {$daysBeforeExpiry} ημέρες");
                continue;
            }

            // Email
            $emailSent = false;
            if (!empty($row['email'])) {
                $subject = $this->composer->emailSubject($category, $spec['emailData']);
                $message = $this->composer->renderEmail($category, $spec['emailData'] + [
                    'first_name' => $row['first_name'],
                    'expiry_date' => $row['expiry_date'],
                    'days_before_expiry' => $daysBeforeExpiry,
                ]);
                $emailSent = $this->emailService->send($row['email'], $subject, $message);
                $this->log("Αποστολή email στον οδηγό {$row['driver_id']} ({$category}/{$spec['licenseType']}): "
                    . ($emailSent ? 'Επιτυχής' : 'Αποτυχία'));
            } else {
                $this->log("Ο οδηγός {$row['driver_id']} δεν έχει email", 'WARNING');
            }

            // SMS μόνο κοντά στη λήξη
            $smsSent = false;
            if ($daysBeforeExpiry <= 15 && !empty($row['phone'])) {
                $smsSent = $this->smsService->sendSms(
                    $row['phone'],
                    $this->composer->smsMessage($category, $spec['smsCtx'], $daysBeforeExpiry)
                );
                $this->log("Αποστολή SMS στον οδηγό {$row['driver_id']} ({$category}/{$spec['licenseType']}): "
                    . ($smsSent ? 'Επιτυχής' : 'Αποτυχία'));
            }

            if ($emailSent || $smsSent) {
                $this->repository->recordNotification(
                    (int) $row['driver_id'], $category, $spec['licenseType'], $row['expiry_date'], $daysBeforeExpiry
                );
                $sent[] = [
                    'driver_id'    => $row['driver_id'],
                    'driver_name'  => $row['first_name'] . ' ' . $row['last_name'],
                    'license_type' => $spec['resultLabel'],
                    'expiry_date'  => $row['expiry_date'],
                    'days_before'  => $daysBeforeExpiry,
                    'email_sent'   => $emailSent,
                    'sms_sent'     => $smsSent,
                ];
            }
        }
    }

    // ---- Βοηθητικά -------------------------------------------------------

    /** Ανώτατη ημερομηνία ελέγχου (σήμερα + maxCheckDays) σε Y-m-d. */
    private function maxDateString(): string
    {
        return (new DateTime())->modify("+{$this->maxCheckDays} days")->format('Y-m-d');
    }

    /** Συνοπτικά διαγνωστικά πινάκων στα logs του cron. */
    private function logDiagnostics(): void
    {
        try {
            $tables = [
                'driver_licenses', 'driver_adr_certificates', 'driver_tachograph_cards',
                'driver_operator_licenses', 'driver_special_licenses', 'license_expiry_notifications',
            ];
            foreach ($tables as $table) {
                if (!$this->repository->tableExists($table)) {
                    $this->log("Πίνακας {$table}: Δεν υπάρχει", 'WARNING');
                    continue;
                }
                $count = $this->repository->countRows($table);
                $upcoming = $table === 'license_expiry_notifications'
                    ? '-'
                    : (string) $this->repository->countUpcomingExpiries($table, 60);
                $this->log("Πίνακας {$table}: {$count} εγγραφές, επερχόμενες λήξεις 60ημ: {$upcoming}");
            }
        } catch (Exception $e) {
            $this->log('Σφάλμα διαγνωστικών: ' . $e->getMessage(), 'ERROR');
        }
    }

    private function log(string $message, string $level = 'INFO'): void
    {
        if (class_exists('Drivejob\Core\Logger') && method_exists('Drivejob\Core\Logger', 'log')) {
            \Drivejob\Core\Logger::log($level, $message, 'LicenseExpiryNotification');
        } else {
            error_log('[' . date('Y-m-d H:i:s') . "] {$level} [LicenseExpiryNotification]: {$message}");
        }
    }
}
