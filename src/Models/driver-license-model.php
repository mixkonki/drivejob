<?php

namespace Drivejob\Models;

use Drivejob\Core\Model;

class DriverLicense extends Model
{
    /**
     * Λήψη όλων των αδειών οδήγησης ενός οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @return array Πίνακας με τις άδειες οδήγησης
     */
    public function getDriverLicenses($driverId)
    {
        $sql = "SELECT 
                    dl.*, 
                    pei_c.expiry_date as pei_expiry_c,
                    pei_d.expiry_date as pei_expiry_d
                FROM 
                    driver_licenses dl
                LEFT JOIN 
                    driver_pei pei_c ON dl.driver_id = pei_c.driver_id AND pei_c.pei_type = 'C'
                LEFT JOIN 
                    driver_pei pei_d ON dl.driver_id = pei_d.driver_id AND pei_d.pei_type = 'D'
                WHERE 
                    dl.driver_id = :driver_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Προσθήκη νέας άδειας οδήγησης
     *
     * @param array $data Δεδομένα άδειας οδήγησης
     * @return bool|int ID της νέας άδειας ή false σε περίπτωση αποτυχίας
     */
    public function addLicense($data)
    {
        $sql = "INSERT INTO driver_licenses (
                    driver_id, 
                    license_type, 
                    expiry_date, 
                    has_pei
                ) VALUES (
                    :driver_id, 
                    :license_type, 
                    :expiry_date, 
                    :has_pei
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $data['driver_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':license_type', $data['license_type'], \PDO::PARAM_STR);
        $stmt->bindParam(':expiry_date', $data['expiry_date'], \PDO::PARAM_STR);
        $stmt->bindParam(':has_pei', $data['has_pei'], \PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Ενημέρωση άδειας οδήγησης
     *
     * @param int $licenseId ID της άδειας
     * @param array $data Νέα δεδομένα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updateLicense($licenseId, $data)
    {
        $sql = "UPDATE driver_licenses SET 
                    expiry_date = :expiry_date, 
                    has_pei = :has_pei
                WHERE 
                    id = :id AND driver_id = :driver_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':expiry_date', $data['expiry_date'], \PDO::PARAM_STR);
        $stmt->bindParam(':has_pei', $data['has_pei'], \PDO::PARAM_INT);
        $stmt->bindParam(':id', $licenseId, \PDO::PARAM_INT);
        $stmt->bindParam(':driver_id', $data['driver_id'], \PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Διαγραφή άδειας οδήγησης
     *
     * @param int $licenseId ID της άδειας
     * @param int $driverId ID του οδηγού για επιβεβαίωση
     * @return bool Επιτυχία ή αποτυχία
     */
    public function deleteLicense($licenseId, $driverId)
    {
        $sql = "DELETE FROM driver_licenses WHERE id = :id AND driver_id = :driver_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $licenseId, \PDO::PARAM_INT);
        $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Λήψη των στοιχείων Πιστοποιητικού Επαγγελματικής Ικανότητας (ΠΕΙ)
     *
     * @param int $driverId ID του οδηγού
     * @return array Πίνακας με τα στοιχεία των ΠΕΙ
     */
    public function getDriverPEI($driverId)
    {
        $sql = "SELECT * FROM driver_pei WHERE driver_id = :driver_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Ενημέρωση ή προσθήκη ΠΕΙ
     *
     * @param array $data Δεδομένα ΠΕΙ
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updatePEI($data)
    {
        // Έλεγχος αν υπάρχει ήδη καταχώρηση για αυτόν τον τύπο ΠΕΙ
        $sql = "SELECT id FROM driver_pei 
                WHERE driver_id = :driver_id AND pei_type = :pei_type";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $data['driver_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':pei_type', $data['pei_type'], \PDO::PARAM_STR);
        $stmt->execute();
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($existing) {
        // Ενημέρωση υπάρχουσας καταχώρησης
            $sql = "UPDATE driver_pei SET 
                        expiry_date = :expiry_date,
                        last_updated = NOW()
                    WHERE 
                        id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':expiry_date', $data['expiry_date'], \PDO::PARAM_STR);
            $stmt->bindParam(':id', $existing['id'], \PDO::PARAM_INT);
        } else {
        // Προσθήκη νέας καταχώρησης
            $sql = "INSERT INTO driver_pei (
                        driver_id,
                        pei_type,
                        expiry_date,
                        created_at,
                        last_updated
                    ) VALUES (
                        :driver_id,
                        :pei_type,
                        :expiry_date,
                        NOW(),
                        NOW()
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':driver_id', $data['driver_id'], \PDO::PARAM_INT);
            $stmt->bindParam(':pei_type', $data['pei_type'], \PDO::PARAM_STR);
            $stmt->bindParam(':expiry_date', $data['expiry_date'], \PDO::PARAM_STR);
        }

        return $stmt->execute();
    }

    /**
     * Έλεγχος επερχόμενης λήξης αδειών και πιστοποιητικών
     *
     * @param int $driverId ID του οδηγού
     * @param int $daysThreshold Ημέρες πριν τη λήξη για ειδοποίηση (προεπιλογή: 90)
     * @return array Λίστα με άδειες που λήγουν σύντομα
     */
    public function checkExpiringLicenses($driverId, $daysThreshold = 90)
    {
        $expiringLicenses = [];
        $threshold = date('Y-m-d', strtotime("+{$daysThreshold} days"));
// Έλεγχος αδειών οδήγησης
        $sql = "SELECT 
                    'license' as type,
                    license_type as category,
                    expiry_date
                FROM 
                    driver_licenses 
                WHERE 
                    driver_id = :driver_id 
                    AND expiry_date <= :threshold 
                    AND expiry_date >= CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
        $stmt->bindParam(':threshold', $threshold, \PDO::PARAM_STR);
        $stmt->execute();
        $expiringLicenses = array_merge($expiringLicenses, $stmt->fetchAll(\PDO::FETCH_ASSOC));
// Έλεγχος ΠΕΙ
        $sql = "SELECT 
                    'pei' as type,
                    pei_type as category,
                    expiry_date
                FROM 
                    driver_pei 
                WHERE 
                    driver_id = :driver_id 
                    AND expiry_date <= :threshold 
                    AND expiry_date >= CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
        $stmt->bindParam(':threshold', $threshold, \PDO::PARAM_STR);
        $stmt->execute();
        $expiringLicenses = array_merge($expiringLicenses, $stmt->fetchAll(\PDO::FETCH_ASSOC));
        return $expiringLicenses;
    }
}
