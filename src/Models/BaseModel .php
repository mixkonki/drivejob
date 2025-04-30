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
     * Λαμβάνει την ημερομηνία λήξης για συγκεκριμένη κατηγορία ά