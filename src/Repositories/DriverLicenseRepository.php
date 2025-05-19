<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις άδειες οδήγησης των οδηγών
 */
class DriverLicenseRepository
{
    /**
     * @var Database Η σύνδεση με τη βάση δεδομένων
     */
    private $db;

    /**
     * Constructor
     *
     * @param Database|null $db Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(Database $db = null)
    {
        $this->db = $db ?? new Database();
    }

    /**
     * Βρίσκει όλες τις άδειες οδήγησης ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array Οι άδειες οδήγησης του οδηγού
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId): array
    {
        try {
            $sql = "SELECT * FROM driver_licenses WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $result = $this->db->query($sql, $params);
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των αδειών οδήγησης: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει μια άδεια οδήγησης με βάση το ID της
     *
     * @param int $id Το ID της άδειας οδήγησης
     * @return array|null Τα στοιχεία της άδειας οδήγησης ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM driver_licenses WHERE id = :id";
            $params = [':id' => $id];

            $result = $this->db->query($sql, $params);
            $license = $result->fetch(\PDO::FETCH_ASSOC);

            return $license ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της άδειας οδήγησης: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί μια νέα άδεια οδήγησης
     *
     * @param array $data Τα δεδομένα της άδειας οδήγησης
     * @return int Το ID της νέας άδειας οδήγησης
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO driver_licenses (driver_id, license_type, issue_date, expiry_date, has_pei, has_adr, has_tachograph, created_at, updated_at)
                    VALUES (:driver_id, :license_type, :issue_date, :expiry_date, :has_pei, :has_adr, :has_tachograph, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':license_type' => $data['license_type'],
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':has_pei' => $data['has_pei'] ?? 0,
                ':has_adr' => $data['has_adr'] ?? 0,
                ':has_tachograph' => $data['has_tachograph'] ?? 0
            ];

            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία της άδειας οδήγησης: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα άδεια οδήγησης
     *
     * @param int $id Το ID της άδειας οδήγησης
     * @param array $data Τα νέα δεδομένα της άδειας οδήγησης
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE driver_licenses SET
                    license_type = :license_type,
                    issue_date = :issue_date,
                    expiry_date = :expiry_date,
                    has_pei = :has_pei,
                    has_adr = :has_adr,
                    has_tachograph = :has_tachograph,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':license_type' => $data['license_type'],
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':has_pei' => $data['has_pei'] ?? 0,
                ':has_adr' => $data['has_adr'] ?? 0,
                ':has_tachograph' => $data['has_tachograph'] ?? 0
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση της άδειας οδήγησης: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει μια άδεια οδήγησης
     *
     * @param int $id Το ID της άδειας οδήγησης
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM driver_licenses WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της άδειας οδήγησης: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει όλες τις άδειες οδήγησης ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByDriver(int $driverId): bool
    {
        try {
            $sql = "DELETE FROM driver_licenses WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των αδειών οδήγησης: " . $e->getMessage());
        }
    }
}
