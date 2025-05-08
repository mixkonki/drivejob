<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Container;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\CompaniesRepository;
use function json_encode;

/**
 * Controller για τις αγγελίες εργασίας
 * 
 * Νέα έκδοση που χρησιμοποιεί το Repository pattern
 */
class NewJobListingController
{
    /**
     * @var JobListingRepository Το repository για τις αγγελίες εργασίας
     */
    private $jobListingRepository;

    /**
     * @var CompaniesRepository Το repository για τις εταιρείες
     */
    private $companiesRepository;

    /**
     * @var Container Το container για τις εξαρτήσεις
     */
    private $container;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Λήψη του container
        $this->container = Container::getInstance();

        // Αν δεν έχει παραχθεί PDO, πάρε το από το container
        if ($pdo === null) {
            $pdo = $this->container->get('pdo');
        }

        // Αρχικοποίηση των repositories
        $this->jobListingRepository = new JobListingRepository($pdo);
        $this->companiesRepository = new CompaniesRepository($pdo);
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
                header('Content-Type: application/json');
                echo json_encode($result);
                exit();
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
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.']);
                exit();
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'home');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listings', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.']);
                exit();
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'home');
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
            if (!$listing['is_active'] || !$listing['is_approved']) {
                // Αν ο χρήστης είναι η εταιρεία που δημιούργησε την αγγελία, επιτρέπουμε την προβολή
                $isOwner = Session::has('user_id') && Session::get('user_role') === 'company' && Session::get('user_id') == $listing['company_id'];

                if (!$isOwner) {
                    Session::set('error_message', 'Η αγγελία δεν είναι διαθέσιμη');
                    header('Location: ' . BASE_URL . 'job-listings');
                    exit;
                }
            }

            // Ανάκτηση της εταιρείας
            $company = $this->companiesRepository->find($listing['company_id']);

            // Αύξηση των προβολών
            $this->jobListingRepository->incrementViews($id);

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
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας
     */
    public function create()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/create.php';
    }

    /**
     * Αποθηκεύει μια νέα αγγελία
     */
    public function store()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
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
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }

        // Λήψη ID της συνδεδεμένης εταιρείας
        $companyId = Session::get('user_id');
        Logger::info('Starting job listing creation', ['company_id' => $companyId]);

        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();
        $data['company_id'] = $companyId;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['views'] = 0;
        $data['applications'] = 0;
        $data['is_active'] = 1;
        $data['is_approved'] = 1; // Αυτόματη έγκριση για τώρα

        Logger::info('Collected form data for job listing', ['data_keys' => array_keys($data)]);

        try {
            // Δημιουργία της αγγελίας με το repository
            $listingId = $this->jobListingRepository->create($data);

            if ($listingId) {
                Logger::info('Job listing creation successful', ['listing_id' => $listingId]);
                Session::set('success_message', 'Η αγγελία δημιουργήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/' . $listingId);
                exit();
            } else {
                Logger::error('Job listing creation failed', [
                    'company_id' => $companyId,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη δημιουργία της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/create');
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing store', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing store', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
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
     * Ενημερώνει μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function update($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
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
            if ($listing['company_id'] != Session::get('user_id')) {
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
                header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
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
                header('Location: ' . BASE_URL . 'job-listings/' . $id);
                exit();
            } else {
                Logger::error('Job listing update failed', [
                    'id' => $id,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing update', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing update', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
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
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αγγελίας');
            header('Location: ' . BASE_URL . 'job-listings');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing delete');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
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
            if ($listing['company_id'] != Session::get('user_id')) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Διαγραφή της αγγελίας με το repository
            $deleteResult = $this->jobListingRepository->delete($id);

            if ($deleteResult) {
                Logger::info('Job listing delete successful', ['id' => $id]);
                Session::set('success_message', 'Η αγγελία διαγράφηκε με επιτυχία.');
            } else {
                Logger::error('Job listing delete failed', ['id' => $id]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing delete', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
        } catch (\Exception $e) {
            Logger::error('Exception in job listing delete', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        header('Location: ' . BASE_URL . 'job-listings');
        exit();
    }

    /**
     * Προσθήκη της μεθόδου collectFormData για το sanitization
     * 
     * @return array Τα καθαρισμένα δεδομένα της φόρμας
     */
    private function collectFormData()
    {
        // Συλλογή των βασικών δεδομένων
        $data = [
            'title' => $this->sanitize($_POST['title'] ?? null),
            'description' => $this->sanitizeHtml($_POST['description'] ?? null),
            'requirements' => $this->sanitizeHtml($_POST['requirements'] ?? null),
            'benefits' => $this->sanitizeHtml($_POST['benefits'] ?? null),
            'location' => $this->sanitize($_POST['location'] ?? null),
            'job_type' => $this->sanitize($_POST['job_type'] ?? null),
            'vehicle_type' => $this->sanitize($_POST['vehicle_type'] ?? null),
            'experience_years' => isset($_POST['experience_years']) ? intval($_POST['experience_years']) : null,
            'salary_min' => isset($_POST['salary_min']) ? intval($_POST['salary_min']) : null,
            'salary_max' => isset($_POST['salary_max']) ? intval($_POST['salary_max']) : null,
            'salary_period' => $this->sanitize($_POST['salary_period'] ?? null),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        // Συλλογή των δεδομένων για τις άδειες
        $data['license_required'] = isset($_POST['license_required']) ? 1 : 0;
        $data['pei_required'] = isset($_POST['pei_required']) ? 1 : 0;
        $data['adr_required'] = isset($_POST['adr_required']) ? 1 : 0;
        $data['tachograph_required'] = isset($_POST['tachograph_required']) ? 1 : 0;
        $data['operator_license_required'] = isset($_POST['operator_license_required']) ? 1 : 0;

        // Συλλογή των τύπων αδειών
        if (isset($_POST['license_types']) && is_array($_POST['license_types'])) {
            $data['license_types'] = implode(',', array_map([$this, 'sanitize'], $_POST['license_types']));
        } else {
            $data['license_types'] = null;
        }

        // Συλλογή της ημερομηνίας λήξης
        if (isset($_POST['expires_at']) && !empty($_POST['expires_at'])) {
            $data['expires_at'] = $this->sanitizeDate($_POST['expires_at']);
        } else {
            $data['expires_at'] = null;
        }

        return $data;
    }

    /**
     * Καθαρίζει μια τιμή εισόδου
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return string|null Η καθαρισμένη τιμή
     */
    private function sanitize($input)
    {
        if ($input === null) {
            return null;
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Καθαρίζει HTML
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return string|null Η καθαρισμένη τιμή
     */
    private function sanitizeHtml($input)
    {
        if ($input === null) {
            return null;
        }
        // Επιτρέπουμε βασικά HTML tags
        $allowedTags = '<p><br><strong><em><ul><ol><li><h2><h3><h4>';
        return strip_tags(trim($input), $allowedTags);
    }

    /**
     * Καθαρίζει μια ημερομηνία
     * 
     * @param string|null $date Η ημερομηνία
     * @return string|null Η καθαρισμένη ημερομηνία
     */
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
}
