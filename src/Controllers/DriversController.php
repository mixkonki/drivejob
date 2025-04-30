<?php

namespace Drivejob\Controllers;

use Drivejob\Models\DriversModel;
use Drivejob\Models\DriverAssessmentModel;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Models\DriverLicenseModel;
use Drivejob\Models\JobListingModel;
use Drivejob\Models\MatchingModel;
use Drivejob\Core\Session;
use Drivejob\Services\DriverLicenseService;
use Drivejob\Services\FileUploadService;
use Drivejob\Services\DriverRatingService;
use Drivejob\Services\DriverCertificationService;

class DriversController
{
    private $driversModel;
    private $driverAssessmentModel;
    // Προσθήκη των νέων ιδιοτήτων
    private $licenseService;
    private $fileUploadService;
    private $ratingService;
    private $certificationService;
    private $pdo;

    public function __construct($pdo = null)
    {
        // Εάν δεν έχει παραχθεί PDO, πάρε το από τη global μεταβλητή αν υπάρχει
        if ($pdo === null && isset($GLOBALS['pdo'])) {
            $pdo = $GLOBALS['pdo'];
        }

        $this->pdo = $pdo;
        $this->driversModel = new DriversModel($pdo);
        $this->driverAssessmentModel = new DriverAssessmentModel($pdo);

        // Δοκίμασε να αρχικοποιήσεις τις υπηρεσίες αν υπάρχουν οι αντίστοιχες κλάσεις
        if (class_exists('\Drivejob\Services\DriverLicenseService')) {
            $this->licenseService = new \Drivejob\Services\DriverLicenseService($this->driversModel);
        }

        if (class_exists('\Drivejob\Services\FileUploadService')) {
            $this->fileUploadService = new \Drivejob\Services\FileUploadService($this->driversModel);
        }

        if (class_exists('\Drivejob\Services\DriverRatingService')) {
            $this->ratingService = new \Drivejob\Services\DriverRatingService($pdo, $this->driversModel);
        }

        if (class_exists('\Drivejob\Services\DriverCertificationService')) {
            $this->certificationService = new \Drivejob\Services\DriverCertificationService($this->driversModel);
        }
    }

    // Προσθήκη της μεθόδου collectFormData για το sanitization
    private function collectFormData()
    {
        return [
            'first_name' => $this->sanitize($_POST['first_name']),
            'last_name' => $this->sanitize($_POST['last_name']),
            'phone' => $this->sanitize($_POST['phone']),
            'landline' => $this->sanitize($_POST['landline'] ?? ''),
            'birth_date' => $this->sanitizeDate($_POST['birth_date'] ?? null),
            'address' => $this->sanitize($_POST['address'] ?? ''),
            'house_number' => $this->sanitize($_POST['house_number'] ?? ''),
            'city' => $this->sanitize($_POST['city'] ?? ''),
            'country' => $this->sanitize($_POST['country'] ?? ''),
            'postal_code' => $this->sanitize($_POST['postal_code'] ?? ''),
            'about_me' => $this->sanitizeHtml($_POST['about_me'] ?? ''),
            'experience_years' => isset($_POST['experience_years']) ? intval($_POST['experience_years']) : null,
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
            'preferred_job_type' => $this->sanitize($_POST['preferred_job_type'] ?? ''),
            'preferred_vehicle_type' => $this->sanitize($_POST['preferred_vehicle_type'] ?? ''),
            'preferred_location' => $this->sanitize($_POST['preferred_location'] ?? ''),
            'preferred_radius' => $this->sanitize($_POST['preferred_radius'] ?? ''),
            'salary_min' => $_POST['salary_min'] ?? null,
            'salary_max' => $_POST['salary_max'] ?? null,
            'salary_period' => $this->sanitize($_POST['salary_period'] ?? ''),
            'social_linkedin' => $this->sanitizeUrl($_POST['social_linkedin'] ?? ''),
            'social_facebook' => $this->sanitizeUrl($_POST['social_facebook'] ?? ''),
            'social_twitter' => $this->sanitizeUrl($_POST['social_twitter'] ?? ''),
            'social_instagram' => $this->sanitizeUrl($_POST['social_instagram'] ?? ''),
            'willing_to_relocate' => isset($_POST['willing_to_relocate']) ? 1 : 0,
            'willing_to_travel' => isset($_POST['willing_to_travel']) ? 1 : 0,
            'license_number' => $this->sanitize($_POST['license_number'] ?? ''),
            'license_document_expiry' => $this->sanitizeDate($_POST['license_document_expiry'] ?? null),
            'license_codes' => $this->sanitize($_POST['license_codes'] ?? ''),
            'marital_status' => $this->sanitize($_POST['marital_status'] ?? ''),
            'education_level' => $this->sanitize($_POST['education_level'] ?? ''),
            'military_service' => $this->sanitize($_POST['military_service'] ?? ''),
            'languages' => isset($_POST['languages']) ? implode(',', array_map([$this, 'sanitize'], $_POST['languages'])) : null,
            'language_notes' => $this->sanitize($_POST['language_notes'] ?? ''),
        ];
    }

    // Μέθοδοι sanitization
    private function sanitize($input)
    {
        if ($input === null) {
            return null;
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeHtml($input)
    {
        if ($input === null) {
            return null;
        }

        // Επιτρέπουμε βασικά HTML tags
        $allowedTags = '<p><br><strong><em><ul><ol><li><h2><h3><h4>';
        return strip_tags(trim($input), $allowedTags);
    }

    private function sanitizeDate($date)
    {
        if ($date === null || empty($date)) {
            return null;
        }

        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj && $dateObj->format('Y-m-d') === $date) {
            return $date;
        }

        return null;
    }

    private function sanitizeUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
        if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
            return $sanitizedUrl;
        }

        return null;
    }

    /**
     * Χειρίζεται την ενημέρωση των προσόντων και πιστοποιήσεων
     * 
     * @param bool $redirect Αν θα γίνει ανακατεύθυνση μετά την ολοκλήρωση
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updateSkills($redirect = true)
    {
        // Καταγραφή των δεδομένων που υποβάλλονται για debug
        Logger::info('Updating skills for driver with DriverCertificationService. Driver ID: ' . $_SESSION['user_id']);

        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            if ($redirect) {
                header('Location: ' . BASE_URL . 'drivers/edit-profile');
                exit();
            }
            return false;
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        try {
            // Χρησιμοποίηση του certification service για την ενημέρωση των δεξιοτήτων
            $result = $this->certificationService->updateSkills($driverId, $_POST);

            if ($result) {
                $_SESSION['success_message'] = 'Τα προσόντα και οι πιστοποιήσεις σας ενημερώθηκαν με επιτυχία.';
            } else {
                $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση των προσόντων. Παρακαλώ δοκιμάστε ξανά.';
            }
        } catch (\Exception $e) {
            Logger::error('Error in updateSkills: ' . $e->getMessage());
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση των προσόντων. Παρακαλώ δοκιμάστε ξανά.';
        }

        if ($redirect) {
            header('Location: ' . BASE_URL . 'drivers/profile#qualifications');
            exit();
        }

        return isset($_SESSION['success_message']);
    }

    /**
     * Παλιά υλοποίηση της μεθόδου updateSkills για συμβατότητα
     */
    private function oldUpdateSkills($driverId)
    {
        try {
            // Εδώ βάλε τον παλιό κώδικα της μεθόδου updateSkills
            // ...

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in oldUpdateSkills: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Εναλλαγή διαθεσιμότητας οδηγού για εργασία
     */
    public function toggleAvailability()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Άκυρο αίτημα.']);
            exit();
        }

        try {
            // Λήψη του τρέχοντος οδηγού
            $driverId = $_SESSION['user_id'];
            $driver = $this->driversModel->getDriverById($driverId);

            if (!$driver) {
                echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε ο οδηγός.']);
                exit();
            }

            // Αλλαγή της κατάστασης διαθεσιμότητας
            $currentStatus = isset($driver['available_for_work']) ? (int)$driver['available_for_work'] : 0;
            $newStatus = $currentStatus ? 0 : 1;

            // Καταγραφή για εντοπισμό σφαλμάτων
            Logger::info("Εναλλαγή διαθεσιμότητας για οδηγό $driverId από $currentStatus σε $newStatus");

            $success = $this->driversModel->updateProfile($driverId, ['available_for_work' => $newStatus]);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Η διαθεσιμότητα ενημερώθηκε με επιτυχία',
                    'newStatus' => $newStatus,
                    'statusText' => $newStatus ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Αποτυχία ενημέρωσης διαθεσιμότητας']);
            }
        } catch (\Exception $e) {
            Logger::error("Σφάλμα κατά την εναλλαγή διαθεσιμότητας: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Σφάλμα επεξεργασίας αιτήματος']);
        }

        exit();
    }

    /**
     * Εμφανίζει σφάλμα με μορφοποιημένο τρόπο
     */
    private function showError($title, $message)
    {
        echo '<div style="color: red; padding: 20px; border: 1px solid red;">';
        echo '<h2>' . $title . '</h2>';
        echo '<p>' . $message . '</p>';
        echo '</div>';
    }

    /**
     * Φόρτωση της σελίδας επεξεργασίας βιογραφικού
     */
    public function editResume()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driver = $this->driversModel->getDriverById($driverId);

        if (!$driver) {
            $_SESSION['error_message'] = 'Τα στοιχεία του οδηγού δεν βρέθηκαν.';
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }

        // Λήψη επιπλέον δεδομένων για το βιογραφικό
        $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
        $driverLicenseTypes = [];
        if (!empty($driverLicenses)) {
            foreach ($driverLicenses as $license) {
                if (isset($license['license_type']) && !empty($license['license_type'])) {
                    $driverLicenseTypes[] = $license['license_type'];
                }
            }
        }

        $driverSkills = $this->driversModel->getDriverSkills($driverId);
        $driverSpecialLicenses = $this->driversModel->getDriverSpecialLicenses($driverId);
        $driverAdrCertificates = $this->driversModel->getDriverADRCertificate($driverId);
        $driverOperatorLicenses = $this->driversModel->getDriverOperatorLicense($driverId);
        $driverTachographCard = $this->driversModel->getDriverTachographCard($driverId);
        $averageRating = $this->driversModel->getDriverRating($driverId);

        // Φόρτωση της προβολής επεξεργασίας βιογραφικού
        include ROOT_DIR . '/src/Views/drivers/edit-resume.php';
    }

    /**
     * Αποθήκευση των αλλαγών στο βιογραφικό
     */
    public function updateResume()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Μη έγκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/edit-resume');
            exit();
        }

        $driverId = $_SESSION['user_id'];

        // Επικύρωση και καθαρισμός δεδομένων φόρμας
        $updateData = [
            'about_me' => $this->sanitizeHtml($_POST['about_me'] ?? ''),
            'experience_years' => intval($_POST['experience_years'] ?? 0),
            'work_experience' => $this->sanitizeHtml($_POST['work_experience'] ?? '')
        ];

        // Διαχείριση γλωσσών αν υπάρχουν
        if (isset($_POST['languages'])) {
            $updateData['language_greek'] = $this->sanitize($_POST['languages']['greek'] ?? '');
            $updateData['language_english'] = $this->sanitize($_POST['languages']['english'] ?? '');
            $updateData['language_german'] = $this->sanitize($_POST['languages']['german'] ?? '');
            $updateData['language_french'] = $this->sanitize($_POST['languages']['french'] ?? '');
            $updateData['language_italian'] = $this->sanitize($_POST['languages']['italian'] ?? '');
            $updateData['language_other_name'] = $this->sanitize($_POST['languages']['other_name'] ?? '');
            $updateData['language_other_level'] = $this->sanitize($_POST['languages']['other_level'] ?? '');
        }

        $success = $this->driversModel->updateProfile($driverId, $updateData);

        if ($success) {
            $_SESSION['success_message'] = 'Οι αλλαγές στο βιογραφικό σας αποθηκεύτηκαν με επιτυχία.';

            // Δημιουργία του βιογραφικού PDF με ResumeController
            $resumeController = new DriverResumeController($this->pdo);
            $resumeController->generateResume($driverId);
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα πρόβλημα κατά την αποθήκευση των αλλαγών.';
        }

        // Ανακατεύθυνση πίσω στο προφίλ
        header('Location: ' . BASE_URL . 'drivers/profile');
        exit();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     */
    public function publicProfile($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            $_SESSION['error_message'] = 'Μη έγκυρο αναγνωριστικό οδηγού';
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Ανάκτηση των στοιχείων του οδηγού
        $driver = $this->driversModel->getDriverById($id);

        if (!$driver) {
            $_SESSION['error_message'] = 'Ο οδηγός δεν βρέθηκε';
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Ανάκτηση πρόσθετων πληροφοριών για τον οδηγό
        $driverSkills = $this->driversModel->getDriverSkills($id);
        $driverLicenses = $this->driversModel->getDriverLicenses($id);
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');
        $driverReviews = $this->driversModel->getDriverReviews($id);
        $averageRating = $this->driversModel->getDriverRating($id);

        // Ανάκτηση των αγγελιών του οδηγού
        $jobListingModel = new JobListingModel($this->pdo);
        $listings = $jobListingModel->getDriverListings($id, true, 1, 5);

        // Απόδοση της σελίδας με τα δεδομένα
        include ROOT_DIR . '/src/Views/drivers/public-profile.php';
    }
    /**
     * Προβάλλει τη σελίδα προφίλ του οδηγού
     */
    public function profile()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);

        // Λήψη των αδειών οδήγησης του οδηγού
        $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
        $driverLicenseTypes = !empty($driverLicenses) ? array_column($driverLicenses, 'license_type') : [];

        // Λήψη των δεξιοτήτων του οδηγού
        $driverSkills = $this->driversModel->getDriverSkills($driverId);

        // Λήψη των πιστοποιήσεων του οδηγού
        $driverCertifications = $this->driversModel->getDriverCertifications($driverId);

        // Λήψη της εμπειρίας σε οχήματα
        $driverVehicleExperience = $this->driversModel->getDriverVehicleExperience($driverId);

        // Έλεγχος για ΠΕΙ
        $hasPeiC = false;
        $hasPeiD = false;
        $peiCExpiryDate = null;
        $peiDExpiryDate = null;

        if (!empty($driverLicenses)) {
            foreach ($driverLicenses as $license) {
                if (!empty($license['has_pei']) && $license['has_pei'] == 1) {
                    if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E'])) {
                        $hasPeiC = true;
                        if (!empty($license['pei_expiry_c'])) {
                            $peiCExpiryDate = $license['pei_expiry_c'];
                        }
                    } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E'])) {
                        $hasPeiD = true;
                        if (!empty($license['pei_expiry_d'])) {
                            $peiDExpiryDate = $license['pei_expiry_d'];
                        }
                    }
                }
            }
        }

        // Υπόλοιπος κώδικας...

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/profile.php';
    }
    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);

        // Λήψη των αδειών οδήγησης του οδηγού
        $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');
        $driverPEI = array_column(array_filter($driverLicenses, function ($license) {
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

        // Φόρτωση των ειδικών αδειών
        $driverSpecialLicenses = $this->driversModel->getDriverSpecialLicenses($driverId);

        // Φόρτωση δεδομένων ταχογράφου
        $driverTachograph = $this->driversModel->getDriverTachographCard($driverId);

        // Λήψη των δεξιοτήτων του οδηγού
        $driverSkills = $this->driversModel->getDriverSkills($driverId);

        // Λήψη των πιστοποιήσεων του οδηγού
        $driverCertifications = $this->driversModel->getDriverCertifications($driverId);

        // Λήψη της εμπειρίας σε οχήματα
        $driverVehicleExperience = $this->driversModel->getDriverVehicleExperience($driverId);

        // Προετοιμασία δεδομένων ΠΕΙ
        $peiCExpiryDate = null;
        $peiDExpiryDate = null;

        foreach ($driverLicenses as $license) {
            if (isset($license['has_pei']) && $license['has_pei'] == 1) {
                if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E']) && !empty($license['pei_expiry_c'])) {
                    $peiCExpiryDate = $license['pei_expiry_c'];
                } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E']) && !empty($license['pei_expiry_d'])) {
                    $peiDExpiryDate = $license['pei_expiry_d'];
                }
            }
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/edit_profile.php';
    }
    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
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

        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();

        // Ενημέρωση του προφίλ
        if ($this->driversModel->updateProfile($driverId, $data)) {
            // Διαχείριση αδειών οδήγησης
            if (isset($this->licenseService)) {
                $this->licenseService->handleDrivingLicenses($driverId, $_POST);
            }

            // Διαχείριση μεταφόρτωσης εικόνων και αρχείων
            if (isset($this->fileUploadService)) {
                $this->fileUploadService->handleFileUploads($driverId, $_FILES);
            }

            // Διαχείριση ειδικών αδειών
            if (isset($this->licenseService)) {
                $this->licenseService->handleSpecialLicenses($driverId, $_POST);
            }

            // Διαχείριση κάρτας ταχογράφου
            if (isset($this->licenseService)) {
                $this->licenseService->handleTachographCard($driverId, $_POST);
            }

            // Διαχείριση πιστοποιητικού ADR
            if (isset($this->licenseService)) {
                $this->licenseService->handleADRCertificate($driverId, $_POST);
            }

            // Διαχείριση άδειας χειριστή μηχανημάτων
            if (isset($this->licenseService)) {
                $this->licenseService->handleOperatorLicense($driverId, $_POST);
            }

            // Διαχείριση δεξιοτήτων και πιστοποιήσεων 
            if (isset($this->certificationService) && (isset($_POST['skills']) || isset($_POST['languages']) || isset($_POST['certifications']))) {
                $this->certificationService->updateSkills($driverId, $_POST);
            }

            $_SESSION['success_message'] = 'Το προφίλ σας ενημερώθηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ σας. Παρακαλώ δοκιμάστε ξανά.';
        }

        header('Location: ' . BASE_URL . 'drivers/profile');
        exit();
    }
    /**
     * Εμφανίζει τη σελίδα βαθμολογίας του οδηγού
     */
    public function driverRating()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);

        // Λήψη της βαθμολογίας
        $driverRating = $this->driversModel->getDriverRatingDetails($driverId);

        // Αν δεν υπάρχει βαθμολογία, δημιουργούμε προεπιλεγμένη
        if (!$driverRating) {
            // Αν έχουμε το service, χρησιμοποιούμε τη μέθοδο updateDriverRating
            if (isset($this->ratingService)) {
                $this->ratingService->updateDriverRating($driverId);
                $driverRating = $this->driversModel->getDriverRatingDetails($driverId);
            } else {
                // Αν δεν έχουμε το service, δημιουργούμε μια προεπιλεγμένη βαθμολογία
                $driverRating = [
                    'skills_score' => 0,
                    'safety_score' => 0,
                    'professionalism_score' => 0,
                    'technical_score' => 0,
                    'total_score' => 0,
                    'last_updated' => date('Y-m-d H:i:s')
                ];
            }
        }

        // Λήψη δεδομένων τηλεματικής
        $telemetryData = $this->driversModel->getDriverTelemetryData($driverId);

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/driver-rating.php';
    }

    /**
     * Ανανεώνει τη βαθμολογία του οδηγού
     */
    public function refreshRating()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Ενημέρωση της βαθμολογίας
        if (isset($this->ratingService)) {
            if ($this->ratingService->updateDriverRating($driverId)) {
                $_SESSION['success_message'] = 'Η βαθμολογία σας ενημερώθηκε με επιτυχία.';
            } else {
                $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της βαθμολογίας.';
            }
        } else {
            $_SESSION['error_message'] = 'Η υπηρεσία βαθμολόγησης δεν είναι διαθέσιμη.';
        }

        header('Location: ' . BASE_URL . 'drivers/driver-rating');
        exit();
    }
    /**
     * Εμφανίζει το ιστορικό συμβάντων του οδηγού
     */
    public function incidentHistory()
    {
        try {
            // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
            AuthMiddleware::hasRole('driver');

            // Λήψη των στοιχείων του οδηγού
            $driverId = $_SESSION['user_id'];
            $incidents = $this->driversModel->getDriverIncidents($driverId);

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/drivers/incident-history.php';
        } catch (\Exception $e) {
            Logger::error('Error in incidentHistory: ' . $e->getMessage());
            $this->showError('Σφάλμα κατά τη φόρτωση του ιστορικού συμβάντων', $e->getMessage());
        }
    }

    /**
     * Εμφανίζει τη φόρμα καταχώρησης συμβάντος
     */
    public function reportIncident()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/report-incident.php';
    }

    /**
     * Αποθηκεύει ένα νέο συμβάν
     */
    public function saveIncident()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        }

        // Επικύρωση δεδομένων
        $validator = new Validator($_POST);
        $validator->required('incident_type', 'Ο τύπος συμβάντος είναι υποχρεωτικός.')
            ->required('incident_date', 'Η ημερομηνία συμβάντος είναι υποχρεωτική.')
            ->required('severity', 'Η σοβαρότητα είναι υποχρεωτική.')
            ->required('description', 'Η περιγραφή είναι υποχρεωτική.');

        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Προετοιμασία δεδομένων
        $incidentData = [
            'incident_type' => $this->sanitize($_POST['incident_type']),
            'incident_date' => $this->sanitizeDate($_POST['incident_date']),
            'severity' => intval($_POST['severity']),
            'description' => $this->sanitizeHtml($_POST['description'])
        ];

        // Αποθήκευση του συμβάντος
        if ($this->driversModel->saveDriverIncident($driverId, $incidentData)) {
            $_SESSION['success_message'] = 'Το συμβάν καταχωρήθηκε με επιτυχία.';

            // Ενημέρωση της συνολικής βαθμολογίας
            if (isset($this->ratingService)) {
                $this->ratingService->updateDriverRating($driverId);
            }

            header('Location: ' . BASE_URL . 'drivers/incident-history');
            exit();
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την καταχώρηση του συμβάντος.';
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        }
    }
    /**
     * Εμφανίζει τη φόρμα αυτοαξιολόγησης του οδηγού
     */
    public function updateAssessment()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Λήψη δεδομένων οδηγού
        $driver = $this->driversModel->getDriverById($driverId);

        // Λήψη δεδομένων αξιολόγησης (αν υπάρχουν)
        $assessment = $this->driversModel->getDriverAssessment($driverId);

        // Έλεγχος αν είναι αίτημα αποθήκευσης
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Επικύρωση CSRF token
            if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
                $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
                header('Location: ' . BASE_URL . 'drivers/update-assessment');
                exit();
            }

            // Συλλογή και καθαρισμός δεδομένων αξιολόγησης
            $assessmentData = [
                // Οδηγικές Ικανότητες
                'driving_experience' => intval($_POST['driving_experience'] ?? 0),
                'annual_kilometers' => intval($_POST['annual_kilometers'] ?? 0),
                'driving_conditions' => intval($_POST['driving_conditions'] ?? 0),
                'eco_driving_rating' => intval($_POST['eco_driving_rating'] ?? 0),
                'night_driving' => intval($_POST['night_driving'] ?? 0),

                // Ασφάλεια & Συμμόρφωση
                'accidents' => intval($_POST['accidents'] ?? 0),
                'traffic_violations' => intval($_POST['traffic_violations'] ?? 0),
                'tachograph_compliance' => intval($_POST['tachograph_compliance'] ?? 0),
                'safety_check' => intval($_POST['safety_check'] ?? 0),
                'load_securing' => intval($_POST['load_securing'] ?? 0),

                // Επαγγελματισμός
                'punctuality' => intval($_POST['punctuality'] ?? 0),
                'customer_interaction' => intval($_POST['customer_interaction'] ?? 0),
                'appearance' => intval($_POST['appearance'] ?? 0),
                'documentation' => intval($_POST['documentation'] ?? 0),

                // Τεχνικές Γνώσεις
                'vehicle_maintenance' => intval($_POST['vehicle_maintenance'] ?? 0),
                'troubleshooting' => intval($_POST['troubleshooting'] ?? 0),
                'navigation_skills' => intval($_POST['navigation_skills'] ?? 0),
                'technical_knowledge' => intval($_POST['technical_knowledge'] ?? 0)
            ];

            // Αποθήκευση δεδομένων αξιολόγησης
            if ($this->driversModel->updateDriverAssessment($driverId, $assessmentData)) {
                // Ενημέρωση βαθμολογίας
                if (isset($this->ratingService)) {
                    $this->ratingService->updateDriverRating($driverId);
                }

                $_SESSION['success_message'] = 'Η αυτοαξιολόγησή σας αποθηκεύτηκε με επιτυχία.';
                header('Location: ' . BASE_URL . 'drivers/driver-rating');
                exit();
            } else {
                $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την αποθήκευση της αυτοαξιολόγησης.';
            }
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/update-assessment.php';
    }

    /**
     * Αποθηκεύει την αυτοαξιολόγηση του οδηγού
     */
    public function saveAssessment()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/update-assessment');
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Συλλογή και καθαρισμός δεδομένων αξιολόγησης
        $assessmentData = [
            // Οδηγικές Ικανότητες
            'driving_experience' => intval($_POST['driving_experience'] ?? 0),
            'annual_kilometers' => intval($_POST['annual_kilometers'] ?? 0),
            'driving_conditions' => intval($_POST['driving_conditions'] ?? 0),
            'eco_driving_rating' => intval($_POST['eco_driving_rating'] ?? 0),
            'night_driving' => intval($_POST['night_driving'] ?? 0),

            // Ασφάλεια & Συμμόρφωση
            'accidents' => intval($_POST['accidents'] ?? 0),
            'traffic_violations' => intval($_POST['traffic_violations'] ?? 0),
            'tachograph_compliance' => intval($_POST['tachograph_compliance'] ?? 0),
            'safety_check' => intval($_POST['safety_check'] ?? 0),
            'load_securing' => intval($_POST['load_securing'] ?? 0),

            // Επαγγελματισμός
            'punctuality' => intval($_POST['punctuality'] ?? 0),
            'customer_interaction' => intval($_POST['customer_interaction'] ?? 0),
            'appearance' => intval($_POST['appearance'] ?? 0),
            'documentation' => intval($_POST['documentation'] ?? 0),

            // Τεχνικές Γνώσεις
            'vehicle_maintenance' => intval($_POST['vehicle_maintenance'] ?? 0),
            'troubleshooting' => intval($_POST['troubleshooting'] ?? 0),
            'navigation_skills' => intval($_POST['navigation_skills'] ?? 0),
            'technical_knowledge' => intval($_POST['technical_knowledge'] ?? 0)
        ];

        // Αποθήκευση δεδομένων αξιολόγησης
        if ($this->driversModel->updateDriverAssessment($driverId, $assessmentData)) {
            // Ενημέρωση βαθμολογίας
            if (isset($this->ratingService)) {
                $this->ratingService->updateDriverRating($driverId);
            }

            $_SESSION['success_message'] = 'Η αυτοαξιολόγησή σας αποθηκεύτηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την αποθήκευση της αυτοαξιολόγησης.';
        }

        header('Location: ' . BASE_URL . 'drivers/driver-rating');
        exit();
    }
}
