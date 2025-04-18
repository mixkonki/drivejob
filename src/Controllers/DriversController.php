<?php
namespace Drivejob\Controllers;
use Drivejob\Models\DriversModel;
use Drivejob\Models\DriverAssessmentModel;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;

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
        
        // Λήψη των αδειών οδήγησης του οδηγού
        $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
        $driverLicenseTypes = !empty($driverLicenses) ? array_column($driverLicenses, 'license_type') : [];
    
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
        
        // Λήψη των αγγελιών του οδηγού
        $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
        $listings = $jobListingModel->getDriverListings($driverId, null, 1, 5);
        
        // Λήψη των συντεταγμένων της τοποθεσίας του οδηγού για τον χάρτη
        $driverLocation = null;
        if (!empty($driverData['address']) && !empty($driverData['city'])) {
            $address = urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']);
            $driverLocation = $this->getGeocodingData($address);
        }
        
        // Λήψη δεδομένων αυτοαξιολόγησης
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
        
        // Φόρτωση των ειδικών αδειών
        $driverSpecialLicenses = $this->driversModel->getDriverSpecialLicenses($driverId);
        
        // Φόρτωση δεδομένων ταχογράφου
        $driverTachograph = $this->driversModel->getDriverTachographCard($driverId);
        
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
            $this->handleDrivingLicenses($driverId);
            
            // Διαχείριση μεταφόρτωσης εικόνων και αρχείων
            $this->handleFileUploads($driverId);
            
            // Διαχείριση ειδικών αδειών
            $this->handleSpecialLicenses($driverId);
            
            // Διαχείριση κάρτας ταχογράφου
            $this->handleTachographCard($driverId);
            
            // Διαχείριση πιστοποιητικού ADR
            $this->handleADRCertificate($driverId);
            
            // Διαχείριση άδειας χειριστή μηχανημάτων
            $this->handleOperatorLicense($driverId);
            
            $_SESSION['success_message'] = 'Το προφίλ σας ενημερώθηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ σας. Παρακαλώ δοκιμάστε ξανά.';
        }
        
        header('Location: ' . BASE_URL . 'drivers/driver_profile');
        exit();
    }
    
    /**
     * Συλλέγει τα δεδομένα της φόρμας σε ένα συγκεντρωτικό πίνακα
     */
    private function collectFormData() {
        return [
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
            'license_number' => $_POST['license_number'] ?? null,
            'license_document_expiry' => $_POST['license_document_expiry'] ?? null,
            'license_codes' => $_POST['license_codes'] ?? null,
            'marital_status' => $_POST['marital_status'] ?? null,
            'education_level' => $_POST['education_level'] ?? null,
            'military_service' => $_POST['military_service'] ?? null,
            'languages' => isset($_POST['languages']) ? implode(',', $_POST['languages']) : null,
            'language_notes' => $_POST['language_notes'] ?? null,
        ];
    }
    
    /**
     * Διαχειρίζεται τις άδειες οδήγησης
     */
    private function handleDrivingLicenses($driverId) {
        $this->driversModel->deleteDriverLicenses($driverId);
        
        if (isset($_POST['license_types']) && is_array($_POST['license_types'])) {
            $licenseNumber = $_POST['license_number'] ?? null;
            $licenseDocumentExpiry = $_POST['license_document_expiry'] ?? null;
            
            foreach ($_POST['license_types'] as $licenseType) {
                $hasPei = false;
                $peiExpiryC = null;
                $peiExpiryD = null;
                
                // Έλεγχος για ΠΕΙ στις κατηγορίες C και D (και υποκατηγορίες)
                if (in_array($licenseType, ['C', 'CE', 'C1', 'C1E'])) {
                    // Έλεγχος για το αντίστοιχο checkbox ΠΕΙ
                    $peiCheckboxName = 'has_pei_' . strtolower($licenseType);
                    if (isset($_POST[$peiCheckboxName])) {
                        $hasPei = true;
                        $peiExpiryC = !empty($_POST['pei_c_expiry']) ? $_POST['pei_c_expiry'] : null;
                    }
                } else if (in_array($licenseType, ['D', 'DE', 'D1', 'D1E'])) {
                    // Έλεγχος για το αντίστοιχο checkbox ΠΕΙ
                    $peiCheckboxName = 'has_pei_' . strtolower($licenseType);
                    if (isset($_POST[$peiCheckboxName])) {
                        $hasPei = true;
                        $peiExpiryD = !empty($_POST['pei_d_expiry']) ? $_POST['pei_d_expiry'] : null;
                    }
                }
                
                // Λήψη της ημερομηνίας λήξης για τη συγκεκριμένη κατηγορία
                $expiryDate = $_POST['license_expiry'][$licenseType] ?? null;
                
                $this->driversModel->addDriverLicense($driverId, $licenseType, $hasPei, $expiryDate, 
                                                      $licenseNumber, $peiExpiryC, $peiExpiryD, $licenseDocumentExpiry);
            }
        }
    }
    
    /**
     * Διαχειρίζεται τις μεταφορτώσεις αρχείων (εικόνες, βιογραφικό)
     */
    private function handleFileUploads($driverId) {
        // Μεταφορτώσεις εικόνων
        $imageFields = [
            'license_front_image',
            'license_back_image',
            'profile_image',
            'adr_front_image',
            'adr_back_image',
            'operator_front_image',
            'operator_back_image',
            'tachograph_front_image',
            'tachograph_back_image'
        ];
        
        foreach ($imageFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                if (in_array($field, ['license_front_image', 'license_back_image'])) {
                    $this->handleLicenseImageUpload($driverId, $field);
                } else if ($field === 'profile_image') {
                    $this->handleProfileImageUpload($driverId);
                } else {
                    $uploadDir = $this->getUploadDirectory($field);
                    $this->handleDocumentImageUpload($driverId, $field, $uploadDir, $field);
                }
            }
        }
        
        // Μεταφόρτωση βιογραφικού
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $this->handleResumeFileUpload($driverId);
        }
    }
    
    /**
     * Επιστρέφει τον κατάλογο μεταφόρτωσης για κάθε τύπο αρχείου
     */
    private function getUploadDirectory($fieldName) {
        $directories = [
            'adr_front_image' => 'uploads/adr_images/',
            'adr_back_image' => 'uploads/adr_images/',
            'operator_front_image' => 'uploads/operator_images/',
            'operator_back_image' => 'uploads/operator_images/',
            'tachograph_front_image' => 'uploads/tachograph_images/',
            'tachograph_back_image' => 'uploads/tachograph_images/',
            'license_front_image' => 'uploads/license_images/',
            'license_back_image' => 'uploads/license_images/',
            'profile_image' => 'uploads/profile_images/',
            'resume_file' => 'uploads/resumes/'
        ];
        
        return $directories[$fieldName] ?? 'uploads/';
    }
    
    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνας άδειας οδήγησης
     */
    private function handleLicenseImageUpload($driverId, $fieldName) {
        $uploadPath = 'uploads/license_images/';
        $this->handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $fieldName);
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
        $filename = $driverId . '_profile_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/profile_images/' . $filename;
            $this->driversModel->updateProfileImage($driverId, $relativePath);
            return true;
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
            $_SESSION['error_message'] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο PDF, DOC και DOCX.';
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
        $filename = $driverId . '_resume_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/resumes/' . $filename;
            $this->driversModel->updateResumeFile($driverId, $relativePath);
            return true;
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση του βιογραφικού. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνων διπλώματος, ADR, ταχογράφου, κλπ.
     */
    private function handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $documentType) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES[$fieldName];
        
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
        $uploadDir = ROOT_DIR . '/public/' . $uploadPath;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $driverId . '_' . $documentType . '_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Επιστροφή του σχετικού μονοπατιού
            $relativePath = $uploadPath . $filename;
            
            // Ενημέρωση του πεδίου στον πίνακα drivers
            $this->driversModel->updateDriverDocumentImage($driverId, $documentType, $relativePath);
            
            return $relativePath;
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση της εικόνας. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    /**
     * Διαχειρίζεται τις ειδικές άδειες
     */
    private function handleSpecialLicenses($driverId) {
        // Διαγραφή των υπαρχουσών ειδικών αδειών
        $this->driversModel->deleteDriverSpecialLicenses($driverId);
        
        // Αν έχουν υποβληθεί ειδικές άδειες, τις προσθέτουμε στη βάση
        if (isset($_POST['special_license_type']) && is_array($_POST['special_license_type'])) {
            foreach ($_POST['special_license_type'] as $index => $type) {
                // Αν ο τύπος άδειας δεν είναι κενός, προσθέτουμε την άδεια
                if (!empty(trim($type))) {
                    $licenseNumber = $_POST['special_license_number'][$index] ?? '';
                    $expiryDate = $_POST['special_license_expiry'][$index] ?? null;
                    $details = $_POST['special_license_details'][$index] ?? '';
                    
                    $this->driversModel->addDriverSpecialLicense($driverId, $type, $licenseNumber, $expiryDate, $details);
                }
            }
        }
    }
    
    /**
     * Διαχειρίζεται το πιστοποιητικό ADR
     */
    private function handleADRCertificate($driverId) {
        if (isset($_POST['adr_certificate']) && $_POST['adr_certificate'] == 1) {
            $adrData = [
                'adr_type' => $_POST['adr_certificate_type'] ?? null,
                'certificate_number' => $_POST['adr_certificate_number'] ?? null,
                'expiry_date' => $_POST['adr_certificate_expiry'] ?? null
            ];
            
            $this->driversModel->updateDriverADRCertificate($driverId, $adrData);
        } else {
            // Αν δεν έχει επιλεγεί το ADR, διαγράφουμε τα στοιχεία
            $this->driversModel->deleteDriverADRCertificate($driverId);
        }
    }
    
    /**
     * Διαχειρίζεται την κάρτα ταχογράφου
     */
    private function handleTachographCard($driverId) {
        if (isset($_POST['tachograph_card']) && $_POST['tachograph_card'] == 1) {
            $tachographData = [
                'card_number' => $_POST['tachograph_card_number'] ?? null,
                'expiry_date' => $_POST['tachograph_card_expiry'] ?? null
            ];
            
            $this->driversModel->updateDriverTachographCard($driverId, $tachographData);
        } else {
            // Αν δεν έχει επιλεγεί η κάρτα ταχογράφου, διαγράφουμε τα στοιχεία
            $this->driversModel->deleteDriverTachographCard($driverId);
        }
    }
    
    /**
     * Διαχειρίζεται την άδεια χειριστή μηχανημάτων
     */
    private function handleOperatorLicense($driverId) {
        Logger::init();
        
        if (isset($_POST['operator_license']) && $_POST['operator_license'] == 1) {
            // Δημιουργία του πίνακα δεδομένων
            $operatorData = [
                'speciality' => $_POST['operator_speciality'] ?? null,
                'license_number' => $_POST['operator_license_number'] ?? null,
                'expiry_date' => $_POST['operator_license_expiry'] ?? null
            ];
            
            // Ενημέρωση ή προσθήκη της άδειας χειριστή
            $operatorLicenseId = $this->driversModel->updateDriverOperatorLicense($driverId, $operatorData);
            
            if ($operatorLicenseId) {
                // Διαχείριση υποειδικοτήτων
                $this->handleOperatorSubSpecialities($operatorLicenseId);
            }
        } else {
            // Αν δεν έχει επιλεγεί η άδεια χειριστή, διαγράφουμε τα στοιχεία
            $this->driversModel->deleteDriverOperatorLicense($driverId);
        }
    }
    
    /**
     * Διαχειρίζεται τις υποειδικότητες της άδειας χειριστή
     */
    private function handleOperatorSubSpecialities($operatorLicenseId) {
        // Διαγραφή υπαρχουσών υποειδικοτήτων
        $this->driversModel->deleteDriverOperatorSubSpecialities($operatorLicenseId);
        
        // Λήψη των επιλεγμένων υποειδικοτήτων και ομάδων από JSON
        $selectedSubSpecialities = [];
        $selectedGroups = [];
        
        // Λήψη από το πεδίο JSON των υποειδικοτήτων
        if (isset($_POST['all_selected_subspecialities']) && !empty($_POST['all_selected_subspecialities'])) {
            try {
                $selectedSubSpecialities = json_decode($_POST['all_selected_subspecialities'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Σφάλμα JSON υποειδικοτήτων: " . json_last_error_msg());
                }
            } catch (\Exception $e) {
                Logger::error("Σφάλμα αποκωδικοποίησης JSON υποειδικοτήτων: " . $e->getMessage(), "OperatorLicense");
                $selectedSubSpecialities = [];
            }
        }
        
        // Λήψη από το πεδίο JSON των ομάδων
        if (isset($_POST['all_selected_groups']) && !empty($_POST['all_selected_groups'])) {
            try {
                $selectedGroups = json_decode($_POST['all_selected_groups'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Σφάλμα JSON ομάδων: " . json_last_error_msg());
                }
            } catch (\Exception $e) {
                Logger::error("Σφάλμα αποκωδικοποίησης JSON ομάδων: " . $e->getMessage(), "OperatorLicense");
                $selectedGroups = [];
            }
        }
        
        // Εναλλακτική μέθοδος λήψης αν η JSON μέθοδος αποτύχει
        if (empty($selectedSubSpecialities) && isset($_POST['operator_sub_specialities'])) {
            $selectedSubSpecialities = is_array($_POST['operator_sub_specialities']) 
                ? $_POST['operator_sub_specialities'] 
                : [$_POST['operator_sub_specialities']];
        }
        
        // Προσθήκη των επιλεγμένων υποειδικοτήτων
        if (!empty($selectedSubSpecialities)) {
            foreach ($selectedSubSpecialities as $subSpeciality) {
                // Καθορισμός της ομάδας (A ή B)
                $groupType = 'A'; // Προεπιλογή
                
                // Από το JSON αντικείμενο ομάδων
                if (isset($selectedGroups[$subSpeciality])) {
                    $groupType = $selectedGroups[$subSpeciality];
                } 
                // Από τα άμεσα πεδία της φόρμας
                else if (isset($_POST["group_{$subSpeciality}"])) {
                    $groupType = $_POST["group_{$subSpeciality}"];
                }
                
                // Προσθήκη της υποειδικότητας με την ομάδα της
                $this->driversModel->addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality, $groupType);
            }
        }
    }
    
    /**
     * Λαμβάνει τις συντεταγμένες από μια διεύθυνση μέσω της υπηρεσίας Geocoding
     * 
     * @param string $address Η διεύθυνση προς γεωκωδικοποίηση
     * @return array|null Συντεταγμένες [lat, lng] ή null σε περίπτωση σφάλματος
     */
    private function getGeocodingData($address) {
        try {
            $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // Αντικαταστήστε με το δικό σας API κλειδί
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";
            
            // Ορίστε ένα ρητό χρονικό όριο 5 δευτερολέπτων
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'][0]['geometry']['location'])) {
                return [
                    'lat' => $data['results'][0]['geometry']['location']['lat'],
                    'lng' => $data['results'][0]['geometry']['location']['lng']
                ];
            }
        } catch (\Exception $e) {
            Logger::error('Σφάλμα κατά τη λήψη δεδομένων geocoding: ' . $e->getMessage());
        }
        
        return null;
    }
}