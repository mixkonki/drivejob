<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\IncidentModel;
use Drivejob\Models\Driver\ProfileModel;

class DriverRatingService
{
    protected $pdo;
    protected $ratingModel;
    protected $skillModel;
    protected $licenseModel;
    protected $certificationModel;
    protected $incidentModel;
    protected $profileModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ratingModel = new RatingModel($pdo);
        $this->skillModel = new SkillModel($pdo);
        $this->licenseModel = new LicenseModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
        $this->incidentModel = new IncidentModel($pdo);
        $this->profileModel = new ProfileModel($pdo);
    }

    /**
     * Υπολογίζει και ενημερώνει τη συνολική βαθμολογία του οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverRating($driverId)
    {
        try {
            // Υπολογισμός των βαθμολογιών
            $ratings = $this->calculateTotalRating($driverId);

            // Ενημέρωση της βαθμολογίας με το νέο μοντέλο
            return $this->ratingModel->updateDriverRating($driverId, $ratings);
        } catch (\Exception $e) {
            Logger::error('Error in updateDriverRating: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Υπολογίζει τη συνολική βαθμολογία ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Συνολική βαθμολογία και επιμέρους βαθμολογίες
     */
    public function calculateTotalRating($driverId)
    {
        // Υπολογισμός επιμέρους βαθμολογιών
        $skillsScore = $this->calculateQualificationsScore($driverId);
        $safetyScore = $this->calculateSafetyScore($driverId);
        $professionalismScore = $this->calculateProfessionalismScore($driverId);
        $technicalScore = $this->calculateTechnicalSkillsScore($driverId);

        // Υπολογισμός συνολικής βαθμολογίας με τις σταθμίσεις:
        // - Προσόντα/Δεξιότητες: 25%
        // - Ασφάλεια: 30%
        // - Επαγγελματισμός: 25%
        // - Τεχνικές δεξιότητες: 20%
        $totalScore = ($skillsScore * 0.25) + ($safetyScore * 0.30) +
            ($professionalismScore * 0.25) + ($technicalScore * 0.20);

        return [
            'skills_score' => $skillsScore,
            'safety_score' => $safetyScore,
            'professionalism_score' => $professionalismScore,
            'technical_score' => $technicalScore,
            'total_score' => round($totalScore)
        ];
    }

    /**
     * Υπολογίζει τη βαθμολογία προσόντων/δεξιοτήτων
     * 
     * @param int $driverId ID του οδηγού
     * @return float Βαθμολογία προσόντων (0-100)
     */
    public function calculateQualificationsScore($driverId)
    {
        try {
            // 1. Λήψη όλων των αδειών οδήγησης
            $licenses = $this->licenseModel->getDriverLicenses($driverId);
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
            $adrCertificate = $this->certificationModel->getDriverADRCertificate($driverId);
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
            $operatorLicense = $this->certificationModel->getDriverOperatorLicense($driverId);
            $operatorScore = 0;

            if ($operatorLicense) {
                // Βασική βαθμολογία για την κατοχή άδειας χειριστή
                $operatorScore = 15;

                // Επιπλέον βαθμοί για κάθε υποειδικότητα
                $subSpecialities = $this->certificationModel->getDriverOperatorSubSpecialities($operatorLicense['id']);
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
            $driver = $this->profileModel->getDriverById($driverId);
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
            $certifications = $this->certificationModel->getDriverCertifications($driverId);
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
    public function calculateSafetyScore($driverId)
    {
        try {
            // Αρχικοποίηση βαθμολογιών
            $incidentScore = 100;  // Ξεκινάμε με τη μέγιστη βαθμολογία
            $telemetryScore = 0;

            // 1. Λήψη συμβάντων των τελευταίων 3 ετών με το νέο μοντέλο
            $incidents = $this->incidentModel->getRecentIncidents($driverId, 3);

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
            $telemetry = $this->skillModel->getDriverTelemetryData($driverId);

            if ($telemetry) {
                // Η βαθμολογία τηλεματικής συμμετέχει με ποσοστό 60%
                $telemetryScore = $telemetry['score'];
            }

            // 5. Υπολογισμός της τελικής βαθμολογίας ασφάλειας
            // Αν δεν υπάρχουν δεδομένα τηλεματικής, η βαθμολογία περιστατικών έχει βάρος 100%
            // Αν υπάρχουν, η βαθμολογία τηλεματικής έχει βάρος 60% και η βαθμολογία περιστατικών 40%
            if ($telemetry) {
                $safetyScore = ($incidentScore * 0.40) + ($telemetryScore * 0.60);
            } else {
                $safetyScore = $incidentScore;
            }

            return min($safetyScore, 100); // Μέγιστη βαθμολογία 100
        } catch (\Exception $e) {
            Logger::error('Error in calculateSafetyScore: ' . $e->getMessage());
            return 50; // Μια μέση τιμή σε περίπτωση σφάλματος
        }
    }

    /**
     * Υπολογίζει τη βαθμολογία επαγγελματισμού
     * 
     * @param int $driverId ID του οδηγού
     * @return float Βαθμολογία επαγγελματισμού (0-100)
     */
    public function calculateProfessionalismScore($driverId)
    {
        try {
            // 1. Λήψη δεδομένων αυτοαξιολόγησης με το νέο μοντέλο
            $assessment = $this->skillModel->getDriverAssessment($driverId);

            // Αν δεν υπάρχει αυτοαξιολόγηση, επιστρέφουμε μια μέση τιμή
            if (!$assessment) {
                return 50; // 50% της μέγιστης βαθμολογίας
            }

            // 2. Υπολογισμός βαθμολογίας με βάση τα πεδία της αυτοαξιολόγησης
            $professionalismFields = [
                'punctuality',
                'customer_interaction',
                'appearance',
                'documentation'
            ];

            $totalScore = 0;
            $fieldsCount = 0;

            foreach ($professionalismFields as $field) {
                if (isset($assessment[$field])) {
                    $totalScore += intval($assessment[$field]);
                    $fieldsCount++;
                }
            }

            // Αν δεν έχουν συμπληρωθεί τα πεδία, επιστρέφουμε μια μέση τιμή
            if ($fieldsCount === 0) {
                return 50;
            }

            // 3. Υπολογισμός μέσου όρου και μετατροπή σε κλίμακα 0-100
            $averageScore = $totalScore / $fieldsCount;
            $professionalismScore = ($averageScore / 5) * 100; // 5 είναι η μέγιστη τιμή στην κλίμακα αυτοαξιολόγησης

            return $professionalismScore;
        } catch (\Exception $e) {
            Logger::error('Error in calculateProfessionalismScore: ' . $e->getMessage());
            return 50; // Μια μέση τιμή σε περίπτωση σφάλματος
        }
    }

    /**
     * Υπολογίζει τη βαθμολογία τεχνικών δεξιοτήτων
     * 
     * @param int $driverId ID του οδηγού
     * @return float Βαθμολογία τεχνικών δεξιοτήτων (0-100)
     */
    public function calculateTechnicalSkillsScore($driverId)
    {
        try {
            // 1. Λήψη δεδομένων αυτοαξιολόγησης με το νέο μοντέλο
            $assessment = $this->skillModel->getDriverAssessment($driverId);

            // Αν δεν υπάρχει αυτοαξιολόγηση, επιστρέφουμε μια μέση τιμή
            if (!$assessment) {
                return 50; // 50% της μέγιστης βαθμολογίας
            }

            // 2. Υπολογισμός βαθμολογίας με βάση τα πεδία της αυτοαξιολόγησης
            $technicalFields = [
                'vehicle_maintenance',
                'troubleshooting',
                'navigation_skills',
                'technical_knowledge'
            ];

            $totalScore = 0;
            $fieldsCount = 0;

            foreach ($technicalFields as $field) {
                if (isset($assessment[$field])) {
                    $totalScore += intval($assessment[$field]);
                    $fieldsCount++;
                }
            }

            // Αν δεν έχουν συμπληρωθεί τα πεδία, επιστρέφουμε μια μέση τιμή
            if ($fieldsCount === 0) {
                return 50;
            }

            // 3. Υπολογισμός μέσου όρου και μετατροπή σε κλίμακα 0-100
            $averageScore = $totalScore / $fieldsCount;
            $technicalScore = ($averageScore / 5) * 100; // 5 είναι η μέγιστη τιμή στην κλίμακα αυτοαξιολόγησης

            return $technicalScore;
        } catch (\Exception $e) {
            Logger::error('Error in calculateTechnicalSkillsScore: ' . $e->getMessage());
            return 50; // Μια μέση τιμή σε περίπτωση σφάλματος
        }
    }

    /**
     * Προσθήκη νέας αξιολόγησης για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param int $companyId ID της εταιρείας
     * @param float $rating Βαθμολογία (0-5)
     * @param string $comment Σχόλιο
     * @return bool Επιτυχία ή αποτυχία
     */
    public function addDriverReview($driverId, $companyId, $rating, $comment = '')
    {
        return $this->ratingModel->addDriverReview($driverId, $companyId, $rating, $comment);
    }

    /**
     * Ανάκτηση της μέσης βαθμολογίας ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return float Μέση βαθμολογία
     */
    public function getDriverRating($driverId)
    {
        return $this->ratingModel->getDriverRating($driverId);
    }

    /**
     * Λαμβάνει τις λεπτομέρειες βαθμολογίας ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|null Λεπτομέρειες βαθμολογίας ή null αν δεν υπάρχουν
     */
    public function getDriverRatingDetails($driverId)
    {
        return $this->ratingModel->getDriverRatingDetails($driverId);
    }
}
