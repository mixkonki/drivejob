<?php
namespace Drivejob\Controllers;

use Drivejob\Models\CompaniesModel;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\Session;
use Drivejob\Core\AuthMiddleware;

class CompaniesController {
    private $companiesModel;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->companiesModel = new CompaniesModel($pdo);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ της εταιρείας
     */
    public function profile() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');
        
        // Λήψη των στοιχείων της εταιρείας
        $companyId = $_SESSION['user_id'];
        
        // Αποσφαλμάτωση
        file_put_contents(
            ROOT_DIR . '/company_controller_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "Company Controller Profile Method - Company ID: {$companyId}, Session: " . print_r($_SESSION, true) . "\n", 
            FILE_APPEND
        );
        
        $companyData = $this->companiesModel->getCompanyById($companyId);
        
        // Αποσφαλμάτωση των δεδομένων εταιρείας
        file_put_contents(
            ROOT_DIR . '/company_controller_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "Company Data Retrieved: " . print_r($companyData, true) . "\n", 
            FILE_APPEND
        );
        
        // Λήψη των αγγελιών της εταιρείας
        $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
        $listings = $jobListingModel->getCompanyListings($companyId, null, 1, 5);
        
        // Έλεγχος διαδρομής του view
        $viewPath = ROOT_DIR . '/src/Views/companies/profile.php';
        file_put_contents(
            ROOT_DIR . '/company_controller_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "View Path: {$viewPath}, Exists: " . (file_exists($viewPath) ? 'Yes' : 'No') . "\n", 
            FILE_APPEND
        );
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/profile.php';
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');
        
        // Λήψη των στοιχείων
        $companyId = $_SESSION['user_id'];
        $companyData = $this->companiesModel->getCompanyById($companyId);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/edit_profile.php';
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');
        
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'companies/edit-profile');
            exit();
        }
        
        // Επικύρωση δεδομένων
        $validator = new Validator($_POST);
        $validator->required('company_name', 'Το όνομα της εταιρείας είναι υποχρεωτικό.')
                  ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                  ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        
        if (isset($_POST['website']) && $_POST['website']) {
            $validator->pattern('website', '/^https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&\/=]*)$/', 'Παρακαλώ εισάγετε ένα έγκυρο URL ιστοσελίδας.');
        }
        
        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            header('Location: ' . BASE_URL . 'companies/edit-profile');
            exit();
        }
        
        // Λήψη ID της συνδεδεμένης εταιρείας
        $companyId = $_SESSION['user_id'];
        
        // Συλλογή των δεδομένων από τη φόρμα
        $data = [
            'company_name' => $_POST['company_name'],
            'phone' => $_POST['phone'],
            'description' => $_POST['description'] ?? null,
            'website' => $_POST['website'] ?? null,
            'address' => $_POST['address'] ?? null,
            'city' => $_POST['city'] ?? null,
            'country' => $_POST['country'] ?? null,
            'postal_code' => $_POST['postal_code'] ?? null,
            'contact_person' => $_POST['contact_person'] ?? null,
            'position' => $_POST['position'] ?? null,
            'vat_number' => $_POST['vat_number'] ?? null,
            'company_size' => $_POST['company_size'] ?? null,
            'foundation_year' => $_POST['foundation_year'] ?? null,
            'industry' => $_POST['industry'] ?? null,
            'social_linkedin' => $_POST['social_linkedin'] ?? null,
            'social_facebook' => $_POST['social_facebook'] ?? null,
            'social_twitter' => $_POST['social_twitter'] ?? null
        ];
        
        // Ενημέρωση του προφίλ
        if ($this->companiesModel->updateProfile($companyId, $data)) {
            // Διαχείριση μεταφόρτωσης λογότυπου αν υπάρχει
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $this->handleLogoUpload($companyId);
            }
            
            $_SESSION['success_message'] = 'Το προφίλ της εταιρείας ενημερώθηκε με επιτυχία.';
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ. Παρακαλώ δοκιμάστε ξανά.';
        }
        
        header('Location: ' . BASE_URL . 'companies/company_profile');
        exit();
    }

    /**
     * Διαχειρίζεται τη μεταφόρτωση λογότυπου
     */
    private function handleLogoUpload($companyId) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['company_logo'];
        
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
        $uploadDir = ROOT_DIR . '/public/uploads/company_logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $companyId . '_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/company_logos/' . $filename;
            return $this->companiesModel->updateCompanyLogo($companyId, $relativePath);
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση του λογότυπου. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }

    /**
     * Προβάλλει το δημόσιο προφίλ μιας εταιρείας (ορατό σε όλους)
     */
    public function publicProfile($id) {
        // Λήψη των στοιχείων της εταιρείας
        $companyData = $this->companiesModel->getCompanyById($id);
        
        if (!$companyData || !$companyData['is_verified']) {
            header('Location: ' . BASE_URL . '404');
            exit();
        }
        
        // Λήψη των δημόσιων αγγελιών της εταιρείας
        $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
        $listings = $jobListingModel->getCompanyListings($id, true, 1, 5);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/public_profile.php';
    }
}