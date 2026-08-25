<?php

namespace Drivejob\Models\Driver;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των δεξιοτήτων των οδηγών
 */
class SkillModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'driver_skills');
    }

    /**
     * Ανάκτηση των δεξιοτήτων ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι δεξιότητες του οδηγού
     */
    public function getDriverSkills($driverId)
    {
        try {
            return $this->selectOne(['driver_id' => $driverId]) ?: [];
        } catch (PDOException $e) {
            Logger::error('Error getting driver skills: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ενημερώνει τις δεξιότητες ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $skills Δεδομένα δεξιοτήτων
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverSkills($driverId, $skills)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingSkills = $this->getDriverSkills($driverId);

            if (!empty($existingSkills)) {
                // Ενημέρωση υπάρχουσας εγγραφής
                return $this->update($skills, ['driver_id' => $driverId]);
            } else {
                // Δημιουργία νέας εγγραφής με driver_id
                $skillsData = $skills;
                $skillsData['driver_id'] = $driverId;

                return $this->insert($skillsData) !== false;
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverSkills: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανακτά την εμπειρία οχημάτων ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Λίστα με την εμπειρία σε οχήματα
     */
    public function getDriverVehicleExperience($driverId)
    {
        try {
            $table = 'driver_vehicle_experience';
            $sql = "SELECT * FROM $table WHERE driver_id = ? ORDER BY years DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);

            $experience = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Προσθήκη των ονομάτων των τύπων οχημάτων για εμφάνιση
            foreach ($experience as &$exp) {
                $exp['vehicle_type_name'] = $this->getVehicleTypeName($exp['vehicle_category'], $exp['vehicle_type']);
            }

            return $experience;
        } catch (PDOException $e) {
            Logger::error('Error in getDriverVehicleExperience: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Διαγράφει όλη την εμπειρία οχημάτων ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverVehicleExperience($driverId)
    {
        try {
            $table = 'driver_vehicle_experience';
            $sql = "DELETE FROM $table WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverVehicleExperience: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθέτει εμπειρία οχημάτων για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param array $vehicleExperience Λίστα με την εμπειρία σε οχήματα
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverVehicleExperience($driverId, $vehicleExperience)
    {
        /*
         * Καθαρίστηκε 25/08/2026: η παλιά εκδοχή δεν είχε εκτελεστεί ΠΟΤΕ
         * (κανείς δεν την καλούσε) και στην πρώτη πραγματική κλήση έσκασε
         * — Logger::info(msg, "string") ενώ το context πρέπει να είναι
         * array. Έγραφε επίσης όλα τα δεδομένα του οδηγού σε δικό της
         * debug-αρχείο (logs/vehicle_experience_debug.log) σε κάθε κλήση.
         *
         * Στρατηγική: διαγραφή όλων + επανεγγραφή όσων ήρθαν. Ο caller
         * (DriversController::update) την καλεί ΜΟΝΟ όταν η φόρμα δηλώνει
         * την ενότητα με το vehicle_experience_submitted.
         */
        try {
            $this->deleteDriverVehicleExperience($driverId);

            if (empty($vehicleExperience)) {
                return true;
            }

            $sql = 'INSERT INTO driver_vehicle_experience (
                driver_id, vehicle_category, vehicle_type, transport_type, employment_type,
                years, months, days, start_date, end_date, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->pdo->prepare($sql);

            $inserted = 0;
            $failed = 0;

            foreach ($vehicleExperience as $exp) {
                // Γραμμές χωρίς κατηγορία οχήματος δεν έχουν νόημα.
                if (empty($exp['vehicle_category'])) {
                    continue;
                }

                $ok = $stmt->execute([
                    $driverId,
                    $exp['vehicle_category'],
                    $exp['vehicle_type'] ?? '',
                    $exp['transport_type'] ?? 'freight',
                    $exp['employment_type'] ?? 'employee',
                    intval($exp['years'] ?? 0),
                    intval($exp['months'] ?? 0),
                    intval($exp['days'] ?? 0),
                    ($exp['start_date'] ?? '') !== '' ? $exp['start_date'] : null,
                    ($exp['end_date'] ?? '') !== '' ? $exp['end_date'] : null,
                    $exp['description'] ?? '',
                ]);

                if ($ok) {
                    $inserted++;
                } else {
                    $failed++;
                    Logger::error('Failed to insert vehicle experience row', [
                        'driver_id' => $driverId,
                        'error' => $stmt->errorInfo(),
                    ]);
                }
            }

            Logger::info('Vehicle experience updated', [
                'driver_id' => $driverId,
                'inserted' => $inserted,
                'failed' => $failed,
            ]);

            return $failed === 0;
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverVehicleExperience: ' . $e->getMessage(), [
                'driver_id' => $driverId,
            ]);
            return false;
        }
    }

    /**
     * Επιστρέφει το όνομα του τύπου οχήματος με βάση την κατηγορία και τον κωδικό τύπου
     * 
     * @param string $category Κατηγορία οχήματος
     * @param string $type Κωδικός τύπου οχήματος
     * @return string Όνομα τύπου οχήματος
     */
    private function getVehicleTypeName($category, $type)
    {
        /*
         * Παλιά εδώ ζούσε μισός πίνακας ονομάτων (3 από τις 10 κατηγορίες,
         * με σχόλιο «Προσθέστε και τις υπόλοιπες») — τα ταξί, τα λεωφορεία
         * και τα υπόλοιπα εμφανίζονταν με τον κωδικό τους. Η μία πηγή
         * αλήθειας είναι πλέον το VehicleExperienceTypes.
         */
        return \Drivejob\Helpers\VehicleExperienceTypes::typeLabel((string) $category, (string) $type);
    }

    /**
     * Προσθέτει ΜΙΑ εγγραφή προϋπηρεσίας. Επιστρέφει το id της ή false.
     *
     * Μέρος του νέου μοντέλου «κάθε εγγραφή αποθηκεύεται τη στιγμή της
     * προσθήκης» — όχι μαζική διαγραφή/επανεγγραφή στο τέλος.
     */
    public function addDriverVehicleExperience($driverId, array $exp)
    {
        try {
            $sql = 'INSERT INTO driver_vehicle_experience (
                driver_id, vehicle_category, vehicle_type, transport_type, employment_type,
                years, months, days, start_date, end_date, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([
                $driverId,
                $exp['vehicle_category'],
                $exp['vehicle_type'] ?? '',
                $exp['transport_type'] ?? 'freight',
                $exp['employment_type'] ?? 'employee',
                intval($exp['years'] ?? 0),
                intval($exp['months'] ?? 0),
                intval($exp['days'] ?? 0),
                ($exp['start_date'] ?? '') !== '' ? $exp['start_date'] : null,
                ($exp['end_date'] ?? '') !== '' ? $exp['end_date'] : null,
                $exp['description'] ?? '',
            ]);

            return $ok ? (int) $this->pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            Logger::error('Error in addDriverVehicleExperience: ' . $e->getMessage(), ['driver_id' => $driverId]);
            return false;
        }
    }

    /**
     * Διαγράφει ΜΙΑ εγγραφή προϋπηρεσίας — μόνο αν ανήκει στον οδηγό.
     */
    public function deleteDriverVehicleExperienceRow($driverId, $rowId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM driver_vehicle_experience WHERE id = ? AND driver_id = ?'
            );
            $stmt->execute([(int) $rowId, (int) $driverId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverVehicleExperienceRow: ' . $e->getMessage(), ['driver_id' => $driverId]);
            return false;
        }
    }

    /**
     * Επιστρέφει τα δεδομένα αυτοαξιολόγησης ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Στοιχεία αυτοαξιολόγησης οδηγού ή κενός πίνακας αν δεν βρέθηκαν
     */
    public function getDriverAssessment($driverId)
    {
        try {
            $table = 'driver_assessment';
            $sql = "SELECT * FROM $table WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Έλεγχος για δεδομένα τηλεματικής
                $telemetryData = $this->getDriverTelemetryData($driverId);
                if ($telemetryData) {
                    $result['telemetry_data'] = $telemetryData;
                }

                return $result;
            }

            return [];
        } catch (PDOException $e) {
            Logger::error('Error in getDriverAssessment: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ενημερώνει τα δεδομένα αυτοαξιολόγησης ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $assessmentData Δεδομένα αυτοαξιολόγησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverAssessment($driverId, $assessmentData)
    {
        try {
            // Υπολογισμός των επιμέρους βαθμολογιών
            $scores = $this->calculateAssessmentScores($assessmentData);

            // Συγχώνευση των δεδομένων αυτοαξιολόγησης με τις βαθμολογίες
            $data = array_merge($assessmentData, $scores);

            $table = 'driver_assessment';
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingAssessment = $this->getDriverAssessment($driverId);

            if (!empty($existingAssessment)) {
                // Προσθήκη του updated_at
                $data['updated_at'] = date('Y-m-d H:i:s');

                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE $table SET " .
                    implode(', ', array_map(function ($key) {
                        return "$key = :$key";
                    }, array_keys($data))) .
                    " WHERE driver_id = :driver_id";

                $params = $data;
                $params['driver_id'] = $driverId;

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            } else {
                // Δημιουργία νέας εγγραφής
                $data['driver_id'] = $driverId;
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                // Δημιουργία SQL για εισαγωγή
                $columns = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_map(function ($key) {
                    return ":$key";
                }, array_keys($data)));

                $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($data);
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverAssessment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Υπολογίζει τις βαθμολογίες αυτοαξιολόγησης
     * 
     * @param array $assessmentData Δεδομένα αυτοαξιολόγησης
     * @return array Υπολογισμένες βαθμολογίες
     */
    private function calculateAssessmentScores($assessmentData)
    {
        // Βάρη για κάθε κατηγορία και τις ερωτήσεις της
        $weights = [
            // Οδηγικές Ικανότητες (40%)
            'driving_skills' => [
                'driving_experience' => 0.08,
                'annual_kilometers' => 0.08,
                'driving_conditions' => 0.08,
                'eco_driving_rating' => 0.08,
                'night_driving' => 0.08
            ],

            // Ασφάλεια & Συμμόρφωση (30%)
            'safety_compliance' => [
                'accidents' => 0.06,
                'traffic_violations' => 0.06,
                'tachograph_compliance' => 0.06,
                'safety_check' => 0.06,
                'load_securing' => 0.06
            ],

            // Επαγγελματισμός (15%)
            'professionalism' => [
                'punctuality' => 0.0375,
                'customer_interaction' => 0.0375,
                'appearance' => 0.0375,
                'documentation' => 0.0375
            ],

            // Τεχνικές Γνώσεις (15%)
            'technical_knowledge' => [
                'vehicle_maintenance' => 0.0375,
                'troubleshooting' => 0.0375,
                'navigation_skills' => 0.0375,
                'technical_knowledge' => 0.0375
            ]
        ];

        $scores = [];

        // Υπολογισμός βαθμολογίας για κάθε κατηγορία
        foreach ($weights as $category => $categoryWeights) {
            $categoryScore = 0;
            $totalWeight = 0;

            foreach ($categoryWeights as $field => $weight) {
                if (isset($assessmentData[$field])) {
                    $value = intval($assessmentData[$field]);
                    $categoryScore += $value * $weight * 20; // Μετατροπή σε κλίμακα 100
                    $totalWeight += $weight;
                }
            }

            // Υπολογισμός τελικής βαθμολογίας κατηγορίας
            if ($totalWeight > 0) {
                $scores[$category] = round($categoryScore / $totalWeight);
            } else {
                $scores[$category] = 0;
            }
        }

        // Υπολογισμός συνολικής βαθμολογίας
        $totalScore = 0;
        $validScores = 0;

        foreach ($scores as $categoryScore) {
            if ($categoryScore > 0) {
                $totalScore += $categoryScore;
                $validScores++;
            }
        }

        if ($validScores > 0) {
            $scores['total_score'] = round($totalScore / $validScores);
        } else {
            $scores['total_score'] = 0;
        }

        return $scores;
    }

    /**
     * Λαμβάνει τα δεδομένα τηλεματικής του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|null Τελευταία δεδομένα τηλεματικής ή null
     */
    public function getDriverTelemetryData($driverId)
    {
        try {
            $table = 'driver_telemetry';
            $sql = "SELECT * FROM $table 
                WHERE driver_id = ? 
                ORDER BY date_collected DESC 
                LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error in getDriverTelemetryData: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Αποθηκεύει νέα δεδομένα τηλεματικής για τον οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param array $telemetryData Δεδομένα τηλεματικής
     * @return bool Επιτυχία/αποτυχία
     */
    public function saveDriverTelemetryData($driverId, $telemetryData)
    {
        try {
            $table = 'driver_telemetry';
            $sql = "INSERT INTO $table (
                    driver_id, avg_speed, max_speed, harsh_braking, 
                    harsh_acceleration, harsh_cornering, fuel_consumption, 
                    total_distance, score, date_collected
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $driverId,
                $telemetryData['avg_speed'],
                $telemetryData['max_speed'],
                $telemetryData['harsh_braking'],
                $telemetryData['harsh_acceleration'],
                $telemetryData['harsh_cornering'],
                $telemetryData['fuel_consumption'],
                $telemetryData['total_distance'],
                $telemetryData['score'],
                $telemetryData['date_collected']
            ]);
        } catch (PDOException $e) {
            Logger::error('Error in saveDriverTelemetryData: ' . $e->getMessage());
            return false;
        }
    }
}
