<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τα πιστοποιητικά ADR των οδηγών
 */
class DriverADRRepository
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
     * Βρίσκει το πιστοποιητικό ADR ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array|null Το πιστοποιητικό ADR του οδηγού ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId): ?array
    {
        try {
            $sql = "SELECT * FROM driver_adr WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $result = $this->db->query($sql, $params);
            $adr = $result->fetch(\PDO::FETCH_ASSOC);

            return $adr ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει ένα πιστοποιητικό ADR με βάση το ID του
     *
     * @param int $id Το ID του πιστοποιητικού ADR
     * @return array|null Τα στοιχεία του πιστοποιητικού ADR ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM driver_adr WHERE id = :id";
            $params = [':id' => $id];

            $result = $this->db->query($sql, $params);
            $adr = $result->fetch(\PDO::FETCH_ASSOC);

            return $adr ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί ένα νέο πιστοποιητικό ADR
     *
     * @param array $data Τα δεδομένα του πιστοποιητικού ADR
     * @return int Το ID του νέου πιστοποιητικού ADR
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO driver_adr (driver_id, certificate_number, issue_date, expiry_date, adr_classes, created_at, updated_at)
                    VALUES (:driver_id, :certificate_number, :issue_date, :expiry_date, :adr_classes, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':certificate_number' => $data['certificate_number'] ?? null,
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':adr_classes' => $data['adr_classes'] ?? null
            ];

            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει ένα υπάρχον πιστοποιητικό ADR
     *
     * @param int $id Το ID του πιστοποιητικού ADR
     * @param array $data Τα νέα δεδομένα του πιστοποιητικού ADR
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE driver_adr SET
                    certificate_number = :certificate_number,
                    issue_date = :issue_date,
                    expiry_date = :expiry_date,
                    adr_classes = :adr_classes,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':certificate_number' => $data['certificate_number'] ?? null,
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':adr_classes' => $data['adr_classes'] ?? null
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει ένα πιστοποιητικό ADR
     *
     * @param int $id Το ID του πιστοποιητικού ADR
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM driver_adr WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει το πιστοποιητικό ADR ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByDriver(int $driverId): bool
    {
        try {
            $sql = "DELETE FROM driver_adr WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }
}
