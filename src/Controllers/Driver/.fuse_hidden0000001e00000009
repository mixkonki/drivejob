<?php

namespace Drivejob\Controllers\Driver;

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
use Drivejob\Repositories\DriversRepository;
use Drivejob\Services\Driver\DriverProfileServiceInterface;
use Drivejob\Services\Driver\DriverProfileService;
use Drivejob\Services\FileService;
use Drivejob\Helpers\JsonHelper;
use Drivejob\Models\Driver\IncidentModel;
use Drivejob\Models\Driver\AssessmentModel;
use Drivejob\Services\EventHookService;

/**
 * Controller για τους οδηγούς
 * 
 * Νέα έκδοση που χρησιμοποιεί το Repository pattern
 * και επεκτείνει τον BaseUserController για κοινές λειτουργίες
 */
class DriversController extends BaseUserController
{
    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var DriverProfileServiceInterface Η υπηρεσία για τα προφίλ οδηγών
     */
    private $driverProfileService;

    /**
     * @var FileService Η υπηρεσία για τη διαχείριση αρχείων
     */
    private $fileService;

    /**
     * @var IncidentModel Το μοντέλο για τα περιστατικά
     */
    private $incidentModel;

    /**
     * @var AssessmentModel Το μοντέλο για τις αυτοαξιολογήσεις
     */
    private $assessmentModel;

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
        $this->driversRepository = new DriversRepository($pdo);
        $this->driverProfileService = new DriverProfileService($pdo);
        $this->fileService = new FileService();
        $this->incidentModel = new IncidentModel($pdo);
        $this->assessmentModel = new AssessmentModel($pdo);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ του οδηγού
     */
    public function profile()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');

        try {
            // Λήψη πλήρους προφίλ του οδηγού με τη νέα υπηρεσία
            $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

            if (!$driverProfile) {
                Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
                header('Location: ' . BASE_URL);
                exit();
            }

            // Προετοιμασία δεδομένων για το view
            $viewData = $this->prepareDriverProfileViewData($driverProfile);

            // Λήψη των αγγελιών του οδηγού
            $jobListingRepository = new \Drivejob\Repositories\JobListingRepository($this->container->get('pdo'));
            $viewData['myListings'] = $jobListingRepository->searchListings(['driver_id' => $driverId], 1, 10);

            // Φόρτωση του view με τα προετοιμασμένα δεδομένα
            extract($viewData);
            include ROOT_DIR . '/src/Views/drivers/driver-profile.php';
        } catch (\Exception $e) {
            Logger::error('Error in driver profile view', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την προβολή του προφίλ. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Προετοιμάζει τα δεδομένα του προφίλ οδηγού για το view
     * 
     * @param array $driverProfile Πλήρες προφίλ οδηγού
     * @return array Δεδομένα για το view
     */
    private function prepareDriverProfileViewData($driverProfile)
    {
        // Αντιστοίχιση μεταβλητών για συμβατότητα με το view
        $viewData = [
            'driverData' => $driverProfile,
            'driverLicenses' => $driverProfile['licenses'] ?? [],
            'driverSkills' => $driverProfile['skills'] ?? [],
            'driverCertifications' => $driverProfile['certifications'] ?? [],
            'driverVehicleExperience' => $driverProfile['vehicle_experience'] ?? [],
            'driverTachograph' => $driverProfile['tachograph_cards'][0] ?? null,
            'driverOperator' => $driverProfile['operator_licenses'][0] ?? null,
            'driverSpecialLicenses' => $driverProfile['special_licenses'] ?? [],
            'driverADR' => $driverProfile['adr_certificates'][0] ?? null,
            'driverRating' => $driverProfile['rating_details'] ?? null,
            'recentIncidents' => array_slice($driverProfile['incidents'] ?? [], 0, 3),
            'telemetryData' => $driverProfile['telemetry_data'] ?? null
        ];

        // Εξαγωγή τύπων αδειών
        $viewData['driverLicenseTypes'] = array_column($viewData['driverLicenses'], 'license_type');

        // Λήψη υποειδικοτήτων άδειας χειριστή
        $viewData['operatorSubSpecialities'] = [];
        if ($viewData['driverOperator']) {
            $viewData['operatorSubSpecialities'] = $viewData['driverOperator']['sub_specialities'] ?? [];
        }

        // Έλεγχος για ΠΕΙ
        $viewData['hasPeiC'] = false;
        $viewData['hasPeiD'] = false;
        $viewData['peiCExpiryDate'] = null;
        $viewData['peiDExpiryDate'] = null;

        if (!empty($viewData['driverLicenses'])) {
            foreach ($viewData['driverLicenses'] as $license) {
                if (!empty($license['has_pei']) && $license['has_pei'] == 1) {
                    if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E'])) {
                        $viewData['hasPeiC'] = true;
                        if (!empty($license['pei_expiry_c'])) {
                            $viewData['peiCExpiryDate'] = $license['pei_expiry_c'];
                        }
                    } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E'])) {
                        $viewData['hasPeiD'] = true;
                        if (!empty($license['pei_expiry_d'])) {
                            $viewData['peiDExpiryDate'] = $license['pei_expiry_d'];
                        }
                    }
                }
            }
        }

        return $viewData;
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');

        try {
            // Λήψη πλήρους προφίλ του οδηγού με τη νέα υπηρεσία
            $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

            if (!$driverProfile) {
                Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
                header('Location: ' . BASE_URL);
                exit();
            }

            // Προετοιμασία δεδομένων για το view
            $viewData = $this->prepareDriverProfileViewData($driverProfile);

            // Επιπλέον δεδομένα για τη φόρμα επεξεργασίας
            $viewData['driverPEI'] = array_column(array_filter($viewData['driverLicenses'], function ($license) {
                return isset($license['has_pei']) && $license['has_pei'] == 1;
            }), 'license_type');

            // Υπολογισμός προϋπηρεσίας για εμπορευματικές και επιβατικές μεταφορές
            $this->calculateExperienceYears($viewData);

            // Φόρτωση του view με τα προετοιμασμένα δεδομένα
            extract($viewData);
            include ROOT_DIR . '/src/Views/drivers/edit-profile.php';
        } catch (\Exception $e) {
            Logger::error('Error in driver profile edit', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την επεξεργασία του προφίλ. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Υπολογίζει τα έτη εμπειρίας για εμπορευματικές και επιβατικές μεταφορές
     * 
     * @param array &$viewData Δεδομένα για το view
     */
    private function calculateExperienceYears(&$viewData)
    {
        $freightYears = 0;
        $freightMonths = 0;
        $freightDays = 0;
        $passengerYears = 0;
        $passengerMonths = 0;
        $passengerDays = 0;

        if (!empty($viewData['driverVehicleExperience'])) {
            foreach ($viewData['driverVehicleExperience'] as $exp) {
                if (isset($exp['transport_type']) && $exp['transport_type'] === 'freight') {
                    $freightYears += isset($exp['years']) ? intval($exp['years']) : 0;
                    $freightMonths += isset($exp['months']) ? intval($exp['months']) : 0;
                    $freightDays += isset($exp['days']) ? intval($exp['days']) : 0;
                } else if (isset($exp['transport_type']) && $exp['transport_type'] === 'passenger') {
                    $passengerYears += isset($exp['years']) ? intval($exp['years']) : 0;
                    $passengerMonths += isset($exp['months']) ? intval($exp['months']) : 0;
                    $passengerDays += isset($exp['days']) ? intval($exp['days']) : 0;
                }
            }

            // Κανονικοποίηση των μηνών και ημερών
            $freightMonths += floor($freightDays / 30);
            $freightDays = $freightDays % 30;
            $freightYears += floor($freightMonths / 12);
            $freightMonths = $freightMonths % 12;

            $passengerMonths += floor($passengerDays / 30);
            $passengerDays = $passengerDays % 30;
            $passengerYears += floor($passengerMonths / 12);
            $passengerMonths = $passengerMonths % 12;

            // Στρογγυλοποίηση των ετών εμπορευματικών μεταφορών
            $freightDecimalYears = $freightYears + ($freightMonths / 12) + ($freightDays / 365);
            $viewData['roundedFreightYears'] = round($freightDecimalYears);

            // Στρογγυλοποίηση των ετών επιβατικών μεταφορών
            $passengerDecimalYears = $passengerYears + ($passengerMonths / 12) + ($passengerDays / 365);
            $viewData['roundedPassengerYears'] = round($passengerDecimalYears);
        } else {
            $viewData['roundedFreightYears'] = 0;
            $viewData['roundedPassengerYears'] = 0;
        }
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in profile update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }

        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
            ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
            ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
            ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');

        if (!$validator->isValid()) {
            Logger::error('Validation failed in profile update', [
                'errors' => $validator->getErrors(),
                'post_data' => $_POST
            ]);
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = Session::get('user_id');
        Logger::info('Starting profile update for driver', ['driver_id' => $driverId]);

        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();
        Logger::info('Collected form data for update', ['data_keys' => array_keys($data)]);

        try {
            // Λήψη παλιών δεδομένων για σύγκριση
            $oldData = $this->driverProfileService->getDriverProfile($driverId);

            // Ενημέρωση του προφίλ με τη νέα υπηρεσία, συμπεριλαμβανομένων των αρχείων
            $updateResult = $this->driverProfileService->updateProfileWithFiles($driverId, $data, $_FILES);

            if ($updateResult) {
                Logger::info('Profile update successful');
                Session::set('success_message', 'Το προφίλ σας ενημερώθηκε με επιτυχία.');

                // Trigger event hook για profile update
                try {
                    $eventHookService = new EventHookService($this->container->get('pdo'));
                    $eventHookService->onDriverProfileUpdate($driverId, $oldData, $data);
                } catch (\Exception $hookError) {
                    Logger::warning('Event hook failed but profile update succeeded: ' . $hookError->getMessage());
                }
            } else {
                Logger::error('Profile update failed', [
                    'driver_id' => $driverId,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ σας. Παρακαλώ δοκιμάστε ξανά.');
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in profile update', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
        } catch (\Exception $e) {
            Logger::error('Exception in profile update', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        header('Location: ' . BASE_URL . 'drivers/profile');
        exit();
    }

    /**
     * Εναλλαγή διαθεσιμότητας οδηγού για εργασία
     */
    public function toggleAvailability()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Άκυρο αίτημα.');
            exit();
        }

        try {
            // Λήψη του τρέχοντος οδηγού
            $driverId = Session::get('user_id');
            $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

            if (!$driverProfile) {
                JsonHelper::error('Δεν βρέθηκε ο οδηγός.');
                exit();
            }

            // Αλλαγή της κατάστασης διαθεσιμότητας
            $currentStatus = isset($driverProfile['available_for_work']) ? (int)$driverProfile['available_for_work'] : 0;
            $newStatus = $currentStatus ? 0 : 1;

            // Καταγραφή για εντοπισμό σφαλμάτων
            Logger::info('Εναλλαγή διαθεσιμότητας για οδηγό', [
                'driver_id' => $driverId,
                'old_status' => $currentStatus,
                'new_status' => $newStatus
            ]);

            // Ενημέρωση του προφίλ με τη νέα υπηρεσία
            $success = $this->driverProfileService->updateBasicInfo($driverId, ['available_for_work' => $newStatus]);

            if ($success) {
                $statusText = $newStatus ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία';
                JsonHelper::success('Η διαθεσιμότητα ενημερώθηκε με επιτυχία', [
                    'newStatus' => $newStatus,
                    'statusText' => $statusText
                ]);
            } else {
                JsonHelper::error('Αποτυχία ενημέρωσης διαθεσιμότητας');
            }
        } catch (DatabaseException $e) {
            Logger::error('Σφάλμα βάσης δεδομένων κατά την εναλλαγή διαθεσιμότητας', [
                'driver_id' => $driverId ?? null,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            JsonHelper::error('Σφάλμα βάσης δεδομένων');
        } catch (\Exception $e) {
            Logger::error('Σφάλμα κατά την εναλλαγή διαθεσιμότητας', [
                'driver_id' => $driverId ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            JsonHelper::error('Σφάλμα επεξεργασίας αιτήματος');
        }

        exit();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     */
    public function publicProfile($id)
    {
        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό οδηγού');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Ανάκτηση των στοιχείων του οδηγού με τη νέα υπηρεσία
        $driverProfile = $this->driverProfileService->getDriverProfile($id);

        if (!$driverProfile) {
            Session::set('error_message', 'Ο οδηγός δεν βρέθηκε');
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        // Αντιστοίχιση μεταβλητών για συμβατότητα με το view
        $driverData = $driverProfile;
        $driverSkills = $driverProfile['skills'] ?? [];
        $driverLicenses = $driverProfile['licenses'] ?? [];
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');
        $driverReviews = $driverProfile['reviews'] ?? [];
        $averageRating = $driverProfile['average_rating'] ?? 0;

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/public-profile.php';
    }

    /**
     * Προσθήκη της μεθόδου collectFormData για το sanitization
     * 
     * @return array Τα καθαρισμένα δεδομένα της φόρμας
     */
    private function collectFormData()
    {
        // Βασικά δεδομένα προφίλ
        $data = [
            'email' => $this->sanitize($_POST['email'] ?? null),
            'first_name' => $this->sanitize($_POST['first_name'] ?? null),
            'last_name' => $this->sanitize($_POST['last_name'] ?? null),
            'phone' => $this->sanitize($_POST['phone'] ?? null),
            'address' => $this->sanitize($_POST['address'] ?? null),
            'city' => $this->sanitize($_POST['city'] ?? null),
            'country' => $this->sanitize($_POST['country'] ?? null),
            'postal_code' => $this->sanitize($_POST['postal_code'] ?? null),
            'date_of_birth' => $this->sanitizeDate($_POST['birth_date'] ?? null),
            'legal_status' => $this->sanitize($_POST['legal_status'] ?? null),
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $data;
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
     * Εμφανίζει το ιστορικό περιστατικών του οδηγού
     */
    public function incidentHistory()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');

        try {
            // Λήψη των περιστατικών του οδηγού
            $incidents = $this->incidentModel->getDriverIncidents($driverId);

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/drivers/incident-history.php';
        } catch (\Exception $e) {
            Logger::error('Error in incident history view', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την προβολή του ιστορικού περιστατικών. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /**
     * Εμφανίζει τη φόρμα αναφοράς περιστατικού
     */
    public function reportIncident()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/report-incident.php';
    }

    /**
     * Αποθηκεύει ένα νέο περιστατικό
     */
    public function saveIncident()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in incident report');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        }

        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('incident_type', 'Ο τύπος περιστατικού είναι υποχρεωτικός.')
            ->required('incident_date', 'Η ημερομηνία περιστατικού είναι υποχρεωτική.')
            ->required('description', 'Η περιγραφή είναι υποχρεωτική.');

        if (!$validator->isValid()) {
            Logger::error('Validation failed in incident report', [
                'errors' => $validator->getErrors(),
                'post_data' => $_POST
            ]);
            Session::set('errors', $validator->getErrors());
            Session::set('old_input', $_POST);
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        }

        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = Session::get('user_id');
        Logger::info('Starting incident report for driver', ['driver_id' => $driverId]);

        // Συλλογή των δεδομένων από τη φόρμα
        $data = [
            'driver_id' => $driverId,
            'incident_type' => $this->sanitize($_POST['incident_type'] ?? null),
            'incident_date' => $this->sanitizeDate($_POST['incident_date'] ?? null),
            'description' => $this->sanitize($_POST['description'] ?? null),
            'location' => $this->sanitize($_POST['location'] ?? null),
            'severity' => $this->sanitize($_POST['severity'] ?? 'medium'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            // Ανέβασμα αρχείου αν υπάρχει
            if (isset($_FILES['incident_file']) && $_FILES['incident_file']['error'] === UPLOAD_ERR_OK) {
                $filePath = $this->uploadFile($_FILES['incident_file'], 'incident_file', 'document');
                if ($filePath) {
                    $data['file_path'] = $filePath;
                }
            }

            // Αποθήκευση του περιστατικού
            $result = $this->incidentModel->addIncident($data);

            if ($result) {
                Logger::info('Incident report successful');
                Session::set('success_message', 'Το περιστατικό καταχωρήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'drivers/incident-history');
                exit();
            } else {
                Logger::error('Incident report failed', [
                    'driver_id' => $driverId,
                    'data' => $data
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την καταχώρηση του περιστατικού. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'drivers/report-incident');
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in incident report', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in incident report', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/report-incident');
            exit();
        }
    }

    /**
     * Εμφανίζει τη φόρμα εγγραφής για νέους οδηγούς
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
        include ROOT_DIR . '/src/Views/drivers/drivers-registration.php';
    }

    /**
     * Εμφανίζει και επεξεργάζεται την αυτοαξιολόγηση του οδηγού
     */
    public function updateAssessment()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');

        // Έλεγχος αν είναι POST αίτημα
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Έλεγχος για CSRF token
            if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
                Logger::error('CSRF token validation failed in assessment update');
                Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'drivers/update-assessment');
                exit();
            }

            // Συλλογή των δεδομένων από τη φόρμα
            $data = [
                'driver_id' => $driverId,
                'driving_skills' => intval($_POST['driving_skills'] ?? 3),
                'vehicle_knowledge' => intval($_POST['vehicle_knowledge'] ?? 3),
                'safety_awareness' => intval($_POST['safety_awareness'] ?? 3),
                'time_management' => intval($_POST['time_management'] ?? 3),
                'customer_service' => intval($_POST['customer_service'] ?? 3),
                'stress_handling' => intval($_POST['stress_handling'] ?? 3),
                'comments' => $this->sanitize($_POST['comments'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            try {
                // Έλεγχος αν υπάρχει ήδη αυτοαξιολόγηση
                $existingAssessment = $this->assessmentModel->getDriverAssessment($driverId);

                if ($existingAssessment) {
                    // Ενημέρωση της υπάρχουσας αυτοαξιολόγησης
                    $result = $this->assessmentModel->updateAssessment($driverId, $data);
                } else {
                    // Προσθήκη νέας αυτοαξιολόγησης
                    $result = $this->assessmentModel->addAssessment($data);
                }

                if ($result) {
                    Logger::info('Assessment update successful');
                    Session::set('success_message', 'Η αυτοαξιολόγησή σας ενημερώθηκε με επιτυχία.');
                    header('Location: ' . BASE_URL . 'drivers/profile');
                    exit();
                } else {
                    Logger::error('Assessment update failed', [
                        'driver_id' => $driverId,
                        'data' => $data
                    ]);
                    Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της αυτοαξιολόγησης. Παρακαλώ δοκιμάστε ξανά.');
                }
            } catch (DatabaseException $e) {
                Logger::error('Database exception in assessment update', [
                    'driver_id' => $driverId,
                    'message' => $e->getMessage(),
                    'context' => $e->getContext()
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            } catch (\Exception $e) {
                Logger::error('Exception in assessment update', [
                    'driver_id' => $driverId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }
        }

        try {
            // Λήψη της τρέχουσας αυτοαξιολόγησης
            $assessment = $this->assessmentModel->getDriverAssessment($driverId);

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/drivers/update-assessment.php';
        } catch (\Exception $e) {
            Logger::error('Error in assessment view', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την προβολή της αυτοαξιολόγησης. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /**
     * Εμφανίζει τις προτεινόμενες θέσεις εργασίας με AI matching
     */
    public function jobMatches()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Λήψη των στοιχείων του οδηγού
        $driverId = Session::get('user_id');

        try {
            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/drivers/job-matches.php';
        } catch (\Exception $e) {
            Logger::error('Error in job matches view', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την προβολή των προτεινόμενων θέσεων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /**
     * Search for drivers
     */
    public function search()
    {
        // Get search parameters
        $criteria = [
            'city' => $_GET['city'] ?? null,
            'license_type' => $_GET['license_type'] ?? null,
            'available_for_work' => isset($_GET['available_for_work']) ? 1 : null,
            'experience_years' => $_GET['experience_years'] ?? null
        ];

        // Remove empty criteria
        $criteria = array_filter($criteria);

        // Get drivers
        $query = "SELECT d.*, u.email 
                  FROM drivers d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE d.is_active = 1";

        $params = [];

        if (!empty($criteria['city'])) {
            $query .= " AND d.city LIKE ?";
            $params[] = '%' . $criteria['city'] . '%';
        }

        if (!empty($criteria['available_for_work'])) {
            $query .= " AND d.available_for_work = 1";
        }

        if (!empty($criteria['experience_years'])) {
            $query .= " AND d.experience_years >= ?";
            $params[] = $criteria['experience_years'];
        }

        $query .= " ORDER BY d.created_at DESC LIMIT 20";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $drivers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Load view
        include ROOT_DIR . '/src/Views/drivers/search.php';
    }
}
