<?php

namespace Drivejob\Models\Driver;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των αδειών οδήγησης των οδηγών
 */
class LicenseModel extends BaseModel
{
    // Ορισμός σταθερών για τύπους αδειών
    const LICENSE_TYPE_DRIVING = 'driving_license';
    const LICENSE_TYPE_PEI = 'pei';
    const LICENSE_TYPE_ADR = 'adr_certificate';
    const LICENSE_TYPE_TACHOGRAPH = 'tachograph_card';
    const LICENSE_TYPE_OPERATOR = 'operator_license';
    const LICENSE_TYPE_SPECIAL = 'special_license';

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'driver_licenses');
    }

    /**
     * Διαγράφει όλες τις άδειες οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverLicenses($driverId)
    {
        try {
            $result = $this->delete(['driver_id' => $driverId]);

            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET driving_license = 0 WHERE id = ?");
                $updateFlag->execute([$driverId]);
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverLicenses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθήκη άδειας οδήγησης για τον οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος άδειας (A, B, C, D, κλπ.)
     * @param bool $hasPei Αν έχει ΠΕΙ
     * @param string $expiryDate Ημερομηνία λήξης της κατηγορίας
     * @param string $licenseNumber Αριθμός άδειας
     * @param string $peiExpiryC Ημερομηνία λήξης ΠΕΙ για κατηγορία C
     * @param string $peiExpiryD Ημερομηνία λήξης ΠΕΙ για κατηγορία D
     * @param string $licenseDocumentExpiry Ημερομηνία λήξης εντύπου
     * @return bool Επιτυχία ή αποτυχία της προσθήκης
     */
    public function addDriverLicense($driverId, $licenseType, $hasPei, $expiryDate, $licenseNumber, $peiExpiryC = null, $peiExpiryD = null, $licenseDocumentExpiry = null)
    {
        try {
            // Καθορισμός της ημερομηνίας λήξης ΠΕΙ ανάλογα με την κατηγορία
            $peiExpiryCValue = null;
            $peiExpiryDValue = null;

            if ($hasPei) {
                if (in_array($licenseType, ['C', 'CE', 'C1', 'C1E'])) {
                    $peiExpiryCValue = $peiExpiryC;
                } else if (in_array($licenseType, ['D', 'DE', 'D1', 'D1E'])) {
                    $peiExpiryDValue = $peiExpiryD;
                }
            }

            $data = [
                'driver_id' => $driverId,
                'license_type' => $licenseType,
                'has_pei' => $hasPei ? 1 : 0,
                'expiry_date' => $expiryDate,
                'license_number' => $licenseNumber,
                'pei_expiry_c' => $peiExpiryCValue,
                'pei_expiry_d' => $peiExpiryDValue,
                'license_document_expiry' => $licenseDocumentExpiry
            ];

            $result = $this->insert($data);

            if ($result !== false) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET driving_license = 1 WHERE id = ?");
                $updateFlag->execute([$driverId]);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            Logger::error('Error in addDriverLicense: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Λαμβάνει όλες τις άδειες οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Λίστα με άδειες οδήγησης
     */
    public function getDriverLicenses($driverId)
    {
        return $this->select(['driver_id' => $driverId], '*', ['license_type' => 'ASC']);
    }

    /**
     * Λαμβάνει την ημερομηνία λήξης για συγκεκριμένη κατηγορία άδειας οδήγησης
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος άδειας
     * @return string|null Ημερομηνία λήξης ή null αν δεν βρέθηκε
     */
    public function getDriverLicenseExpiryDate($driverId, $licenseType)
    {
        $license = $this->selectOne(['driver_id' => $driverId, 'license_type' => $licenseType], 'expiry_date');
        return $license ? $license['expiry_date'] : null;
    }

    /**
     * Ελέγχει αν ο οδηγός έχει ΠΕΙ για συγκεκριμένη κατηγορία
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος άδειας
     * @return bool Αν έχει ΠΕΙ
     */
    public function hasDriverPEI($driverId, $licenseType)
    {
        $license = $this->selectOne(['driver_id' => $driverId, 'license_type' => $licenseType], 'has_pei');
        return $license && $license['has_pei'] == 1;
    }

    /**
     * Λαμβάνει τις ημερομηνίες λήξης των ΠΕΙ του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Ημερομηνίες λήξης ΠΕΙ για εμπορεύματα και επιβάτες
     */
    public function getDriverPEIExpiryDates($driverId)
    {
        $sql = "SELECT pei_expiry_c, pei_expiry_d FROM {$this->table} 
                WHERE driver_id = ? AND has_pei = 1 
                ORDER BY pei_expiry_c DESC, pei_expiry_d DESC 
                LIMIT 1";

        $result = $this->queryOne($sql, [$driverId]);
        return $result ?: ['pei_expiry_c' => null, 'pei_expiry_d' => null];
    }

    /**
     * Μορφοποίηση των αδειών οδήγησης σε αναγνώσιμη μορφή
     * 
     * @param array $licenses Οι άδειες οδήγησης
     * @return array Μορφοποιημένες άδειες
     */
    public function formatDriverLicenses($licenses)
    {
        $categories = [];
        $licenseInfo = [];

        foreach ($licenses as $license) {
            if (!empty($license['license_type'])) {
                $categories[] = $license['license_type'];

                $licenseInfo[$license['license_type']] = [
                    'has_pei' => $license['has_pei'] ?? 0,
                    'expiry_date' => $license['expiry_date'] ?? null,
                    'license_number' => $license['license_number'] ?? null,
                    'pei_expiry_c' => $license['pei_expiry_c'] ?? null,
                    'pei_expiry_d' => $license['pei_expiry_d'] ?? null
                ];
            }
        }

        return [
            'categories' => $categories,
            'details' => $licenseInfo
        ];
    }

    /**
     * Ενημερώνει τον αριθμό άδειας οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseNumber Αριθμός άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverLicenseNumber($driverId, $licenseNumber)
    {
        // Ενημερώνουμε όλες τις άδειες του οδηγού με τον νέο αριθμό άδειας
        try {
            $sql = "UPDATE {$this->table} SET license_number = ? WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$licenseNumber, $driverId]);
        } catch (PDOException $e) {
            Logger::error('Error updating license number: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Λαμβάνει τους οδηγούς με άδειες που λήγουν σύντομα
     * 
     * @param string $period Περίοδος ελέγχου (π.χ. '2 months')
     * @return array Λίστα οδηγών με άδειες που λήγουν
     */
    public function getDriversWithExpiringLicenses($period = '2 months')
    {
        $targetDate = date('Y-m-d', strtotime('+' . $period));

        $sql = "
            SELECT d.id, d.first_name, d.last_name, d.email, 
                   'driving_license' as type, 
                   dl.expiry_date, dl.license_type
            FROM drivers d 
            JOIN {$this->table} dl ON dl.driver_id = d.id
            WHERE dl.expiry_date <= ? 
              AND dl.expiry_date >= CURRENT_DATE()
        ";

        return $this->query($sql, [$targetDate]) ?: [];
    }
}
