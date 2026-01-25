<?php

namespace Drivejob\Models\Driver;

use Drivejob\Models\BaseModel;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Μοντέλο για τα περιστατικά οδηγών
 */
class IncidentModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo, 'driver_incidents');
    }

    /**
     * Επιστρέφει τα περιστατικά ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Τα περιστατικά του οδηγού
     */
    public function getDriverIncidents($driverId)
    {
        try {
            $sql = "SELECT * FROM driver_incidents WHERE driver_id = :driver_id ORDER BY incident_date DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την ανάκτηση των περιστατικών οδηγού', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new DatabaseException('Σφάλμα κατά την ανάκτηση των περιστατικών οδηγού', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    /**
     * Προσθέτει ένα νέο περιστατικό
     * 
     * @param array $data Τα δεδομένα του περιστατικού
     * @return bool Αν η προσθήκη ήταν επιτυχής
     */
    public function addIncident($data)
    {
        try {
            $sql = "INSERT INTO driver_incidents (
                driver_id, incident_type, incident_date, description, location, severity, file_path, created_at
            ) VALUES (
                :driver_id, :incident_type, :incident_date, :description, :location, :severity, :file_path, :created_at
            )";

            $stmt = $this->pdo->prepare($sql);
            $driverId = $data['driver_id'];
            $incidentType = $data['incident_type'];
            $incidentDate = $data['incident_date'];
            $description = $data['description'];
            $location = $data['location'];
            $severity = $data['severity'];
            $filePath = $data['file_path'] ?? null;
            $createdAt = $data['created_at'];

            $stmt->bindParam(':driver_id', $driverId, \PDO::PARAM_INT);
            $stmt->bindParam(':incident_type', $incidentType, \PDO::PARAM_STR);
            $stmt->bindParam(':incident_date', $incidentDate, \PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, \PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, \PDO::PARAM_STR);
            $stmt->bindParam(':severity', $severity, \PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $filePath, \PDO::PARAM_STR);
            $stmt->bindParam(':created_at', $createdAt, \PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την προσθήκη περιστατικού', [
                'driver_id' => $data['driver_id'],
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new DatabaseException('Σφάλμα κατά την προσθήκη περιστατικού', [
                'driver_id' => $data['driver_id'],
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    /**
     * Ενημερώνει ένα περιστατικό
     * 
     * @param int $incidentId Το ID του περιστατικού
     * @param array $data Τα δεδομένα του περιστατικού
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     */
    public function updateIncident($incidentId, $data)
    {
        try {
            $sql = "UPDATE driver_incidents SET 
                incident_type = :incident_type,
                incident_date = :incident_date,
                description = :description,
                location = :location,
                severity = :severity,
                file_path = :file_path,
                updated_at = :updated_at
            WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $incidentId, \PDO::PARAM_INT);
            $stmt->bindParam(':incident_type', $data['incident_type'], \PDO::PARAM_STR);
            $stmt->bindParam(':incident_date', $data['incident_date'], \PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], \PDO::PARAM_STR);
            $stmt->bindParam(':location', $data['location'], \PDO::PARAM_STR);
            $stmt->bindParam(':severity', $data['severity'], \PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $data['file_path'] ?? null, \PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $data['updated_at'], \PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την ενημέρωση περιστατικού', [
                'incident_id' => $incidentId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new DatabaseException('Σφάλμα κατά την ενημέρωση περιστατικού', [
                'incident_id' => $incidentId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    /**
     * Διαγράφει ένα περιστατικό
     * 
     * @param int $incidentId Το ID του περιστατικού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     */
    public function deleteIncident($incidentId)
    {
        try {
            $sql = "DELETE FROM driver_incidents WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $incidentId, \PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά τη διαγραφή περιστατικού', [
                'incident_id' => $incidentId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new DatabaseException('Σφάλμα κατά τη διαγραφή περιστατικού', [
                'incident_id' => $incidentId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    /**
     * Επιστρέφει ένα περιστατικό με βάση το ID του
     * 
     * @param int $incidentId Το ID του περιστατικού
     * @return array|false Τα δεδομένα του περιστατικού ή false αν δεν βρέθηκε
     */
    public function getIncidentById($incidentId)
    {
        try {
            $sql = "SELECT * FROM driver_incidents WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $incidentId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Σφάλμα κατά την ανάκτηση περιστατικού', [
                'incident_id' => $incidentId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new DatabaseException('Σφάλμα κατά την ανάκτηση περιστατικού', [
                'incident_id' => $incidentId,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}
