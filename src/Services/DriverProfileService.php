<?php

namespace Drivejob\Services;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\Driver\IncidentModel;

/**
 * Υπηρεσία για τη διαχείριση των προφίλ οδηγών
 */
class DriverProfileService
{
    private $pdo;
    private $profileModel;
    private $licenseModel;
    private $certificationModel;
    private $skillModel;
    private $ratingModel;
    private $incidentModel;

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
        $this->licenseModel = new LicenseModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
        $this->skillModel = new SkillModel($pdo);
        $this->ratingModel = new RatingModel($pdo);
        $this->incidentModel = new IncidentModel($pdo);
    }

    /**
     * Δημιουργεί έναν νέο λογαριασμό οδηγού
     * 
     * @param array $data Δεδομένα νέου οδηγού
     * @return int|false ID του νέου οδηγού ή false σε περίπτωση αποτυχίας
     */
    public function registerDriver($data)
    {
        try {
            // Έλεγχος αν το email υπάρχει ήδη
            if ($this->profileModel->emailExists($data['email'])) {
                return false;
            }

            // Κρυπτογράφηση του κωδικού
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Δημιουργία του λογαριασμού
            $driverId = $this->profileModel->create($data);

            if ($driverId) {
                // Ενημέρωση τελευταίας σύνδεσης
                $this->profileModel->updateLastLogin($driverId);
            }

            return $driverId;
        } catch (PDOException $e) {
            Logger::error('Error in registerDriver: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανάκτηση ολοκληρωμένου προφίλ οδηγού με όλες τις λεπτομέρειες
     * 
     * @param int $driverId ID του οδηγού
     * @return array|false Δεδομένα προφίλ οδηγού ή false αν δεν βρέθηκε
     */
    public function getDriverProfile($driverId)
    {
        try {
            // Ανάκτηση βασικών πληροφοριών
            $driver = $this->profileModel->getDriverById($driverId);

            if (!$driver) {
                return false;
            }

            // Προσθήκη των αδειών οδήγησης
            $driver['licenses'] = $this->licenseModel->getDriverLicenses($driverId);

            // Προσθήκη των πιστοποιητικών ADR
            $driver['adr_certificates'] = $this->certificationModel->getDriverAdrCertificates($driverId);

            // Προσθήκη των αδειών χειριστή
            $driver['operator_licenses'] = $this->certificationModel->getDriverOperatorLicenses($driverId);

            // Προσθήκη των καρτών ταχογράφου
            $driver['tachograph_cards'] = $this->certificationModel->getDriverTachographCards($driverId);

            // Προσθήκη των ειδικών αδειών
            $driver['special_licenses'] = $this->certificationModel->getDriverSpecialLicenses($driverId);

            // Προσθήκη των δεξιοτήτων
            $driver['skills'] = $this->skillModel->getDriverSkills($driverId);

            // Προσθήκη της αυτοαξιολόγησης
            $driver['assessment'] = $this->skillModel->getDriverAssessment($driverId);

            // Προσθήκη της εμπειρίας οχημάτων
            $driver['vehicle_experience'] = $this->skillModel->getDriverVehicleExperience($driverId);

            // Προσθήκη των πιστοποιήσεων και σεμιναρίων
            $driver['certifications'] = $this->certificationModel->getDriverCertifications($driverId);

            // Προσθήκη των συμβάντων
            $driver['incidents'] = $this->incidentModel->getDriverIncidents($driverId);

            // Προσθήκη των βαθμολογιών
            $driver['rating_details'] = $this->ratingModel->getDriverRatingDetails($driverId);
            $driver['average_rating'] = $this->ratingModel->getDriverRating($driverId);
            $driver['reviews'] = $this->ratingModel->getDriverReviews($driverId);

            return $driver;
        } catch (PDOException $e) {
            Logger::error('Error in getDriverProfile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση βασικών πληροφοριών οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προς ενημέρωση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateBasicInfo($driverId, $data)
    {
        try {
            return $this->profileModel->update($driverId, $data);
        } catch (PDOException $e) {
            Logger::error('Error in updateBasicInfo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση προφίλ οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($driverId, $data)
    {
        try {
            return $this->profileModel->updateProfile($driverId, $data);
        } catch (PDOException $e) {
            Logger::error('Error in updateProfile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση κωδικού πρόσβασης
     * 
     * @param int $driverId ID του οδηγού
     * @param string $currentPassword Τρέχων κωδικός
     * @param string $newPassword Νέος κωδικός
     * @return bool Επιτυχία/αποτυχία
     */
    public function updatePassword($driverId, $currentPassword, $newPassword)
    {
        try {
            // Ανάκτηση του οδηγού
            $driver = $this->profileModel->getDriverById($driverId);

            if (!$driver || !password_verify($currentPassword, $driver['password'])) {
                return false;
            }

            // Κρυπτογράφηση του νέου κωδικού
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            return $this->profileModel->updatePassword($driverId, $hashedPassword);
        } catch (PDOException $e) {
            Logger::error('Error in updatePassword: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανάκτηση συνοπτικών στοιχείων προφίλ οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Συνοπτικά στοιχεία οδηγού
     */
    public function getDriverSummary($driverId)
    {
        try {
            // Ανάκτηση βασικών πληροφοριών
            $driver = $this->profileModel->getDriverById($driverId);

            if (!$driver) {
                return [];
            }

            // Δημιουργία συνοπτικών στοιχείων
            $summary = [
                'id' => $driver['id'],
                'first_name' => $driver['first_name'],
                'last_name' => $driver['last_name'],
                'city' => $driver['city'] ?? '',
                'country' => $driver['country'] ?? '',
                'experience_years' => $driver['experience_years'] ?? 0,
                'profile_image' => $driver['profile_image'] ?? '',
                'rating' => $this->ratingModel->getDriverRating($driverId),
                'licenses' => [],
                'skills' => []
            ];

            // Προσθήκη των αδειών οδήγησης (μόνο τύποι)
            $licenses = $this->licenseModel->getDriverLicenses($driverId);
            foreach ($licenses as $license) {
                $summary['licenses'][] = $license['license_type'];
            }

            // Προσθήκη ύπαρξης ADR, χειριστή, ταχογράφου
            $summary['has_adr'] = !empty($this->certificationModel->getDriverAdrCertificates($driverId));
            $summary['has_operator'] = !empty($this->certificationModel->getDriverOperatorLicenses($driverId));
            $summary['has_tachograph'] = !empty($this->certificationModel->getDriverTachographCards($driverId));

            // Προσθήκη top skills
            $skills = $this->skillModel->getDriverSkills($driverId);
            if (!empty($skills)) {
                // Φιλτράρισμα μόνο των δεξιοτήτων που έχουν τιμή 1
                $topSkills = [];
                foreach ($skills as $key => $value) {
                    if ($value == 1 && !in_array($key, ['driver_id'])) {
                        $topSkills[] = $key;
                    }
                }
                $summary['skills'] = array_slice($topSkills, 0, 5); // Μόνο οι 5 πρώτες
            }

            return $summary;
        } catch (PDOException $e) {
            Logger::error('Error in getDriverSummary: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ανάκτηση οδηγών με βάση κριτήρια αναζήτησης
     * 
     * @param array $params Παράμετροι αναζήτησης
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Αποτελέσματα αναζήτησης
     */
    public function searchDrivers($params, $page = 1, $limit = 10)
    {
        try {
            return $this->profileModel->searchDrivers($params, $page, $limit);
        } catch (PDOException $e) {
            Logger::error('Error in searchDrivers: ' . $e->getMessage());
            return [
                'results' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }

    /**
     * Ανάκτηση πρόσφατων διαθέσιμων οδηγών
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getRecentAvailableDrivers($limit = 5)
    {
        try {
            $drivers = $this->profileModel->getRecentAvailableDrivers($limit);

            // Εμπλουτισμός με επιπλέον πληροφορίες
            foreach ($drivers as &$driver) {
                $driver['rating'] = $this->ratingModel->getDriverRating($driver['id']);

                // Προσθήκη των τύπων αδειών
                $licenses = $this->licenseModel->getDriverLicenses($driver['id']);
                $licenseTypes = [];
                foreach ($licenses as $license) {
                    $licenseTypes[] = $license['license_type'];
                }
                $driver['license_types'] = $licenseTypes;
            }

            return $drivers;
        } catch (PDOException $e) {
            Logger::error('Error in getRecentAvailableDrivers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ανάκτηση κορυφαίων αξιολογημένων οδηγών
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getTopRatedDrivers($limit = 5)
    {
        try {
            $drivers = $this->profileModel->getTopRatedDrivers($limit);

            // Εμπλουτισμός με επιπλέον πληροφορίες
            foreach ($drivers as &$driver) {
                // Προσθήκη των τύπων αδειών
                $licenses = $this->licenseModel->getDriverLicenses($driver['id']);
                $licenseTypes = [];
                foreach ($licenses as $license) {
                    $licenseTypes[] = $license['license_type'];
                }
                $driver['license_types'] = $licenseTypes;
            }

            return $drivers;
        } catch (PDOException $e) {
            Logger::error('Error in getTopRatedDrivers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ανάκτηση των αδειών και πιστοποιήσεων οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Πληροφορίες αδειών και πιστοποιήσεων
     */
    public function getDriverCertifications($driverId)
    {
        try {
            // Ανάκτηση βασικών πληροφοριών
            $driver = $this->profileModel->getDriverById($driverId);

            if (!$driver) {
                return [
                    'success' => false,
                    'message' => 'Ο οδηγός δεν βρέθηκε'
                ];
            }

            // Ανάκτηση των αδειών
            $licenses = $this->licenseModel->getDriverLicenses($driverId);
            $formattedLicenses = $this->licenseModel->formatDriverLicenses($licenses);

            // Ανάκτηση των πιστοποιητικών ADR
            $adrCertificates = $this->certificationModel->getDriverAdrCertificates($driverId);
            $formattedADR = $this->certificationModel->formatAdrCertificates($adrCertificates);

            // Ανάκτηση των αδειών χειριστή
            $operatorLicenses = $this->certificationModel->getDriverOperatorLicenses($driverId);
            $formattedOperator = $this->certificationModel->formatOperatorLicenses($operatorLicenses);

            // Ανάκτηση των καρτών ταχογράφου
            $tachographCards = $this->certificationModel->getDriverTachographCards($driverId);
            $formattedTachograph = $this->certificationModel->formatTachographCards($tachographCards);

            // Ανάκτηση των ειδικών αδειών
            $specialLicenses = $this->certificationModel->getDriverSpecialLicenses($driverId);
            $formattedSpecial = $this->certificationModel->formatSpecialLicenses($specialLicenses);

            return [
                'success' => true,
                'driver' => $driver,
                'licenses' => $formattedLicenses,
                'adr' => $formattedADR,
                'operator' => $formattedOperator,
                'tachograph' => $formattedTachograph,
                'special_licenses' => $formattedSpecial
            ];
        } catch (PDOException $e) {
            Logger::error('Error in getDriverCertifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Σφάλμα κατά την ανάκτηση των πιστοποιήσεων'
            ];
        }
    }

    /**
     * Διαγραφή λογαριασμού οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriver($driverId)
    {
        try {
            // Έναρξη συναλλαγής
            $this->pdo->beginTransaction();

            // Διαγραφή όλων των σχετικών δεδομένων
            $this->licenseModel->deleteDriverLicenses($driverId);
            $this->certificationModel->deleteDriverADRCertificate($driverId);
            $this->certificationModel->deleteDriverOperatorLicense($driverId);
            $this->certificationModel->deleteDriverTachographCard($driverId);
            $this->certificationModel->deleteDriverSpecialLicenses($driverId);
            $this->certificationModel->deleteDriverCertifications($driverId);
            $this->skillModel->deleteDriverVehicleExperience($driverId);

            // Διαγραφή του λογαριασμού
            $result = $this->profileModel->delete($driverId);

            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            Logger::error('Error in deleteDriver: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση συνολικής βαθμολογίας οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverRating($driverId)
    {
        try {
            // Υπολογισμός συνολικής βαθμολογίας
            $ratings = $this->ratingModel->calculateTotalRating($driverId);

            // Ενημέρωση της βαθμολογίας στη βάση
            return $this->ratingModel->updateDriverRating($driverId, $ratings);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverRating: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθήκη αξιολόγησης για οδηγό
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
            return $this->ratingModel->addDriverReview($driverId, $companyId, $rating, $comment);
        } catch (PDOException $e) {
            Logger::error('Error in addDriverReview: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση δεξιοτήτων οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $skillsData Δεδομένα δεξιοτήτων
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverSkills($driverId, $skillsData)
    {
        try {
            return $this->skillModel->updateDriverSkills($driverId, $skillsData);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverSkills: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση εμπειρίας οχημάτων οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $vehicleExperience Δεδομένα εμπειρίας οχημάτων
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverVehicleExperience($driverId, $vehicleExperience)
    {
        try {
            return $this->skillModel->updateDriverVehicleExperience($driverId, $vehicleExperience);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverVehicleExperience: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση αυτοαξιολόγησης οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $assessmentData Δεδομένα αυτοαξιολόγησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverAssessment($driverId, $assessmentData)
    {
        try {
            return $this->skillModel->updateDriverAssessment($driverId, $assessmentData);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverAssessment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθήκη συμβάντος για οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param array $incidentData Δεδομένα συμβάντος
     * @return int|bool ID του συμβάντος ή false σε αποτυχία
     */
    public function addDriverIncident($driverId, $incidentData)
    {
        try {
            return $this->incidentModel->saveDriverIncident($driverId, $incidentData);
        } catch (PDOException $e) {
            Logger::error('Error in addDriverIncident: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανάκτηση στατιστικών οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Στατιστικά οδηγού
     */
    public function getDriverStatistics($driverId)
    {
        try {
            $stats = [
                'total_incidents' => 0,
                'incidents_by_type' => [],
                'average_severity' => 0,
                'days_since_last_incident' => -1,
                'incident_trends' => [],
                'rating' => 0,
                'rating_breakdown' => [],
                'skills_stats' => [],
                'assessment_summary' => []
            ];

            // Στατιστικά συμβάντων
            $incidents = $this->incidentModel->getDriverIncidents($driverId);
            $stats['total_incidents'] = count($incidents);
            $stats['incidents_by_type'] = $this->incidentModel->countIncidentsByType($driverId);
            $stats['average_severity'] = $this->incidentModel->getAverageSeverity($driverId);
            $stats['days_since_last_incident'] = $this->incidentModel->getDaysSinceLastIncident($driverId);
            $stats['incident_trends'] = $this->incidentModel->getIncidentTrendsByYear($driverId);

            // Στατιστικά βαθμολογίας
            $stats['rating'] = $this->ratingModel->getDriverRating($driverId);
            $ratingDetails = $this->ratingModel->getDriverRatingDetails($driverId);

            if ($ratingDetails) {
                $stats['rating_breakdown'] = [
                    'skills' => $ratingDetails['skills_score'] ?? 0,
                    'safety' => $ratingDetails['safety_score'] ?? 0,
                    'professionalism' => $ratingDetails['professionalism_score'] ?? 0,
                    'technical' => $ratingDetails['technical_score'] ?? 0
                ];
            }

            // Στατιστικά δεξιοτήτων
            $skills = $this->skillModel->getDriverSkills($driverId);
            if (!empty($skills)) {
                $skillCount = 0;
                foreach ($skills as $key => $value) {
                    if ($value == 1 && !in_array($key, ['driver_id'])) {
                        $skillCount++;
                    }
                }
                $stats['skills_stats'] = [
                    'total_skills' => $skillCount,
                    'skill_categories' => [
                        'driving' => $this->countSkillsInCategory($skills, 'driving'),
                        'safety' => $this->countSkillsInCategory($skills, 'safety'),
                        'customer' => $this->countSkillsInCategory($skills, 'customer'),
                        'technical' => $this->countSkillsInCategory($skills, 'technical')
                    ]
                ];
            }

            // Σύνοψη αυτοαξιολόγησης
            $assessment = $this->skillModel->getDriverAssessment($driverId);
            if (!empty($assessment)) {
                $stats['assessment_summary'] = [
                    'driving_skills' => $assessment['driving_skills'] ?? 0,
                    'safety_compliance' => $assessment['safety_compliance'] ?? 0,
                    'professionalism' => $assessment['professionalism'] ?? 0,
                    'technical_knowledge' => $assessment['technical_knowledge'] ?? 0,
                    'total_score' => $assessment['total_score'] ?? 0
                ];
            }

            return $stats;
        } catch (PDOException $e) {
            Logger::error('Error in getDriverStatistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Υπολογίζει τον αριθμό δεξιοτήτων σε μια συγκεκριμένη κατηγορία
     * 
     * @param array $skills Όλες οι δεξιότητες
     * @param string $category Όνομα κατηγορίας
     * @return int Αριθμός δεξιοτήτων στην κατηγορία
     */
    private function countSkillsInCategory($skills, $category)
    {
        $categoryMap = [
            'driving' => ['defensive_driving', 'eco_driving', 'night_driving', 'mountain_driving', 'extreme_conditions'],
            'safety' => ['loading_securing', 'emergency_response', 'first_aid', 'dangerous_goods', 'tacograph_compliance'],
            'customer' => ['customer_service', 'time_management', 'route_planning', 'conflict_resolution', 'multilingual'],
            'technical' => ['vehicle_maintenance', 'troubleshooting', 'digital_tachograph', 'gps_systems', 'logistics_software']
        ];

        $count = 0;
        if (isset($categoryMap[$category])) {
            foreach ($categoryMap[$category] as $skill) {
                if (isset($skills[$skill]) && $skills[$skill] == 1) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Λαμβάνει όλες τις άδειες που λήγουν σύντομα
     * 
     * @return array Λίστα αδειών που λήγουν ανά τύπο
     */
    public function getExpiringLicenses()
    {
        try {
            $result = [
                'driving_licenses' => [],
                'adr_certificates' => [],
                'operator_licenses' => []
            ];

            // Άδειες οδήγησης
            $result['driving_licenses'] = $this->licenseModel->getDriversWithExpiringLicenses();

            // Πιστοποιητικά ADR και άδειες χειριστή
            $certifications = $this->certificationModel->getDriversWithExpiringCertifications();
            $result['adr_certificates'] = $certifications['adr_certificate'] ?? [];
            $result['operator_licenses'] = $certifications['operator_license'] ?? [];

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in getExpiringLicenses: ' . $e->getMessage());
            return [
                'driving_licenses' => [],
                'adr_certificates' => [],
                'operator_licenses' => []
            ];
        }
    }
}
