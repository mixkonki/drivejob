<?php

namespace Drivejob\Models\Driver;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των συμβάντων των οδηγών
 */
class IncidentModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'driver_incidents');
    }

    /**
     * Αποθηκεύει ένα νέο συμβάν για τον οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param array $incidentData Δεδομένα συμβάντος
     * @return int|bool ID του συμβάντος ή false σε αποτυχία
     */
    public function saveDriverIncident($driverId, $incidentData)
    {
        try {
            $data = [
                'driver_id' => $driverId,
                'incident_type' => $incidentData['incident_type'],
                'incident_date' => $incidentData['incident_date'],
                'description' => $incidentData['description'],
                'severity' => $incidentData['severity']
            ];

            return $this->insert($data);
        } catch (PDOException $e) {
            Logger::error('Error in saveDriverIncident: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Λαμβάνει τα συμβάντα ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Λίστα συμβάντων
     */
    public function getDriverIncidents($driverId)
    {
        try {
            return $this->select(
                ['driver_id' => $driverId],
                '*',
                ['incident_date' => 'DESC']
            );
        } catch (PDOException $e) {
            Logger::error('Error in getDriverIncidents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Λαμβάνει ένα συγκεκριμένο συμβάν
     * 
     * @param int $incidentId ID του συμβάντος
     * @return array|null Δεδομένα συμβάντος ή null αν δεν βρέθηκε
     */
    public function getIncidentById($incidentId)
    {
        try {
            return $this->selectOne(['id' => $incidentId]);
        } catch (PDOException $e) {
            Logger::error('Error in getIncidentById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ενημερώνει ένα συμβάν
     * 
     * @param int $incidentId ID του συμβάντος
     * @param array $incidentData Νέα δεδομένα συμβάντος
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateIncident($incidentId, $incidentData)
    {
        try {
            return $this->update($incidentData, ['id' => $incidentId]);
        } catch (PDOException $e) {
            Logger::error('Error in updateIncident: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει ένα συμβάν
     * 
     * @param int $incidentId ID του συμβάντος
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteIncident($incidentId)
    {
        try {
            return $this->delete(['id' => $incidentId]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteIncident: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Λαμβάνει τα πρόσφατα συμβάντα ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param int $years Αριθμός ετών προς τα πίσω
     * @return array Λίστα πρόσφατων συμβάντων
     */
    public function getRecentIncidents($driverId, $years = 3)
    {
        try {
            $date = date('Y-m-d', strtotime("-$years years"));

            $sql = "SELECT * FROM {$this->table} 
                    WHERE driver_id = ? AND incident_date >= ? 
                    ORDER BY incident_date DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $date]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error in getRecentIncidents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Λαμβάνει τα συμβάντα ανά τύπο για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param string $incidentType Τύπος συμβάντος (π.χ. 'accident', 'traffic_violation')
     * @return array Λίστα συμβάντων
     */
    public function getIncidentsByType($driverId, $incidentType)
    {
        try {
            return $this->select(
                ['driver_id' => $driverId, 'incident_type' => $incidentType],
                '*',
                ['incident_date' => 'DESC']
            );
        } catch (PDOException $e) {
            Logger::error('Error in getIncidentsByType: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Υπολογίζει τον αριθμό συμβάντων ανά τύπο για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @return array Αριθμός συμβάντων ανά τύπο
     */
    public function countIncidentsByType($driverId)
    {
        try {
            $sql = "SELECT incident_type, COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE driver_id = ? 
                    GROUP BY incident_type";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $counts = [];

            foreach ($results as $row) {
                $counts[$row['incident_type']] = $row['count'];
            }

            return $counts;
        } catch (PDOException $e) {
            Logger::error('Error in countIncidentsByType: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Υπολογίζει το μέσο επίπεδο σοβαρότητας των συμβάντων ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string|null $incidentType Προαιρετικά φιλτράρισμα ανά τύπο συμβάντος
     * @return float Μέσο επίπεδο σοβαρότητας
     */
    public function getAverageSeverity($driverId, $incidentType = null)
    {
        try {
            $sql = "SELECT AVG(severity) as avg_severity 
                    FROM {$this->table} 
                    WHERE driver_id = ?";

            $params = [$driverId];

            if ($incidentType !== null) {
                $sql .= " AND incident_type = ?";
                $params[] = $incidentType;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? floatval($result['avg_severity']) : 0;
        } catch (PDOException $e) {
            Logger::error('Error in getAverageSeverity: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Λαμβάνει τις τάσεις συμβάντων ενός οδηγού ανά έτος
     * 
     * @param int $driverId ID του οδηγού
     * @param int $years Αριθμός ετών προς τα πίσω
     * @return array Αριθμός συμβάντων ανά έτος
     */
    public function getIncidentTrendsByYear($driverId, $years = 5)
    {
        try {
            $sql = "SELECT YEAR(incident_date) as year, COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE driver_id = ? AND incident_date >= DATE_SUB(CURDATE(), INTERVAL ? YEAR)
                    GROUP BY YEAR(incident_date) 
                    ORDER BY year";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $years]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $trends = [];

            // Έλεγχος στην πληρότητα των ετών
            $currentYear = date('Y');
            for ($i = 0; $i < $years; $i++) {
                $year = $currentYear - $i;
                $trends[$year] = 0;
            }

            foreach ($results as $row) {
                $trends[$row['year']] = intval($row['count']);
            }

            // Ταξινόμηση με βάση το έτος (αύξουσα)
            ksort($trends);

            return $trends;
        } catch (PDOException $e) {
            Logger::error('Error in getIncidentTrendsByYear: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Λαμβάνει το πιο πρόσφατο συμβάν ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|null Δεδομένα συμβάντος ή null αν δεν βρέθηκε
     */
    public function getLatestIncident($driverId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE driver_id = ? 
                    ORDER BY incident_date DESC 
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error in getLatestIncident: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Υπολογίζει το χρόνο από το τελευταίο συμβάν σε ημέρες
     * 
     * @param int $driverId ID του οδηγού
     * @return int Αριθμός ημερών από το τελευταίο συμβάν ή -1 αν δεν υπάρχει
     */
    public function getDaysSinceLastIncident($driverId)
    {
        try {
            $latestIncident = $this->getLatestIncident($driverId);

            if (!$latestIncident) {
                return -1; // Δεν υπάρχει συμβάν
            }

            $incidentDate = new \DateTime($latestIncident['incident_date']);
            $today = new \DateTime();

            return $incidentDate->diff($today)->days;
        } catch (\Exception $e) {
            Logger::error('Error in getDaysSinceLastIncident: ' . $e->getMessage());
            return -1;
        }
    }
}
