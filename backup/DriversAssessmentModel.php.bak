<?php

namespace Drivejob\Models;

use Drivejob\Core\Logger;

/**
 * Μοντέλο για τη διαχείριση των αξιολογήσεων των οδηγών
 */
class DriversAssessmentModel
{
    private $pdo;
    private $profileModel;

    /**
     * Κατασκευαστής του μοντέλου
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
    }

    /**
     * Επιστρέφει τα στοιχεία αξιολόγησης ενός οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @return array|false Τα στοιχεία αξιολόγησης ή false αν δεν βρέθηκαν
     */
    public function getDriverAssessment($driverId)
    {
        try {
            $query = "SELECT * FROM driver_assessments WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$driverId]);

            $assessment = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Αν δεν υπάρχει αξιολόγηση, επιστρέφουμε ένα κενό πίνακα
            if (!$assessment) {
                return [];
            }

            // Λήψη των δεδομένων τηλεματικής αν υπάρχουν
            $telemetryQuery = "SELECT * FROM driver_telemetry WHERE driver_id = ? ORDER BY created_at DESC LIMIT 1";
            $telemetryStmt = $this->pdo->prepare($telemetryQuery);
            $telemetryStmt->execute([$driverId]);
            $telemetry = $telemetryStmt->fetch(\PDO::FETCH_ASSOC);

            if ($telemetry) {
                $assessment['telemetry_data'] = [
                    'avg_speed' => $telemetry['avg_speed'],
                    'harsh_braking' => $telemetry['harsh_braking'],
                    'harsh_acceleration' => $telemetry['harsh_acceleration'],
                    'harsh_cornering' => $telemetry['harsh_cornering'],
                    'fuel_consumption' => $telemetry['fuel_consumption'],
                    'total_distance' => $telemetry['total_distance'],
                    'score' => $telemetry['score'],
                    'updated_at' => $telemetry['created_at']
                ];
            }

            return $assessment;
        } catch (\PDOException $e) {
            Logger::error('Error in getDriverAssessment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει την αξιολόγηση ενός οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα αξιολόγησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverAssessment($driverId, $data)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη αξιολόγηση για τον οδηγό
            $checkQuery = "SELECT id FROM driver_assessments WHERE driver_id = ?";
            $checkStmt = $this->pdo->prepare($checkQuery);
            $checkStmt->execute([$driverId]);
            $exists = $checkStmt->fetch(\PDO::FETCH_ASSOC);

            // Προετοιμασία των δεδομένων
            $assessmentData = [
                'punctuality' => $data['punctuality'] ?? null,
                'customer_interaction' => $data['customer_interaction'] ?? null,
                'appearance' => $data['appearance'] ?? null,
                'documentation' => $data['documentation'] ?? null,
                'vehicle_maintenance' => $data['vehicle_maintenance'] ?? null,
                'troubleshooting' => $data['troubleshooting'] ?? null,
                'navigation_skills' => $data['navigation_skills'] ?? null,
                'technical_knowledge' => $data['technical_knowledge'] ?? null,
                'driving_experience' => $data['driving_experience'] ?? null,
                'annual_kilometers' => $data['annual_kilometers'] ?? null,
                'driving_conditions' => $data['driving_conditions'] ?? null,
                'eco_driving_rating' => $data['eco_driving_rating'] ?? null,
                'night_driving' => $data['night_driving'] ?? null,
                'accidents' => $data['accidents'] ?? null,
                'traffic_violations' => $data['traffic_violations'] ?? null,
                'tachograph_compliance' => $data['tachograph_compliance'] ?? null,
                'safety_check' => $data['safety_check'] ?? null,
                'load_securing' => $data['load_securing'] ?? null,
                'comments' => $data['comments'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Υπολογισμός του συνολικού σκορ
            $totalScore = 0;
            $scoreCount = 0;

            foreach ($assessmentData as $key => $value) {
                if (is_numeric($value) && $key !== 'driver_id' && $key !== 'updated_at' && $key !== 'comments') {
                    $totalScore += (int)$value;
                    $scoreCount++;
                }
            }

            $assessmentData['overall_score'] = $scoreCount > 0 ? round($totalScore / $scoreCount * 20) : 0;

            if ($exists) {
                // Ενημέρωση υπάρχουσας αξιολόγησης
                $updateFields = [];
                $updateParams = [];

                foreach ($assessmentData as $key => $value) {
                    $updateFields[] = "$key = ?";
                    $updateParams[] = $value;
                }

                $updateParams[] = $driverId;

                $updateQuery = "UPDATE driver_assessments SET " . implode(', ', $updateFields) . " WHERE driver_id = ?";
                $updateStmt = $this->pdo->prepare($updateQuery);

                return $updateStmt->execute($updateParams);
            } else {
                // Δημιουργία νέας αξιολόγησης
                $assessmentData['driver_id'] = $driverId;
                $assessmentData['created_at'] = date('Y-m-d H:i:s');

                $fields = implode(', ', array_keys($assessmentData));
                $placeholders = implode(', ', array_fill(0, count($assessmentData), '?'));

                $insertQuery = "INSERT INTO driver_assessments ($fields) VALUES ($placeholders)";
                $insertStmt = $this->pdo->prepare($insertQuery);

                return $insertStmt->execute(array_values($assessmentData));
            }
        } catch (\PDOException $e) {
            Logger::error('Error in updateDriverAssessment: ' . $e->getMessage());
            return false;
        }
    }
}
