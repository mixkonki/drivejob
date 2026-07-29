<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις αξιολογήσεις των οδηγών
 */
class DriverRatingRepository
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
        $this->db = $db ?? new Database();
    }

    /**
     * Βρίσκει όλες τις αξιολογήσεις ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Οι αξιολογήσεις του οδηγού
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId, int $page = 1, int $limit = 10): array
    {
        try {
            $offset = ($page - 1) * $limit;

            $sql = "SELECT r.*, c.company_name, c.logo
                    FROM driver_ratings r
                    LEFT JOIN companies c ON r.company_id = c.id
                    WHERE r.driver_id = :driver_id
                    ORDER BY r.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $params = [
                ':driver_id' => $driverId,
                ':limit' => $limit,
                ':offset' => $offset
            ];

            // Μέτρηση συνολικών αποτελεσμάτων
            $countSql = "SELECT COUNT(*) FROM driver_ratings WHERE driver_id = :driver_id";
            $countParams = [':driver_id' => $driverId];
            $totalResults = $this->db->query($countSql, $countParams)->fetchColumn();

            // Εκτέλεση του κύριου ερωτήματος
            $result = $this->db->query($sql, $params);
            $ratings = $result->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'results' => $ratings,
                'pagination' => [
                    'total' => $totalResults,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalResults / $limit)
                ]
            ];
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των αξιολογήσεων: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει μια αξιολόγηση με βάση το ID της
     *
     * @param int $id Το ID της αξιολόγησης
     * @return array|null Τα στοιχεία της αξιολόγησης ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT r.*, c.company_name, c.logo
                    FROM driver_ratings r
                    LEFT JOIN companies c ON r.company_id = c.id
                    WHERE r.id = :id";

            $params = [':id' => $id];

            $result = $this->db->query($sql, $params);
            $rating = $result->fetch(\PDO::FETCH_ASSOC);

            return $rating ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της αξιολόγησης: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί μια νέα αξιολόγηση
     *
     * @param array $data Τα δεδομένα της αξιολόγησης
     * @return int Το ID της νέας αξιολόγησης
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO driver_ratings (
                    driver_id, company_id, job_id, rating, comment, 
                    professionalism_rating, safety_rating, punctuality_rating, communication_rating,
                    created_at, updated_at
                ) VALUES (
                    :driver_id, :company_id, :job_id, :rating, :comment,
                    :professionalism_rating, :safety_rating, :punctuality_rating, :communication_rating,
                    NOW(), NOW()
                )";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':company_id' => $data['company_id'],
                ':job_id' => $data['job_id'] ?? null,
                ':rating' => $data['rating'],
                ':comment' => $data['comment'] ?? null,
                ':professionalism_rating' => $data['professionalism_rating'] ?? null,
                ':safety_rating' => $data['safety_rating'] ?? null,
                ':punctuality_rating' => $data['punctuality_rating'] ?? null,
                ':communication_rating' => $data['communication_rating'] ?? null
            ];

            $this->db->query($sql, $params);
            $ratingId = $this->db->lastInsertId();

            // Ενημέρωση του μέσου όρου αξιολόγησης του οδηγού
            $this->updateDriverAverageRating($data['driver_id']);

            return $ratingId;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία της αξιολόγησης: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα αξιολόγηση
     *
     * @param int $id Το ID της αξιολόγησης
     * @param array $data Τα νέα δεδομένα της αξιολόγησης
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE driver_ratings SET
                    rating = :rating,
                    comment = :comment,
                    professionalism_rating = :professionalism_rating,
                    safety_rating = :safety_rating,
                    punctuality_rating = :punctuality_rating,
                    communication_rating = :communication_rating,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':rating' => $data['rating'],
                ':comment' => $data['comment'] ?? null,
                ':professionalism_rating' => $data['professionalism_rating'] ?? null,
                ':safety_rating' => $data['safety_rating'] ?? null,
                ':punctuality_rating' => $data['punctuality_rating'] ?? null,
                ':communication_rating' => $data['communication_rating'] ?? null
            ];

            $stmt = $this->db->query($sql, $params);
            $success = $stmt->rowCount() > 0;

            if ($success) {
                // Λήψη του driver_id από την αξιολόγηση
                $rating = $this->find($id);
                if ($rating) {
                    // Ενημέρωση του μέσου όρου αξιολόγησης του οδηγού
                    $this->updateDriverAverageRating($rating['driver_id']);
                }
            }

            return $success;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση της αξιολόγησης: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει μια αξιολόγηση
     *
     * @param int $id Το ID της αξιολόγησης
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            // Λήψη του driver_id από την αξιολόγηση πριν τη διαγραφή
            $rating = $this->find($id);
            if (!$rating) {
                return false;
            }

            $sql = "DELETE FROM driver_ratings WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->query($sql, $params);
            $success = $stmt->rowCount() > 0;

            if ($success) {
                // Ενημέρωση του μέσου όρου αξιολόγησης του οδηγού
                $this->updateDriverAverageRating($rating['driver_id']);
            }

            return $success;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της αξιολόγησης: " . $e->getMessage());
        }
    }

    /**
     * Λαμβάνει τον μέσο όρο αξιολόγησης ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return float Ο μέσος όρος αξιολόγησης
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function getAverageRating(int $driverId): float
    {
        try {
            $sql = "SELECT AVG(rating) FROM driver_ratings WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $result = $this->db->query($sql, $params);
            $avgRating = $result->fetchColumn();

            return $avgRating ? (float)$avgRating : 0.0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τον υπολογισμό του μέσου όρου αξιολόγησης: " . $e->getMessage());
        }
    }

    /**
     * Λαμβάνει αναλυτικά στατιστικά αξιολόγησης ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array Τα στατιστικά αξιολόγησης
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function getRatingStats(int $driverId): array
    {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_ratings,
                    AVG(rating) as avg_rating,
                    AVG(professionalism_rating) as avg_professionalism,
                    AVG(safety_rating) as avg_safety,
                    AVG(punctuality_rating) as avg_punctuality,
                    AVG(communication_rating) as avg_communication
                    FROM driver_ratings 
                    WHERE driver_id = :driver_id";

            $params = [':driver_id' => $driverId];

            $result = $this->db->query($sql, $params);
            $stats = $result->fetch(\PDO::FETCH_ASSOC);

            // Μετατροπή των τιμών σε float
            foreach ($stats as $key => $value) {
                if (strpos($key, 'avg_') === 0) {
                    $stats[$key] = $value ? (float)$value : 0.0;
                }
            }

            return $stats;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη λήψη των στατιστικών αξιολόγησης: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει τον μέσο όρο αξιολόγησης ενός οδηγού στον πίνακα drivers
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    private function updateDriverAverageRating(int $driverId): bool
    {
        try {
            $avgRating = $this->getAverageRating($driverId);

            $sql = "UPDATE drivers SET 
                    average_rating = :average_rating,
                    updated_at = NOW()
                    WHERE id = :driver_id";

            $params = [
                ':driver_id' => $driverId,
                ':average_rating' => $avgRating
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση του μέσου όρου αξιολόγησης: " . $e->getMessage());
        }
    }
}
