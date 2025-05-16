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
use Drivejob\Repositories\DriversRepository;
use Drivejob\Services\DriverProfileService;

/**
 * Controller για τους οδηγούς
 * 
 * Νέα έκδοση που χρησιμοποιεί το Repository pattern
 */
class NewDriversController
{
    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var DriverProfileService Η υπηρεσία για τα προφίλ οδηγών
     */
    private $driverProfileService;

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

        // Αρχικοποίηση του repository και της υπηρεσίας
        $this->driversRepository = new DriversRepository($pdo);
        $this->driverProfileService = new DriverProfileService($pdo);
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

        // Λήψη πλήρους προφίλ του οδηγού με τη νέα υπηρεσία
        $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

        if (!$driverProfile) {
            Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
            header('Location: ' . BASE_URL);
            exit();
        }

        // Αντιστοίχιση μεταβλητών για συμβατότητα με το view
        $driverData = $driverProfile;
        $driverLicenses = $driverProfile['licenses'] ?? [];
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');
        $driverSkills = $driverProfile['skills'] ?? [];
        $driverCertifications = $driverProfile['certifications'] ?? [];
        $driverVehicleExperience = $driverProfile['vehicle_experience'] ?? [];

        // Λήψη δεδομένων κάρτας ταχογράφου
        $driverTachograph = $driverProfile['tachograph_cards'][0] ?? null;

        // Λήψη δεδομένων άδειας χειριστή
        $driverOperator = $driverProfile['operator_licenses'][0] ?? null;

        // Λήψη υποειδικοτήτων άδειας χειριστή
        $operatorSubSpecialities = [];
        if ($driverOperator) {
            $operatorSubSpecialities = $driverOperator['sub_specialities'] ?? [];
        }

        // Λήψη ειδικών αδειών
        $driverSpecialLicenses = $driverProfile['special_licenses'] ?? [];

        // Λήψη δεδομένων πιστοποιητικού ADR
        $driverADR = $driverProfile['adr_certificates'][0] ?? null;

        // Λήψη βαθμολογίας οδηγού
        $driverRating = $driverProfile['rating_details'] ?? null;

        // Λήψη πρόσφατων συμβάντων
        $recentIncidents = array_slice($driverProfile['incidents'] ?? [], 0, 3);

        // Λήψη δεδομένων τηλεματικής
        $telemetryData = $driverProfile['telemetry_data'] ?? null;

        // Έλεγχος για ΠΕΙ
        $hasPeiC = false;
        $hasPeiD = false;
        $peiCExpiryDate = null;
        $peiDExpiryDate = null;

        if (!empty($driverLicenses)) {
            foreach ($driverLicenses as $license) {
                if (!empty($license['has_pei']) && $license['has_pei'] == 1) {
                    if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E'])) {
                        $hasPeiC = true;
                        if (!empty($license['pei_expiry_c'])) {
                            $peiCExpiryDate = $license['pei_expiry_c'];
                        }
                    } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E'])) {
                        $hasPeiD = true;
                        if (!empty($license['pei_expiry_d'])) {
                            $peiDExpiryDate = $license['pei_expiry_d'];
                        }
                    }
                }
            }
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/profile.php';
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

        // Λήψη πλήρους προφίλ του οδηγού με τη νέα υπηρεσία
        $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

        if (!$driverProfile) {
            Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
            header('Location: ' . BASE_URL);
            exit();
        }

        // Αντιστοίχιση μεταβλητών για συμβατότητα με το view
        $driverData = $driverProfile;
        $driverLicenses = $driverProfile['licenses'] ?? [];
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');

        // Υπολογισμός των αδειών που έχουν ΠΕΙ
        $driverPEI = array_column(array_filter($driverLicenses, function ($license) {
            return isset($license['has_pei']) && $license['has_pei'] == 1;
        }), 'license_type');

        // Αντιστοίχιση υπόλοιπων μεταβλητών
        $driverADR = $driverProfile['adr_certificates'][0] ?? null;
        $driverOperator = $driverProfile['operator_licenses'][0] ?? null;
        $driverOperatorSubSpecialities = [];

        if ($driverOperator) {
            $driverOperatorSubSpecialities = $driverOperator['sub_specialities'] ?? [];
        }

        $driverSpecialLicenses = $driverProfile['special_licenses'] ?? [];
        $driverTachograph = $driverProfile['tachograph_cards'][0] ?? null;
        $driverSkills = $driverProfile['skills'] ?? [];
        $driverCertifications = $driverProfile['certifications'] ?? [];
        $driverVehicleExperience = $driverProfile['vehicle_experience'] ?? [];

        // Υπολογισμός προϋπηρεσίας για εμπορευματικές και επιβατικές μεταφορές
        $freightYears = 0;
        $freightMonths = 0;
        $freightDays = 0;
        $passengerYears = 0;
        $passengerMonths = 0;
        $passengerDays = 0;

        if (!empty($driverVehicleExperience)) {
            foreach ($driverVehicleExperience as $exp) {
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
            $roundedFreightYears = round($freightDecimalYears);

            // Στρογγυλοποίηση των ετών επιβατικών μεταφορών
            $passengerDecimalYears = $passengerYears + ($passengerMonths / 12) + ($passengerDays / 365);
            $roundedPassengerYears = round($passengerDecimalYears);
        } else {
            $roundedFreightYears = 0;
            $roundedPassengerYears = 0;
        }

        // Προετοιμασία δεδομένων ΠΕΙ
        $peiCExpiryDate = null;
        $peiDExpiryDate = null;

        foreach ($driverLicenses as $license) {
            if (isset($license['has_pei']) && $license['has_pei'] == 1) {
                if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E']) && !empty($license['pei_expiry_c'])) {
                    $peiCExpiryDate = $license['pei_expiry_c'];
                } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E']) && !empty($license['pei_expiry_d'])) {
                    $peiDExpiryDate = $license['pei_expiry_d'];
                }
            }
        }

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/edit_profile.php';
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
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

        // Επεξεργασία των αρχείων που ανεβάζονται
        $uploadedFiles = [
            'profile_image' => $_FILES['profile_image'] ?? null,
            'resume_file' => $_FILES['resume_file'] ?? null,
            'criminal_record_file' => $_FILES['criminal_record_file'] ?? null,
            'license_front_image' => $_FILES['license_front_image'] ?? null,
            'license_back_image' => $_FILES['license_back_image'] ?? null,
            'tachograph_front_image' => $_FILES['tachograph_front_image'] ?? null,
            'tachograph_back_image' => $_FILES['tachograph_back_image'] ?? null,
            'adr_front_image' => $_FILES['adr_front_image'] ?? null,
            'adr_back_image' => $_FILES['adr_back_image'] ?? null,
            'operator_front_image' => $_FILES['operator_front_image'] ?? null,
            'operator_back_image' => $_FILES['operator_back_image'] ?? null
        ];

        // Επεξεργασία των αρχείων
        foreach ($uploadedFiles as $fileType => $fileData) {
            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $uploadedFilePath = $this->uploadFile($fileData, $this->getUploadDirectory($fileType));
                if ($uploadedFilePath) {
                    $data[$fileType] = $uploadedFilePath;
                }
            }
        }

        try {
            // Ενημέρωση του προφίλ με τη νέα υπηρεσία
            $updateResult = $this->driverProfileService->updateProfile($driverId, $data);

            if ($updateResult) {
                Logger::info('Profile update successful');
                Session::set('success_message', 'Το προφίλ σας ενημερώθηκε με επιτυχία.');
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
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            echo \json_encode(['success' => false, 'message' => 'Άκυρο αίτημα.']);
            exit();
        }

        try {
            // Λήψη του τρέχοντος οδηγού
            $driverId = Session::get('user_id');
            $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

            if (!$driverProfile) {
                echo \json_encode(['success' => false, 'message' => 'Δεν βρέθηκε ο οδηγός.']);
                exit();
            }

            // Αλλαγή της κατάστασης διαθεσιμότητας
            $currentStatus = isset($driverProfile['available_for_work']) ? (int)$driverProfile['available_for_work'] : 0;
            $newStatus = $currentStatus ? 0 : 1;

            // Καταγραφή για εντοπισμό σφαλμάτων
            Logger::info("Εναλλαγή διαθεσιμότητας για οδηγό $driverId από $currentStatus σε $newStatus");

            $success = $this->driverProfileService->updateProfile($driverId, ['available_for_work' => $newStatus]);

            if ($success) {
                echo \json_encode([
                    'success' => true,
                    'message' => 'Η διαθεσιμότητα ενημερώθηκε με επιτυχία',
                    'newStatus' => $newStatus,
                    'statusText' => $newStatus ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'
                ]);
            } else {
                echo \json_encode(['success' => false, 'message' => 'Αποτυχία ενημέρωσης διαθεσιμότητας']);
            }
        } catch (DatabaseException $e) {
            Logger::error("Σφάλμα βάσης δεδομένων κατά την εναλλαγή διαθεσιμότητας: " . $e->getMessage(), $e->getContext());
            echo \json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων']);
        } catch (\Exception $e) {
            Logger::error("Σφάλμα κατά την εναλλαγή διαθεσιμότητας: " . $e->getMessage());
            echo \json_encode(['success' => false, 'message' => 'Σφάλμα επεξεργασίας αιτήματος']);
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
        // Λήψη του τρέχοντος οδηγού για να διατηρήσουμε την κατάσταση διαθεσιμότητας
        $driverId = Session::get('user_id');
        $driverProfile = $this->driverProfileService->getDriverProfile($driverId);
        $currentAvailability = isset($driverProfile['available_for_work']) ? (int)$driverProfile['available_for_work'] : 1;

        // Βασικά δεδομένα προφίλ
        $data = [
            'email' => $this->sanitize($_POST['email'] ?? null), // Προσθήκη του email
            'first_name' => $this->sanitize($_POST['first_name'] ?? null),
            'last_name' => $this->sanitize($_POST['last_name'] ?? null),
            'phone' => $this->sanitize($_POST['phone'] ?? null),
            'address' => $this->sanitize($_POST['address'] ?? null),
            'city' => $this->sanitize($_POST['city'] ?? null),
            'country' => $this->sanitize($_POST['country'] ?? null),
            'postal_code' => $this->sanitize($_POST['postal_code'] ?? null),
            'date_of_birth' => $this->sanitizeDate($_POST['birth_date'] ?? null),
            'legal_status' => $this->sanitize($_POST['legal_status'] ?? null), // Προσθήκη του πεδίου legal_status
            // Διατήρηση της τρέχουσας τιμής του available_for_work
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Καταγραφή για εντοπισμό σφαλμάτων
        Logger::info("Επεξεργασία προφίλ οδηγού - legal_status: " . ($data['legal_status'] ?? 'null'));

        // Καταγραφή των δεδομένων πριν την επεξεργασία
        Logger::info("Δεδομένα φόρμας πριν την επεξεργασία:", [
            'legal_status' => $data['legal_status'] ?? 'null',
            'has_file' => isset($_FILES['criminal_record_file']) && $_FILES['criminal_record_file']['error'] === UPLOAD_ERR_OK ? 'yes' : 'no'
        ]);

        // Επεξεργασία του αρχείου ποινικού μητρώου
        if (isset($_FILES['criminal_record_file']) && $_FILES['criminal_record_file']['error'] === UPLOAD_ERR_OK) {
            // Βεβαιωνόμαστε ότι το legal_status είναι 'yes' αν ανεβάζουμε αρχείο
            $data['legal_status'] = 'yes';
            Logger::info("Ορισμός legal_status σε 'yes' λόγω ανεβάσματος αρχείου");

            $criminalRecordFile = $this->uploadFile($_FILES['criminal_record_file'], 'criminal_records');
            if ($criminalRecordFile) {
                $data['criminal_record_file'] = $criminalRecordFile;
                Logger::info("Αρχείο ποινικού μητρώου ανέβηκε επιτυχώς: " . $criminalRecordFile);
            } else {
                Logger::error("Αποτυχία ανεβάσματος αρχείου ποινικού μητρώου");
            }
        } else if ($data['legal_status'] === 'yes') {
            // Αν το legal_status είναι 'yes' αλλά δεν ανεβάσαμε νέο αρχείο, ελέγχουμε αν υπάρχει ήδη αρχείο
            $driverProfile = $this->driverProfileService->getDriverProfile($driverId);
            if (isset($driverProfile['criminal_record_file']) && $driverProfile['criminal_record_file']) {
                // Διατηρούμε το υπάρχον αρχείο
                $data['criminal_record_file'] = $driverProfile['criminal_record_file'];
                Logger::info("Διατήρηση υπάρχοντος αρχείου ποινικού μητρώου: " . $driverProfile['criminal_record_file']);
            } else {
                // Δεν υπάρχει αρχείο, αλλά το legal_status είναι 'yes'
                Logger::warning("Το legal_status είναι 'yes' αλλά δεν υπάρχει αρχείο ποινικού μητρώου");
            }
        } else if ($data['legal_status'] === 'no') {
            // Αν το legal_status είναι 'no', καθαρίζουμε το πεδίο του αρχείου
            $data['criminal_record_file'] = null;
            Logger::info("legal_status είναι 'no', καθαρισμός του πεδίου αρχείου ποινικού μητρώου");
        }

        // Καταγραφή των δεδομένων μετά την επεξεργασία
        Logger::info("Δεδομένα φόρμας μετά την επεξεργασία:", [
            'legal_status' => $data['legal_status'] ?? 'null',
            'criminal_record_file' => $data['criminal_record_file'] ?? 'null'
        ]);

        // Επεξεργασία των δεξιοτήτων
        if (isset($_POST['skills']) && is_array($_POST['skills'])) {
            foreach ($_POST['skills'] as $skill => $value) {
                $data[$skill] = 1;
            }
        }

        // Επεξεργασία των δεδομένων για τις επιλογές που είναι μόνο για εμπορευματικές μεταφορές
        if (isset($_POST['freight_only']) && is_array($_POST['freight_only'])) {
            foreach ($_POST['freight_only'] as $key => $value) {
                $data['freight_only_' . $key] = 1;
            }
        } else {
            // Αν δεν υπάρχουν επιλογές, θέτουμε τις προεπιλεγμένες τιμές
            $data['freight_only_loading'] = 1;
            $data['freight_only_dangerous'] = 1;
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
     * Καθαρίζει ένα URL
     * 
     * @param string|null $url Το URL
     * @return string|null Το καθαρισμένο URL
     */
    private function sanitizeUrl($url)
    {
        if (empty($url)) {
            return null;
        }
        $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
        if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
            return $sanitizedUrl;
        }
        return null;
    }

    /**
     * Ανεβάζει ένα αρχείο στον server
     * 
     * @param array $file Το αρχείο από το $_FILES
     * @param string $directory Ο υποφάκελος για την αποθήκευση
     * @return string|false Η διαδρομή του αρχείου ή false σε περίπτωση αποτυχίας
     */
    private function uploadFile($file, $directory)
    {
        // Έλεγχος για σφάλματα
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Logger::error('File upload error: ' . $file['error']);
            return false;
        }

        // Έλεγχος μεγέθους αρχείου (5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            Logger::error('File too large: ' . $file['size']);
            return false;
        }

        // Έλεγχος τύπου αρχείου
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            Logger::error('Invalid file type: ' . $mimeType);
            return false;
        }

        // Δημιουργία του φακέλου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/' . $directory . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Δημιουργία μοναδικού ονόματος αρχείου
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;

        // Μετακίνηση του αρχείου
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/' . $directory . '/' . $filename;
        }

        Logger::error('Failed to move uploaded file');
        return false;
    }

    /**
     * Επιστρέφει τον κατάλληλο φάκελο για το ανέβασμα αρχείων
     * 
     * @param string $fileType Ο τύπος του αρχείου
     * @return string Ο φάκελος για το ανέβασμα
     */
    private function getUploadDirectory($fileType)
    {
        switch ($fileType) {
            case 'profile_image':
                return 'profile_images';
            case 'resume_file':
                return 'resumes';
            case 'criminal_record_file':
                return 'criminal_records';
            case 'license_front_image':
            case 'license_back_image':
                return 'license_images';
            case 'adr_front_image':
            case 'adr_back_image':
                return 'adr_images';
            case 'tachograph_front_image':
            case 'tachograph_back_image':
                return 'tachograph_images';
            case 'operator_front_image':
            case 'operator_back_image':
                return 'operator_images';
            default:
                return 'other_documents';
        }
    }
}
