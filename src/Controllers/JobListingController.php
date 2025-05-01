<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Validator;
use Drivejob\Core\Logger;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Session;
use Drivejob\Core\Sanitizer;
use Drivejob\Core\CSRF;

use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\JobListingModel;
use Drivejob\Models\MatchingModel;
use Drivejob\Services\DriverProfileService;

class JobListingController
{
    private $jobListingModel;
    private $matchingModel;
    
    private $licenseModel;
    private $certificationModel;
    private $skillModel;
    private $ratingModel;
    private $driverProfileService;
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->jobListingModel = new JobListingModel($pdo);
        $this->matchingModel = new MatchingModel($pdo);
        $this->profileModel = new ProfileModel($pdo);  // Αλλαγή
        $this->licenseModel = new LicenseModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
        $this->skillModel = new SkillModel($pdo);
        $this->ratingModel = new RatingModel($pdo);
        $this->driverProfileService = new DriverProfileService($pdo);
    }
    /**
     * Κεντρική μέθοδος δημιουργίας αγγελίας που ανακατευθύνει στη σωστή φόρμα ανάλογα με το ρόλο
     */
    public function create()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }

        // Ανακατεύθυνση στη σωστή φόρμα ανάλογα με το ρόλο του χρήστη
        if ($_SESSION['role'] === 'driver') {
            $this->createDriverListing();
        } else {
            $this->createCompanyListing();
        }
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας για οδηγούς
     */
    protected function createDriverListing()
    {
        // Λήψη όλων των διαθέσιμων tags
        $tags = $this->jobTagModel->getAllTags();

        // Λήψη πλήρων πληροφοριών πιστοποιήσεων
        $certificationInfo = $this->certificationModel->getFormattedDriverCertifications($_SESSION['user_id']);

        if ($certificationInfo['success']) {
            // Ανάθεση των πληροφοριών σε μεταβλητές για το template
            $driverProfile = $certificationInfo['driver'];

            // Λεπτομέρειες για άδειες οδήγησης
            $driverLicenses = $certificationInfo['licenses'];
            $driverLicenseTypes = $driverLicenses['categories'];

            // Λεπτομέρειες για ADR
            $driverAdr = $certificationInfo['adr'];
            $hasAdr = $driverAdr['has_adr'];
            $adrTypes = $driverAdr['types_text'];
            $driverAdrDetails = $driverAdr['detail'];

            // Λεπτομέρειες για άδειες χειριστή
            $driverOperator = $certificationInfo['operator'];
            $hasOperator = $driverOperator['has_operator_license'];
            $operatorSpecialities = $driverOperator['specialities_text'];
            $operatorSubSpecialities = $driverOperator['sub_specialities_text'];
            $operatorGroupedText = $driverOperator['grouped_text'];
            $driverOperatorDetails = $driverOperator['details'];

            // Λεπτομέρειες για κάρτα ταχογράφου
            $driverTachograph = $certificationInfo['tachograph'];
            $hasTachograph = $driverTachograph['has_tachograph'];
            $driverTachographDetails = $driverTachograph['detail'];

            // Λεπτομέρειες για ειδικές άδειες
            $driverSpecialLicenses = $certificationInfo['special_licenses'];
            $hasSpecialLicenses = $driverSpecialLicenses['has_special_licenses'];
            $driverSpecialLicensesDetails = $driverSpecialLicenses['details'];
        } else {
            // Λήψη των στοιχείων του οδηγού
            $driverProfile = $this->profileModel->getDriverById($_SESSION['user_id']);

            // Λήψη των αδειών οδήγησης του οδηγού
            $driverLicenses = $this->licenseModel->getDriverLicenses($_SESSION['user_id']);
            $driverLicenseTypes = [];

            if (!empty($driverLicenses)) {
                foreach ($driverLicenses as $license) {
                    if (isset($license['category']) && !empty($license['category'])) {
                        $driverLicenseTypes[] = $license['category'];
                    }
                }
            }
        }

        // Λήψη των δεξιοτήτων του οδηγού
        $driverSkills = $this->skillModel->getDriverSkills($_SESSION['user_id']);

        // Λήψη της βαθμολογίας του οδηγού
        $driverRating = $this->ratingModel->getDriverRating($_SESSION['user_id']);

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/create-driver.php';
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας για επιχειρήσεις
     */
    protected function createCompanyListing()
    {
        // Λήψη όλων των διαθέσιμων tags
        $tags = $this->jobTagModel->getAllTags();

        // Λήψη των στοιχείων της εταιρείας
        $companyModel = new \Drivejob\Models\CompaniesModel($this->pdo);
        $companyProfile = $companyModel->getCompanyById($_SESSION['user_id']);

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/create-company.php';
    }

    /**
     * Αποθηκεύει μια νέα αγγελία
     */
    public function store()
    {
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

        // Διαφορετική επικύρωση ανάλογα με το ρόλο
        if ($_SESSION['role'] === 'driver') {
            $this->validateDriverListing();
        } else {
            $this->validateCompanyListing();
        }

        // Συγκέντρωση κοινών δεδομένων αγγελίας
        $data = $this->collectCommonListingData();

        // Προσθήκη επιπλέον πεδίων ανάλογα με το ρόλο
        if ($_SESSION['role'] === 'driver') {
            $data = $this->collectDriverSpecificData($data);
        } else {
            $data = $this->collectCompanySpecificData($data);
        }

        // Ανάλογα με τον ρόλο του χρήστη, προσθέτουμε company_id ή driver_id
        if ($_SESSION['role'] === 'company') {
            $data['company_id'] = $_SESSION['user_id'];
            $data['driver_id'] = null;
        } else {
            $data['driver_id'] = $_SESSION['user_id'];
            $data['company_id'] = null;
        }

        try {
            // Δημιουργία της αγγελίας
            $jobListingId = $this->jobListingModel->create($data);

            // Προσθήκη tags αν έχουν επιλεγεί
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                foreach ($_POST['tags'] as $tagId) {
                    $this->jobListingModel->addTag($jobListingId, $tagId);
                }
            }

            // Αν είναι οδηγός, αποθήκευση των επιλεγμένων δεξιοτήτων που έχει τονίσει
            if ($_SESSION['role'] === 'driver' && isset($_POST['highlighted_skills']) && is_array($_POST['highlighted_skills'])) {
                $this->saveHighlightedSkills($jobListingId, $_POST['highlighted_skills']);
            }

            $_SESSION['success_message'] = 'Η αγγελία δημιουργήθηκε με επιτυχία!';

            // Ανακατεύθυνση στη σελίδα της αγγελίας
            header('Location: ' . BASE_URL . 'job-listings/show/' . $jobListingId);
            exit();
        } catch (\Exception $e) {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά τη δημιουργία της αγγελίας: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }
    }

    /**
     * Επικύρωση για αγγελία οδηγού
     */
    /**
     * Επικύρωση για αγγελία οδηγού
     */
    /**
     * Επικύρωση για αγγελία οδηγού
     */
    private function validateDriverListing()
    {
        $validator = new Validator($_POST);

        $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
            ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
            ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
            ->required('required_license', 'Η απαιτούμενη άδεια είναι υποχρεωτική.');

        if (isset($_POST['contact_email']) && $_POST['contact_email']) {
            $validator->email('contact_email', 'Το email επικοινωνίας δεν είναι έγκυρο.');
        }

        // Δημιουργία ξεχωριστής επικύρωσης για τους τύπους οχημάτων
        $hasVehicleTypeError = false;
        if (!isset($_POST['vehicle_types']) || empty($_POST['vehicle_types'])) {
            $hasVehicleTypeError = true;
        }

        // Συνδυασμός σφαλμάτων
        if (!$validator->isValid() || $hasVehicleTypeError) {
            // Λήψη των σφαλμάτων από τον Validator
            $errors = $validator->getErrors();

            // Προσθήκη του σφάλματος για τους τύπους οχημάτων αν χρειάζεται
            if ($hasVehicleTypeError) {
                $errors['vehicle_types'] = 'Πρέπει να επιλέξετε τουλάχιστον έναν τύπο οχήματος.';
            }

            // Αποθήκευση των σφαλμάτων και των δεδομένων στο session
            Session::set('errors', $errors);
            Session::set('old_input', $_POST);

            // Ανακατεύθυνση πίσω στη φόρμα
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }
    }

    /**
     * Επικύρωση για αγγελία επιχείρησης
     */
    private function validateCompanyListing()
    {
        $validator = new Validator($_POST);

        $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
            ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
            ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
            ->required('required_license', 'Η απαιτούμενη άδεια είναι υποχρεωτική.');

        // Επικύρωση τύπων οχημάτων (τουλάχιστον ένας πρέπει να επιλεγεί)
        if (!isset($_POST['vehicle_types']) || empty($_POST['vehicle_types'])) {
            $validator->addError('vehicle_types', 'Πρέπει να επιλέξετε τουλάχιστον έναν τύπο οχήματος.');
        }

        if (isset($_POST['contact_email']) && $_POST['contact_email']) {
            $validator->email('contact_email', 'Το email επικοινωνίας δεν είναι έγκυρο.');
        }

        if (!$validator->isValid()) {
            // Αποθήκευση των σφαλμάτων και των δεδομένων στο session για να τα εμφανίσουμε στη φόρμα
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);
            // Ανακατεύθυνση πίσω στη φόρμα
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }
    }

    /**
     * Συλλογή κοινών δεδομένων αγγελίας για όλους τους τύπους χρηστών
     */
    private function collectCommonListingData()
    {
        // Προετοιμασία της διαμόρφωσης του πεδίου preferred_schedule
        $preferredSchedule = isset($_POST['preferred_schedule']) && is_array($_POST['preferred_schedule'])
            ? implode(',', $_POST['preferred_schedule'])
            : null;

        // Επεξεργασία και επικύρωση δεδομένων
        return [
            'title' => trim($_POST['title']),
            'listing_type' => $_POST['listing_type'],
            'job_type' => $_POST['job_type'],
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
            'required_training' => isset($_POST['required_training']) ? trim($_POST['required_training']) : null,
            'benefits' => isset($_POST['benefits']) ? trim($_POST['benefits']) : null,
            'contact_email' => trim($_POST['contact_email']),
            'contact_phone' => trim($_POST['contact_phone']),
            'expires_at' => $_POST['expires_at'] ? $_POST['expires_at'] : null,
            'preferred_schedule' => $preferredSchedule,
            'max_days_away' => isset($_POST['max_days_away']) ? intval($_POST['max_days_away']) : null,
            'vehicle_types' => isset($_POST['vehicle_types']) ? $_POST['vehicle_types'] : []
        ];
    }

    /**
     * Συλλογή δεδομένων ειδικά για αγγελίες οδηγών
     * Διορθωμένη μέθοδος για να συμπεριλάβει όλα τα απαραίτητα πεδία
     */
    private function collectDriverSpecificData($data)
    {
        // Ειδικά πεδία για αγγελίες οδηγών
        $data['show_rating'] = isset($_POST['show_rating']) ? 1 : 0;
        $data['show_adr'] = isset($_POST['show_adr']) ? 1 : 0;
        $data['show_operator_license'] = isset($_POST['show_operator_license']) ? 1 : 0;
        $data['show_tachograph'] = isset($_POST['show_tachograph']) ? 1 : 0;
        $data['show_skills'] = isset($_POST['show_skills']) ? 1 : 0;
        $data['show_experience'] = isset($_POST['show_experience']) ? 1 : 0;

        // Προσθήκη του πεδίου για εξειδικευμένη εμπειρία αν έχει συμπληρωθεί
        if (isset($_POST['specialized_experience']) && !empty($_POST['specialized_experience'])) {
            $data['specialized_experience'] = trim($_POST['specialized_experience']);
        }

        return $data;
    }

    /**
     * Συλλογή δεδομένων ειδικά για αγγελίες επιχειρήσεων
     */
    private function collectCompanySpecificData($data)
    {
        // Εδώ μπορούμε να προσθέσουμε ειδικά πεδία για τις αγγελίες επιχειρήσεων
        // πχ. παροχές εταιρείας, ειδικές απαιτήσεις κλπ.
        return $data;
    }

    /**
     * Αποθήκευση των δεξιοτήτων που έχει τονίσει ο οδηγός στην αγγελία
     */
    private function saveHighlightedSkills($jobListingId, $highlightedSkills)
    {
        // Υλοποίηση αποθήκευσης των δεξιοτήτων
        // Θα χρειαστεί ένας νέος πίνακας job_listing_highlighted_skills
        // και μια νέα μέθοδο στο μοντέλο που θα αποθηκεύει αυτές τις δεξιότητες
    }

    /**
     * Εμφανίζει την σελίδα επεξεργασίας αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     */
    public function edit($id)
    {
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
        if (
            ($_SESSION['role'] === 'company' && $listing['company_id'] != $_SESSION['user_id']) ||
            ($_SESSION['role'] === 'driver' && $listing['driver_id'] != $_SESSION['user_id'])
        ) {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Λήψη των τύπων οχημάτων της αγγελίας
        $listing['vehicle_types'] = $this->jobListingModel->getVehicleTypes($id);

        // Λήψη των tags της αγγελίας
        $listingTags = $this->jobListingModel->getTagsByJobId($id);
        $selectedTagIds = array_column($listingTags, 'id');

        // Λήψη όλων των διαθέσιμων tags
        $allTags = $this->jobTagModel->getAllTags();

        // Ανακατεύθυνση στη σωστή φόρμα επεξεργασίας ανάλογα με τον τύπο της αγγελίας
        if ($_SESSION['role'] === 'driver') {
            // Λήψη όλων των πληροφοριών πιστοποιήσεων
            $certificationInfo = $this->certificationModel->getFormattedDriverCertifications($_SESSION['user_id']);

            if ($certificationInfo['success']) {
                // Ανάθεση των πληροφοριών σε μεταβλητές για το template
                $driverProfile = $certificationInfo['driver'];

                // Λεπτομέρειες για άδειες οδήγησης
                $driverLicenses = $certificationInfo['licenses'];
                $driverLicenseTypes = $driverLicenses['categories'];

                // Λεπτομέρειες για ADR
                $driverAdr = $certificationInfo['adr'];
                $hasAdr = $driverAdr['has_adr'];
                $adrTypes = $driverAdr['types_text'];
                $driverAdrDetails = $driverAdr['detail'];

                // Λεπτομέρειες για άδειες χειριστή
                $driverOperator = $certificationInfo['operator'];
                $hasOperator = $driverOperator['has_operator_license'];
                $operatorSpecialities = $driverOperator['specialities_text'];
                $operatorSubSpecialities = $driverOperator['sub_specialities_text'];
                $operatorGroupedText = $driverOperator['grouped_text'];
                $driverOperatorDetails = $driverOperator['details'];

                // Λεπτομέρειες για κάρτα ταχογράφου
                $driverTachograph = $certificationInfo['tachograph'];
                $hasTachograph = $driverTachograph['has_tachograph'];
                $driverTachographDetails = $driverTachograph['detail'];

                // Λεπτομέρειες για ειδικές άδειες
                $driverSpecialLicenses = $certificationInfo['special_licenses'];
                $hasSpecialLicenses = $driverSpecialLicenses['has_special_licenses'];
                $driverSpecialLicensesDetails = $driverSpecialLicenses['details'];
            } else {
                // Απλή ανάκτηση του προφίλ οδηγού
                $driverProfile = $this->profileModel->getDriverById($_SESSION['user_id']);
                $driverLicenses = $this->licenseModel->getDriverLicenses($_SESSION['user_id']);
                $driverLicenseTypes = [];

                if (!empty($driverLicenses)) {
                    foreach ($driverLicenses as $license) {
                        if (isset($license['category']) && !empty($license['category'])) {
                            $driverLicenseTypes[] = $license['category'];
                        }
                    }
                }
            }

            // Λήψη των δεξιοτήτων του οδηγού
            $driverSkills = $this->skillModel->getDriverSkills($_SESSION['user_id']);

            // Μετατροπή του πεδίου preferred_schedule σε πίνακα
            if (isset($listing['preferred_schedule']) && !empty($listing['preferred_schedule'])) {
                $listing['preferred_schedule'] = explode(',', $listing['preferred_schedule']);
            } else {
                $listing['preferred_schedule'] = [];
            }

            // Φόρτωση της φόρμας επεξεργασίας για οδηγούς
            include ROOT_DIR . '/src/Views/job-listings/edit-driver.php';
        } else {
            // Μετατροπή του πεδίου preferred_schedule σε πίνακα αν χρειάζεται
            if (isset($listing['preferred_schedule']) && !empty($listing['preferred_schedule'])) {
                $listing['preferred_schedule'] = explode(',', $listing['preferred_schedule']);
            } else {
                $listing['preferred_schedule'] = [];
            }

            // Φόρτωση της φόρμας επεξεργασίας για επιχειρήσεις
            include ROOT_DIR . '/src/Views/job-listings/edit.php'; // Προσωρινά χρησιμοποιούμε την υπάρχουσα φόρμα
        }
    }
    /**
     * Εμφανίζει μια αγγελία με πλήρεις πληροφορίες
     *
     * @param int $id Το ID της αγγελίας
     */
    public function show($id)
    {
        try {
            Debug::log("JobListingController::show - Έναρξη με ID: $id");

            // Έλεγχος αν το ID είναι έγκυρο
            if (!$id || !is_numeric($id)) {
                Debug::log("ID μη έγκυρο: $id");
                $_SESSION['error_message'] = 'Μη έγκυρο αναγνωριστικό αγγελίας';
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Ανάκτηση της αγγελίας με όλες τις λεπτομέρειες
            Debug::log("Προσπάθεια ανάκτησης αγγελίας με ID: $id");
            $listing = $this->jobListingModel->getById($id);

            // Προσθήκη των τύπων οχημάτων στην αγγελία
            if ($listing) {
                $listing['vehicle_types'] = $this->jobListingModel->getVehicleTypes($id);
            }

            Debug::log("Ανάκτηση αγγελίας ολοκληρώθηκε", $listing);

            // Αν δεν βρέθηκε η αγγελία
            if (!$listing) {
                Debug::log("Η αγγελία δεν βρέθηκε: $id");
                $_SESSION['error_message'] = 'Η αγγελία δεν βρέθηκε';
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Αύξηση του μετρητή προβολών
            $this->jobListingModel->incrementViewsCount($id);

            // Ανάκτηση tags της αγγελίας
            $tags = $this->jobTagModel->getTagsByJobId($id);

            // Ανάλογα με τον τύπο της αγγελίας, φορτώνουμε διαφορετικό template
            if ($listing['listing_type'] === 'job_search' && isset($listing['driver_id']) && $listing['driver_id']) {
                // Αγγελία αναζήτησης εργασίας από οδηγό
                Debug::log("Φόρτωση προβολής αγγελίας οδηγού");

                // Ανάκτηση πλήρων πληροφοριών οδηγού με πιστοποιήσεις
                $certificationInfo = $this->certificationModel->getFormattedDriverCertifications($listing['driver_id']);

                if ($certificationInfo['success']) {
                    // Ανάθεση των πληροφοριών σε μεταβλητές για το template
                    $driver = $certificationInfo['driver'];

                    // Λεπτομέρειες για άδειες οδήγησης
                    $driverLicenses = $certificationInfo['licenses'];
                    $driverLicenseTypes = $driverLicenses['categories'];

                    // Λεπτομέρειες για ADR
                    $driverAdr = $certificationInfo['adr'];
                    $hasAdr = $driverAdr['has_adr'];
                    $adrTypes = $driverAdr['types_text'];

                    // Λεπτομέρειες για άδειες χειριστή
                    $driverOperator = $certificationInfo['operator'];
                    $hasOperator = $driverOperator['has_operator_license'];
                    $operatorSpecialities = $driverOperator['specialities_text'];
                    $operatorSubSpecialities = $driverOperator['sub_specialities_text'];
                    $operatorGroupedText = $driverOperator['grouped_text'];

                    // Λεπτομέρειες για κάρτα ταχογράφου
                    $driverTachograph = $certificationInfo['tachograph'];
                    $hasTachograph = $driverTachograph['has_tachograph'];

                    // Λεπτομέρειες για ειδικές άδειες
                    $driverSpecialLicenses = $certificationInfo['special_licenses'];
                    $hasSpecialLicenses = $driverSpecialLicenses['has_special_licenses'];

                    // Επιπλέον πληροφορίες από το μοντέλο
                    $driverSkills = $this->skillModel->getDriverSkills($listing['driver_id']);
                    $driverReviews = $this->ratingModel->getDriverReviews($listing['driver_id']);
                    $averageRating = $this->ratingModel->getDriverRating($listing['driver_id']);
                } else {
                    // Απλή ανάκτηση του οδηγού χωρίς τις λεπτομέρειες
                    $driver = $this->profileModel->getDriverById($listing['driver_id']);
                    $driverLicenseTypes = [];
                    $hasAdr = false;
                    $hasOperator = false;
                    $hasTachograph = false;
                    $hasSpecialLicenses = false;
                    $driverSkills = [];
                    $driverReviews = [];
                    $averageRating = 0;
                }

                // Παρόμοιες αγγελίες
                $similarListings = []; // Υλοποιήστε τη λογική για παρόμοιες αγγελίες αν χρειάζεται

                // Φόρτωση του template
                Debug::log("Φόρτωση του αρχείου προβολής: show-driver.php");
                include ROOT_DIR . '/src/Views/job-listings/show-driver.php';
            } else {
                // Αγγελία προσφοράς εργασίας από εταιρεία
                Debug::log("Φόρτωση προβολής αγγελίας εταιρείας");

                // Λήψη πληροφοριών εταιρείας
                $company = null;
                if (isset($listing['company_id']) && $listing['company_id']) {
                    $companyModel = new \Drivejob\Models\CompaniesModel($this->pdo);
                    $company = $companyModel->getCompanyById($listing['company_id']);
                }

                // Παρόμοιες αγγελίες
                $similarListings = []; // Υλοποιήστε τη λογική για παρόμοιες αγγελίες αν χρειάζεται

                // Φόρτωση του template
                Debug::log("Φόρτωση του αρχείου προβολής: show-company.php");
                include ROOT_DIR . '/src/Views/job-listings/show-company.php';
            }

            Debug::log("JobListingController::show - Ολοκλήρωση");
        } catch (\Exception $e) {
            Debug::log("JobListingController::show - ΣΦΑΛΜΑ: " . $e->getMessage(), $e->getTraceAsString());

            // Εμφάνιση σφάλματος για αποσφαλμάτωση (σε περιβάλλον ανάπτυξης)
            if (ini_get('display_errors')) {
                echo "<h1>Σφάλμα εφαρμογής</h1>";
                echo "<p>Προέκυψε σφάλμα στη μέθοδο show: " . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            } else {
                $_SESSION['error_message'] = 'Προέκυψε σφάλμα κατά την εμφάνιση της αγγελίας.';
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }
        }
    }
    /**
     * Ενημερώνει μια υπάρχουσα αγγελία
     *
     * @param int $id Το ID της αγγελίας
     * @return void
     */
    /**
     * Διορθωμένη μέθοδος update για το JobListingController
     * για να χειρίζεται σωστά τους τύπους οχημάτων
     */
    public function update($id)
    {
        Debug::log("JobListingController::update - Έναρξη με ID: $id");

        try {
            // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
                Debug::log("Ο χρήστης δεν είναι συνδεδεμένος");
                header('Location: ' . BASE_URL . 'login.php');
                exit();
            }

            Debug::log("Χρήστης συνδεδεμένος: ID={$_SESSION['user_id']}, Ρόλος={$_SESSION['role']}");

            // Λήψη της αγγελίας από τη βάση δεδομένων
            $listing = $this->jobListingModel->getById($id);
            Debug::log("Ανακτήθηκε η αγγελία", $listing);

            // Έλεγχος αν η αγγελία ανήκει στον τρέχοντα χρήστη
            if (
                !$listing ||
                ($_SESSION['role'] === 'company' && $listing['company_id'] != $_SESSION['user_id']) ||
                ($_SESSION['role'] === 'driver' && $listing['driver_id'] != $_SESSION['user_id'])
            ) {
                $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας.';
                Debug::log("Ο χρήστης δεν έχει δικαίωμα επεξεργασίας της αγγελίας");
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            Debug::log("Επικύρωση δικαιωμάτων επιτυχής");

            // Επικύρωση δεδομένων φόρμας
            if ($_SESSION['role'] === 'driver') {
                Debug::log("Εκτέλεση επικύρωσης για οδηγό");
                $this->validateDriverListing();
            } else {
                Debug::log("Εκτέλεση επικύρωσης για εταιρεία");
                $this->validateCompanyListing();
            }

            Debug::log("Επικύρωση δεδομένων επιτυχής");

            // Συλλογή δεδομένων φόρμας
            $data = $this->collectCommonListingData();
            Debug::log("Συλλογή κοινών δεδομένων αγγελίας", $data);

            // Αφαίρεση του vehicle_types από τα δεδομένα που θα περάσουν στο update
            $vehicleTypes = isset($_POST['vehicle_types']) ? $_POST['vehicle_types'] : [];
            if (isset($data['vehicle_types'])) {
                unset($data['vehicle_types']);
            }
            Debug::log("Τύποι οχημάτων αποθηκεύτηκαν ξεχωριστά", $vehicleTypes);

            // Προσθήκη του id
            $data['id'] = $id;

            // Προσθήκη επιπλέον πεδίων ανάλογα με το ρόλο
            if ($_SESSION['role'] === 'driver') {
                $data = $this->collectDriverSpecificData($data);
                Debug::log("Προστέθηκαν δεδομένα ειδικά για οδηγό", $data);
            } else {
                $data = $this->collectCompanySpecificData($data);
                Debug::log("Προστέθηκαν δεδομένα ειδικά για εταιρεία", $data);
            }

            // Ενημέρωση της κατάστασης is_active
            $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

            // Μετατροπή του preferred_schedule σε string αν είναι array
            if (isset($data['preferred_schedule']) && is_array($data['preferred_schedule'])) {
                $data['preferred_schedule'] = implode(',', $data['preferred_schedule']);
                Debug::log("Μετατροπή preferred_schedule σε string", $data['preferred_schedule']);
            }

            try {
                Debug::log("Εκκίνηση διαδικασίας ενημέρωσης");

                // Ενημέρωση της αγγελίας
                Debug::log("Προσπάθεια ενημέρωσης αγγελίας", $data);
                $this->jobListingModel->update($id, $data);
                Debug::log("Επιτυχής ενημέρωση αγγελίας");

                // Ενημέρωση των τύπων οχημάτων
                Debug::log("Προσπάθεια ενημέρωσης τύπων οχημάτων", $vehicleTypes);
                $this->jobListingModel->updateVehicleTypes($id, $vehicleTypes);
                Debug::log("Επιτυχής ενημέρωση τύπων οχημάτων");

                // Διαχείριση των tags
                if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                    Debug::log("Προσπάθεια ενημέρωσης tags", $_POST['tags']);
                    // Διαγραφή όλων των υπαρχόντων tags
                    $this->jobTagModel->deleteAllTagsForJob($id);

                    // Προσθήκη των νέων tags
                    foreach ($_POST['tags'] as $tagId) {
                        $this->jobListingModel->addTag($id, $tagId);
                    }
                    Debug::log("Επιτυχής ενημέρωση tags");
                }

                $_SESSION['success_message'] = 'Η αγγελία ενημερώθηκε με επιτυχία!';
                Debug::log("Ολοκλήρωση διαδικασίας ενημέρωσης - Ανακατεύθυνση στη σελίδα προβολής");

                // Ανακατεύθυνση στη σελίδα της αγγελίας
                header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
                exit();
            } catch (\Exception $e) {
                Debug::log("ΣΦΑΛΜΑ κατά την ενημέρωση: " . $e->getMessage(), $e->getTraceAsString());
                $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της αγγελίας: ' . $e->getMessage();

                // Αποθήκευση των δεδομένων εισόδου για να τα εμφανίσουμε ξανά στη φόρμα
                Session::set('old_input', $_POST);

                header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
                exit();
            }
        } catch (\Exception $e) {
            Debug::log("JobListingController::update - Γενικό ΣΦΑΛΜΑ: " . $e->getMessage(), $e->getTraceAsString());
            throw $e;
        }
    }
    /**
     * Εμφανίζει τη λίστα των αγγελιών
     */
    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // Αριθμός αγγελιών ανά σελίδα

        // Φιλτράρισμα
        $filters = [];
        if (isset($_GET['listing_type']) && in_array($_GET['listing_type'], ['job_offer', 'job_search'])) {
            $filters['listing_type'] = $_GET['listing_type'];
        }

        if (isset($_GET['job_type']) && in_array($_GET['job_type'], ['full_time', 'part_time', 'contract', 'temporary'])) {
            $filters['job_type'] = $_GET['job_type'];
        }

        if (isset($_GET['vehicle_types']) && is_array($_GET['vehicle_types'])) {
            $filters['vehicle_types'] = $_GET['vehicle_types'];
        }

        // Τοποθεσία και ακτίνα
        if (isset($_GET['latitude']) && isset($_GET['longitude']) && isset($_GET['search_radius'])) {
            $filters['latitude'] = $_GET['latitude'];
            $filters['longitude'] = $_GET['longitude'];
            $filters['search_radius'] = $_GET['search_radius'];
        }

        // Λήψη των αγγελιών με βάση τα φίλτρα
        $listings = $this->jobListingModel->getActiveListings($filters, $page, $limit);

        // Λήψη των δημοφιλών ετικετών
        $popularTags = $this->jobTagModel->getPopularTags(10);

        // Φόρτωση της προβολής
        include ROOT_DIR . '/src/Views/job-listings/index.php';
    }
    // Προσθέστε την παρακάτω μέθοδο στο αρχείο src/Controllers/JobListingsController.php
    // μέσα στην κλάση JobListingsController

    /**
     * Εμφανίζει τις αγγελίες ενός συγκεκριμένου οδηγού
     *
     * @param int $id Το ID του οδηγού
     */
    public function driverListings($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            $this->redirect('job-listings', ['error_message' => 'Μη έγκυρο αναγνωριστικό οδηγού']);
            return;
        }

        // Ανάκτηση πληροφοριών οδηγού
        $profileModel = $this->profileModel;        $driver = $profileModel->getDriverById($id);

        // Αν δεν βρέθηκε ο οδηγός
        if (!$driver) {
            $this->redirect('job-listings', ['error_message' => 'Ο οδηγός δεν βρέθηκε']);
            return;
        }

        // Ορισμός της σελίδας από το αίτημα URL ή προεπιλεγμένη τιμή 1
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        // Ανάκτηση των αγγελιών του οδηγού
        $listings = $this->jobListingModel->getDriverListings($id, true, $page, 10);

        // Απόδοση της σελίδας με τα δεδομένα
        $this->render('job-listings/driver-listings', [
            'driver' => $driver,
            'listings' => $listings
        ]);
    }
    /**
     * Επιστρέφει παρόμοιες αγγελίες με βάση διάφορα κριτήρια
     *
     * @param int $currentListingId ID της τρέχουσας αγγελίας (για εξαίρεση)
     * @param int $ownerId ID οδηγού ή εταιρείας
     * @param string $ownerType Τύπος ιδιοκτήτη ('driver' ή 'company')
     * @param int $limit Αριθμός αγγελιών προς επιστροφή
     * @return array Οι παρόμοιες αγγελίες
     */
    private function getSimilarListings($currentListingId, $ownerId = null, $ownerType = null, $limit = 3)
    {
        // Ανάκτηση της τρέχουσας αγγελίας
        $currentListing = $this->jobListingModel->getById($currentListingId);
        if (!$currentListing) {
            return [];
        }

        // Βασικές συνθήκες για παρόμοιες αγγελίες
        $conditions = [
            "id != :current_listing_id",
            "is_active = 1"
        ];
        $parameters = ['current_listing_id' => $currentListingId];

        // Αν έχουμε ιδιοκτήτη, βρίσκουμε άλλες αγγελίες του ίδιου ιδιοκτήτη
        if ($ownerId && $ownerType) {
            if ($ownerType === 'driver') {
                $conditions[] = "driver_id = :owner_id";
            } elseif ($ownerType === 'company') {
                $conditions[] = "company_id = :owner_id";
            }
            $parameters['owner_id'] = $ownerId;
        }

        // Ίδιος τύπος αγγελίας
        $conditions[] = "listing_type = :listing_type";
        $parameters['listing_type'] = $currentListing['listing_type'];

        // Ίδιος τύπος εργασίας
        if (!empty($currentListing['job_type'])) {
            $conditions[] = "job_type = :job_type";
            $parameters['job_type'] = $currentListing['job_type'];
        }

        // Αν δεν έχουμε αρκετές αγγελίες από τον ίδιο ιδιοκτήτη, ψάχνουμε και με βάση την τοποθεσία
        $sameOwnerListings = $this->jobListingModel->getListingsByCustomConditions($conditions, $parameters, 'created_at DESC', $limit);

        // Αν έχουμε αρκετές αγγελίες από τον ίδιο ιδιοκτήτη, τις επιστρέφουμε
        if (count($sameOwnerListings) >= $limit) {
            return $sameOwnerListings;
        }

        // Αλλιώς, αναζητούμε και με βάση την τοποθεσία ή άλλα κριτήρια
        // Απομακρύνουμε την προϋπόθεση του ίδιου ιδιοκτήτη
        $locationConditions = [
            "id != :current_listing_id",
            "is_active = 1",
            "listing_type = :listing_type"
        ];
        $locationParameters = [
            'current_listing_id' => $currentListingId,
            'listing_type' => $currentListing['listing_type']
        ];

        // Προσθέτουμε μια συνθήκη για να μην συμπεριλάβουμε αγγελίες που έχουμε ήδη βρει
        if (!empty($sameOwnerListings)) {
            $existingIds = array_column($sameOwnerListings, 'id');
            $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
            $locationConditions[] = "id NOT IN ($placeholders)";
            $locationParameters = array_merge($locationParameters, $existingIds);
        }

        // Αν έχουμε γεωγραφικές συντεταγμένες, βρίσκουμε αγγελίες στην ίδια περιοχή
        if (!empty($currentListing['latitude']) && !empty($currentListing['longitude'])) {
            $locationConditions[] = "(
            6371 * acos(
                cos(radians(:latitude)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(:longitude)) + 
                sin(radians(:latitude)) * 
                sin(radians(latitude))
            )
        ) <= 50"; // Ακτίνα 50 χλμ
            $locationParameters['latitude'] = $currentListing['latitude'];
            $locationParameters['longitude'] = $currentListing['longitude'];
        } elseif (!empty($currentListing['location'])) {
            // Αν δεν έχουμε συντεταγμένες, ψάχνουμε με βάση το κείμενο της τοποθεσίας
            $locationConditions[] = "location LIKE :location";
            $locationParameters['location'] = "%" . $currentListing['location'] . "%";
        }

        // Ανάκτηση αγγελιών με βάση την τοποθεσία
        $locationListings = $this->jobListingModel->getListingsByCustomConditions(
            $locationConditions,
            $locationParameters,
            'created_at DESC',
            $limit - count($sameOwnerListings)
        );

        // Συνδυασμός των αποτελεσμάτων
        return array_merge($sameOwnerListings, $locationListings);
    }

    /**
     * Βοηθητική συνάρτηση για το JobListingModel
     * Ανάκτηση αγγελιών με βάση προσαρμοσμένες συνθήκες
     *
     * Να προστεθεί στο μοντέλο JobListingModel:
     */
    public function getListingsByCustomConditions($conditions, $parameters, $orderBy = 'created_at DESC', $limit = 10)
    {
        // Μετατροπή των συνθηκών σε SQL
        $whereClause = implode(" AND ", $conditions);

        // Σύνθεση του SQL ερωτήματος
        $sql = "SELECT * FROM job_listings WHERE $whereClause ORDER BY $orderBy LIMIT :limit";

        // Δέσμευση των παραμέτρων
        $stmt = $this->pdo->prepare($sql);

        // Εγγραφή των παραμέτρων
        foreach ($parameters as $key => $value) {
            // Ελέγχουμε αν το κλειδί είναι αριθμητικό (για τα placeholders του IN)
            if (is_int($key)) {
                $stmt->bindValue($key + 1, $value, \PDO::PARAM_INT);
            } elseif ($key === 'limit') {
                $stmt->bindValue(':' . $key, $limit, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        // Αν δεν έχει οριστεί το 'limit' στις παραμέτρους
        if (!isset($parameters['limit'])) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Προσθέτει έναν τύπο οχήματος σε μια αγγελία
     *
     * @param int $jobListingId ID της αγγελίας
     * @param string $vehicleType Τύπος οχήματος
     * @return bool Επιτυχία/αποτυχία
     */
    public function addVehicleType($jobListingId, $vehicleType)
    {
        $sql = "INSERT INTO job_listing_vehicle_types (job_listing_id, vehicle_type) VALUES (:job_listing_id, :vehicle_type)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'job_listing_id' => $jobListingId,
            'vehicle_type' => $vehicleType
        ]);
    }
    /**
     * Εμφανίζει την σελίδα επιβεβαίωσης διαγραφής αγγελίας
     *
     * @param int $id Το ID της αγγελίας προς διαγραφή
     */
    public function delete($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }

        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);

        if (!$listing) {
            $_SESSION['error_message'] = 'Η αγγελία δεν βρέθηκε';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
        if (
            ($_SESSION['role'] === 'company' && $listing['company_id'] != $_SESSION['user_id']) ||
            ($_SESSION['role'] === 'driver' && $listing['driver_id'] != $_SESSION['user_id'])
        ) {
            $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Φόρτωση της σελίδας επιβεβαίωσης διαγραφής
        include ROOT_DIR . '/src/Views/job-listings/delete.php';
    }

    /**
     * Διαγράφει μια αγγελία (ενέργεια μετά την επιβεβαίωση)
     * Γενική μέθοδος που ελέγχει τον τύπο χρήστη και ανακατευθύνει στην κατάλληλη μέθοδο
     *
     * @param int $id Το ID της αγγελίας προς διαγραφή
     */
    public function destroy($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }

        // Έλεγχος αν η μέθοδος είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Έλεγχος CSRF token
        if (isset($_POST['csrf_token']) && !\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο CSRF token. Προσπαθήστε ξανά.';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Ανακατεύθυνση στη σωστή μέθοδο ανάλογα με το ρόλο
        if ($_SESSION['role'] === 'driver') {
            $this->destroyDriverListing($id);
        } elseif ($_SESSION['role'] === 'company') {
            $this->destroyCompanyListing($id);
        } else {
            $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα διαγραφής αγγελιών.';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Διαγράφει μια αγγελία οδηγού
     *
     * @param int $id Το ID της αγγελίας προς διαγραφή
     */
    protected function destroyDriverListing($id)
    {
        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);

        if (!$listing) {
            $_SESSION['error_message'] = 'Η αγγελία δεν βρέθηκε';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Έλεγχος αν ο οδηγός είναι ο ιδιοκτήτης της αγγελίας
        if ($listing['driver_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        try {
            // Διαγραφή των tags της αγγελίας
            $this->jobTagModel->deleteAllTagsForJob($id);

            // Διαγραφή των vehicle_types της αγγελίας
            $this->jobListingModel->deleteVehicleTypes($id);

            // Διαγραφή της αγγελίας
            $this->jobListingModel->delete($id);

            $_SESSION['success_message'] = 'Η αγγελία διαγράφηκε με επιτυχία';

            // Ανακατεύθυνση σε μια υπάρχουσα σελίδα
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Διαγράφει μια αγγελία εταιρείας
     *
     * @param int $id Το ID της αγγελίας προς διαγραφή
     */
    protected function destroyCompanyListing($id)
    {
        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($id);

        if (!$listing) {
            $_SESSION['error_message'] = 'Η αγγελία δεν βρέθηκε';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Έλεγχος αν η εταιρεία είναι ο ιδιοκτήτης της αγγελίας
        if ($listing['company_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        try {
            // Διαγραφή των tags της αγγελίας
            $this->jobTagModel->deleteAllTagsForJob($id);

            // Διαγραφή των vehicle_types της αγγελίας
            $this->jobListingModel->deleteVehicleTypes($id);

            // Διαγραφή τυχόν αιτήσεων για την αγγελία
            // $this->jobApplicationModel->deleteApplicationsByJobId($id);  // Αν υπάρχει αυτή η μέθοδος

            // Διαγραφή της αγγελίας
            $this->jobListingModel->delete($id);

            $_SESSION['success_message'] = 'Η αγγελία διαγράφηκε με επιτυχία';

            // Ανακατεύθυνση σε μια υπάρχουσα σελίδα
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }
}
