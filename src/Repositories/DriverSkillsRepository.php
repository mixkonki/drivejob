<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις δεξιότητες των οδηγών
 */
class DriverSkillsRepository
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
    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Βρίσκει όλες τις δεξιότητες ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array Οι δεξιότητες του οδηγού
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId): array
    {
        try {
            $sql = "SELECT * FROM driver_skills WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $result = $this->db->query($sql, $params);
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των δεξιοτήτων: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει μια δεξιότητα με βάση το ID της
     *
     * @param int $id Το ID της δεξιότητας
     * @return array|null Τα στοιχεία της δεξιότητας ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM driver_skills WHERE id = :id";
            $params = [':id' => $id];

            $result = $this->db->query($sql, $params);
            $skill = $result->fetch(\PDO::FETCH_ASSOC);

            return $skill ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της δεξιότητας: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί μια νέα δεξιότητα
     *
     * @param array $data Τα δεδομένα της δεξιότητας
     * @return int Το ID της νέας δεξιότητας
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO driver_skills (driver_id, skill_name, skill_level, years_experience, created_at, updated_at)
                    VALUES (:driver_id, :skill_name, :skill_level, :years_experience, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':skill_name' => $data['skill_name'],
                ':skill_level' => $data['skill_level'] ?? 1,
                ':years_experience' => $data['years_experience'] ?? 0
            ];

            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία της δεξιότητας: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα δεξιότητα
     *
     * @param int $id Το ID της δεξιότητας
     * @param array $data Τα νέα δεδομένα της δεξιότητας
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE driver_skills SET
                    skill_name = :skill_name,
                    skill_level = :skill_level,
                    years_experience = :years_experience,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':skill_name' => $data['skill_name'],
                ':skill_level' => $data['skill_level'] ?? 1,
                ':years_experience' => $data['years_experience'] ?? 0
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση της δεξιότητας: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει μια δεξιότητα
     *
     * @param int $id Το ID της δεξιότητας
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM driver_skills WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της δεξιότητας: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει όλες τις δεξιότητες ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByDriver(int $driverId): bool
    {
        try {
            $sql = "DELETE FROM driver_skills WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των δεξιοτήτων: " . $e->getMessage());
        }
    }
}
