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

        // Αρχικοποίηση του repository
        $this->companiesRepository = new CompaniesRepository($pdo);
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
        include ROOT_DIR . '/src/Views/companies/company_profile.php';
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
        include ROOT_DIR . '/src/Views/companies/edit_profile.php';
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('company');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
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
     * Προσθήκη της μεθόδου collectFormData για το sanitization
     * 
     * @return array Τα καθαρισμένα δεδομένα της φόρμας
     */
    private function collectFormData()
    {
        return [
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
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
}
