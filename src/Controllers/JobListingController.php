<?php

namespace Drivejob\Controllers;

use Drivejob\Models\JobListingModel;
use Drivejob\Models\JobTagModel;

class JobListingController {
    private $jobListingModel;
    private $jobTagModel;

    public function __construct($pdo) {
        $this->jobListingModel = new JobListingModel($pdo);
        $this->jobTagModel = new JobTagModel($pdo);
    }

    public function index() {
        // Επιτρέπουμε σε όλους να βλέπουν τις αγγελίες - δεν απαιτείται σύνδεση
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $params = [];

        // Φιλτράρισμα αποτελεσμάτων από $_GET παραμέτρους
        if (isset($_GET['listing_type'])) {
            $params['listing_type'] = $_GET['listing_type'];
        }
        
        if (isset($_GET['job_type'])) {
            $params['job_type'] = $_GET['job_type'];
        }
        
        if (isset($_GET['vehicle_type'])) {
            $params['vehicle_type'] = $_GET['vehicle_type'];
        }
        
        // Φιλτρα τοποθεσίας
        if (isset($_GET['latitude']) && isset($_GET['longitude']) && isset($_GET['radius'])) {
            $params['latitude'] = $_GET['latitude'];
            $params['longitude'] = $_GET['longitude'];
            $params['search_radius'] = $_GET['radius'];
        }
        
        // Ειδικές απαιτήσεις
        if (isset($_GET['adr_certificate'])) {
            $params['adr_certificate'] = (bool)$_GET['adr_certificate'];
        }
        
        if (isset($_GET['operator_license'])) {
            $params['operator_license'] = (bool)$_GET['operator_license'];
        }

        // Λήψη αγγελιών με φιλτράρισμα
        $listings = $this->jobListingModel->getActiveListings($params, $page, $limit);

        // Φόρτωση του view
        require ROOT_DIR . '/src/Views/job-listings/index.php';
    }

    public function show($id) {
        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);
        
        if (!$listing) {
            // Αν δεν βρέθηκε η αγγελία
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Λήψη των tags της αγγελίας
        $tags = $this->jobListingModel->getTagsByJobId($id);
        
        // Λήψη στοιχείων για την εταιρεία ή τον οδηγό
        if ($listing['company_id']) {
            $companyModel = new \Drivejob\Models\CompaniesModel($this->pdo);
            $author = $companyModel->getCompanyById($listing['company_id']);
        } else {
            $driverModel = new \Drivejob\Models\DriversModel($this->pdo);
            $author = $driverModel->getDriverById($listing['driver_id']);
        }
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/show.php';
    }

    public function create() {
        // Βεβαιωθείτε ότι η συνεδρία έχει ξεκινήσει
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Λήψη όλων των διαθέσιμων tags
        $tags = $this->jobTagModel->getAllTags();
        
        // Φόρτωση του view με τη φόρμα δημιουργίας
        include ROOT_DIR . '/src/Views/job-listings/create.php';
    }

    public function store() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Έλεγχος αν η μέθοδος είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }
        
        // Επικύρωση δεδομένων
        $validator = new \Drivejob\Core\Validator($_POST);
        $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
                ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
                ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
                ->required('vehicle_type', 'Ο τύπος οχήματος είναι υποχρεωτικός.')
                ->required('required_license', 'Η απαιτούμενη άδεια είναι υποχρεωτική.');
        
        if (isset($_POST['contact_email']) && $_POST['contact_email']) {
            $validator->email('contact_email', 'Το email επικοινωνίας δεν είναι έγκυρο.');
        }
        
        if (!$validator->isValid()) {
            // Αποθήκευση των σφαλμάτων και των δεδομένων στο session για να τα εμφανίσουμε στη φόρμα
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            
            // Ανακατεύθυνση πίσω στη φόρμα
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }
        
        // Επεξεργασία και επικύρωση δεδομένων
        $data = [
            'title' => trim($_POST['title']),
            'listing_type' => $_POST['listing_type'],
            'job_type' => $_POST['job_type'],
            'vehicle_type' => $_POST['vehicle_type'],
            'required_license' => $_POST['required_license'],
            'description' => trim($_POST['description']),
            'salary_min' => $_POST['salary_min'] ? $_POST['salary_min'] : null,
            'salary_max' => $_POST['salary_max'] ? $_POST['salary_max'] : null,
            'salary_type' => $_POST['salary_type'] ? $_POST['salary_type'] : null,
            'location' => trim($_POST['location']),
            'latitude' => $_POST['latitude'] ? $_POST['latitude'] : null,
            'longitude' => $_POST['longitude'] ? $_POST['longitude'] : null,
            'radius' => $_POST['radius'] ? $_POST['radius'] : null,
            'remote_possible' => isset($_POST['remote_possible']) ? 1 : 0,
            'experience_years' => $_POST['experience_years'] ? $_POST['experience_years'] : null,
            'adr_certificate' => isset($_POST['adr_certificate']) ? 1 : 0,
            'operator_license' => isset($_POST['operator_license']) ? 1 : 0,
            'required_training' => trim($_POST['required_training']),
            'benefits' => trim($_POST['benefits']),
            'contact_email' => trim($_POST['contact_email']),
            'contact_phone' => trim($_POST['contact_phone']),
            'expires_at' => $_POST['expires_at'] ? $_POST['expires_at'] : null
        ];
        
        // Ανάλογα με τον ρόλο του χρήστη, προσθέτουμε company_id ή driver_id
        if ($_SESSION['role'] === 'company') {
            $data['company_id'] = $_SESSION['user_id'];
            $data['driver_id'] = null;
        } else {
            $data['driver_id'] = $_SESSION['user_id'];
            $data['company_id'] = null;
        }
        
        // Δημιουργία της αγγελίας
        $jobListingId = $this->jobListingModel->create($data);
        
        // Προσθήκη tags αν έχουν επιλεγεί
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            foreach ($_POST['tags'] as $tagId) {
                $this->jobListingModel->addTag($jobListingId, $tagId);
            }
        }
        
        // Ανακατεύθυνση στη σελίδα της αγγελίας
        header('Location: ' . BASE_URL . 'job-listings/show/' . $jobListingId);
        exit();
    }

    public function edit($id) {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);
        
        if (!$listing) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
        if (($_SESSION['role'] === 'company' && $listing['company_id'] != $_SESSION['user_id']) || 
            ($_SESSION['role'] === 'driver' && $listing['driver_id'] != $_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Λήψη των tags της αγγελίας
        $listingTags = $this->jobListingModel->getTagsByJobId($id);
        $selectedTagIds = array_column($listingTags, 'id');
        
        // Λήψη όλων των διαθέσιμων tags
        $allTags = $this->jobTagModel->getAllTags();
        
        // Φόρτωση του view με τη φόρμα επεξεργασίας
        include ROOT_DIR . '/src/Views/job-listings/edit.php';
    }

    public function update($id) {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Έλεγχος αν η μέθοδος είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
            exit();
        }
        
        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);
        
        if (!$listing) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
        if (($_SESSION['role'] === 'company' && $listing['company_id'] != $_SESSION['user_id']) || 
            ($_SESSION['role'] === 'driver' && $listing['driver_id'] != $_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Επεξεργασία και επικύρωση δεδομένων
        $data = [
            'title' => trim($_POST['title']),
            'job_type' => $_POST['job_type'],
            'vehicle_type' => $_POST['vehicle_type'],
            'required_license' => $_POST['required_license'],
            'description' => trim($_POST['description']),
            'salary_min' => $_POST['salary_min'] ? $_POST['salary_min'] : null,
            'salary_max' => $_POST['salary_max'] ? $_POST['salary_max'] : null,
            'salary_type' => $_POST['salary_type'] ? $_POST['salary_type'] : null,
            'location' => trim($_POST['location']),
            'latitude' => $_POST['latitude'] ? $_POST['latitude'] : null,
            'longitude' => $_POST['longitude'] ? $_POST['longitude'] : null,
            'radius' => $_POST['radius'] ? $_POST['radius'] : null,
            'remote_possible' => isset($_POST['remote_possible']) ? 1 : 0,
            'experience_years' => $_POST['experience_years'] ? $_POST['experience_years'] : null,
            'adr_certificate' => isset($_POST['adr_certificate']) ? 1 : 0,
            'operator_license' => isset($_POST['operator_license']) ? 1 : 0,
            'required_training' => trim($_POST['required_training']),
            'benefits' => trim($_POST['benefits']),
            'contact_email' => trim($_POST['contact_email']),
            'contact_phone' => trim($_POST['contact_phone']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'expires_at' => $_POST['expires_at'] ? $_POST['expires_at'] : null
        ];
        
        // Ενημέρωση της αγγελίας
        $this->jobListingModel->update($id, $data);
        
        // Ενημέρωση των tags
        // Πρώτα διαγράφουμε όλες τις συσχετίσεις tags
        $this->jobTagModel->deleteAllTagsForJob($id);
        
        // Μετά προσθέτουμε τα νέα tags
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            foreach ($_POST['tags'] as $tagId) {
                $this->jobListingModel->addTag($id, $tagId);
            }
        }
        
        // Ανακατεύθυνση στη σελίδα της αγγελίας
        header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
        exit();
    }

    public function delete($id) {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);
        
        if (!$listing) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
        if (($_SESSION['role'] === 'company' && $listing['company_id'] != $_SESSION['user_id']) || 
            ($_SESSION['role'] === 'driver' && $listing['driver_id'] != $_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Διαγραφή της αγγελίας
        $this->jobListingModel->delete($id);
        
        // Ανακατεύθυνση στη λίστα αγγελιών
        header('Location: ' . BASE_URL . 'job-listings');
        exit();
    }

    // Προβολή αγγελιών μιας εταιρείας
    public function companyListings($companyId) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Λήψη των αγγελιών της εταιρείας
        $listings = $this->jobListingModel->getCompanyListings($companyId, true, $page, $limit);
        
        // Λήψη πληροφοριών για την εταιρεία
        $companyModel = new \Drivejob\Models\CompaniesModel($this->pdo);
        $company = $companyModel->getCompanyById($companyId);
        
        if (!$company) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/company-listings.php';
    }

    // Προβολή αγγελιών ενός οδηγού
    public function driverListings($driverId) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Λήψη των αγγελιών του οδηγού
        $listings = $this->jobListingModel->getDriverListings($driverId, true, $page, $limit);
        
        // Λήψη πληροφοριών για τον οδηγό
        $driverModel = new \Drivejob\Models\DriversModel($this->pdo);
        $driver = $driverModel->getDriverById($driverId);
        
        if (!$driver) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/driver-listings.php';
    }

    // Dashboard αγγελιών του συνδεδεμένου χρήστη
    public function myListings() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Λήψη των αγγελιών του χρήστη ανάλογα με τον ρόλο του
        if ($_SESSION['role'] === 'company') {
            $listings = $this->jobListingModel->getCompanyListings($_SESSION['user_id'], null, $page, $limit);
        } else {
            $listings = $this->jobListingModel->getDriverListings($_SESSION['user_id'], null, $page, $limit);
        }
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/my-listings.php';
    }
}