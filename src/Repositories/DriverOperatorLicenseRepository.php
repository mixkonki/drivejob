<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις άδειες χειριστή μηχανημάτων έργου των οδηγών
 */
class DriverOperatorLicenseRepository
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
     * Βρίσκει όλες τις άδειες χειριστή μηχανημάτων έργου ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array Οι άδειες χειριστή μηχανημάτων έργου του οδηγού
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId): array
    {
        try {
            $sql = "SELECT * FROM driver_operator_licenses WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $result = $this->db->query($sql, $params);
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των αδειών χειριστή μηχανημάτων έργου: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει μια άδεια χειριστή μηχανημάτων έργου με βάση το ID της
     *
     * @param int $id Το ID της άδειας χειριστή μηχανημάτων έργου
     * @return array|null Τα στοιχεία της άδειας χειριστή μηχανημάτων έργου ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM driver_operator_licenses WHERE id = :id";
            $params = [':id' => $id];

            $result = $this->db->query($sql, $params);
            $license = $result->fetch(\PDO::FETCH_ASSOC);

            return $license ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της άδειας χειριστή μηχανημάτων έργου: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί μια νέα άδεια χειριστή μηχανημάτων έργου
     *
     * @param array $data Τα δεδομένα της άδειας χειριστή μηχανημάτων έργου
     * @return int Το ID της νέας άδειας χειριστή μηχανημάτων έργου
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO driver_operator_licenses (driver_id, license_type, issue_date, expiry_date, created_at, updated_at)
                    VALUES (:driver_id, :license_type, :issue_date, :expiry_date, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':license_type' => $data['license_type'],
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null
            ];

            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία της άδειας χειριστή μηχανημάτων έργου: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα άδεια χειριστή μηχανημάτων έργου
     *
     * @param int $id Το ID της άδειας χειριστή μηχανημάτων έργου
     * @param array $data Τα νέα δεδομένα της άδειας χειριστή μηχανημάτων έργου
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE driver_operator_licenses SET
                    license_type = :license_type,
                    issue_date = :issue_date,
                    expiry_date = :expiry_date,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':license_type' => $data['license_type'],
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση της άδειας χειριστή μηχανημάτων έργου: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει μια άδεια χειριστή μηχανημάτων έργου
     *
     * @param int $id Το ID της άδειας χειριστή μηχανημάτων έργου
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM driver_operator_licenses WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της άδειας χειριστή μηχανημάτων έργου: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει όλες τις άδειες χειριστή μηχανημάτων έργου ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByDriver(int $driverId): bool
    {
        try {
            $sql = "DELETE FROM driver_operator_licenses WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των αδειών χειριστή μηχανημάτων έργου: " . $e->getMessage());
        }
    }
}
