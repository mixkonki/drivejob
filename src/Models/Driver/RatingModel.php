<?php

namespace Drivejob\Models\Driver;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των αξιολογήσεων των οδηγών
 */
class RatingModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'driver_ratings');
    }

    /**
     * Επιστρέφει το ID της τελευταίας εγγραφής που προστέθηκε
     * 
     * @return int Το ID της τελευταίας εγγραφής
     */
    public function getLastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Επιστρέφει τη σύνδεση με τη βάση δεδομένων
     * 
     * @return PDO Η σύνδεση με τη βάση δεδομένων
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Επιστρέφει τα δεδομένα αξιολόγησης ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array|null Οι βαθμολογίες του οδηγού ή null αν δεν υπάρχουν
     */
    public function getDriverRatingDetails($driverId)
    {
        try {
            return $this->selectOne(['driver_id' => $driverId]);
        } catch (PDOException $e) {
            Logger::error('Error getting driver rating details: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Υπολογίζει και ενημερώνει τη συνολική βαθμολογία του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $ratings Οι βαθμολογίες του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverRating($driverId, $ratings)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingRating = $this->getDriverRatingDetails($driverId);

            // Προσθήκη της ημερομηνίας τελευταίας ενημέρωσης
            $ratings['last_updated'] = date('Y-m-d H:i:s');

            if ($existingRating) {
                // Ενημέρωση υπάρχουσας βαθμολογίας
                return $this->update($ratings, ['driver_id' => $driverId]);
            } else {
                // Δημιουργία νέας βαθμολογίας
                $ratings['driver_id'] = $driverId;
                return $this->insert($ratings) !== false;
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverRating: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανάκτηση της μέσης βαθμολογίας ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return float Η μέση βαθμολογία του οδηγού
     */
    public function getDriverRating($driverId)
    {
        try {
            // Πρώτα προσπαθούμε να πάρουμε την τιμή από τον πίνακα driver_ratings
            $rating = $this->selectOne(['driver_id' => $driverId], 'total_score');

            if ($rating && isset($rating['total_score'])) {
                // Μετατροπή του score 0-100 σε βαθμολογία 0-5
                return min(5, round($rating['total_score'] / 20, 1));
            }

            // Αν δεν υπάρχει, ελέγχουμε τον πίνακα driver_reviews
            $reviewsTable = 'driver_reviews';
            $sql = "SELECT AVG(rating) as avg_rating FROM $reviewsTable WHERE driver_id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['avg_rating'])) {
                return round($result['avg_rating'], 1);
            }

            // Αν δεν υπάρχει ούτε στις αξιολογήσεις, ελέγχουμε το πεδίο rating του πίνακα drivers
            $driversTable = 'drivers';
            $sql = "SELECT rating FROM $driversTable WHERE id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['rating'])) {
                return $result['rating'];
            }

            // Αλλιώς επιστρέφουμε 0
            return 0;
        } catch (PDOException $e) {
            Logger::error('Error getting driver rating: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ανάκτηση των αξιολογήσεων ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι αξιολογήσεις του οδηγού
     */
    public function getDriverReviews($driverId)
    {
        try {
            $reviewsTable = 'driver_reviews';
            $companiesTable = 'companies';

            $sql = "SELECT r.*, c.company_name
                FROM $reviewsTable r
                LEFT JOIN $companiesTable c ON r.company_id = c.id
                WHERE r.driver_id = :driver_id
                ORDER BY r.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error getting driver reviews: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Προσθήκη νέας αξιολόγησης για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param int $companyId ID της εταιρείας
     * @param float $rating Βαθμολογία (0-5)
     * @param string $comment Σχόλιο
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverReview($driverId, $companyId, $rating, $comment = '')
    {
        try {
            $reviewsTable = 'driver_reviews';
            $sql = "INSERT INTO $reviewsTable (driver_id, company_id, rating, comment, created_at) 
                    VALUES (:driver_id, :company_id, :rating, :comment, NOW())";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                'driver_id' => $driverId,
                'company_id' => $companyId,
                'rating' => $rating,
                'comment' => $comment
            ]);

            if ($result) {
                // Ενημέρωση της μέσης βαθμολογίας στον πίνακα drivers
                $this->updateDriverAverageRating($driverId);
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error adding driver review: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση της μέσης βαθμολογίας ενός οδηγού στον πίνακα drivers
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateDriverAverageRating($driverId)
    {
        try {
            $reviewsTable = 'driver_reviews';
            $driversTable = 'drivers';

            // Υπολογισμός της μέσης βαθμολογίας
            $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as count 
                    FROM $reviewsTable WHERE driver_id = :driver_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['avg_rating'])) {
                // Ενημέρωση του πίνακα drivers
                $sql = "UPDATE $driversTable SET 
                        rating = :rating, 
                        rating_count = :count 
                        WHERE id = :driver_id";

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    'rating' => round($result['avg_rating'], 1),
                    'count' => $result['count'],
                    'driver_id' => $driverId
                ]);
            }

            return false;
        } catch (PDOException $e) {
            Logger::error('Error updating driver average rating: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Υπολογίζει τη συνολική βαθμολογία ενός οδηγού συνδυάζοντας διάφορα κριτήρια
     * 
     * @param int $driverId ID του οδηγού
     * @return array Συνολική βαθμολογία και επιμέρους βαθμολογίες
     */
    public function calculateTotalRating($driverId)
    {
        $skillsModel = new SkillModel($this->pdo);
        $incidentModel = new IncidentModel($this->pdo);

        // Προεπιλεγμένες τιμές
        $ratings = [
            'skills_score' => 0,
            'safety_score' => 0,
            'professionalism_score' => 0,
            'technical_score' => 0,
            'total_score' => 0
        ];

        // 1. Βαθμολογία προσόντων/δεξιοτήτων (25%)
        $ratings['skills_score'] = $this->calculateQualificationsScore($driverId);

        // 2. Βαθμολογία ασφάλειας (30%)
        $ratings['safety_score'] = $this->calculateSafetyScore($driverId);

        // 3. Βαθμολογία επαγγελματισμού (25%)
        $assessment = $skillsModel->getDriverAssessment($driverId);
        if ($assessment) {
            $ratings['professionalism_score'] = $assessment['professionalism'] ?? 0;
        } else {
            $ratings['professionalism_score'] = 0;
        }

        // 4. Βαθμολογία τεχνικών δεξιοτήτων (20%)
        if ($assessment) {
            $ratings['technical_score'] = $assessment['technical_knowledge'] ?? 0;
        } else {
            $ratings['technical_score'] = 0;
        }

        // 5. Υπολογισμός της συνολικής βαθμολογίας
        $ratings['total_score'] =
            ($ratings['skills_score'] * 0.25) +
            ($ratings['safety_score'] * 0.30) +
            ($ratings['professionalism_score'] * 0.25) +
            ($ratings['technical_score'] * 0.20);

        return $ratings;
    }

    /**
     * Υπολογίζει τη βαθμολογία προσόντων/δεξιοτήτων
     * 
     * @param int $driverId ID του οδηγού
     * @return float Βαθμολογία προσόντων (0-100)
     */
    private function calculateQualificationsScore($driverId)
    {
        try {
            $licenseModel = new LicenseModel($this->pdo);
            $certificationModel = new CertificationModel($this->pdo);
            $profileModel = new ProfileModel($this->pdo);

            // 1. Λήψη όλων των αδειών οδήγησης
            $licenses = $licenseModel->getDriverLicenses($driverId);
            $licenseScore = 0;

            // 2. Βαθμολόγηση με βάση τις κατηγορίες και την ισχύ των αδειών
            foreach ($licenses as $license) {
                // Βασική βαθμολογία ανά κατηγορία
                $categoryScores = [
                    'B' => 5,
                    'BE' => 7,
                    'C' => 10,
                    'CE' => 15,
                    'C1' => 8,
                    'C1E' => 12,
                    'D' => 10,
                    'DE' => 15,
                    'D1' => 8,
                    'D1E' => 12,
                ];

                // Προσθήκη βαθμών για την κατηγορία
                if (isset($categoryScores[$license['license_type']])) {
                    $licenseScore += $categoryScores[$license['license_type']];
                }

                // Έλεγχος ΠΕΙ
                if (isset($license['has_pei']) && $license['has_pei'] == 1) {
                    $licenseScore += 5;
                }

                // Έλεγχος ισχύος
                if (!empty($license['expiry_date'])) {
                    $isExpired = strtotime($license['expiry_date']) < time();
                    if (!$isExpired) {
                        $licenseScore += 5; // Επιπλέον βαθμοί για άδεια σε ισχύ
                    }
                }
            }

            // 3. Λήψη και βαθμολόγηση πιστοποιήσεων ADR
            $adrCertificate = $certificationModel->getDriverADRCertificate($driverId);
            $adrScore = 0;

            if ($adrCertificate) {
                // Βαθμολογία με βάση τον τύπο ADR
                $adrTypeScores = [
                    'Π1' => 10,
                    'Π2' => 12,
                    'Π3' => 12,
                    'Π4' => 15,
                    'Π5' => 15,
                    'Π6' => 18,
                    'Π7' => 18,
                    'Π8' => 20
                ];

                if (isset($adrTypeScores[$adrCertificate['adr_type']])) {
                    $adrScore = $adrTypeScores[$adrCertificate['adr_type']];
                } else {
                    $adrScore = 10; // Βασική βαθμολογία για απροσδιόριστο τύπο
                }

                // Έλεγχος ισχύος
                if (!empty($adrCertificate['expiry_date'])) {
                    $isExpired = strtotime($adrCertificate['expiry_date']) < time();
                    if (!$isExpired) {
                        $adrScore += 5; // Επιπλέον βαθμοί για πιστοποιητικό σε ισχύ
                    }
                }
            }

            // 4. Λήψη και βαθμολόγηση άδειας χειριστή
            $operatorLicense = $certificationModel->getDriverOperatorLicense($driverId);
            $operatorScore = 0;

            if ($operatorLicense) {
                // Βασική βαθμολογία για την κατοχή άδειας χειριστή
                $operatorScore = 15;

                // Επιπλέον βαθμοί για κάθε υποειδικότητα
                $subSpecialities = $certificationModel->getDriverOperatorSubSpecialities($operatorLicense['id']);
                $operatorScore += count($subSpecialities) * 2;

                // Έλεγχος ισχύος
                if (!empty($operatorLicense['expiry_date'])) {
                    $isExpired = strtotime($operatorLicense['expiry_date']) < time();
                    if (!$isExpired) {
                        $operatorScore += 5; // Επιπλέον βαθμοί για άδεια σε ισχύ
                    }
                }
            }

            // 5. Λήψη και βαθμολόγηση ετών εμπειρίας
            $driver = $profileModel->getDriverById($driverId);
            $experienceScore = 0;

            if (isset($driver['experience_years'])) {
                // Κλιμακωτή βαθμολογία με βάση τα έτη εμπειρίας
                if ($driver['experience_years'] < 1) {
                    $experienceScore = 5;
                } elseif ($driver['experience_years'] < 3) {
                    $experienceScore = 10;
                } elseif ($driver['experience_years'] < 5) {
                    $experienceScore = 15;
                } elseif ($driver['experience_years'] < 10) {
                    $experienceScore = 20;
                } else {
                    $experienceScore = 25;
                }
            }

            // 6. Λήψη και βαθμολόγηση πιστοποιήσεων και σεμιναρίων
            $certifications = $certificationModel->getDriverCertifications($driverId);
            $certificationScore = min(count($certifications) * 5, 20); // Μέχρι 20 βαθμοί

            // 7. Συνδυασμός όλων των επιμέρους βαθμολογιών
            $totalPossibleScore = 100; // Μέγιστη δυνατή βαθμολογία
            $rawScore = $licenseScore + $adrScore + $operatorScore + $experienceScore + $certificationScore;

            // Κανονικοποίηση της βαθμολογίας σε κλίμακα 0-100
            return min($rawScore, $totalPossibleScore);
        } catch (\Exception $e) {
            Logger::error('Error in calculateQualificationsScore: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Υπολογίζει τη βαθμολογία ασφάλειας
     * 
     * @param int $driverId ID του οδηγού
     * @return float Βαθμολογία ασφάλειας (0-100)
     */
    private function calculateSafetyScore($driverId)
    {
        try {
            $incidentModel = new IncidentModel($this->pdo);
            $skillsModel = new SkillModel($this->pdo);

            // Αρχικοποίηση βαθμολογιών
            $incidentScore = 100;  // Ξεκινάμε με τη μέγιστη βαθμολογία
            $telemetryScore = 0;

            // 1. Λήψη συμβάντων των τελευταίων 3 ετών
            $incidents = $incidentModel->getRecentIncidents($driverId, 3);

            // 2. Αφαίρεση βαθμών για κάθε συμβάν με βάση τον τύπο και τη σοβαρότητα
            foreach ($incidents as $incident) {
                $penalty = 0;

                switch ($incident['incident_type']) {
                    case 'accident':
                        $penalty = $incident['severity'] * 5; // 5-25 βαθμοί για ατύχημα
                        break;
                    case 'traffic_violation':
                        $penalty = $incident['severity'] * 3; // 3-15 βαθμοί για παράβαση
                        break;
                    case 'near_miss':
                        $penalty = $incident['severity'] * 2; // 2-10 βαθμοί για παρ' ολίγον
                        break;
                    case 'complaint':
                        $penalty = $incident['severity'] * 2; // 2-10 βαθμοί για παράπονο
                        break;
                    default:
                        $penalty = $incident['severity'] * 1; // 1-5 βαθμοί για άλλο συμβάν
                }

                // Αφαίρεση από τη βαθμολογία
                $incidentScore -= $penalty;
            }

            // 3. Διασφάλιση ότι η βαθμολογία δεν είναι αρνητική
            $incidentScore = max(0, $incidentScore);

            // 4. Λήψη δεδομένων τηλεματικής (αν υπάρχουν)
            $telemetry = $skillsModel->getDriverTelemetryData($driverId);

            if ($telemetry) {
                // Η βαθμολογία τηλεματικής συμμετέχει με ποσοστό 25%
                $telemetryScore = $telemetry['score'];
            }

            // 5. Υπολογισμός της τελικής βαθμολογίας ασφάλειας
            // Αν δεν υπάρχουν δεδομένα τηλεματικής, η βαθμολογία περιστατικών έχει βάρος 100%
            // Αν υπάρχουν, η βαθμολογία περιστατικών έχει βάρος 75%
            if ($telemetry) {
                $safetyScore = ($incidentScore * 0.75) + ($telemetryScore * 0.25);
            } else {
                $safetyScore = $incidentScore;
            }

            return min($safetyScore, 100); // Μέγιστη βαθμολογία 100
        } catch (\Exception $e) {
            Logger::error('Error in calculateSafetyScore: ' . $e->getMessage());
            return 50; // Μια μέση τιμή σε περίπτωση σφάλματος
        }
    }
}
