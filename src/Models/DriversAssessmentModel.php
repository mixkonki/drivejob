<?php

namespace Drivejob\Models;

use Drivejob\Core\Database;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Μοντέλο για την αυτοαξιολόγηση οδηγών
 */
class DriversAssessmentModel extends BaseModel
{
    /**
     * Ο πίνακας της βάσης δεδομένων
     *
     * @var string
     */
    protected $table = 'driver_assessments';

    /**
     * Τα πεδία που μπορούν να συμπληρωθούν
     *
     * @var array
     */
    protected $fillable = [
        'driver_id',
        'driving_skills',
        'vehicle_knowledge',
        'safety_awareness',
        'time_management',
        'customer_service',
        'stress_handling',
        'comments',
        'created_at',
        'updated_at'
    ];

    /**
     * Constructor
     *
     * @param \PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        parent::__construct($pdo);
    }

    /**
     * Δημιουργεί μια νέα αυτοαξιολόγηση
     *
     * @param array $data Τα δεδομένα της αυτοαξιολόγησης
     * @return int|bool Το ID της νέας αυτοαξιολόγησης ή false σε περίπτωση αποτυχίας
     */
    public function create(array $data)
    {
        // Προσθήκη των timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {
            // Έλεγχος αν υπάρχει ήδη αυτοαξιολόγηση για τον οδηγό
            $existingAssessment = $this->getDriverAssessment($data['driver_id']);

            if ($existingAssessment) {
                // Αν υπάρχει ήδη, ενημέρωση
                return $this->update($existingAssessment['id'], $data);
            }

            // Δημιουργία νέας αυτοαξιολόγησης
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));

            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά τη δημιουργία αυτοαξιολόγησης', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            throw new DatabaseException('Σφάλμα κατά τη δημιουργία αυτοαξιολόγησης', 0, null, [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα αυτοαξιολόγηση
     *
     * @param int $id Το ID της αυτοαξιολόγησης
     * @param array $data Τα νέα δεδομένα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function update($id, array $data)
    {
        // Προσθήκη του timestamp ενημέρωσης
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {
            $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

            $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";

            $values = array_values($data);
            $values[] = $id;

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την ενημέρωση αυτοαξιολόγησης', [
                'id' => $id,
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            throw new DatabaseException('Σφάλμα κατά την ενημέρωση αυτοαξιολόγησης', 0, null, [
                'id' => $id,
                'message' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Βρίσκει την αυτοαξιολόγηση ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return array|null Τα δεδομένα της αυτοαξιολόγησης ή null αν δεν βρέθηκε
     */
    public function getDriverAssessment($driverId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE driver_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την αναζήτηση αυτοαξιολόγησης οδηγού', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);

            throw new DatabaseException('Σφάλμα κατά την αναζήτηση αυτοαξιολόγησης οδηγού', 0, null, [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Διαγράφει την αυτοαξιολόγηση ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Επιτυχία ή αποτυχία
     */
    public function deleteByDriverId($driverId)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE driver_id = ?";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId]);
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά τη διαγραφή αυτοαξιολόγησης οδηγού', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);

            throw new DatabaseException('Σφάλμα κατά τη διαγραφή αυτοαξιολόγησης οδηγού', 0, null, [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Βρίσκει τις κορυφαίες αυτοαξιολογήσεις
     *
     * @param int $limit Ο μέγιστος αριθμός αποτελεσμάτων
     * @return array Οι κορυφαίες αυτοαξιολογήσεις
     */
    public function findTopRated($limit = 10)
    {
        try {
            $sql = "SELECT a.*, d.first_name, d.last_name, d.profile_image,
                    (a.driving_skills + a.vehicle_knowledge + a.safety_awareness + 
                     a.time_management + a.customer_service + a.stress_handling) / 6 as average_rating
                    FROM {$this->table} a
                    JOIN drivers d ON a.driver_id = d.id
                    ORDER BY average_rating DESC
                    LIMIT ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την αναζήτηση κορυφαίων αυτοαξιολογήσεων', [
                'message' => $e->getMessage()
            ]);

            throw new DatabaseException('Σφάλμα κατά την αναζήτηση κορυφαίων αυτοαξιολογήσεων', 0, null, [
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Υπολογίζει τον μέσο όρο αυτοαξιολόγησης για έναν οδηγό
     *
     * @param int $driverId Το ID του οδηγού
     * @return float|null Ο μέσος όρος αυτοαξιολόγησης ή null αν δεν βρέθηκε
     */
    public function calculateAverageRating($driverId)
    {
        try {
            $assessment = $this->getDriverAssessment($driverId);

            if (!$assessment) {
                return null;
            }

            $ratingFields = [
                'driving_skills',
                'vehicle_knowledge',
                'safety_awareness',
                'time_management',
                'customer_service',
                'stress_handling'
            ];

            $sum = 0;
            $count = 0;

            foreach ($ratingFields as $field) {
                if (isset($assessment[$field]) && is_numeric($assessment[$field])) {
                    $sum += $assessment[$field];
                    $count++;
                }
            }

            return $count > 0 ? $sum / $count : null;
        } catch (\Exception $e) {
            Logger::error('Σφάλμα κατά τον υπολογισμό μέσου όρου αυτοαξιολόγησης', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Προσθέτει μια νέα αυτοαξιολόγηση
     *
     * @param array $data Τα δεδομένα της αυτοαξιολόγησης
     * @return int|bool Το ID της νέας αυτοαξιολόγησης ή false σε περίπτωση αποτυχίας
     */
    public function addAssessment(array $data)
    {
        return $this->create($data);
    }

    /**
     * Ενημερώνει μια υπάρχουσα αυτοαξιολόγηση με βάση το driver_id
     *
     * @param int $driverId Το ID του οδηγού
     * @param array $data Τα νέα δεδομένα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updateAssessment($driverId, array $data)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη αυτοαξιολόγηση για τον οδηγό
            $existingAssessment = $this->getDriverAssessment($driverId);

            if (!$existingAssessment) {
                // Αν δεν υπάρχει, δημιουργία νέας
                $data['driver_id'] = $driverId;
                return $this->create($data);
            }

            // Αν υπάρχει, ενημέρωση
            return $this->update($existingAssessment['id'], $data);
        } catch (\Exception $e) {
            Logger::error('Σφάλμα κατά την ενημέρωση αυτοαξιολόγησης οδηγού', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            throw new DatabaseException('Σφάλμα κατά την ενημέρωση αυτοαξιολόγησης οδηγού', 0, null, [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Διαγράφει την αυτοαξιολόγηση ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Επιτυχία ή αποτυχία
     */
    public function deleteAssessment($driverId)
    {
        return $this->deleteByDriverId($driverId);
    }
}
