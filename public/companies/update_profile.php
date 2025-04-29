<?php
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../../config/config.php';

// Συμπερίληψη του database.php για σύνδεση με τη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

use Drivejob\Core\Session;

// Ξεκίνημα συνεδρίας
Session::start();

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!Session::has('user_id') || !Session::has('role') || Session::get('role') !== 'company') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Έλεγχος αν η μέθοδος είναι POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'companies/edit_profile.php');
    exit();
}

// Ελέγχουμε το CSRF token
if (!isset($_POST['csrf_token']) || !\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'])) {
    Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
    header('Location: ' . BASE_URL . 'companies/edit_profile.php');
    exit();
}

// Λήψη του ID της εταιρείας από τη συνεδρία
$companyId = Session::get('user_id');

// Προετοιμασία των δεδομένων για ενημέρωση
$data = [
    'company_name' => $_POST['company_name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
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
    'foundation_year' => $_POST['foundation_year'] ? (int)$_POST['foundation_year'] : null,
    'industry' => $_POST['industry'] ?? null,
    'social_linkedin' => $_POST['social_linkedin'] ?? null,
    'social_facebook' => $_POST['social_facebook'] ?? null,
    'social_twitter' => $_POST['social_twitter'] ?? null
];

// Δημιουργία του controller και χρήση της μεθόδου update
$companyModel = new \Drivejob\Models\CompaniesModel($pdo);

// Ενημέρωση των δεδομένων της εταιρείας
if ($companyModel->updateProfile($companyId, $data)) {
    // Διαχείριση μεταφόρτωσης λογότυπου αν υπάρχει
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        // Επιτρεπόμενοι τύποι αρχείων
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['company_logo'];
        
        // Έλεγχος τύπου αρχείου
        if (!in_array($file['type'], $allowedTypes)) {
            Session::set('error_message', 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο JPEG, PNG και GIF.');
            header('Location: ' . BASE_URL . 'companies/edit_profile.php');
            exit();
        }
        
        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $maxSize) {
            Session::set('error_message', 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 2MB.');
            header('Location: ' . BASE_URL . 'companies/edit_profile.php');
            exit();
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
            $companyModel->updateCompanyLogo($companyId, $relativePath);
        } else {
            Session::set('error_message', 'Σφάλμα κατά τη μεταφόρτωση του λογότυπου. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/edit_profile.php');
            exit();
        }
    }
    
    Session::set('success_message', 'Το προφίλ της εταιρείας ενημερώθηκε με επιτυχία.');
} else {
    Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ. Παρακαλώ δοκιμάστε ξανά.');
}

// Ανακατεύθυνση πίσω στο προφίλ
header('Location: ' . BASE_URL . 'companies/company_profile.php');
exit();
?>