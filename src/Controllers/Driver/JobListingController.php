<?php

namespace Drivejob\Controllers\Driver;

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
use Drivejob\Services\JobListing\JobListingService;
use Drivejob\Services\JobListing\JobListingServiceInterface;
use Drivejob\Controllers\BaseJobListingController;

/**
 * Controller για τις αγγελίες οδηγών
 */
class JobListingController extends BaseJobListingController
{
    /**
     * @var \Drivejob\Repositories\DriverLicenseRepositoryInterface
     */
    protected $driverLicenseRepository;

    /**
     * @var \Drivejob\Repositories\DriverOperatorLicenseRepositoryInterface
     */
    protected $driverOperatorLicenseRepository;

    /**
     * @var \Drivejob\Repositories\DriverADRRepositoryInterface
     */
    protected $driverADRRepository;

    /**
     * @var \Drivejob\Repositories\DriverTachographRepositoryInterface
     */
    protected $driverTachographRepository;

    /**
     * @var FileService Η υπηρεσία για τη διαχείριση αρχείων
     */
    private $fileService;

    /**
     * @var JobListingServiceInterface Η υπηρεσία για τη διαχείριση αγγελιών
     */
    private $jobListingService;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct($pdo);

        // Αρχικοποίηση των repositories
        $container = \Drivejob\Core\Container::getInstance();
        $this->driverLicenseRepository = $container->get('DriverLicenseRepository');
        $this->driverOperatorLicenseRepository = $container->get('DriverOperatorLicenseRepository');
        $this->driverADRRepository = $container->get('DriverADRRepository');
        $this->driverTachographRepository = $container->get('DriverTachographRepository');

        // Αρχικοποίηση του FileService
        $this->fileService = new FileService();

        // Αρχικοποίηση του JobListingService
        $this->jobListingService = new JobListingService(
            $this->jobListingRepository
        );
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας για οδηγούς
     */
    public function create()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
        AuthMiddleware::hasRole('driver');

        $userId = Session::get('user_id');

        try {
            // Λήψη των στοιχείων του οδηγού
            $driverData = $this->driversRepository->find($userId);

            if (!$driverData) {
                Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
                header('Location: ' . BASE_URL . 'drivers/profile');
                exit();
            }

            // Λήψη των αδειών οδήγησης του οδηγού
            $driverLicenses = $this->driverLicenseRepository->findByDriver($userId);
            $_SESSION['driver_licenses'] = $driverLicenses;

            // Λήψη των αδειών χειριστή μηχανημάτων έργου του οδηγού
            $driverOperatorLicenses = $this->driverOperatorLicenseRepository->findByDriver($userId);
            $_SESSION['driver_operator_licenses'] = $driverOperatorLicenses;

            // Έλεγχος αν ο οδηγός έχει ΠΕΙ
            $hasPEI = false;
            foreach ($driverLicenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei']) {
                    $hasPEI = true;
                    break;
                }
            }
            $_SESSION['driver_has_pei'] = $hasPEI;

            // Έλεγχος αν ο οδηγός έχει ADR
            $hasADR = $this->driverADRRepository->findByDriver($userId) ? true : false;
            $_SESSION['driver_has_adr'] = $hasADR;

            // Έλεγχος αν ο οδηγός έχει κάρτα ταχογράφου
            $hasTachograph = $this->driverTachographRepository->findByDriver($userId) ? true : false;
            $_SESSION['driver_has_tachograph'] = $hasTachograph;

            // Φόρτωση του view για οδηγούς
            include ROOT_DIR . '/src/Views/job-listings/Driver/create.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing create', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing create', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /**
     * Αποθηκεύει μια νέα αγγελία οδηγού
     */
    public function store()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in driver job listing store');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/Driver/create');
            exit();
        }

        $userId = Session::get('user_id');

        try {
            // Επικύρωση βασικών δεδομένων
            $validator = new Validator($_POST);
            $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
                ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
                ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
                ->required('job_type', 'Ο τύπος εργασίας είναι υποχρεωτικός.');

            if (!$validator->isValid()) {
                Logger::error('Validation failed in driver job listing store', [
                    'errors' => $validator->getErrors(),
                    'post_data' => $_POST
                ]);
                Session::set('errors', $validator->getErrors());
                Session::set('old_input', $_POST);

                header('Location: ' . BASE_URL . 'job-listings/Driver/create');
                exit();
            }

            // Συλλογή των δεδομένων από τη φόρμα
            $data = $this->collectFormData();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['views_count'] = 0;
            $data['applications_count'] = 0;
            $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
            $data['is_approved'] = 1; // Αυτόματη έγκριση για τώρα

            // Αγγελία αναζήτησης εργασίας από οδηγό
            $data['driver_id'] = $userId;
            $data['company_id'] = null;
            $data['listing_type'] = 'job_search';

            // Λήψη των αδειών οδήγησης του οδηγού
            $driverLicenses = $this->driverLicenseRepository->findByDriver($userId);
            $data['driver_licenses'] = !empty($driverLicenses) ? JsonHelper::encode($driverLicenses) : null;

            // Λήψη των αδειών χειριστή μηχανημάτων έργου του οδηγού
            $operatorLicenses = $this->driverOperatorLicenseRepository->findByDriver($userId);
            $data['operator_licenses'] = !empty($operatorLicenses) ? JsonHelper::encode($operatorLicenses) : null;

            // Έλεγχος αν ο οδηγός έχει ΠΕΙ
            $hasPEI = false;
            foreach ($driverLicenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei']) {
                    $hasPEI = true;
                    break;
                }
            }
            $data['has_pei'] = $hasPEI ? 1 : 0;

            // Έλεγχος αν ο οδηγός έχει ADR
            $hasADR = $this->driverADRRepository->findByDriver($userId) ? true : false;
            $data['has_adr'] = $hasADR ? 1 : 0;

            // Έλεγχος αν ο οδηγός έχει κάρτα ταχογράφου
            $hasTachograph = $this->driverTachographRepository->findByDriver($userId) ? true : false;
            $data['has_tachograph'] = $hasTachograph ? 1 : 0;

            Logger::info('Starting driver job listing creation', ['driver_id' => $userId]);

            // Επεξεργασία των τύπων οχημάτων
            if (isset($_POST['vehicle_types']) && is_array($_POST['vehicle_types'])) {
                $data['vehicle_types'] = implode(',', $_POST['vehicle_types']);
            }

            Logger::info('Collected form data for driver job listing', ['data_keys' => array_keys($data)]);

            // Δημιουργία της αγγελίας με το service
            try {
                $listingId = $this->jobListingService->createJobListing($data);

                Logger::info('Driver job listing creation successful', ['listing_id' => $listingId]);
                Session::set('success_message', 'Η αγγελία δημιουργήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
                exit();
            } catch (ValidationException $e) {
                Logger::error('Validation exception in driver job listing creation', [
                    'user_id' => $userId,
                    'errors' => $e->getErrors()
                ]);

                Session::set('errors', $e->getErrors());
                Session::set('old_input', $_POST);
                header('Location: ' . BASE_URL . 'job-listings/Driver/create');
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing store', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
                'data' => $data ?? [],
                'post_data' => $_POST
            ]);

            // Αποθήκευση του σφάλματος στο session για αποσφαλμάτωση
            Session::set('debug_error', [
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing store', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? [],
                'post_data' => $_POST
            ]);

            // Αποθήκευση του σφάλματος στο session για αποσφαλμάτωση
            Session::set('debug_error', [
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
    public function edit($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
        AuthMiddleware::hasRole('driver');

        $userId = Session::get('user_id');

        try {
            // Ανάκτηση της αγγελίας με το service
            $listing = $this->jobListingService->findJobListing($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if (empty($listing['driver_id']) || $userId != $listing['driver_id']) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Λήψη των αδειών οδήγησης του οδηγού
            $driverLicenses = $this->driverLicenseRepository->findByDriver($userId);
            $_SESSION['driver_licenses'] = $driverLicenses;

            // Λήψη των αδειών χειριστή μηχανημάτων έργου του οδηγού
            $driverOperatorLicenses = $this->driverOperatorLicenseRepository->findByDriver($userId);
            $_SESSION['driver_operator_licenses'] = $driverOperatorLicenses;

            // Έλεγχος αν ο οδηγός έχει ΠΕΙ
            $hasPEI = false;
            foreach ($driverLicenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei']) {
                    $hasPEI = true;
                    break;
                }
            }
            $_SESSION['driver_has_pei'] = $hasPEI;

            // Έλεγχος αν ο οδηγός έχει ADR
            $hasADR = $this->driverADRRepository->findByDriver($userId) ? true : false;
            $_SESSION['driver_has_adr'] = $hasADR;

            // Έλεγχος αν ο οδηγός έχει κάρτα ταχογράφου
            $hasTachograph = $this->driverTachographRepository->findByDriver($userId) ? true : false;
            $_SESSION['driver_has_tachograph'] = $hasTachograph;

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/Driver/edit.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing edit', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing edit', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Ενημερώνει μια αγγελία οδηγού
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function update($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in driver job listing update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
            exit();
        }

        $userId = Session::get('user_id');

        try {
            // Ανάκτηση της αγγελίας με το service
            $listing = $this->jobListingService->findJobListing($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if (empty($listing['driver_id']) || $userId != $listing['driver_id']) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Επικύρωση βασικών δεδομένων
            $validator = new Validator($_POST);
            $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
                ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
                ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
                ->required('job_type', 'Ο τύπος εργασίας είναι υποχρεωτικός.');

            if (!$validator->isValid()) {
                Logger::error('Validation failed in driver job listing update', [
                    'errors' => $validator->getErrors(),
                    'post_data' => $_POST
                ]);
                Session::set('errors', $validator->getErrors());
                Session::set('old_input', $_POST);

                header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
                exit();
            }

            // Συλλογή των δεδομένων από τη φόρμα
            $data = $this->collectFormData();
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

            // Επεξεργασία των τύπων οχημάτων
            if (isset($_POST['vehicle_types']) && is_array($_POST['vehicle_types'])) {
                $data['vehicle_types'] = implode(',', $_POST['vehicle_types']);
            }

            // Ενημέρωση της αγγελίας με το service
            try {
                $success = $this->jobListingService->updateJobListing($id, $data);

                Session::set('success_message', 'Η αγγελία ενημερώθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
                exit();
            } catch (ValidationException $e) {
                Logger::error('Validation exception in driver job listing update', [
                    'id' => $id,
                    'user_id' => $userId,
                    'errors' => $e->getErrors()
                ]);

                Session::set('errors', $e->getErrors());
                Session::set('old_input', $_POST);
                header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing update', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αποθήκευση του σφάλματος στο session για αποσφαλμάτωση
            Session::set('debug_error', [
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing update', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αποθήκευση του σφάλματος στο session για αποσφαλμάτωση
            Session::set('debug_error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Εμφανίζει τη σελίδα επιβεβαίωσης διαγραφής αγγελίας οδηγού
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function delete($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
        AuthMiddleware::hasRole('driver');

        $userId = Session::get('user_id');

        try {
            // Ανάκτηση της αγγελίας με το service
            $listing = $this->jobListingService->findJobListing($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if (empty($listing['driver_id']) || $userId != $listing['driver_id']) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/Driver/delete.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing delete', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing delete', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    /**
     * Διαγράφει μια αγγελία οδηγού
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function destroy($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in driver job listing destroy');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/delete-driver/' . $id);
            exit();
        }

        $userId = Session::get('user_id');

        try {
            // Ανάκτηση της αγγελίας με το service
            $listing = $this->jobListingService->findJobListing($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if (empty($listing['driver_id']) || $userId != $listing['driver_id']) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Διαγραφή της αγγελίας με το service
            try {
                $success = $this->jobListingService->deleteJobListing($id);

                Session::set('success_message', 'Η αγγελία διαγράφηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'drivers/profile');
                exit();
            } catch (\Exception $e) {
                Logger::error('Exception in driver job listing deletion', [
                    'id' => $id,
                    'user_id' => $userId,
                    'message' => $e->getMessage()
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/delete-driver/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in driver job listing destroy', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αποθήκευση του σφάλματος στο session για αποσφαλμάτωση
            Session::set('debug_error', [
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing destroy', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αποθήκευση του σφάλματος στο session για αποσφαλμάτωση
            Session::set('debug_error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }
}
