<?php

namespace Drivejob\Controllers\Company;

use Drivejob\Controllers\BaseUserController;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Container;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Repositories\CompaniesRepository;
use Drivejob\Models\Company\CompanyRatingModel;
use Drivejob\Services\FileService;
use Drivejob\Helpers\JsonHelper;

/**
 * Controller για τις εταιρείες
 * 
 * Νέα έκδοση που χρησιμοποιεί το Repository pattern
 * και επεκτείνει τον BaseUserController για κοινές λειτουργίες
 */
class CompaniesController extends BaseUserController
{
    /**
     * @var CompaniesRepository Το repository για τις εταιρείες
     */
    private $companiesRepository;

    /**
     * @var \Drivejob\Services\Rating\RatingServiceInterface Η υπηρεσία για τις αξιολογήσεις
     */
    private $ratingService;

    /**
     * @var FileService Η υπηρεσία για τη διαχείριση αρχείων
     */
    private $fileService;

    /**
     * @var Container Το container για τις εξαρτήσεις
     */
    protected $container;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct();

        // Λήψη του container
        $this->container = Container::getInstance();

        // Αν δεν έχει παραχθεί PDO, πάρε το από το container
        if ($pdo === null) {
            $pdo = $this->container->get('pdo');
        }

        // Αρχικοποίηση του repository και των υπηρεσιών
        $this->companiesRepository = new CompaniesRepository($pdo);
        $this->ratingService = $this->container->get('rating_service') ?? new \Drivejob\Services\Rating\RatingService($pdo);
        $this->fileService = new FileService();
    }

    /**
     * Εμφανίζει τη φόρμα εγγραφής για νέες εταιρείες
     */
    public function showRegistrationForm()
    {
        // Έλεγχος αν ο χρήστης είναι ήδη συνδεδεμένος
        if (Session::has('user_id')) {
            // Ανακατεύθυνση στην αρχική σελίδα
            header('Location: ' . BASE_URL);
            exit();
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/company-registration.php';
    }

    /**
     * Προβάλλει τη σελίδα προφίλ της εταιρείας
     */
    public function profile()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');

        // Λήψη των στοιχείων της εταιρείας
        $companyId = Session::get('user_id');
        $companyData = $this->companiesRepository->find($companyId);

        if (!$companyData) {
            Session::set('error_message', 'Τα στοιχεία της εταιρείας δεν βρέθηκαν.');
            header('Location: ' . BASE_URL);
            exit();
        }

        // Λήψη των αγγελιών της εταιρείας
        $jobListingRepository = new \Drivejob\Repositories\JobListingRepository($this->container->get('pdo'));
        $listings = $jobListingRepository->searchListings(['company_id' => $companyId], 1, 5);

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/company-profile.php';
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');

        // Λήψη των στοιχείων της εταιρείας
        $companyId = Session::get('user_id');
        $companyData = $this->companiesRepository->find($companyId);

        if (!$companyData) {
            Session::set('error_message', 'Τα στοιχεία της εταιρείας δεν βρέθηκαν.');
            header('Location: ' . BASE_URL);
            exit();
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/edit-profile.php';
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in company profile update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/edit-profile');
            exit();
        }

        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('company_name', 'Το όνομα της εταιρείας είναι υποχρεωτικό.')
            ->required('contact_person', 'Το όνομα του υπεύθυνου επικοινωνίας είναι υποχρεωτικό.')
            ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
            ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');

        if (!$validator->isValid()) {
            Logger::error('Validation failed in company profile update', [
                'errors' => $validator->getErrors(),
                'post_data' => $_POST
            ]);
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);
            header('Location: ' . BASE_URL . 'companies/edit-profile');
            exit();
        }

        // Λήψη ID της συνδεδεμένης εταιρείας
        $companyId = Session::get('user_id');
        Logger::info('Starting company profile update', ['company_id' => $companyId]);

        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();
        Logger::info('Collected form data for update', ['data_keys' => array_keys($data)]);

        try {
            // Ενημέρωση του προφίλ με το repository
            $updateResult = $this->companiesRepository->update($companyId, $data);

            if ($updateResult) {
                Logger::info('Company profile update successful');
                Session::set('success_message', 'Το προφίλ της εταιρείας ενημερώθηκε με επιτυχία.');
            } else {
                Logger::error('Company profile update failed', [
                    'company_id' => $companyId,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ. Παρακαλώ δοκιμάστε ξανά.');
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in company profile update', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
        } catch (\Exception $e) {
            Logger::error('Exception in company profile update', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        header('Location: ' . BASE_URL . 'companies/profile');
        exit();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ μιας εταιρείας
     * 
     * @param int $id Το ID της εταιρείας
     */
    public function publicProfile($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό εταιρείας');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Ανάκτηση των στοιχείων της εταιρείας
        $companyData = $this->companiesRepository->find($id);

        if (!$companyData) {
            Session::set('error_message', 'Η εταιρεία δεν βρέθηκε');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Λήψη των αγγελιών της εταιρείας
        $jobListingRepository = new \Drivejob\Repositories\JobListingRepository($this->container->get('pdo'));
        $listings = $jobListingRepository->searchListings(['company_id' => $id], 1, 5);

        // Λήψη των αξιολογήσεων της εταιρείας
        $companyReviews = $this->ratingService->getCompanyReviews($id);
        $averageRating = $this->ratingService->getCompanyRating($id);

        // Έλεγχος αν ο συνδεδεμένος χρήστης είναι οδηγός και μπορεί να αξιολογήσει την εταιρεία
        $canReview = false;
        $hasReviewed = false;

        if (Session::has('user_id') && Session::get('user_role') === 'driver') {
            $driverId = Session::get('user_id');

            // Έλεγχος αν ο οδηγός έχει ήδη αξιολογήσει την εταιρεία
            foreach ($companyReviews as $review) {
                if ($review['driver_id'] == $driverId) {
                    $hasReviewed = true;
                    break;
                }
            }

            // Ο οδηγός μπορεί να αξιολογήσει την εταιρεία αν δεν την έχει ήδη αξιολογήσει
            $canReview = !$hasReviewed;
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/companies/public-profile.php';
    }

    /**
     * Αναζητά εταιρείες με βάση διάφορα κριτήρια
     */
    public function search()
    {
        // Συλλογή των κριτηρίων αναζήτησης
        $criteria = [
            'name' => $_GET['name'] ?? null,
            'location' => $_GET['location'] ?? null,
            'industry' => $_GET['industry'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'last_login',
            'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
        ];

        // Λήψη της τρέχουσας σελίδας και του ορίου
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        try {
            // Αναζήτηση εταιρειών με το repository
            $result = $this->companiesRepository->searchCompanies($criteria, $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                // Δημιουργία JSON χειροκίνητα
                $jsonResults = '[]';
                $jsonPagination = '{}';

                if (!empty($result['results'])) {
                    $jsonResults = '[';
                    foreach ($result['results'] as $index => $company) {
                        if ($index > 0) {
                            $jsonResults .= ',';
                        }
                        $jsonResults .= '{';
                        foreach ($company as $key => $value) {
                            if (is_string($value)) {
                                $value = str_replace('"', '\"', $value);
                                $jsonResults .= '"' . $key . '":"' . $value . '",';
                            } else if (is_numeric($value)) {
                                $jsonResults .= '"' . $key . '":' . $value . ',';
                            } else if (is_null($value)) {
                                $jsonResults .= '"' . $key . '":null,';
                            } else if (is_bool($value)) {
                                $jsonResults .= '"' . $key . '":' . ($value ? 'true' : 'false') . ',';
                            }
                        }
                        // Αφαίρεση του τελευταίου κόμματος
                        $jsonResults = rtrim($jsonResults, ',');
                        $jsonResults .= '}';
                    }
                    $jsonResults .= ']';
                }

                if (!empty($result['pagination'])) {
                    $jsonPagination = '{';
                    foreach ($result['pagination'] as $key => $value) {
                        if (is_numeric($value)) {
                            $jsonPagination .= '"' . $key . '":' . $value . ',';
                        } else if (is_string($value)) {
                            $value = str_replace('"', '\"', $value);
                            $jsonPagination .= '"' . $key . '":"' . $value . '",';
                        } else if (is_null($value)) {
                            $jsonPagination .= '"' . $key . '":null,';
                        } else if (is_bool($value)) {
                            $jsonPagination .= '"' . $key . '":' . ($value ? 'true' : 'false') . ',';
                        }
                    }
                    // Αφαίρεση του τελευταίου κόμματος
                    $jsonPagination = rtrim($jsonPagination, ',');
                    $jsonPagination .= '}';
                }

                echo '{"results": ' . $jsonResults . ', "pagination": ' . $jsonPagination . '}';
                exit();
            }

            // Αλλιώς, φόρτωση του view
            $companies = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/companies/search.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in company search', [
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo '{"error": "Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά."}';
                exit();
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'home');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in company search', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo '{"error": "Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά."}';
                exit();
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'home');
            exit();
        }
    }

    /**
     * Προσθέτει μια νέα αξιολόγηση για μια εταιρεία
     * 
     * @param int $id Το ID της εταιρείας
     */
    public function addReview($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό εταιρείας');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in company review');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/profile/' . $id);
            exit();
        }

        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('rating', 'Η βαθμολογία είναι υποχρεωτική.')
            ->numeric('rating', 'Η βαθμολογία πρέπει να είναι αριθμός.');

        // Έλεγχος εύρους τιμών για τη βαθμολογία
        if (isset($_POST['rating']) && is_numeric($_POST['rating'])) {
            $rating = floatval($_POST['rating']);
            if ($rating < 1 || $rating > 5) {
                // Δεν μπορούμε να προσπελάσουμε απευθείας το $errors, οπότε θα χρησιμοποιήσουμε μια διαφορετική προσέγγιση
                Session::set('errors', ['rating' => 'Η βαθμολογία πρέπει να είναι μεταξύ 1 και 5.']);
                Session::set('old_input', $_POST);
                header('Location: ' . BASE_URL . 'companies/profile/' . $id);
                exit();
            }
        }

        if (!$validator->isValid()) {
            Logger::error('Validation failed in company review', [
                'errors' => $validator->getErrors(),
                'post_data' => $_POST
            ]);
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);
            header('Location: ' . BASE_URL . 'companies/profile/' . $id);
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = Session::get('user_id');
        Logger::info('Starting company review', ['company_id' => $id, 'driver_id' => $driverId]);

        // Συλλογή των δεδομένων από τη φόρμα
        $rating = floatval($_POST['rating']);
        $comment = $this->sanitize($_POST['comment'] ?? '');

        // Επιμέρους βαθμολογίες (αν υπάρχουν)
        $reliabilityRating = isset($_POST['reliability_rating']) ? floatval($_POST['reliability_rating']) : $rating;
        $communicationRating = isset($_POST['communication_rating']) ? floatval($_POST['communication_rating']) : $rating;
        $paymentRating = isset($_POST['payment_rating']) ? floatval($_POST['payment_rating']) : $rating;
        $workingConditionsRating = isset($_POST['working_conditions_rating']) ? floatval($_POST['working_conditions_rating']) : $rating;

        try {
            // Έλεγχος αν ο οδηγός έχει ήδη αξιολογήσει την εταιρεία
            if ($this->ratingService->hasDriverReviewedCompany($driverId, $id)) {
                Session::set('error_message', 'Έχετε ήδη αξιολογήσει αυτή την εταιρεία.');
                header('Location: ' . BASE_URL . 'companies/profile/' . $id);
                exit();
            }

            // Συλλογή των λεπτομερών αξιολογήσεων
            $detailedRatings = [
                'reliability_rating' => $reliabilityRating,
                'communication_rating' => $communicationRating,
                'payment_rating' => $paymentRating,
                'working_conditions_rating' => $workingConditionsRating
            ];

            // Προσθήκη της αξιολόγησης
            $result = $this->ratingService->addCompanyReview($id, $driverId, $rating, $comment, $detailedRatings);

            if ($result) {
                Logger::info('Company review successful');
                Session::set('success_message', 'Η αξιολόγησή σας προστέθηκε με επιτυχία.');
            } else {
                Logger::error('Company review failed', [
                    'company_id' => $id,
                    'driver_id' => $driverId,
                    'rating' => $rating
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την προσθήκη της αξιολόγησης. Παρακαλώ δοκιμάστε ξανά.');
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in company review', [
                'company_id' => $id,
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
        } catch (\Exception $e) {
            Logger::error('Exception in company review', [
                'company_id' => $id,
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        header('Location: ' . BASE_URL . 'companies/profile/' . $id);
        exit();
    }

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

    /**
     * Προσθήκη της μεθόδου collectFormData για το sanitization
     * 
     * @return array Τα καθαρισμένα δεδομένα της φόρμας
     */
    private function collectFormData()
    {
        $data = [
            'email' => $this->sanitize($_POST['email'] ?? null),
            'company_name' => $this->sanitize($_POST['company_name'] ?? null),
            'contact_person' => $this->sanitize($_POST['contact_person'] ?? null),
            'phone' => $this->sanitize($_POST['phone'] ?? null),
            'address' => $this->sanitize($_POST['address'] ?? null),
            'city' => $this->sanitize($_POST['city'] ?? null),
            'country' => $this->sanitize($_POST['country'] ?? null),
            'postal_code' => $this->sanitize($_POST['postal_code'] ?? null),
            'website' => $this->sanitizeUrl($_POST['website'] ?? null),
            'description' => $this->sanitizeHtml($_POST['description'] ?? null),
            'industry' => $this->sanitize($_POST['industry'] ?? null),
            'company_size' => $this->sanitize($_POST['company_size'] ?? null),
            'founded_year' => $this->sanitize($_POST['founded_year'] ?? null),
            'vat_number' => $this->sanitize($_POST['vat_number'] ?? null),
            'position' => $this->sanitize($_POST['position'] ?? null),
            'foundation_year' => $this->sanitize($_POST['foundation_year'] ?? null),
            'social_linkedin' => $this->sanitizeUrl($_POST['social_linkedin'] ?? null),
            'social_facebook' => $this->sanitizeUrl($_POST['social_facebook'] ?? null),
            'social_twitter' => $this->sanitizeUrl($_POST['social_twitter'] ?? null),

            // Νέα πεδία για fleet management
            'fleet_size' => intval($_POST['fleet_size'] ?? 0),
            'active_drivers' => intval($_POST['active_drivers'] ?? 0),
            'has_hr_system' => isset($_POST['has_hr_system']) ? 1 : 0,
            'has_payroll_system' => isset($_POST['has_payroll_system']) ? 1 : 0,
            'has_training_program' => isset($_POST['has_training_program']) ? 1 : 0,
            'has_fleet_management' => isset($_POST['has_fleet_management']) ? 1 : 0,
            'has_telematics' => isset($_POST['has_telematics']) ? 1 : 0,
            'has_route_optimization' => isset($_POST['has_route_optimization']) ? 1 : 0,
            'maintenance_provider' => $this->sanitize($_POST['maintenance_provider'] ?? null),
            'average_hiring_time' => intval($_POST['average_hiring_time'] ?? 0),

            // Compliance & Legal
            'has_legal_support' => isset($_POST['has_legal_support']) ? 1 : 0,
            'operates_internationally' => isset($_POST['operates_internationally']) ? 1 : 0,

            // Subscription
            'subscription_plan' => $this->sanitize($_POST['subscription_plan'] ?? 'basic'),

            // JSON fields
            'transport_types' => isset($_POST['transport_types']) ? json_encode($_POST['transport_types']) : '[]',
            'operating_countries' => isset($_POST['operating_countries']) ? json_encode($_POST['operating_countries']) : '[]',
            'specializations' => isset($_POST['specializations']) ? json_encode($_POST['specializations']) : '[]',
            'enabled_modules' => isset($_POST['enabled_modules']) ? json_encode($_POST['enabled_modules']) : '[]',

            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Επεξεργασία των αρχείων που ανεβάζονται
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $logoPath = $this->uploadFile($_FILES['company_logo'], 'company_logo', 'image');
            if ($logoPath) {
                $data['logo'] = $logoPath;
            }
        }

        // Επεξεργασία άλλων αρχείων
        $fileTypes = [
            'company_brochure' => 'document',
            'company_certificate' => 'document',
            'company_license' => 'document'
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
