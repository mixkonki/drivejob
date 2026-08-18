<?php

namespace Drivejob\Services\Expiry;

use PDO;
use PDOException;

/**
 * Πρόσβαση βάσης για τον έλεγχο λήξης αδειών (Πακέτο 5).
 *
 * ΟΛΑ τα SQL queries του κυκλώματος ειδοποιήσεων λήξης ζουν εδώ:
 * εύρεση αδειών που λήγουν ανά κατηγορία, έλεγχος/καταγραφή σταλμένων
 * ειδοποιήσεων και οι διαγνωστικοί μετρητές.
 *
 * Τα tableExists/columnExists διατηρούνται ΜΟΝΟ ως δίχτυ ασφαλείας για το
 * cron (μία φορά ανά run, με cache) — δεν τρέχουν σε web requests.
 */
class LicenseExpiryRepository
{
    private PDO $pdo;

    /** @var array<string,bool> cache υπάρξεων πινάκων για το τρέχον run */
    private array $tableCache = [];

    /** @var array<string,bool> cache υπάρξεων στηλών για το τρέχον run */
    private array $columnCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---- Εύρεση αδειών που λήγουν ---------------------------------------

    /**
     * Οδηγοί με άδειες οδήγησης που λήγουν έως την $maxDate.
     * Μία γραμμή ανά οδηγό: κοντινότερη λήξη + λίστα κατηγοριών.
     */
    public function findExpiringDrivingLicenses(string $maxDate): array
    {
        return $this->fetch("
            SELECT d.id AS driver_id, d.first_name, d.last_name, d.email, d.phone,
                   MIN(dl.expiry_date) AS expiry_date,
                   GROUP_CONCAT(dl.license_type SEPARATOR ', ') AS license_types
            FROM drivers d
            JOIN driver_licenses dl ON d.id = dl.driver_id
            WHERE dl.expiry_date BETWEEN CURDATE() AND :max_date
              AND d.is_verified = 1
            GROUP BY d.id
        ", $maxDate);
    }

    /**
     * ΠΕΙ που λήγουν έως την $maxDate.
     *
     * @param string $expiryDateField 'pei_expiry_c' ή 'pei_expiry_d' (whitelisted)
     */
    public function findExpiringPei(string $expiryDateField, string $maxDate): array
    {
        if (!in_array($expiryDateField, ['pei_expiry_c', 'pei_expiry_d'], true)) {
            throw new \InvalidArgumentException("Μη έγκυρο πεδίο ΠΕΙ: {$expiryDateField}");
        }

        return $this->fetch("
            SELECT d.id AS driver_id, d.first_name, d.last_name, d.email, d.phone,
                   dl.{$expiryDateField} AS expiry_date
            FROM drivers d
            JOIN driver_licenses dl ON d.id = dl.driver_id
            WHERE dl.{$expiryDateField} BETWEEN CURDATE() AND :max_date
              AND dl.has_pei = 1
              AND d.is_verified = 1
            GROUP BY d.id, dl.{$expiryDateField}
        ", $maxDate);
    }

    /** Πιστοποιητικά ADR που λήγουν έως την $maxDate. */
    public function findExpiringAdrCertificates(string $maxDate): array
    {
        return $this->fetch("
            SELECT d.id AS driver_id, d.first_name, d.last_name, d.email, d.phone,
                   dac.adr_type, dac.expiry_date
            FROM drivers d
            JOIN driver_adr_certificates dac ON d.id = dac.driver_id
            WHERE dac.expiry_date BETWEEN CURDATE() AND :max_date
              AND d.is_verified = 1
        ", $maxDate);
    }

    /** Κάρτες ψηφιακού ταχογράφου που λήγουν έως την $maxDate. */
    public function findExpiringTachographCards(string $maxDate): array
    {
        return $this->fetch("
            SELECT d.id AS driver_id, d.first_name, d.last_name, d.email, d.phone,
                   dtc.card_number, dtc.expiry_date
            FROM drivers d
            JOIN driver_tachograph_cards dtc ON d.id = dtc.driver_id
            WHERE dtc.expiry_date BETWEEN CURDATE() AND :max_date
              AND d.is_verified = 1
        ", $maxDate);
    }

    /** Άδειες χειριστή μηχανημάτων έργου που λήγουν έως την $maxDate. */
    public function findExpiringOperatorLicenses(string $maxDate): array
    {
        return $this->fetch("
            SELECT d.id AS driver_id, d.first_name, d.last_name, d.email, d.phone,
                   dol.speciality, dol.license_number, dol.expiry_date
            FROM drivers d
            JOIN driver_operator_licenses dol ON d.id = dol.driver_id
            WHERE dol.expiry_date BETWEEN CURDATE() AND :max_date
              AND d.is_verified = 1
        ", $maxDate);
    }

    /** Ειδικές άδειες που λήγουν έως την $maxDate. */
    public function findExpiringSpecialLicenses(string $maxDate): array
    {
        return $this->fetch("
            SELECT d.id AS driver_id, d.first_name, d.last_name, d.email, d.phone,
                   dsl.license_type, dsl.license_number, dsl.expiry_date, dsl.details
            FROM drivers d
            JOIN driver_special_licenses dsl ON d.id = dsl.driver_id
            WHERE dsl.expiry_date BETWEEN CURDATE() AND :max_date
              AND d.is_verified = 1
        ", $maxDate);
    }

    // ---- Ιστορικό ειδοποιήσεων ------------------------------------------

    /**
     * Έχει ήδη σταλεί ειδοποίηση για τον συγκεκριμένο συνδυασμό;
     * Σε σφάλμα επιστρέφει false ώστε να επιτραπεί η αποστολή (fail-open,
     * το duplicate key στο recordNotification προστατεύει από διπλά).
     */
    public function hasNotificationBeenSent(int $driverId, string $licenseCategory, string $licenseType, string $expiryDate, int $daysBefore): bool
    {
        if (!$this->tableExists('license_expiry_notifications')) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM license_expiry_notifications
                WHERE driver_id = :driver_id
                  AND license_category = :license_category
                  AND license_type = :license_type
                  AND expiry_date = :expiry_date
                  AND days_before = :days_before
            ");
            $stmt->execute([
                'driver_id' => $driverId,
                'license_category' => $licenseCategory,
                'license_type' => $licenseType,
                'expiry_date' => $expiryDate,
                'days_before' => $daysBefore,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Καταγράφει σταλμένη ειδοποίηση. Duplicate entry θεωρείται επιτυχία.
     */
    public function recordNotification(int $driverId, string $licenseCategory, string $licenseType, string $expiryDate, int $daysBefore): bool
    {
        if (!$this->tableExists('license_expiry_notifications')) {
            $this->createNotificationsTable();
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO license_expiry_notifications
                    (driver_id, license_category, license_type, expiry_date, days_before, sent_at)
                VALUES (:driver_id, :license_category, :license_type, :expiry_date, :days_before, NOW())
            ");
            return $stmt->execute([
                'driver_id' => $driverId,
                'license_category' => $licenseCategory,
                'license_type' => $licenseType,
                'expiry_date' => $expiryDate,
                'days_before' => $daysBefore,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return true; // υπάρχει ήδη — δεν είναι σφάλμα
            }
            throw $e;
        }
    }

    private function createNotificationsTable(): void
    {
        $this->pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->tableCache['license_expiry_notifications'] = true;
    }

    // ---- Διαγνωστικά & guards -------------------------------------------

    public function tableExists(string $tableName): bool
    {
        if (!isset($this->tableCache[$tableName])) {
            try {
                $stmt = $this->pdo->prepare(
                    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
                );
                $stmt->execute([$tableName]);
                $this->tableCache[$tableName] = (int) $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        }
        return $this->tableCache[$tableName];
    }

    public function columnExists(string $tableName, string $columnName): bool
    {
        $key = "{$tableName}.{$columnName}";
        if (!isset($this->columnCache[$key])) {
            try {
                $stmt = $this->pdo->prepare(
                    'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
                );
                $stmt->execute([$tableName, $columnName]);
                $this->columnCache[$key] = (int) $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        }
        return $this->columnCache[$key];
    }

    /** Αριθμός εγγραφών πίνακα (για τα διαγνωστικά logs του cron). */
    public function countRows(string $tableName): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
    }

    /** Επερχόμενες λήξεις πίνακα στις επόμενες $days ημέρες (στήλη expiry_date). */
    public function countUpcomingExpiries(string $tableName, int $days = 60): int
    {
        if (!$this->columnExists($tableName, 'expiry_date')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `{$tableName}` WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)"
        );
        $stmt->execute(['days' => $days]);
        return (int) $stmt->fetchColumn();
    }

    // ---- helpers ---------------------------------------------------------

    private function fetch(string $sql, string $maxDate): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['max_date' => $maxDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
