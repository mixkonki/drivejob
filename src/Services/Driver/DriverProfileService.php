<?php

namespace Drivejob\Services\Driver;

use PDO;
use Drivejob\Services\Driver\DriverProfileServiceInterface;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\Driver\IncidentModel;
use Drivejob\Services\FileService;

/**
 * Υπηρεσία για τη διαχείριση των προφίλ οδηγών
 */
class DriverProfileService implements DriverProfileServiceInterface
{
    private $pdo;
    private $profileModel;
    private $licenseModel;
    private $certificationModel;
    private $skillModel;
    private $ratingModel;
    private $incidentModel;
    private $fileService;

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
        $this->fileService = new FileService();
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

            // Γλωσσικές ικανότητες (πίνακας driver_languages — μία γραμμή ανά γλώσσα)
            $driver['languages_list'] = $this->skillModel->getDriverLanguages($driverId);

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

            // Προσθήκη του πεδίου legal_status (υπεύθυνη δήλωση)
            $driver['legal_status'] = $driver['legal_status'] ?? null;

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
     * Ενημέρωση προφίλ οδηγού με επεξεργασία αρχείων
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προφίλ
     * @param array $files Αρχεία που ανεβάζονται
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfileWithFiles($driverId, $data, $files)
    {
        try {
            // Επεξεργασία των αρχείων που ανεβάζονται
            $uploadedFiles = [
                'profile_image' => $files['profile_image'] ?? null,
                'resume_file' => $files['resume_file'] ?? null,
                'license_front_image' => $files['license_front_image'] ?? null,
                'license_back_image' => $files['license_back_image'] ?? null,
                'tachograph_front_image' => $files['tachograph_front_image'] ?? null,
                'tachograph_back_image' => $files['tachograph_back_image'] ?? null,
                'adr_front_image' => $files['adr_front_image'] ?? null,
                'adr_back_image' => $files['adr_back_image'] ?? null,
                'operator_front_image' => $files['operator_front_image'] ?? null,
                'operator_back_image' => $files['operator_back_image'] ?? null
            ];

            // Καθορισμός κατηγορίας αρχείου για κάθε τύπο
            $fileCategories = [
                'profile_image' => 'image',
                'license_front_image' => 'image',
                'license_back_image' => 'image',
                'tachograph_front_image' => 'image',
                'tachograph_back_image' => 'image',
                'adr_front_image' => 'image',
                'adr_back_image' => 'image',
                'operator_front_image' => 'image',
                'operator_back_image' => 'image',
                'resume_file' => 'document'
            ];

            // Επεξεργασία των αρχείων
            foreach ($uploadedFiles as $fileType => $fileData) {
                if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                    $category = $fileCategories[$fileType] ?? 'all';
                    $uploadedFilePath = $this->uploadFile($fileData, $fileType, $category);
                    if ($uploadedFilePath) {
                        $data[$fileType] = $uploadedFilePath;
                    }
                }
            }

            // Ενημέρωση του προφίλ
            return $this->profileModel->updateProfile($driverId, $data);
        } catch (PDOException $e) {
            Logger::error('Error in updateProfileWithFiles: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Logger::error('Error in updateProfileWithFiles: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανεβάζει ένα αρχείο χρησιμοποιώντας το FileService
     * 
     * @param array $file Τα δεδομένα του αρχείου
     * @param string $fileType Ο τύπος του αρχείου
     * @param string $category Η κατηγορία του αρχείου (image, document, all)
     * @return string|false Η διαδρομή του αρχείου ή false σε περίπτωση αποτυχίας
     */
    private function uploadFile($file, $fileType, $category = 'all')
    {
        $result = $this->fileService->uploadFile($file, $fileType, $category);

        if ($result['success']) {
            return $result['file_path'];
        }

        Logger::error('Αποτυχία ανεβάσματος αρχείου', [
            'file_type' => $fileType,
            'error' => $result['message'],
            'error_code' => $result['error_code'] ?? 'unknown'
        ]);

        return false;
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
     * Ενημέρωση πιστοποιητικών εκπαίδευσης οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $certifications Δεδομένα πιστοποιητικών
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverCertifications($driverId, $certifications)
    {
        try {
            return $this->certificationModel->addDriverCertifications($driverId, $certifications);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverCertifications: ' . $e->getMessage());
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
            // Προσθήκη του driver_id στα δεδομένα του περιστατικού
            $incidentData['driver_id'] = $driverId;
            $incidentData['created_at'] = date('Y-m-d H:i:s');

            return $this->incidentModel->addIncident($incidentData);
        } catch (PDOException $e) {
            Logger::error('Error in addDriverIncident: ' . $e->getMessage());
            return false;
        }
    }
}
