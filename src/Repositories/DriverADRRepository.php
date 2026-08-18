<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τα πιστοποιητικά ADR των οδηγών
 */
class DriverADRRepository
{
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    private $db;

    /**
     * Constructor
     *
     * @param PDO $db Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
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
            $sql = "SELECT * FROM driver_adr_certificates WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $adr = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $sql = "SELECT * FROM driver_adr_certificates WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $adr = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $sql = "INSERT INTO driver_adr_certificates (driver_id, certificate_number, expiry_date, adr_type, created_at, updated_at)
                    VALUES (:driver_id, :certificate_number, :expiry_date, :adr_type, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':certificate_number' => $data['certificate_number'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':adr_type' => $data['adr_type'] ?? $data['adr_classes'] ?? null
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
            $sql = "UPDATE driver_adr_certificates SET
                    certificate_number = :certificate_number,
                    expiry_date = :expiry_date,
                    adr_type = :adr_type,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':certificate_number' => $data['certificate_number'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null,
                ':adr_type' => $data['adr_type'] ?? $data['adr_classes'] ?? null
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
            $sql = "DELETE FROM driver_adr_certificates WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
            $sql = "DELETE FROM driver_adr_certificates WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή του πιστοποιητικού ADR: " . $e->getMessage());
        }
    }
}
