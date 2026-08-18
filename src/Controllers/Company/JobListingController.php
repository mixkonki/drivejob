<?php

namespace Drivejob\Controllers\Company;

use Drivejob\Controllers\BaseJobListingController;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Helpers\JsonHelper;
use Drivejob\Services\FileService;

/**
 * Controller για τις αγγελίες εργασίας
 * 
 * Επεκτείνει τον BaseJobListingController για κοινές λειτουργίες
 */
class JobListingController extends BaseJobListingController
{
    /**
     * @var FileService Η υπηρεσία για τη διαχείριση αρχείων
     */
    private $fileService;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct($pdo);

        // Αρχικοποίηση του FileService
        $this->fileService = new FileService();
    }

    /**
     * Εμφανίζει τη λίστα αγγελιών
     */
    public function index()
    {
        // Συλλογή των κριτηρίων αναζήτησης
        $criteria = [
            'title' => $_GET['title'] ?? null,
            'location' => $_GET['location'] ?? null,
            'job_type' => $_GET['job_type'] ?? null,
            'vehicle_type' => $_GET['vehicle_type'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'created_at',
            'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
        ];

        // Λήψη της τρέχουσας σελίδας και του ορίου
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        try {
            // Αναζήτηση αγγελιών με το repository
            $result = $this->jobListingRepository->searchListings($criteria, $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $listings = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-listings/index.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listings', [
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listings', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Εμφανίζει μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function show($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν η αγγελία είναι ενεργή και εγκεκριμένη
            $isActive = isset($listing['is_active']) ? $listing['is_active'] : false;
            $isApproved = isset($listing['is_approved']) ? $listing['is_approved'] : true;

            if (!$isActive || !$isApproved) {
                // Αν ο χρήστης είναι η εταιρεία που δημιούργησε την αγγελία, επιτρέπουμε την προβολή
                $isOwner = Session::has('user_id') && Session::get('user_role') === 'company' && Session::get('user_id') == $listing['company_id'];

                if (!$isOwner) {
                    Session::set('error_message', 'Η αγγελία δεν είναι διαθέσιμη');
                    header('Location: ' . BASE_URL . 'job-listings');
                    exit;
                }
            }

            // Ανάκτηση της εταιρείας ή του οδηγού ανάλογα με τον τύπο της αγγελίας
            $company = null;
            $driver = null;
            $author = null;

            if (!empty($listing['company_id'])) {
                $company = $this->companiesRepository->find($listing['company_id']);
                $author = $company;
            } elseif (!empty($listing['driver_id'])) {
                $driver = $this->driversRepository->find($listing['driver_id']);
                $author = $driver;
            }

            // Αύξηση των προβολών (αγνοούμε το αποτέλεσμα)
            try {
                $this->jobListingRepository->incrementViews($id);
            } catch (\Exception $e) {
                // Αγνοούμε τυχόν σφάλματα κατά την αύξηση των προβολών
                Logger::error('Error incrementing views', [
                    'id' => $id,
                    'message' => $e->getMessage()
                ]);
            }

            // Ανάκτηση των τύπων οχημάτων
            $vehicleTypes = [];
            if (isset($listing['vehicle_type']) && !empty($listing['vehicle_type'])) {
                $vehicleTypes = [$listing['vehicle_type']];
            }

            // Ανάκτηση των tags
            $tags = [];

            // Έλεγχος αν ο χρήστης έχει ήδη υποβάλει αίτηση (για αγγελίες εταιρειών)
            $hasApplied = false;
            if (Session::has('user_id') && Session::get('user_role') === 'driver' && !empty($listing['company_id'])) {
                // Εδώ θα μπορούσαμε να ελέγξουμε αν ο οδηγός έχει ήδη υποβάλει αίτηση
                // αλλά αυτό θα απαιτούσε πρόσβαση στο JobApplicationRepository
            }

            // Παρόμοιες αγγελίες
            $similarListings = ['results' => []];

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/show.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing show', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing show', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας για εταιρείες
     */
    public function create()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/Company/create.php';
    }


    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας για οδηγούς
     */
    public function createDriverListing()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');
        $driverProfile = $this->driversRepository->find($driverId);

        if (!$driverProfile) {
            Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }

        // Λήψη των δεδομένων του οδηγού για τη φόρμα
        $driverLicenseTypes = [];
        $driverOperatorSubSpecialities = [];

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/Driver/create.php';
    }

    /**
     * Αποθηκεύει μια νέα αγγελία
     */
    public function store()
    {
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing store');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }

        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
            ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
            ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
            ->required('job_type', 'Ο τύπος εργασίας είναι υποχρεωτικός.');

        if (!$validator->isValid()) {
            Logger::error('Validation failed in job listing store', [
                'errors' => $validator->getErrors(),
                'post_data' => $_POST
            ]);
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);

            // Ανακατεύθυνση ανάλογα με τον τύπο της αγγελίας
            if (isset($_POST['listing_type']) && $_POST['listing_type'] === 'job_search') {
                header('Location: ' . BASE_URL . 'job-listings/create');
            } else {
                header('Location: ' . BASE_URL . 'job-listings/create');
            }
            exit();
        }

        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['views'] = 0;
        $data['applications'] = 0;
        $data['is_active'] = 1;
        $data['is_approved'] = 1; // Αυτόματη έγκριση για τώρα

        // Ανάλογα με τον τύπο της αγγελίας, προσθέτουμε τα κατάλληλα πεδία
        if (isset($_POST['listing_type']) && $_POST['listing_type'] === 'job_search') {
            // Αγγελία αναζήτησης εργασίας από οδηγό
            $driverId = Session::get('user_id');
            $data['driver_id'] = $driverId;
            $data['company_id'] = null;

            Logger::info('Starting driver job listing creation', ['driver_id' => $driverId]);
        } else {
            // Αγγελία προσφοράς εργασίας από εταιρεία
            $companyId = Session::get('user_id');
            $data['company_id'] = $companyId;
            $data['driver_id'] = null;

            Logger::info('Starting company job listing creation', ['company_id' => $companyId]);
        }

        Logger::info('Collected form data for job listing', ['data_keys' => array_keys($data)]);

        try {
            // Δημιουργία της αγγελίας με το repository
            $listingId = $this->jobListingRepository->create($data);

            if ($listingId) {
                Logger::info('Job listing creation successful', ['listing_id' => $listingId]);
                Session::set('success_message', 'Η αγγελία δημιουργήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
                exit();
            } else {
                Logger::error('Job listing creation failed', [
                    'user_id' => Session::get('user_id'),
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη δημιουργία της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');

                // Ανακατεύθυνση ανάλογα με τον τύπο της αγγελίας
                if (isset($_POST['listing_type']) && $_POST['listing_type'] === 'job_search') {
                    header('Location: ' . BASE_URL . 'job-listings/create');
                } else {
                    header('Location: ' . BASE_URL . 'job-listings/create');
                }
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing store', [
                'user_id' => Session::get('user_id'),
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');

            // Ανακατεύθυνση ανάλογα με τον τύπο της αγγελίας
            if (isset($_POST['listing_type']) && $_POST['listing_type'] === 'job_search') {
                header('Location: ' . BASE_URL . 'job-listings/create');
            } else {
                header('Location: ' . BASE_URL . 'job-listings/create');
            }
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing store', [
                'user_id' => Session::get('user_id'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');

            // Ανακατεύθυνση στη σελίδα δημιουργίας αγγελίας
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }
    }

    /**
     * Εμφανίζει τη φόρμα επεξεργασίας αγγελίας
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function edit($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if ($listing['company_id'] != Session::get('user_id')) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/edit.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing edit', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing edit', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Εμφανίζει τη φόρμα επεξεργασίας αγγελίας οδηγού
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function editDriverListing($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if ($listing['driver_id'] != Session::get('user_id')) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Λήψη των στοιχείων του οδηγού
            $driverId = Session::get('user_id');
            $driverProfile = $this->driversRepository->find($driverId);

            // Λήψη των δεδομένων του οδηγού για τη φόρμα
            $driverLicenseTypes = [];
            $driverOperatorSubSpecialities = [];

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/Driver/edit.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing edit', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing edit', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Ενημερώνει μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function update($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
            exit();
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            $isCompanyOwner = Session::get('user_role') === 'company' && $listing['company_id'] == Session::get('user_id');
            $isDriverOwner = Session::get('user_role') === 'driver' && $listing['driver_id'] == Session::get('user_id');

            if (!$isCompanyOwner && !$isDriverOwner) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Επικύρωση βασικών δεδομένων
            $validator = new Validator($_POST);
            $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
                ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
                ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
                ->required('job_type', 'Ο τύπος εργασίας είναι υποχρεωτικός.');

            if (!$validator->isValid()) {
                Logger::error('Validation failed in job listing update', [
                    'id' => $id,
                    'errors' => $validator->getErrors(),
                    'post_data' => $_POST
                ]);
                Session::set('errors', $validator->getErrors());
                Session::set('old_input', $_POST);

                // Ανακατεύθυνση ανάλογα με τον τύπο του χρήστη
                if ($isDriverOwner) {
                    header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
                } else {
                    header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
                }
                exit();
            }

            // Συλλογή των δεδομένων από τη φόρμα
            $data = $this->collectFormData();
            $data['updated_at'] = date('Y-m-d H:i:s');

            Logger::info('Collected form data for job listing update', [
                'id' => $id,
                'data_keys' => array_keys($data)
            ]);

            // Ενημέρωση της αγγελίας με το repository
            $updateResult = $this->jobListingRepository->update($id, $data);

            if ($updateResult) {
                Logger::info('Job listing update successful', ['id' => $id]);
                Session::set('success_message', 'Η αγγελία ενημερώθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
                exit();
            } else {
                Logger::error('Job listing update failed', [
                    'id' => $id,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');

                // Ανακατεύθυνση ανάλογα με τον τύπο του χρήστη
                if ($isDriverOwner) {
                    header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
                } else {
                    header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
                }
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing update', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');

            // Ανακατεύθυνση ανάλογα με τον τύπο του χρήστη
            if (isset($isDriverOwner) && $isDriverOwner) {
                header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
            } else {
                header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
            }
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing update', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');

            // Ανακατεύθυνση ανάλογα με τον τύπο του χρήστη
            if (isset($isDriverOwner) && $isDriverOwner) {
                header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
            } else {
                header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
            }
            exit();
        }
    }

    /**
     * Διαγράφει μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function delete($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            $isCompanyOwner = Session::get('user_role') === 'company' && $listing['company_id'] == Session::get('user_id');
            $isDriverOwner = Session::get('user_role') === 'driver' && $listing['driver_id'] == Session::get('user_id');

            if (!$isCompanyOwner && !$isDriverOwner) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Φόρτωση του view επιβεβαίωσης
            include ROOT_DIR . '/src/Views/job-listings/delete.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing delete', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing delete', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Διαγράφει οριστικά μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function destroy($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing destroy');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
            exit();
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            $isCompanyOwner = Session::get('user_role') === 'company' && $listing['company_id'] == Session::get('user_id');
            $isDriverOwner = Session::get('user_role') === 'driver' && $listing['driver_id'] == Session::get('user_id');

            if (!$isCompanyOwner && !$isDriverOwner) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Διαγραφή της αγγελίας με το repository
            $deleteResult = $this->jobListingRepository->delete($id);

            if ($deleteResult) {
                Logger::info('Job listing delete successful', ['id' => $id]);
                Session::set('success_message', 'Η αγγελία διαγράφηκε με επιτυχία.');

                // Ανακατεύθυνση ανάλογα με τον τύπο του χρήστη
                if ($isDriverOwner) {
                    header('Location: ' . BASE_URL . 'drivers/profile');
                } else {
                    header('Location: ' . BASE_URL . 'companies/profile');
                }
                exit();
            } else {
                Logger::error('Job listing delete failed', ['id' => $id]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing destroy', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing destroy', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
            exit();
        }
    }

    /**
     * Εμφανίζει τις αγγελίες μιας εταιρείας
     * 
     * @param int $id Το ID της εταιρείας
     */
    public function companyListings($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό εταιρείας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        try {
            // Ανάκτηση της εταιρείας
            $company = $this->companiesRepository->find($id);

            if (!$company) {
                Session::set('error_message', 'Η εταιρεία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση αγγελιών της εταιρείας
            $result = $this->jobListingRepository->searchListings(['company_id' => $id], $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $listings = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-listings/Company/listings.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in company listings', [
                'company_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in company listings', [
                'company_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Εμφανίζει τις αγγελίες ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     */
    public function driverListings($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό οδηγού');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        try {
            // Ανάκτηση του οδηγού
            $driver = $this->driversRepository->find($id);

            if (!$driver) {
                Session::set('error_message', 'Ο οδηγός δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση αγγελιών του οδηγού
            $result = $this->jobListingRepository->searchListings(['driver_id' => $id], $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $listings = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-listings/Driver/listings.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver listings', [
                'driver_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver listings', [
                'driver_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Εμφανίζει τις αγγελίες του συνδεδεμένου χρήστη
     */
    public function myListings()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να δείτε τις αγγελίες σας.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        try {
            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση αγγελιών ανάλογα με τον τύπο του χρήστη
            $userId = Session::get('user_id');
            $userRole = Session::get('user_role');

            if ($userRole === 'company') {
                $result = $this->jobListingRepository->searchListings(['company_id' => $userId], $page, $limit);
            } else if ($userRole === 'driver') {
                $result = $this->jobListingRepository->searchListings(['driver_id' => $userId], $page, $limit);
            } else {
                Session::set('error_message', 'Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.');
                header('Location: ' . BASE_URL);
                exit();
            }

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $listings = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-listings/my-listings.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in my listings', [
                'user_id' => Session::get('user_id'),
                'user_role' => Session::get('user_role'),
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in my listings', [
                'user_id' => Session::get('user_id'),
                'user_role' => Session::get('user_role'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Προσθήκη της μεθόδου collectFormData για το sanitization
     * 
     * @return array Τα καθαρισμένα δεδομένα της φόρμας
     */
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

    protected function collectFormData()
    {
        // Βασικά δεδομένα αγγελίας
        $data = [
            'title' => parent::sanitize($_POST['title'] ?? null),
            'description' => parent::sanitizeHtml($_POST['description'] ?? null),
            'location' => parent::sanitize($_POST['location'] ?? null),
            'job_type' => parent::sanitize($_POST['job_type'] ?? null),
            'vehicle_type' => parent::sanitize($_POST['vehicle_type'] ?? null),
            'salary_min' => parent::sanitizeFloat($_POST['salary_min'] ?? null),
            'salary_max' => parent::sanitizeFloat($_POST['salary_max'] ?? null),
            'salary_period' => parent::sanitize($_POST['salary_period'] ?? null),
            'experience_years' => parent::sanitizeInt($_POST['experience_years'] ?? null),
            'required_licenses' => isset($_POST['required_licenses']) ? implode(',', $_POST['required_licenses']) : null,
            'required_skills' => isset($_POST['required_skills']) ? implode(',', $_POST['required_skills']) : null,
            'benefits' => parent::sanitizeHtml($_POST['benefits'] ?? null),
            'contact_email' => parent::sanitizeEmail($_POST['contact_email'] ?? null),
            'contact_phone' => parent::sanitize($_POST['contact_phone'] ?? null),
            'expires_at' => parent::sanitizeDate($_POST['expires_at'] ?? null),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Επεξεργασία των αρχείων που ανεβάζονται
        if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadFile($_FILES['job_image'], 'job_image', 'image');
            if ($imagePath) {
                $data['image'] = $imagePath;
            }
        }

        // Επεξεργασία άλλων αρχείων
        $fileTypes = [
            'job_attachment' => 'document',
            'job_brochure' => 'document'
        ];

        foreach ($fileTypes as $fileField => $category) {
            if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
                $filePath = $this->uploadFile($_FILES[$fileField], $fileField, $category);
                if ($filePath) {
                    $data[$fileField] = $filePath;
                }
            }
        }

        return $data;
    }
}
