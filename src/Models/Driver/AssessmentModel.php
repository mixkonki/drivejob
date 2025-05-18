<?php

namespace Drivejob\Models\Driver;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των αξιολογήσεων των οδηγών
 */
class AssessmentModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'driver_assessments');
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
            $assessment = $this->selectOne(['driver_id' => $driverId]);

            // Αν δεν υπάρχει αξιολόγηση, επιστρέφουμε ένα κενό πίνακα
            if (!$assessment) {
                return [];
            }

            // Λήψη των δεδομένων τηλεματικής αν υπάρχουν
            $telemetryQuery = "SELECT * FROM driver_telemetry WHERE driver_id = ? ORDER BY created_at DESC LIMIT 1";
            $telemetry = $this->queryOne($telemetryQuery, [$driverId]);

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
        } catch (PDOException $e) {
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
            $exists = $this->selectOne(['driver_id' => $driverId]) !== null;

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
                return $this->update($assessmentData, ['driver_id' => $driverId]);
            } else {
                // Δημιουργία νέας αξιολόγησης
                $assessmentData['driver_id'] = $driverId;
                $assessmentData['created_at'] = date('Y-m-d H:i:s');
                return $this->insert($assessmentData);
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverAssessment: ' . $e->getMessage());
            return false;
        }
    }
}
