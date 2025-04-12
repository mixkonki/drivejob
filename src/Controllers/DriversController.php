<?php

namespace Drivejob\Controllers;

use Drivejob\Models\DriversModel;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;

class DriversController {
    private $driversModel;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->driversModel = new DriversModel($pdo);
    }

    /**
     * Εμφανίζει τη φόρμα εγγραφής για οδηγούς
     */
    public function showRegistrationForm() {
        include ROOT_DIR . '/src/Views/drivers/drivers_registration.php';
    }

    /**
     * Επεξεργάζεται την εγγραφή ενός οδηγού
     */
    public function register() {
        // Έλεγχος αν η μέθοδος είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'drivers/drivers_registration.php');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/drivers_registration.php');
            exit();
        }

        // Επικύρωση δεδομένων
        $validator = new Validator($_POST);
        $validator->required('email', 'Το email είναι υποχρεωτικό.')
                  ->email('email', 'Παρακαλώ εισάγετε ένα έγκυρο email.')
                  ->required('password', 'Ο κωδικός είναι υποχρεωτικός.')
                  ->minLength('password', 8, 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.')
                  ->pattern('password', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', 'Ο κωδικός πρέπει να περιέχει τουλάχιστον ένα πεζό γράμμα, ένα κεφαλαίο γράμμα, έναν αριθμό και έναν ειδικό χαρακτήρα.')
                  ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
                  ->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
                  ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                  ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');

        // Έλεγχος αν το email υπάρχει ήδη
        if ($this->driversModel->emailExists($_POST['email'])) {
            $validator->getErrors()['email'] = 'Το email υπάρχει ήδη. Παρακαλώ χρησιμοποιήστε άλλο email.';
        }

        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            header('Location: ' . BASE_URL . 'drivers/drivers_registration.php');
            exit();
        }

        // Δημιουργία hash για το συνθηματικό
        $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Προετοιμασία των δεδομένων
        $data = [
            'email' => $_POST['email'],
            'password' => $hashedPassword,
            'last_name' => $_POST['last_name'],
            'first_name' => $_POST['first_name'],
            'phone' => $_POST['phone'],
            'is_verified' => 0 // Ο λογαριασμός θα χρειαστεί επαλήθευση
        ];

        // Εισαγωγή στη βάση δεδομένων
        $driverId = $this->driversModel->create($data);

        if ($driverId) {
            // Δημιουργία συνδέσμου επαλήθευσης
            $verificationToken = bin2hex(random_bytes(32));
            $_SESSION['verification_token'][$_POST['email']] = $verificationToken;
            
            $verificationLink = BASE_URL . "verify.php?email=" . urlencode($_POST['email']) . "&token=" . $verificationToken . "&role=driver";

            // Αποστολή email επαλήθευσης
            $subject = "Επαλήθευση Λογαριασμού DriveJob";
            $message = "Καλωσορίσατε στο DriveJob! Παρακαλώ επιβεβαιώστε το email σας πατώντας στον παρακάτω σύνδεσμο:\n\n" . $verificationLink;
            
            // Ελέγχουμε αν έχουμε συμπεριλάβει το email_helper.php
            if (function_exists('sendEmail')) {
                sendEmail($_POST['email'], $subject, $message);
            } else {
                // Για σκοπούς ανάπτυξης, απλά εμφανίζουμε τον σύνδεσμο
                $_SESSION['verification_link'] = $verificationLink;
            }

            // Ανακατεύθυνση σε μια σελίδα επιτυχίας
            $_SESSION['success_message'] = 'Η εγγραφή ολοκληρώθηκε με επιτυχία. Παρακαλώ ελέγξτε το email σας για να επαληθεύσετε τον λογαριασμό σας.';
            header('Location: ' . BASE_URL . 'registration_success.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/drivers_registration.php');
            exit();
        }
    }

    /**
     * Επαληθεύει έναν λογαριασμό οδηγού
     */
    public function verify($email, $token) {
        // Έλεγχος αν το token είναι έγκυρο
        if (!isset($_SESSION['verification_token'][$email]) || $_SESSION['verification_token'][$email] !== $token) {
            $_SESSION['error_message'] = 'Άκυρος σύνδεσμος επαλήθευσης.';
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }

        // Επαλήθευση του λογαριασμού
        if ($this->driversModel->verifyDriver($email)) {
            // Διαγραφή του token
            unset($_SESSION['verification_token'][$email]);
            
            $_SESSION['success_message'] = 'Ο λογαριασμός σας επαληθεύτηκε με επιτυχία. Μπορείτε τώρα να συνδεθείτε.';
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την επαλήθευση του λογαριασμού σας. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
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
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/profile.php';
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');
        
        // Λήψη των στοιχείων
        $companyId = $_SESSION['user_id'];
        $companyData = $this->companiesModel->getCompanyById($companyId);
        
        // Αποσφαλμάτωση - επιβεβαίωση ότι τα δεδομένα ανακτήθηκαν
        file_put_contents(
            ROOT_DIR . '/edit_profile_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "Edit Method Called - Company ID: {$companyId}\n" .
            "Company Data: " . print_r($companyData, true) . "\n", 
            FILE_APPEND
        );
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/edit_profile.php';
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
        
        // Επικύρωση δεδομένων
        $validator = new Validator($_POST);
        $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
                  ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
                  ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                  ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        
        if (isset($_POST['social_linkedin']) && $_POST['social_linkedin']) {
            $validator->pattern('social_linkedin', '/^https?:\/\/(?:www\.)?linkedin\.com\/.*$/', 'Παρακαλώ εισάγετε ένα έγκυρο URL LinkedIn.');
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
            'birth_date' => $_POST['birth_date'] ?? null,
            'address' => $_POST['address'] ?? null,
            'house_number' => $_POST['house_number'] ?? null,
            'city' => $_POST['city'] ?? null,
            'country' => $_POST['country'] ?? null,
            'postal_code' => $_POST['postal_code'] ?? null,
            'about_me' => $_POST['about_me'] ?? null,
            'experience_years' => $_POST['experience_years'] ?? null,
            'driving_license' => $_POST['driving_license'] ?? null,
            'driving_license_expiry' => $_POST['driving_license_expiry'] ?? null,
            'adr_certificate' => isset($_POST['adr_certificate']) ? 1 : 0,
            'adr_certificate_expiry' => $_POST['adr_certificate_expiry'] ?? null,
            'operator_license' => isset($_POST['operator_license']) ? 1 : 0,
            'operator_license_expiry' => $_POST['operator_license_expiry'] ?? null,
            'training_seminars' => isset($_POST['training_seminars']) ? 1 : 0,
            'training_details' => $_POST['training_details'] ?? null,
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
            'preferred_job_type' => $_POST['preferred_job_type'] ?? null,
            'preferred_location' => $_POST['preferred_location'] ?? null,
            'social_linkedin' => $_POST['social_linkedin'] ?? null
        ];
        
        // Ενημέρωση του προφίλ
        if ($this->driversModel->updateProfile($driverId, $data)) {
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
     * Προβάλλει το δημόσιο προφίλ ενός οδηγού (ορατό σε όλους)
     */
    public function publicProfile($id) {
        // Λήψη των στοιχείων του οδηγού
        $driverData = $this->driversModel->getDriverById($id);
        
        if (!$driverData || !$driverData['is_verified']) {
            header('Location: ' . BASE_URL . '404');
            exit();
        }
        
        // Λήψη των δημόσιων αγγελιών του οδηγού
        $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
        $listings = $jobListingModel->getDriverListings($id, true, 1, 5);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/public_profile.php';
    }

    /**
     * Αναζήτηση οδηγών
     */
    public function search() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Συλλογή παραμέτρων αναζήτησης
        $params = [
            'min_experience' => $_GET['min_experience'] ?? null,
            'location' => $_GET['location'] ?? null,
            'driving_license' => $_GET['driving_license'] ?? null,
            'adr_certificate' => isset($_GET['adr_certificate']) ? 1 : 0,
            'operator_license' => isset($_GET['operator_license']) ? 1 : 0,
            'training_seminars' => isset($_GET['training_seminars']) ? 1 : 0,
            'name' => $_GET['name'] ?? null
        ];
        
        // Εκτέλεση αναζήτησης
        $searchResults = $this->driversModel->searchDrivers($params, $page, $limit);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/search.php';
    }

    /**
     * Προβάλλει τη λίστα των κορυφαίων οδηγών
     */
    public function topRated() {
        $topDrivers = $this->driversModel->getTopRatedDrivers(10);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/top_rated.php';
    }

    /**
     * Προβάλλει τη λίστα των πρόσφατα διαθέσιμων οδηγών
     */
    public function recentlyAvailable() {
        $recentDrivers = $this->driversModel->getRecentAvailableDrivers(10);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/recently_available.php';
    }

    /**
     * Προσθήκη αξιολόγησης σε οδηγό
     */
    public function addRating($driverId) {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');
        
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/view/' . $driverId);
            exit();
        }
        
        // Επικύρωση δεδομένων
        $validator = new Validator($_POST);
        $validator->required('rating', 'Η αξιολόγηση είναι υποχρεωτική.')
                  ->inList('rating', ['1', '2', '3', '4', '5'], 'Η αξιολόγηση πρέπει να είναι από 1 έως 5.');
        
        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            header('Location: ' . BASE_URL . 'drivers/view/' . $driverId);
            exit();
        }
        
        // Ενημέρωση της αξιολόγησης
        if ($this->driversModel->updateRating($driverId, $_POST['rating'])) {
            $_SESSION['success_message'] = 'Η αξιολόγηση καταχωρήθηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την καταχώρηση της αξιολόγησης. Παρακαλώ δοκιμάστε ξανά.';
        }
        
        header('Location: ' . BASE_URL . 'drivers/view/' . $driverId);
        exit();
    }

    /**
     * Αλλαγή κωδικού πρόσβασης
     */
    public function changePassword() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Επικύρωση δεδομένων
        $validator = new Validator($_POST);
        $validator->required('current_password', 'Ο τρέχων κωδικός είναι υποχρεωτικός.')
                  ->required('new_password', 'Ο νέος κωδικός είναι υποχρεωτικός.')
                  ->minLength('new_password', 8, 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.')
                  ->pattern('new_password', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', 'Ο νέος κωδικός πρέπει να περιέχει τουλάχιστον ένα πεζό γράμμα, ένα κεφαλαίο γράμμα, έναν αριθμό και έναν ειδικό χαρακτήρα.')
                  ->required('confirm_password', 'Η επιβεβαίωση του νέου κωδικού είναι υποχρεωτική.')
                  ->matches('confirm_password', 'new_password', 'Οι κωδικοί δεν ταιριάζουν.');
        
        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];
        
        // Λήψη του οδηγού από τη βάση δεδομένων
        $driver = $this->driversModel->getDriverById($driverId);
        
        // Έλεγχος αν ο τρέχων κωδικός είναι σωστός
        if (!password_verify($_POST['current_password'], $driver['password'])) {
            $_SESSION['error_message'] = 'Ο τρέχων κωδικός είναι λανθασμένος.';
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Δημιουργία hash για τον νέο κωδικό
        $hashedPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        
        // Ενημέρωση του κωδικού
        if ($this->driversModel->updatePassword($driverId, $hashedPassword)) {
            $_SESSION['success_message'] = 'Ο κωδικός σας ενημερώθηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του κωδικού σας. Παρακαλώ δοκιμάστε ξανά.';
        }
        
        header('Location: ' . BASE_URL . 'drivers/edit-profile');
        exit();
    }
}