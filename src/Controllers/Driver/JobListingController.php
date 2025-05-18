<?php

namespace Drivejob\Controllers\Driver;

use Drivejob\Controllers\BaseController;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\DriversRepository;

/**
 * Controller για τις αγγελίες εργασίας από οδηγούς
 */
class JobListingController extends BaseController
{
    /**
     * @var JobListingRepository Το repository για τις αγγελίες εργασίας
     */
    private $jobListingRepository;

    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct();

        // Αρχικοποίηση των repositories
        $this->jobListingRepository = new JobListingRepository($this->pdo);
        $this->driversRepository = new DriversRepository($this->pdo);
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας για οδηγούς
     */
    public function create()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');
        $driverData = $this->driversRepository->find($driverId);

        if (!$driverData) {
            Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/Driver/create.php';
    }

    /**
     * Αποθηκεύει μια νέα αγγελία από οδηγό
     */
    public function store()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in driver job listing store');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/Driver/create');
            exit();
        }

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
        $data['listing_type'] = 'job_search'; // Τύπος αγγελίας: αναζήτηση εργασίας

        // Προσθήκη του ID του οδηγού
        $driverId = Session::get('user_id');
        $data['driver_id'] = $driverId;
        $data['company_id'] = null;

        Logger::info('Starting driver job listing creation', ['driver_id' => $driverId]);

        try {
            // Δημιουργία της αγγελίας
            $listingId = $this->jobListingRepository->create($data);

            if ($listingId) {
                // Αποθήκευση των τύπων οχημάτων στα μεταδεδομένα της αγγελίας
                if (isset($_POST['vehicle_types']) && is_array($_POST['vehicle_types'])) {
                    $vehicleTypes = implode(',', $_POST['vehicle_types']);
                    $this->jobListingRepository->update($listingId, ['vehicle_types' => $vehicleTypes]);
                }

                Logger::info('Driver job listing creation successful', ['listing_id' => $listingId]);
                Session::set('success_message', 'Η αγγελία δημιουργήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit();
            } else {
                Logger::error('Driver job listing creation failed', [
                    'driver_id' => $driverId,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη δημιουργία της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/Driver/create');
                exit();
            }
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing store', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/Driver/create');
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
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
            exit;
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if ($listing['driver_id'] != Session::get('user_id')) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit;
            }

            // Επεξεργασία των τύπων οχημάτων της αγγελίας
            if (isset($listing['vehicle_types'])) {
                $listing['vehicle_types'] = explode(',', $listing['vehicle_types']);
            } else {
                $listing['vehicle_types'] = [];
            }

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/edit-driver.php';
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing edit', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
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
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in driver job listing update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
            exit();
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if ($listing['driver_id'] != Session::get('user_id')) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit;
            }

            // Επικύρωση βασικών δεδομένων
            $validator = new Validator($_POST);
            $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
                ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
                ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
                ->required('job_type', 'Ο τύπος εργασίας είναι υποχρεωτικός.');

            if (!$validator->isValid()) {
                Logger::error('Validation failed in driver job listing update', [
                    'id' => $id,
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

            Logger::info('Collected form data for driver job listing update', [
                'id' => $id,
                'data_keys' => array_keys($data)
            ]);

            // Ενημέρωση της αγγελίας
            $updateResult = $this->jobListingRepository->update($id, $data);

            if ($updateResult) {
                // Ενημέρωση των τύπων οχημάτων
                if (isset($_POST['vehicle_types']) && is_array($_POST['vehicle_types'])) {
                    $vehicleTypes = implode(',', $_POST['vehicle_types']);
                    $this->jobListingRepository->update($id, ['vehicle_types' => $vehicleTypes]);
                } else {
                    $this->jobListingRepository->update($id, ['vehicle_types' => null]);
                }

                Logger::info('Driver job listing update successful', ['id' => $id]);
                Session::set('success_message', 'Η αγγελία ενημερώθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit();
            } else {
                Logger::error('Driver job listing update failed', [
                    'id' => $id,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
                exit();
            }
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing update', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit-driver/' . $id);
            exit();
        }
    }

    /**
     * Διαγράφει μια αγγελία οδηγού
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function delete($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in driver job listing delete');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
            exit();
        }

        try {
            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
            if ($listing['driver_id'] != Session::get('user_id')) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
                exit;
            }

            // Διαγραφή της αγγελίας
            $deleteResult = $this->jobListingRepository->delete($id);

            if ($deleteResult) {
                Logger::info('Driver job listing delete successful', ['id' => $id]);
                Session::set('success_message', 'Η αγγελία διαγράφηκε με επιτυχία.');
            } else {
                Logger::error('Driver job listing delete failed', ['id' => $id]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
            }
        } catch (\Exception $e) {
            Logger::error('Exception in driver job listing delete', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        header('Location: ' . BASE_URL . 'drivers/profile#my-listings');
        exit();
    }

    /**
     * Συλλέγει τα δεδομένα από τη φόρμα
     * 
     * @return array Τα δεδομένα της φόρμας
     */
    private function collectFormData()
    {
        $data = [
            'title' => parent::sanitize($_POST['title'] ?? ''),
            'description' => parent::sanitize($_POST['description'] ?? ''),
            'location' => parent::sanitize($_POST['location'] ?? ''),
            'job_type' => parent::sanitize($_POST['job_type'] ?? ''),
            'salary_range' => parent::sanitize($_POST['salary_range'] ?? ''),
            'availability' => parent::sanitize($_POST['availability'] ?? ''),
            'additional_info' => parent::sanitize($_POST['additional_info'] ?? '')
        ];

        return $data;
    }
}
