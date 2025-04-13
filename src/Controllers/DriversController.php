<?php
namespace Drivejob\Controllers;
use Drivejob\Models\DriversModel;
use Drivejob\Models\DriverAssessmentModel;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;

class DriversController {
    private $driversModel;
    private $driverAssessmentModel;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->driversModel = new DriversModel($pdo);
        // Θεωρητικά θα δημιουργήσουμε ένα μοντέλο για την αυτοαξιολόγηση του οδηγού
        // $this->driverAssessmentModel = new DriverAssessmentModel($pdo);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ του οδηγού
     */
    public function profile() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);
        
        // Λήψη των αγγελιών του οδηγού
        $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
        $listings = $jobListingModel->getDriverListings($driverId, null, 1, 5);
        
        // Λήψη των συντεταγμένων της τοποθεσίας του οδηγού για τον χάρτη
      // Λήψη των συντεταγμένων της τοποθεσίας του οδηγού για τον χάρτη
$driverLocation = null;
if (!empty($driverData['address']) && !empty($driverData['city'])) {
    $address = urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']);
    $driverLocation = $this->getGeocodingData($address);
}

// Συνεχίστε μόνο αν έχουμε έγκυρες συντεταγμένες
if ($driverLocation !== null) {
    // Κώδικας που χρησιμοποιεί το $driverLocation
} else {
    // Προσθέστε μια σημείωση στο session ότι οι συντεταγμένες δεν βρέθηκαν
    $_SESSION['info_message'] = 'Δεν ήταν δυνατή η εύρεση συντεταγμένων για τη διεύθυνσή σας.';
}
        
        // Λήψη δεδομένων αυτοαξιολόγησης
        // Προς το παρόν επιστρέφουμε ψευδή δεδομένα για επίδειξη
        $driverAssessment = [
            'total_score' => 75,
            'driving_skills' => 80,
            'safety_compliance' => 70,
            'professionalism' => 85,
            'technical_knowledge' => 65
        ];
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/profile.php';
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);
        
        // Λήψη των αδειών οδήγησης του οδηγού
        $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');
        $driverPEI = array_column(array_filter($driverLicenses, function($license) {
            return $license['has_pei'] == 1;
        }), 'license_type');
        
        // Λήψη του πιστοποιητικού ADR του οδηγού
        $driverADR = $this->driversModel->getDriverADRCertificate($driverId);
        
        // Λήψη της άδειας χειριστή μηχανημάτων του οδηγού
        $driverOperator = $this->driversModel->getDriverOperatorLicense($driverId);
        $driverOperatorSubSpecialities = [];
        
        if ($driverOperator) {
            $driverOperatorSubSpecialities = $this->driversModel->getDriverOperatorSubSpecialities($driverOperator['id']);
        }
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/edit_profile.php';
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
            
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
                  ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
                  ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                  ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        
        // Επιπλέον επικύρωση για προαιρετικά πεδία
        if (!empty($_POST['landline'])) {
            $validator->pattern('landline', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο σταθερό τηλέφωνο.');
        }
        
        if (!empty($_POST['postal_code'])) {
            $validator->pattern('postal_code', '/^[0-9a-zA-Z\s-]{3,10}$/', 'Μη έγκυρος ταχυδρομικός κώδικας.');
        }
        
        if (!empty($_POST['social_linkedin'])) {
            $validator->pattern('social_linkedin', '/^https?:\/\/(?:www\.)?linkedin\.com\/.*$/', 'Παρακαλώ εισάγετε ένα έγκυρο URL LinkedIn.');
        }
        
        if (!empty($_POST['social_facebook'])) {
            $validator->pattern('social_facebook', '/^https?:\/\/(?:www\.)?facebook\.com\/.*$/', 'Παρακαλώ εισάγετε ένα έγκυρο URL Facebook.');
        }
        
        if (!empty($_POST['social_twitter'])) {
            $validator->pattern('social_twitter', '/^https?:\/\/(?:www\.)?twitter\.com\/.*$/', 'Παρακαλώ εισάγετε ένα έγκυρο URL Twitter.');
        }
        
        if (!empty($_POST['social_instagram'])) {
            $validator->pattern('social_instagram', '/^https?:\/\/(?:www\.)?instagram\.com\/.*$/', 'Παρακαλώ εισάγετε ένα έγκυρο URL Instagram.');
        }
        
        // Επικύρωση πεδίων ημερομηνίας
        $dateFields = ['birth_date', 'driving_license_expiry', 'adr_certificate_expiry', 'operator_license_expiry'];
        foreach ($dateFields as $field) {
            if (!empty($_POST[$field])) {
                $date = date_create_from_format('Y-m-d', $_POST[$field]);
                if (!$date) {
                    $validator->getErrors()[$field] = 'Μη έγκυρη ημερομηνία.';
                }
            }
        }
        
        // Επικύρωση αλλαγής κωδικού αν έχουν συμπληρωθεί τα σχετικά πεδία
        if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
            $validator->required('current_password', 'Ο τρέχων κωδικός είναι υποχρεωτικός για την αλλαγή.')
                      ->required('new_password', 'Ο νέος κωδικός είναι υποχρεωτικός.')
                      ->minLength('new_password', 8, 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.')
                      ->pattern('new_password', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', 'Ο νέος κωδικός πρέπει να περιέχει τουλάχιστον ένα πεζό γράμμα, ένα κεφαλαίο γράμμα, έναν αριθμό και έναν ειδικό χαρακτήρα.')
                      ->required('confirm_password', 'Η επιβεβαίωση του νέου κωδικού είναι υποχρεωτική.')
                      ->matches('confirm_password', 'new_password', 'Οι κωδικοί δεν ταιριάζουν.');
        }
        
        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];
        
        // Συλλογή των δεδομένων από τη φόρμα
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'phone' => $_POST['phone'],
            'landline' => $_POST['landline'] ?? null,
            'birth_date' => $_POST['birth_date'] ?? null,
            'address' => $_POST['address'] ?? null,
            'house_number' => $_POST['house_number'] ?? null,
            'city' => $_POST['city'] ?? null,
            'country' => $_POST['country'] ?? null,
            'postal_code' => $_POST['postal_code'] ?? null,
            'about_me' => $_POST['about_me'] ?? null,
            'experience_years' => $_POST['experience_years'] ? intval($_POST['experience_years']) : null,
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
            'preferred_job_type' => $_POST['preferred_job_type'] ?? null,
            'preferred_vehicle_type' => $_POST['preferred_vehicle_type'] ?? null,
            'preferred_location' => $_POST['preferred_location'] ?? null,
            'preferred_radius' => $_POST['preferred_radius'] ?? null,
            'salary_min' => $_POST['salary_min'] ?? null,
            'salary_max' => $_POST['salary_max'] ?? null,
            'salary_period' => $_POST['salary_period'] ?? null,
            'social_linkedin' => $_POST['social_linkedin'] ?? null,
            'social_facebook' => $_POST['social_facebook'] ?? null,
            'social_twitter' => $_POST['social_twitter'] ?? null,
            'social_instagram' => $_POST['social_instagram'] ?? null,
            'willing_to_relocate' => isset($_POST['willing_to_relocate']) ? 1 : 0,
            'willing_to_travel' => isset($_POST['willing_to_travel']) ? 1 : 0,
        ];
        
        // Ενημέρωση του προφίλ
        if ($this->driversModel->updateProfile($driverId, $data)) {
           // Διαχείριση αδειών οδήγησης
    $this->driversModel->deleteDriverLicenses($driverId);
    if (isset($_POST['license_types']) && is_array($_POST['license_types'])) {
        $licenseNumber = $_POST['license_number'] ?? null;
        $licenseDocumentExpiry = $_POST['license_document_expiry'] ?? null;
        
        foreach ($_POST['license_types'] as $licenseType) {
            $hasPei = false;
            $peiExpiryC = null;
            $peiExpiryD = null;
            
            if (($licenseType == 'C' || $licenseType == 'CE') && isset($_POST['has_pei_c'])) {
                $hasPei = true;
                $peiExpiryC = $_POST['pei_c_expiry'] ?? null;
            } else if (($licenseType == 'D' || $licenseType == 'DE') && isset($_POST['has_pei_d'])) {
                $hasPei = true;
                $peiExpiryD = $_POST['pei_d_expiry'] ?? null;
            }
            
            // Επιλογή της κατάλληλης ημερομηνίας λήξης ανάλογα με την κατηγορία
            $expiryDate = null;
            if (in_array($licenseType, ['AM', 'A1', 'A2', 'A'])) {
                $expiryDate = $_POST['motorcycle_license_expiry'] ?? null;
            } else if (in_array($licenseType, ['B', 'BE'])) {
                $expiryDate = $_POST['car_license_expiry'] ?? null;
            } else if (in_array($licenseType, ['C', 'CE'])) {
                $expiryDate = $_POST['truck_license_expiry'] ?? null;
            } else if (in_array($licenseType, ['D', 'DE'])) {
                $expiryDate = $_POST['bus_license_expiry'] ?? null;
            }
            
            $this->driversModel->addDriverLicense($driverId, $licenseType, $hasPei, $expiryDate, $licenseNumber, $peiExpiryC, $peiExpiryD, $licenseDocumentExpiry);
        }
    }
    
    // Διαχείριση πιστοποιητικού ADR
    $this->driversModel->deleteDriverADRCertificates($driverId);
    if (isset($_POST['adr_certificate']) && $_POST['adr_certificate'] && isset($_POST['adr_certificate_type'])) {
        $certificateNumber = $_POST['adr_certificate_number'] ?? null;
        $this->driversModel->addDriverADRCertificate($driverId, $_POST['adr_certificate_type'], $_POST['adr_certificate_expiry'], $certificateNumber);
    }
    
    // Διαχείριση άδειας χειριστή μηχανημάτων
    $this->driversModel->deleteDriverOperatorLicenses($driverId);
    if (isset($_POST['operator_license']) && $_POST['operator_license'] && isset($_POST['operator_speciality'])) {
        $licenseNumber = $_POST['operator_license_number'] ?? null;
        $operatorLicenseId = $this->driversModel->addDriverOperatorLicense($driverId, $_POST['operator_speciality'], $_POST['operator_license_expiry'], $licenseNumber);
        
        if (isset($_POST['operator_sub_specialities']) && is_array($_POST['operator_sub_specialities'])) {
            foreach ($_POST['operator_sub_specialities'] as $subSpeciality) {
                $subSpecialityId = $this->driversModel->addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality);
                
                // Προσθήκη ομάδων A, B για κάθε υποειδικότητα
                if (isset($_POST['sub_speciality_groups'][$subSpeciality]) && is_array($_POST['sub_speciality_groups'][$subSpeciality])) {
                    foreach ($_POST['sub_speciality_groups'][$subSpeciality] as $groupType) {
                        $this->driversModel->addDriverOperatorSubSpecialityGroup($subSpecialityId, $groupType);
                    }
                }
            }
        }
    }
    
    // Διαχείριση κάρτας ψηφιακού ταχογράφου
    $this->driversModel->deleteDriverTachographCard($driverId);
    if (isset($_POST['has_tachograph_card']) && $_POST['has_tachograph_card']) {
        $cardNumber = $_POST['tachograph_card_number'] ?? null;
        $expiryDate = $_POST['tachograph_card_expiry'] ?? null;
        if ($cardNumber && $expiryDate) {
            $this->driversModel->addDriverTachographCard($driverId, $cardNumber, $expiryDate);
        }
    }
    
    // Διαχείριση ειδικών αδειών
    $this->driversModel->deleteDriverSpecialLicenses($driverId);
    
    // Άδεια ΤΑΞΙ
    if (isset($_POST['has_taxi_license']) && $_POST['has_taxi_license']) {
        $licenseNumber = $_POST['taxi_license_number'] ?? null;
        $expiryDate = $_POST['taxi_license_expiry'] ?? null;
        $details = $_POST['taxi_license_details'] ?? null;
        if ($licenseNumber) {
            $this->driversModel->addDriverSpecialLicense($driverId, 'TAXI', $licenseNumber, $expiryDate, $details);
        }
    }
    
    // Άδεια μεταφοράς ζώντων ζώων
    if (isset($_POST['has_animal_transport_license']) && $_POST['has_animal_transport_license']) {
        $licenseNumber = $_POST['animal_transport_license_number'] ?? null;
        $expiryDate = $_POST['animal_transport_license_expiry'] ?? null;
        $details = $_POST['animal_transport_license_details'] ?? null;
        if ($licenseNumber) {
            $this->driversModel->addDriverSpecialLicense($driverId, 'ANIMAL_TRANSPORT', $licenseNumber, $expiryDate, $details);
        }
    }
            
            // Διαχείριση μεταφόρτωσης εικόνας προφίλ αν υπάρχει
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $this->handleProfileImageUpload($driverId);
            }
            
            // Διαχείριση μεταφόρτωσης βιογραφικού αν υπάρχει
            if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
                $this->handleResumeFileUpload($driverId);
            }
            
            $_SESSION['success_message'] = 'Το προφίλ σας ενημερώθηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ σας. Παρακαλώ δοκιμάστε ξανά.';
        }
        
        header('Location: ' . BASE_URL . 'drivers/driver_profile');
        exit();
    }

    /**
     * Ενημέρωση της αυτοαξιολόγησης του οδηγού
     */
    public function updateAssessment() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/driver_profile');
            exit();
        }
        
        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];
        
        // Υπολογισμός βαθμολογίας από τις απαντήσεις του οδηγού
        $drivingSkills = $this->calculateCategoryScore([
            'driving_experience' => $_POST['driving_experience'] ?? 0,
            'annual_kilometers' => $_POST['annual_kilometers'] ?? 0,
            // Άλλες μετρικές
        ]);
        
        $safetyCompliance = $this->calculateCategoryScore([
            'accidents' => $_POST['accidents'] ?? 0,
            'traffic_violations' => $_POST['traffic_violations'] ?? 0,
            // Άλλες μετρικές
        ]);
        
        $professionalism = $this->calculateCategoryScore([
            // Συμπλήρωση με τις κατάλληλες μετρικές
            'professionalism' => 4 // Προσωρινή τιμή
        ]);
        
        $technicalKnowledge = $this->calculateCategoryScore([
            // Συμπλήρωση με τις κατάλληλες μετρικές
            'technical_knowledge' => 3 // Προσωρινή τιμή
        ]);
        
        // Υπολογισμός συνολικής βαθμολογίας
        $totalScore = ($drivingSkills + $safetyCompliance + $professionalism + $technicalKnowledge) / 4;
        
        // Αποθήκευση της αξιολόγησης
        // Εδώ θα χρησιμοποιούσαμε το DriverAssessmentModel
        // $this->driverAssessmentModel->updateAssessment($driverId, $totalScore, $drivingSkills, $safetyCompliance, $professionalism, $technicalKnowledge);
        
        $_SESSION['success_message'] = 'Η αυτοαξιολόγησή σας ενημερώθηκε με επιτυχία.';
        header('Location: ' . BASE_URL . 'drivers/driver_profile#self-assessment');
        exit();
    }
    
    /**
     * Υπολογίζει τη βαθμολογία κατηγορίας από τις απαντήσεις (σε κλίμακα 0-100)
     */
    private function calculateCategoryScore($answers) {
        $totalPoints = 0;
        $maxPoints = 0;
        
        foreach ($answers as $answer) {
            $totalPoints += intval($answer);
            $maxPoints += 5; // Θεωρούμε ότι η μέγιστη βαθμολογία για κάθε απάντηση είναι 5
        }
        
        if ($maxPoints === 0) return 0;
        
        return ($totalPoints / $maxPoints) * 100;
    }

    /**
     * Προβάλλει τα ταιριάσματα εργασίας για τον οδηγό
     */
    public function showJobMatches() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);
        
        // Εύρεση των συντεταγμένων της τοποθεσίας του οδηγού
        $driverLocation = null;
        if (!empty($driverData['address']) && !empty($driverData['city'])) {
            $address = urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']);
            $driverLocation = $this->getGeocodingData($address);
        }
        
        // Παράμετροι αναζήτησης
        $radius = isset($_GET['radius']) ? intval($_GET['radius']) : 10;
        
        // Εύρεση ταιριασμάτων εργασίας
        $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
        $matchedJobs = [];
        
        if ($driverLocation) {
            $params = [
                'latitude' => $driverLocation['lat'],
                'longitude' => $driverLocation['lng'],
                'search_radius' => $radius,
                'listing_type' => 'job_offer',
                'is_active' => 1
            ];
            
            // Προσθήκη φίλτρων με βάση τα προσόντα του οδηγού
            if ($driverData['driving_license']) {
                $params['required_license'] = $driverData['driving_license'];
            }
            
            if ($driverData['adr_certificate']) {
                $params['adr_certificate'] = 1;
            }
            
            if ($driverData['operator_license']) {
                $params['operator_license'] = 1;
            }
            
            if ($driverData['preferred_job_type']) {
                $params['job_type'] = $driverData['preferred_job_type'];
            }
            
            if ($driverData['preferred_vehicle_type']) {
                $params['vehicle_type'] = $driverData['preferred_vehicle_type'];
            }
            
            $matchedJobs = $jobListingModel->getActiveListings($params, 1, 10);
            
            // Υπολογισμός ποσοστού ταιριάσματος για κάθε θέση
            foreach ($matchedJobs['results'] as &$job) {
                $job['match_score'] = $this->calculateJobMatchScore($job, $driverData);
                $job['distance'] = $this->calculateDistance(
                    $driverLocation['lat'], 
                    $driverLocation['lng'], 
                    $job['latitude'], 
                    $job['longitude']
                );
            }
            
            // Ταξινόμηση με βάση το ποσοστό ταιριάσματος (φθίνουσα σειρά)
            usort($matchedJobs['results'], function($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });
        }
        
        // Επιστροφή των αποτελεσμάτων σε JSON
        header('Content-Type: application/json');
        echo json_encode($matchedJobs);
        exit();
    }
    
    /**
     * Υπολογίζει το ποσοστό ταιριάσματος μεταξύ οδηγού και αγγελίας (0-100)
     */
    private function calculateJobMatchScore($job, $driverData) {
        $score = 0;
        $total = 0;
        
        // Έλεγχος άδειας οδήγησης
        if (!empty($job['required_license']) && !empty($driverData['driving_license'])) {
            $total += 25;
            if ($job['required_license'] === $driverData['driving_license']) {
                $score += 25;
            }
        }
        
        // Έλεγχος ADR
        if ($job['adr_certificate']) {
            $total += 15;
            if ($driverData['adr_certificate']) {
                $score += 15;
            }
        }
        
        // Έλεγχος άδειας χειριστή
        if ($job['operator_license']) {
            $total += 15;
            if ($driverData['operator_license']) {
                $score += 15;
            }
        }
        
        // Έλεγχος τύπου εργασίας
        if (!empty($job['job_type']) && !empty($driverData['preferred_job_type'])) {
            $total += 10;
            if ($job['job_type'] === $driverData['preferred_job_type'] || $driverData['preferred_job_type'] === 'any') {
                $score += 10;
            }
        }
        
        // Έλεγχος τύπου οχήματος
        if (!empty($job['vehicle_type']) && !empty($driverData['preferred_vehicle_type'])) {
            $total += 10;
            if ($job['vehicle_type'] === $driverData['preferred_vehicle_type'] || $driverData['preferred_vehicle_type'] === 'any') {
                $score += 10;
            }
        }
        
        // Έλεγχος ετών εμπειρίας
        if (!empty($job['experience_years']) && !empty($driverData['experience_years'])) {
            $total += 15;
            if ($driverData['experience_years'] >= $job['experience_years']) {
                $score += 15;
            } else {
                // Μερικό ταίριασμα
                $ratio = $driverData['experience_years'] / $job['experience_years'];
                $score += round(15 * $ratio);
            }
        }
        
        // Έλεγχος απόστασης
        if (!empty($job['distance'])) {
            $total += 10;
            // Αντίστροφη κλιμάκωση με βάση την απόσταση
            if ($job['distance'] <= 5) {
                $score += 10;
            } else if ($job['distance'] <= 10) {
                $score += 8;
            } else if ($job['distance'] <= 20) {
                $score += 6;
            } else if ($job['distance'] <= 50) {
                $score += 4;
            } else {
                $score += 2;
            }
        }
        
        // Επιστροφή του ποσοστού
        if ($total === 0) return 0;
        
        return round(($score / $total) * 100);
    }

    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνας προφίλ
     */
    private function handleProfileImageUpload($driverId) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_image'];
        
        // Έλεγχος τύπου αρχείου
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο JPEG, PNG και GIF.';
            return false;
        }
        
        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 2MB.';
            return false;
        }
        
        // Δημιουργία του καταλόγου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $driverId . '_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/profile_images/' . $filename;
            return $this->driversModel->updateProfileImage($driverId, $relativePath);
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση της εικόνας. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }

    /**
     * Διαχειρίζεται τη μεταφόρτωση βιογραφικού
     */
    private function handleResumeFileUpload($driverId) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['resume_file'];
        
        // Έλεγχος τύπου αρχείου
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο PDF και DOC/DOCX.';
            return false;
        }
        
        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 5MB.';
            return false;
        }
        
        // Δημιουργία του καταλόγου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $driverId . '_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/resumes/' . $filename;
            return $this->driversModel->updateResumeFile($driverId, $relativePath);
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση του βιογραφικού. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    /**
     * Λαμβάνει τις συντεταγμένες από μια διεύθυνση μέσω της υπηρεσίας Geocoding
     */
    private function getGeocodingData($address) {
        $apiKey = 'AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc';
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";
        
        try {
            // Ορίστε ένα ρητό χρονικό όριο 5 δευτερολέπτων
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                // Σε περίπτωση αποτυχίας επιστρέφουμε null
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'][0]['geometry']['location'])) {
                return [
                    'lat' => $data['results'][0]['geometry']['location']['lat'],
                    'lng' => $data['results'][0]['geometry']['location']['lng']
                ];
            }
        } catch (Exception $e) {
            // Σε περίπτωση εξαίρεσης επιστρέφουμε null
        }
        
        return null;
    }
    
    /**
     * Υπολογίζει την απόσταση μεταξύ δύο σημείων (σε χιλιόμετρα)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Ακτίνα της Γης σε χιλιόμετρα
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 1);
    }
    
    /**
     * Βοηθητική μέθοδος για τον καθορισμό του τύπου ομάδας υποειδικότητας
     */
    private function getSubSpecialityGroupType($subSpeciality) {
        // Πίνακας με τις ομάδες για κάθε υποειδικότητα
        $groupTypes = [
            '1.1' => 'A', '1.2' => 'B', '1.3' => 'A', '1.4' => 'A', '1.5' => 'A',
            '1.6' => 'B', '1.7' => 'B', '1.8' => 'A', '1.9' => 'B',
            '2.1' => 'A', '2.2' => 'A', '2.3' => 'B', '2.4' => 'B', '2.5' => 'B',
            '2.6' => 'A', '2.7' => 'A', '2.8' => 'B', '2.9' => 'A',
            '3.1' => 'A', '3.2' => 'A', '3.3' => 'A', '3.4' => 'B', '3.5' => 'A',
            '3.6' => 'B', '3.7' => 'A', '3.8' => 'B', '3.9' => 'B', '3.10' => 'B',
            '3.11' => 'A', '3.12' => 'B',
            '4.1' => 'A', '4.2' => 'A', '4.3' => 'B', '4.4' => 'B', '4.5' => 'A',
            '4.6' => 'B', '4.7' => 'A', '4.8' => 'B',
            '5.1' => 'A', '5.2' => 'A', '5.3' => 'A', '5.4' => 'B', '5.5' => 'A',
            '5.6' => 'A',
            '6.1' => 'A', '6.2' => 'B',
            '7.1' => 'A', '7.2' => 'A', '7.3' => 'B',
            '8.1' => 'A', '8.2' => 'A', '8.3' => 'B', '8.4' => 'A', '8.5' => 'A',
            '8.6' => 'B', '8.7' => 'B', '8.8' => 'A', '8.9' => 'B'
        ];
        
        return $groupTypes[$subSpeciality] ?? 'A';
    }
}