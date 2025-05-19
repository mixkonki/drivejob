<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις κάρτες ταχογράφου των οδηγών
 */
class DriverTachographRepository
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
     * Βρίσκει την κάρτα ταχογράφου ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array|null Η κάρτα ταχογράφου του οδηγού ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId): ?array
    {
        try {
            $sql = "SELECT * FROM driver_tachograph WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $tachograph = $stmt->fetch(PDO::FETCH_ASSOC);

            return $tachograph ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της κάρτας ταχογράφου: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει μια κάρτα ταχογράφου με βάση το ID της
     *
     * @param int $id Το ID της κάρτας ταχογράφου
     * @return array|null Τα στοιχεία της κάρτας ταχογράφου ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM driver_tachograph WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $tachograph = $stmt->fetch(PDO::FETCH_ASSOC);

            return $tachograph ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της κάρτας ταχογράφου: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί μια νέα κάρτα ταχογράφου
     *
     * @param array $data Τα δεδομένα της κάρτας ταχογράφου
     * @return int Το ID της νέας κάρτας ταχογράφου
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO driver_tachograph (driver_id, card_number, issue_date, expiry_date, created_at, updated_at)
                    VALUES (:driver_id, :card_number, :issue_date, :expiry_date, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':card_number' => $data['card_number'] ?? null,
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία της κάρτας ταχογράφου: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα κάρτα ταχογράφου
     *
     * @param int $id Το ID της κάρτας ταχογράφου
     * @param array $data Τα νέα δεδομένα της κάρτας ταχογράφου
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE driver_tachograph SET
                    card_number = :card_number,
                    issue_date = :issue_date,
                    expiry_date = :expiry_date,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':card_number' => $data['card_number'] ?? null,
                ':issue_date' => $data['issue_date'] ?? null,
                ':expiry_date' => $data['expiry_date'] ?? null
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση της κάρτας ταχογράφου: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει μια κάρτα ταχογράφου
     *
     * @param int $id Το ID της κάρτας ταχογράφου
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM driver_tachograph WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της κάρτας ταχογράφου: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει την κάρτα ταχογράφου ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByDriver(int $driverId): bool
    {
        try {
            $sql = "DELETE FROM driver_tachograph WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της κάρτας ταχογράφου: " . $e->getMessage());
        }
    }
}
