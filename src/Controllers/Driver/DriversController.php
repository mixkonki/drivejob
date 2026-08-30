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
use Drivejob\Services\Driver\DriverLicenseService;
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
    private $driverLicenseService;

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
        $this->driverLicenseService = new DriverLicenseService($pdo);
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
            'driverLanguages' => $driverProfile['languages_list'] ?? [],
            'driverCertifications' => $driverProfile['certifications'] ?? [],
            'driverVehicleExperience' => $driverProfile['vehicle_experience'] ?? [],
            'driverTachograph' => $driverProfile['tachograph_cards'][0] ?? null,
            'driverOperator' => $driverProfile['operator_licenses'][0] ?? null,
            // v2 (25/08): ΟΛΕΣ οι άδειες χειριστή — πολλές ανά κάτοχο
            'driverOperatorLicenses' => $driverProfile['operator_licenses'] ?? [],
            'driverSpecialLicenses' => $driverProfile['special_licenses'] ?? [],
            'driverADR' => $driverProfile['adr_certificates'][0] ?? null,
            'driverRating' => $driverProfile['rating_details'] ?? null,
            'recentIncidents' => array_slice($driverProfile['incidents'] ?? [], 0, 3),
            'telemetryData' => $driverProfile['telemetry_data'] ?? null
        ];

        // Εξαγωγή τύπων αδειών
        $viewData['driverLicenseTypes'] = array_column($viewData['driverLicenses'], 'license_type');

        /*
         * Στατιστικά κεφαλίδας (30/08). Το view διάβαζε $driverStats — μια
         * μεταβλητή που ΔΕΝ οριζόταν πουθενά, οπότε έδειχνε πάντα 0/0/0.
         * Τώρα ορίζεται με πραγματικά δεδομένα· δες DriverStatsService.
         */
        try {
            $statsService = new \Drivejob\Services\Driver\DriverStatsService($this->container->get('pdo'));
            $viewData['driverStats'] = $statsService->forDriver(
                (int) ($driverProfile['id'] ?? $driverProfile['user_id'] ?? 0),
                $driverProfile
            );
        } catch (\Throwable $e) {
            // Η κεφαλίδα δεν αξίζει να ρίξει το προφίλ.
            Logger::error('Driver stats failed', ['message' => $e->getMessage()]);
            $viewData['driverStats'] = null;
        }

        /*
         * ΤΟ ΒΙΟΓΡΑΦΙΚΟ ΩΣ ΔΟΜΗ (30/08) — μία πηγή για την επισκόπηση και
         * για το PDF. Δες DriverCvService: αν οι δύο όψεις χτίζονταν
         * χωριστά θα απέκλιναν, και ο οδηγός θα έβλεπε στην οθόνη άλλα
         * από όσα στέλνει στον εργοδότη.
         */
        try {
            $viewData['cv'] = (new \Drivejob\Services\Driver\DriverCvService())->build($driverProfile, true);
        } catch (\Throwable $e) {
            Logger::error('CV build failed', ['message' => $e->getMessage()]);
            // Κενή δομή: οι όψεις δείχνουν «δεν έχει καταχωρηθεί», δεν σπάνε.
            $viewData['cv'] = [
                'identity' => ['reach' => ['declared' => false, 'label' => '', 'travel' => false]],
                'alerts' => [],
                'experience' => ['items' => [], 'count' => 0, 'total_label' => '—'],
                'certifications' => ['items' => [], 'count' => 0],
                'languages' => [],
                'skills' => ['groups' => [], 'count' => 0],
            ];
        }

        /*
         * Υποειδικότητες άδειας χειριστή — ΟΛΩΝ των αδειών (30/08).
         *
         * ΗΤΑΝ BUG: διαβαζόταν μόνο η ΠΡΩΤΗ άδεια (operator_licenses[0]).
         * Με το v2 ο χειριστής έχει πολλές άδειες· αν η πρώτη κάλυπτε
         * «το σύνολο της ειδικότητας» (καμία υποειδικότητα), το προφίλ
         * έγραφε «Δεν έχουν καταχωρηθεί υποειδικότητες» ενώ οι υπόλοιπες
         * άδειες υπήρχαν κανονικά στη βάση.
         */
        $viewData['operatorSubSpecialities'] = [];
        foreach ($viewData['driverOperatorLicenses'] as $opLicense) {
            foreach ($opLicense['sub_specialities'] ?? [] as $opSub) {
                $viewData['operatorSubSpecialities'][] = $opSub;
            }
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
     * Προϋπηρεσία σε οχήματα.
     *
     * ΓΙΑΤΙ ΠΡΟΣΤΕΘΗΚΕ: το κουμπί «Διαχείριση Προϋπηρεσίας σε Οχήματα» στη
     * φόρμα επεξεργασίας έδειχνε στο /drivers/vehicle-experience — διαδρομή
     * που ΔΕΝ υπήρχε ούτε στα routes ούτε ως μέθοδος. Ο οδηγός πατούσε το
     * κουμπί και έπαιρνε 404, ενώ το view (`vehicle-experience.php`) και ο
     * πίνακας (`driver_vehicle_experience`) υπήρχαν κανονικά.
     *
     * Το view είναι partial — γράφτηκε για να ενσωματωθεί σε φόρμα, όχι ως
     * αυτόνομη σελίδα. Εδώ τυλίγεται σε header/footer ώστε να στέκει μόνο
     * του, με τα ίδια δεδομένα που έχει και η φόρμα επεξεργασίας.
     */
    public function vehicleExperience()
    {
        AuthMiddleware::hasRole('driver');

        $driverId = Session::get('user_id');

        try {
            $driverProfile = $this->driverProfileService->getDriverProfile($driverId);

            if (!$driverProfile) {
                Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
                header('Location: ' . BASE_URL . 'drivers/edit-profile');
                exit();
            }

            /*
             * Νέο μοντέλο (25/08/2026): η σελίδα ΔΕΝ είναι πια μέρος της
             * μεγάλης φόρμας update-profile. Κάθε εγγραφή αποθηκεύεται τη
             * στιγμή της προσθήκης (POST /drivers/vehicle-experience) και
             * διαγράφεται επιτόπου — κανένα «Αποθήκευση Αλλαγών», καμία
             * αλλαγή σελίδας. Ο πίνακας ζωγραφίζεται από τη βάση.
             */
            $rows = $driverProfile['vehicle_experience'] ?? [];
            $totals = self::vehicleExperienceTotals($rows);
            $pageTitle = 'Προϋπηρεσία σε Οχήματα';

            include ROOT_DIR . '/src/Views/partials/header.php';
            include ROOT_DIR . '/src/Views/drivers/vehicle-experience.php';
            include ROOT_DIR . '/src/Views/partials/footer.php';
        } catch (\Exception $e) {
            Logger::error('Error in vehicle experience page', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
    }

    /**
     * POST /drivers/vehicle-experience — προσθήκη ΜΙΑΣ εγγραφής προϋπηρεσίας.
     *
     * Αποθηκεύει τη στιγμή που ο χρήστης πατά «Προσθήκη»: επιστρέφει JSON
     * με τη σωσμένη γραμμή (με ονόματα για εμφάνιση) και τα νέα σύνολα.
     */
    public function addVehicleExperience()
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');

        $category = $this->sanitize($_POST['vehicle_category'] ?? '');
        $type = $this->sanitize($_POST['vehicle_type'] ?? '');
        $employment = $this->sanitize($_POST['employment_type'] ?? '');
        $startDate = $this->sanitizeDate($_POST['start_date'] ?? null);
        $endDate = $this->sanitizeDate($_POST['end_date'] ?? null);
        $description = $this->sanitize($_POST['description'] ?? '');

        // Allowlist από τη μία πηγή αλήθειας — όχι ελεύθερες τιμές στη βάση.
        if (!\Drivejob\Helpers\VehicleExperienceTypes::isValid($category, $type)) {
            JsonHelper::error('Επιλέξτε τύπο οχήματος από τη λίστα.');
        }
        if (!isset(\Drivejob\Helpers\VehicleExperienceTypes::EMPLOYMENT_LABELS[$employment])) {
            $employment = 'employee';
        }
        if (!$startDate) {
            JsonHelper::error('Συμπληρώστε την ημερομηνία έναρξης.');
        }
        if ($startDate > date('Y-m-d')) {
            JsonHelper::error('Η ημερομηνία έναρξης δεν μπορεί να είναι στο μέλλον.');
        }
        if ($endDate && $endDate < $startDate) {
            JsonHelper::error('Η ημερομηνία λήξης πρέπει να είναι μετά την έναρξη.');
        }

        // Διάρκεια από τις πραγματικές ημερομηνίες (κενό τέλος = έως σήμερα).
        $diff = (new \DateTime($startDate))->diff(new \DateTime($endDate ?: date('Y-m-d')));

        $transport = \Drivejob\Helpers\VehicleExperienceTypes::transportOfCategory($category);
        $skillModel = new \Drivejob\Models\Driver\SkillModel($this->container->get('pdo'));
        $newId = $skillModel->addDriverVehicleExperience($driverId, [
            'vehicle_category' => $category,
            'vehicle_type' => $type,
            'transport_type' => $transport,
            'employment_type' => $employment,
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $description,
        ]);

        if ($newId === false) {
            JsonHelper::error('Η αποθήκευση απέτυχε. Δοκιμάστε ξανά.');
        }

        $rows = $skillModel->getDriverVehicleExperience($driverId);

        JsonHelper::success('Η προϋπηρεσία αποθηκεύτηκε.', [
            'row' => [
                'id' => $newId,
                'type_label' => \Drivejob\Helpers\VehicleExperienceTypes::typeLabel($category, $type),
                'category_label' => \Drivejob\Helpers\VehicleExperienceTypes::categoryLabel($category),
                'transport_label' => \Drivejob\Helpers\VehicleExperienceTypes::transportLabel($transport),
                'duration' => self::formatDuration($diff->y, $diff->m, $diff->d),
                'period' => date('d/m/Y', strtotime($startDate)) . ' — '
                    . ($endDate ? date('d/m/Y', strtotime($endDate)) : 'σήμερα'),
            ],
            'totals' => self::vehicleExperienceTotals($rows),
        ]);
    }

    /**
     * POST /drivers/vehicle-experience/delete/{id} — διαγραφή μίας εγγραφής.
     */
    public function deleteVehicleExperience($id)
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');
        $skillModel = new \Drivejob\Models\Driver\SkillModel($this->container->get('pdo'));

        if (!$skillModel->deleteDriverVehicleExperienceRow($driverId, (int) $id)) {
            JsonHelper::error('Η εγγραφή δεν βρέθηκε.');
        }

        $rows = $skillModel->getDriverVehicleExperience($driverId);

        JsonHelper::success('Η εγγραφή διαγράφηκε.', [
            'totals' => self::vehicleExperienceTotals($rows),
        ]);
    }

    /** Θεματολογίες σεμιναρίων/πιστοποιητικών — μία πηγή για UI και έλεγχο. */
    /*
     * Οι θεματολογίες μετακινήθηκαν στον κατάλογο (lookup_values) 30/08 —
     * τις συντηρεί ο διαχειριστής. Ο τύπος μεταφοράς μένει σταθερός:
     * τρεις τιμές πάνω στις οποίες υπολογίζει το ταίριασμα.
     */

    /**
     * GET /drivers/certifications — σελίδα σεμιναρίων & πιστοποιητικών.
     * Ίδιο μοτίβο με την προϋπηρεσία: server-rendered λίστα, άμεση
     * αποθήκευση ανά εγγραφή, κανένα «Αποθήκευση Αλλαγών».
     */
    public function certifications()
    {
        AuthMiddleware::hasRole('driver');
        $driverId = Session::get('user_id');

        try {
            $certModel = new \Drivejob\Models\Driver\CertificationModel($this->container->get('pdo'));
            $rows = $certModel->getDriverCertifications($driverId) ?: [];
            $categories = \Drivejob\Helpers\CertificationCategories::options();
            $transports = \Drivejob\Helpers\CertificationCategories::TRANSPORT;
            $pageTitle = 'Σεμινάρια & Πιστοποιητικά';

            include ROOT_DIR . '/src/Views/partials/header.php';
            include ROOT_DIR . '/src/Views/drivers/certifications.php';
            include ROOT_DIR . '/src/Views/partials/footer.php';
        } catch (\Exception $e) {
            Logger::error('Error in certifications page', ['driver_id' => $driverId, 'message' => $e->getMessage()]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
    }

    /** POST /drivers/certifications — προσθήκη ΜΙΑΣ πιστοποίησης (με προαιρετικό αρχείο). */
    public function addCertification()
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');

        $title = trim($this->sanitize($_POST['title'] ?? ''));
        $provider = trim($this->sanitize($_POST['provider'] ?? ''));
        $category = $_POST['category'] ?? '';
        $transport = $_POST['transport_type'] ?? 'both';
        $date = $this->sanitizeDate($_POST['date'] ?? null);
        $expiry = $this->sanitizeDate($_POST['expiry'] ?? null);
        $duration = ($_POST['duration'] ?? '') !== '' ? max(0, (int) $_POST['duration']) : null;
        $description = trim($this->sanitize($_POST['description'] ?? ''));

        if ($title === '' || mb_strlen($title) > 255) {
            JsonHelper::error('Συμπληρώστε τον τίτλο της πιστοποίησης (έως 255 χαρακτήρες).');
        }
        if ($category !== '' && !\Drivejob\Helpers\CertificationCategories::isValid($category)) {
            JsonHelper::error('Επιλέξτε θεματολογία από τη λίστα.');
        }
        if (!isset(\Drivejob\Helpers\CertificationCategories::TRANSPORT[$transport])) {
            $transport = 'both';
        }
        if ($date && $expiry && $expiry < $date) {
            JsonHelper::error('Η λήξη πρέπει να είναι μετά την ημερομηνία απόκτησης.');
        }

        // Προαιρετικό αρχείο βεβαίωσης — μέσω του υπάρχοντος FileService
        // (έλεγχος MIME/μεγέθους, αποθήκευση στο storage/uploads/certificates).
        $filePath = null;
        if (!empty($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileService = new \Drivejob\Services\FileService();
            $result = $fileService->uploadFile($_FILES['certificate_file'], 'certificate_file', 'all');
            if (empty($result['success'])) {
                JsonHelper::error('Το αρχείο δεν ανέβηκε: ' . ($result['message'] ?? 'άγνωστο σφάλμα'));
            }
            $filePath = $result['file_path'];
        }

        $certModel = new \Drivejob\Models\Driver\CertificationModel($this->container->get('pdo'));
        $newId = $certModel->addDriverCertification($driverId, [
            'title' => $title,
            'provider' => $provider !== '' ? $provider : null,
            'category' => $category !== '' ? $category : null,
            'transport_type' => $transport,
            'date' => $date,
            'expiry' => $expiry,
            'duration' => $duration,
            'description' => $description !== '' ? $description : null,
            'certificate_file' => $filePath,
        ]);

        if ($newId === false) {
            JsonHelper::error('Η αποθήκευση απέτυχε. Δοκιμάστε ξανά.');
        }

        $expired = $expiry !== null && $expiry < date('Y-m-d');

        JsonHelper::success('Η πιστοποίηση αποθηκεύτηκε.', [
            'row' => [
                'id' => $newId,
                'title' => $title,
                'provider' => $provider,
                'category_label' => $category !== '' ? \Drivejob\Helpers\CertificationCategories::label($category) : '',
                'transport_label' => \Drivejob\Helpers\CertificationCategories::transportLabel($transport),
                'date' => $date ? date('d/m/Y', strtotime($date)) : '',
                'expiry' => $expiry ? date('d/m/Y', strtotime($expiry)) : '',
                'expired' => $expired,
                'duration' => $duration,
                'file_url' => $filePath ? BASE_URL . $filePath : null,
            ],
        ]);
    }

    /**
     * POST /drivers/certifications/update/{id} — διόρθωση πιστοποίησης.
     *
     * Έλειπε εντελώς (30/08): ο οδηγός μπορούσε μόνο να προσθέσει ή να
     * διαγράψει — ένα λάθος στην ημερομηνία σήμαινε διαγραφή και εκ νέου
     * καταχώρηση, με το αρχείο της βεβαίωσης να ανεβαίνει ξανά.
     */
    public function updateCertification($id)
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');

        $title = trim($this->sanitize($_POST['title'] ?? ''));
        $provider = trim($this->sanitize($_POST['provider'] ?? ''));
        $category = $_POST['category'] ?? '';
        $transport = $_POST['transport_type'] ?? 'both';
        $date = $this->sanitizeDate($_POST['date'] ?? null);
        $expiry = $this->sanitizeDate($_POST['expiry'] ?? null);
        $duration = ($_POST['duration'] ?? '') !== '' ? max(0, (int) $_POST['duration']) : null;
        $description = trim($this->sanitize($_POST['description'] ?? ''));

        if ($title === '' || mb_strlen($title) > 255) {
            JsonHelper::error('Συμπληρώστε τον τίτλο της πιστοποίησης (έως 255 χαρακτήρες).');
        }
        if ($category !== '' && !\Drivejob\Helpers\CertificationCategories::isValid($category)) {
            JsonHelper::error('Επιλέξτε θεματολογία από τη λίστα.');
        }
        if (!isset(\Drivejob\Helpers\CertificationCategories::TRANSPORT[$transport])) {
            $transport = 'both';
        }
        if ($date && $expiry && $expiry < $date) {
            JsonHelper::error('Η λήξη πρέπει να είναι μετά την ημερομηνία απόκτησης.');
        }

        // Νέο αρχείο μόνο αν ανέβηκε — αλλιώς κρατιέται το υπάρχον.
        $filePath = null;
        if (!empty($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileService = new \Drivejob\Services\FileService();
            $result = $fileService->uploadFile($_FILES['certificate_file'], 'certificate_file', 'all');
            if (empty($result['success'])) {
                JsonHelper::error('Το αρχείο δεν ανέβηκε: ' . ($result['message'] ?? 'άγνωστο σφάλμα'));
            }
            $filePath = $result['file_path'];
        }

        $certModel = new \Drivejob\Models\Driver\CertificationModel($this->container->get('pdo'));
        $ok = $certModel->updateDriverCertificationRow($driverId, (int) $id, [
            'title' => $title,
            'provider' => $provider !== '' ? $provider : null,
            'category' => $category !== '' ? $category : null,
            'transport_type' => $transport,
            'date' => $date,
            'expiry' => $expiry,
            'duration' => $duration,
            'description' => $description !== '' ? $description : null,
            'certificate_file' => $filePath,
        ]);

        if (!$ok) {
            JsonHelper::error('Η πιστοποίηση δεν βρέθηκε ή δεν ήταν δυνατή η αποθήκευση.');
        }

        JsonHelper::success('Η πιστοποίηση ενημερώθηκε.', [
            'row' => [
                'id' => (int) $id,
                'title' => $title,
                'provider' => $provider,
                'category' => $category,
                'category_label' => $category !== '' ? \Drivejob\Helpers\CertificationCategories::label($category) : '',
                'transport_type' => $transport,
                'transport_label' => \Drivejob\Helpers\CertificationCategories::transportLabel($transport),
                'date' => $date ? date('d/m/Y', strtotime($date)) : '',
                'date_raw' => $date,
                'expiry' => $expiry ? date('d/m/Y', strtotime($expiry)) : '',
                'expiry_raw' => $expiry,
                'expired' => $expiry !== null && $expiry < date('Y-m-d'),
                'duration' => $duration,
                'description' => $description,
            ],
        ]);
    }

    /** POST /drivers/certifications/delete/{id} — διαγραφή μίας πιστοποίησης. */
    public function deleteCertification($id)
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');
        $certModel = new \Drivejob\Models\Driver\CertificationModel($this->container->get('pdo'));

        if (!$certModel->deleteDriverCertificationRow($driverId, (int) $id)) {
            JsonHelper::error('Η εγγραφή δεν βρέθηκε.');
        }

        JsonHelper::success('Η πιστοποίηση διαγράφηκε.');
    }

    /** Ετικέτες επιπέδων γλώσσας — μία πηγή για UI και JSON. */
    private const LANGUAGE_LEVELS = [
        'native' => 'Μητρική Γλώσσα',
        'fluent' => 'Άριστα',
        'good' => 'Καλά',
        'basic' => 'Βασικά',
    ];

    /**
     * POST /drivers/languages — προσθήκη/ενημέρωση ΜΙΑΣ γλώσσας.
     * Ίδια φιλοσοφία με την προϋπηρεσία: αποθήκευση τη στιγμή της πράξης.
     */
    public function addLanguage()
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');
        $name = trim($this->sanitize($_POST['language_name'] ?? ''));
        $level = $_POST['level'] ?? '';

        if ($name === '' || mb_strlen($name) > 50) {
            JsonHelper::error('Γράψτε το όνομα της γλώσσας (έως 50 χαρακτήρες).');
        }
        if (!isset(self::LANGUAGE_LEVELS[$level])) {
            JsonHelper::error('Επιλέξτε επίπεδο γνώσης.');
        }

        // Κανονικοποίηση: πρώτο γράμμα κεφαλαίο, ώστε «αγγλικά» και
        // «Αγγλικά» να είναι η ίδια εγγραφή (το unique key ολοκληρώνει).
        $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);

        $skillModel = new \Drivejob\Models\Driver\SkillModel($this->container->get('pdo'));
        $rowId = $skillModel->addDriverLanguage($driverId, $name, $level);

        if ($rowId === false) {
            JsonHelper::error('Η αποθήκευση απέτυχε. Δοκιμάστε ξανά.');
        }

        JsonHelper::success('Η γλώσσα αποθηκεύτηκε.', [
            'row' => [
                'id' => $rowId,
                'name' => $name,
                'level' => $level,
                'level_label' => self::LANGUAGE_LEVELS[$level],
            ],
        ]);
    }

    /** POST /drivers/languages/delete/{id} — διαγραφή μίας γλώσσας. */
    public function deleteLanguage($id)
    {
        AuthMiddleware::hasRole('driver');

        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            JsonHelper::error('Η φόρμα έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.');
        }

        $driverId = Session::get('user_id');
        $skillModel = new \Drivejob\Models\Driver\SkillModel($this->container->get('pdo'));

        if (!$skillModel->deleteDriverLanguage($driverId, (int) $id)) {
            JsonHelper::error('Η εγγραφή δεν βρέθηκε.');
        }

        JsonHelper::success('Η γλώσσα διαγράφηκε.');
    }

    /**
     * Σύνολα προϋπηρεσίας ανά είδος μεταφοράς + γενικό, με κανονικοποίηση
     * (30 ημέρες → μήνας, 12 μήνες → έτος).
     */
    private static function vehicleExperienceTotals(array $rows): array
    {
        $sum = [
            'freight' => ['y' => 0, 'm' => 0, 'd' => 0],
            'passenger' => ['y' => 0, 'm' => 0, 'd' => 0],
        ];

        foreach ($rows as $row) {
            $key = ($row['transport_type'] ?? 'freight') === 'passenger' ? 'passenger' : 'freight';
            $sum[$key]['y'] += (int) ($row['years'] ?? 0);
            $sum[$key]['m'] += (int) ($row['months'] ?? 0);
            $sum[$key]['d'] += (int) ($row['days'] ?? 0);
        }

        $normalize = static function (array $t): array {
            $t['m'] += intdiv($t['d'], 30);
            $t['d'] %= 30;
            $t['y'] += intdiv($t['m'], 12);
            $t['m'] %= 12;

            return $t;
        };

        $freight = $normalize($sum['freight']);
        $passenger = $normalize($sum['passenger']);
        $all = $normalize([
            'y' => $sum['freight']['y'] + $sum['passenger']['y'],
            'm' => $sum['freight']['m'] + $sum['passenger']['m'],
            'd' => $sum['freight']['d'] + $sum['passenger']['d'],
        ]);

        return [
            'freight' => self::formatDuration($freight['y'], $freight['m'], $freight['d']),
            'passenger' => self::formatDuration($passenger['y'], $passenger['m'], $passenger['d']),
            'all' => self::formatDuration($all['y'], $all['m'], $all['d']),
        ];
    }

    private static function formatDuration(int $years, int $months, int $days): string
    {
        return sprintf('%d έτη, %d μήνες, %d ημέρες', $years, $months, $days);
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    /**
     * Σελίδα «Ασφάλεια & Κωδικός» — φόρμα αλλαγής κωδικού (POST στο
     * υπάρχον drivers/change-password του BaseUserController).
     */
    /**
     * GET /drivers/cv — το βιογραφικό σε PDF.
     *
     * Παράγεται ΚΑΘΕ ΦΟΡΑ από τα τρέχοντα δεδομένα, δεν αποθηκεύεται ως
     * αρχείο. Το παλιό μοντέλο («ανέβασε το CV σου», resume_file) είχε το
     * κλασικό πρόβλημα του αντιγράφου: ο οδηγός ανανέωνε το ΠΕΙ στο
     * προφίλ και έστελνε βιογραφικό που έγραφε την παλιά λήξη.
     *
     * Το ΙΔΙΟ DriverCvService τροφοδοτεί και την καρτέλα Επισκόπηση —
     * οθόνη και PDF δεν μπορούν να αποκλίνουν.
     */
    public function cv()
    {
        AuthMiddleware::hasRole('driver');
        $driverId = Session::get('user_id');

        try {
            $profile = $this->driverProfileService->getDriverProfile($driverId);
            if (!$profile) {
                Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
                header('Location: ' . BASE_URL . 'drivers/profile');
                exit();
            }

            $service = new \Drivejob\Services\Driver\DriverCvService();
            $options = \Drivejob\Services\Driver\DriverCvService::optionsFromProfile($profile);

            $viewData = [
                'driverData' => $profile,
                'cv' => $service->build($profile, false, $options),
                'cvOptions' => $options,
                'cvSummarySaved' => trim((string) ($profile['cv_summary'] ?? '')),
                'cvSummaryAuto' => $service->autoSummary($profile),
            ];

            extract($viewData);
            include ROOT_DIR . '/src/Views/drivers/cv.php';
        } catch (\Throwable $e) {
            Logger::error('CV screen failed', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Session::set('error_message', 'Δεν ήταν δυνατή η προβολή του βιογραφικού.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /**
     * POST /drivers/cv/settings — αποθήκευση προτιμήσεων (AJAX).
     *
     * Άμεση αποθήκευση ανά πράξη, όπως στα πιστοποιητικά και τις γλώσσες:
     * ο οδηγός γυρίζει έναν διακόπτη και το βλέπει να ισχύει, χωρίς
     * «Αποθήκευση Αλλαγών» στο τέλος μιας οθόνης με τέσσερα πεδία.
     */
    public function saveCvSettings()
    {
        AuthMiddleware::hasRole('driver');
        $driverId = Session::get('user_id');

        // JsonHelper::error/success — ΟΧΙ send(): η μέθοδος δεν υπάρχει και
        // ο ExceptionHandler γύριζε ολόκληρη σελίδα HTML αντί για JSON,
        // οπότε το fetch έσκαγε σιωπηλά με «Πρόβλημα σύνδεσης».
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            JsonHelper::error('Η σελίδα έληξε. Ανανεώστε και δοκιμάστε ξανά.');
        }

        try {
            $data = [
                // Το κείμενο κόβεται στους 600 χαρακτήρες: μια σύνοψη
                // βιογραφικού που δεν χωρά σε παράγραφο δεν είναι σύνοψη.
                'cv_summary' => mb_substr(trim((string) ($_POST['cv_summary'] ?? '')), 0, 600),
                'cv_show_photo' => !empty($_POST['cv_show_photo']) ? 1 : 0,
                'cv_show_age' => !empty($_POST['cv_show_age']) ? 1 : 0,
                'cv_show_phone' => !empty($_POST['cv_show_phone']) ? 1 : 0,
                'cv_show_email' => !empty($_POST['cv_show_email']) ? 1 : 0,
                'cv_show_rating' => !empty($_POST['cv_show_rating']) ? 1 : 0,
            ];

            // Ο έλεγχος του αποτελέσματος ΔΕΝ είναι τυπικός: το model
            // επέστρεφε false σιωπηλά σε μερική ενημέρωση και ο χρήστης
            // έβλεπε «Αποθηκεύτηκε» χωρίς να έχει γραφτεί τίποτα.
            if (!$this->driverProfileService->updateBasicInfo($driverId, $data)) {
                JsonHelper::error('Δεν αποθηκεύτηκε. Δοκιμάστε ξανά.');
            }
            JsonHelper::success('Αποθηκεύτηκε.');
        } catch (\Throwable $e) {
            Logger::error('CV settings save failed', ['driver_id' => $driverId, 'message' => $e->getMessage()]);
            JsonHelper::error('Δεν αποθηκεύτηκε. Δοκιμάστε ξανά.');
        }
    }

    /** GET /drivers/cv/pdf — το αρχείο. */
    public function cvPdf()
    {
        AuthMiddleware::hasRole('driver');
        $driverId = Session::get('user_id');

        try {
            $profile = $this->driverProfileService->getDriverProfile($driverId);
            if (!$profile) {
                Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
                header('Location: ' . BASE_URL . 'drivers/profile');
                exit();
            }

            // Δημόσια όψη: το «τι λείπει» αφορά μόνο τον ίδιο τον οδηγό,
            // δεν έχει καμία θέση σε βιογραφικό που πάει σε εργοδότη.
            $options = \Drivejob\Services\Driver\DriverCvService::optionsFromProfile($profile);
            $cvData = (new \Drivejob\Services\Driver\DriverCvService())->build($profile, false, $options);
            $bytes = (new \Drivejob\Services\Driver\DriverCvPdf($cvData, $profile))->render();

            $name = trim(($profile['first_name'] ?? '') . '_' . ($profile['last_name'] ?? ''));
            // Ο τίτλος γίνεται όνομα αρχείου: μόνο ασφαλείς χαρακτήρες,
            // αλλιώς σπάει το Content-Disposition σε ελληνικά ονόματα.
            $safe = preg_replace('/[^A-Za-z0-9_\-]/', '', $this->latinize($name)) ?: 'driver';

            if (ob_get_length()) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="drivejob_cv_' . $safe . '.pdf"');
            header('Content-Length: ' . strlen($bytes));
            echo $bytes;
            exit();
        } catch (\Throwable $e) {
            Logger::error('CV PDF failed', [
                'driver_id' => $driverId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Session::set('error_message', 'Δεν ήταν δυνατή η δημιουργία του βιογραφικού. Δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /** Έγκυρη συντεταγμένη ή null — ποτέ σκουπίδι στη βάση. */
    private function coordinate($value, float $max): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $n = (float) $value;
        return ($n >= -$max && $n <= $max && $n != 0.0) ? $n : null;
    }

    /** Ελληνικά σε λατινικά, μόνο για όνομα αρχείου. */
    private function latinize(string $text): string
    {
        $map = [
            'α'=>'a','ά'=>'a','β'=>'v','γ'=>'g','δ'=>'d','ε'=>'e','έ'=>'e','ζ'=>'z','η'=>'i','ή'=>'i',
            'θ'=>'th','ι'=>'i','ί'=>'i','ϊ'=>'i','ΐ'=>'i','κ'=>'k','λ'=>'l','μ'=>'m','ν'=>'n','ξ'=>'x',
            'ο'=>'o','ό'=>'o','π'=>'p','ρ'=>'r','σ'=>'s','ς'=>'s','τ'=>'t','υ'=>'y','ύ'=>'y','ϋ'=>'y',
            'ΰ'=>'y','φ'=>'f','χ'=>'ch','ψ'=>'ps','ω'=>'o','ώ'=>'o',
        ];
        $lower = mb_strtolower($text, 'UTF-8');
        return strtr($lower, $map);
    }

    public function security()
    {
        AuthMiddleware::hasRole('driver');
        require ROOT_DIR . '/src/Views/drivers/security.php';
    }

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

        /*
         * Επικύρωση ΜΟΝΟ των πεδίων που ήρθαν — ίδιος κανόνας με το
         * collectFormData («πεδίο που δεν ήρθε δεν γράφεται»). Το
         * update-profile δέχεται POST και από μερικές φόρμες (π.χ. τη
         * σελίδα προϋπηρεσίας οχημάτων) που δεν κουβαλούν όνομα/τηλέφωνο·
         * το παλιό ανεξαίρετο required τις έκοβε όλες στο validation και
         * η αποθήκευση φαινόταν να «μην κρατάει» τίποτα.
         */
        $validator = new Validator($_POST);
        if (array_key_exists('first_name', $_POST)) {
            $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.');
        }
        if (array_key_exists('last_name', $_POST)) {
            $validator->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.');
        }
        if (array_key_exists('phone', $_POST)) {
            $validator->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        }

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

                // Ενημέρωση αδειών & πιστοποιητικών (ADR, ταχογράφος, χειριστής κ.λπ.)
                // Τα unchecked checkboxes ΔΕΝ έρχονται στο POST — οι handlers
                // διαγράφουν τα αντίστοιχα στοιχεία όταν λείπει το πεδίο.
                $licenseResults = [
                    'driving_licenses' => $this->driverLicenseService->handleDrivingLicenses($driverId, $_POST),
                    'adr'              => $this->driverLicenseService->handleADRCertificate($driverId, $_POST),
                    'tachograph'       => $this->driverLicenseService->handleTachographCard($driverId, $_POST),
                    'special_licenses' => $this->driverLicenseService->handleSpecialLicenses($driverId, $_POST),
                    'operator'         => $this->driverLicenseService->handleOperatorLicense($driverId, $_POST),
                ];
                foreach ($licenseResults as $section => $ok) {
                    if (!$ok) {
                        Logger::error('License section update failed', ['section' => $section, 'driver_id' => $driverId]);
                    }
                }
                if (in_array(false, $licenseResults, true)) {
                    Session::set('warning_message', 'Το προφίλ αποθηκεύτηκε, αλλά κάποια στοιχεία αδειών/πιστοποιητικών δεν ενημερώθηκαν. Ελέγξτε την καρτέλα αδειών.');
                }

                /*
                 * Προϋπηρεσία οχημάτων — ΜΟΝΟ όταν η φόρμα δηλώνει ρητά την
                 * ενότητα (κρυφό πεδίο vehicle_experience_submitted). Έτσι:
                 *  - η σελίδα προϋπηρεσίας αποθηκεύει (μέχρι σήμερα ΚΑΝΕΙΣ
                 *    δεν διάβαζε το vehicle_experience[] — η μέθοδος του
                 *    μοντέλου υπήρχε αλλά δεν καλούνταν από πουθενά)
                 *  - το άδειασμα ΟΛΩΝ των γραμμών όντως τις διαγράφει
                 *  - μια φόρμα ΧΩΡΙΣ την ενότητα δεν σβήνει ό,τι υπάρχει.
                 */
                if (isset($_POST['vehicle_experience_submitted'])) {
                    $skillModel = new \Drivejob\Models\Driver\SkillModel($this->container->get('pdo'));
                    $veOk = $skillModel->updateDriverVehicleExperience(
                        $driverId,
                        is_array($_POST['vehicle_experience'] ?? null) ? $_POST['vehicle_experience'] : []
                    );
                    if (!$veOk) {
                        Logger::error('Vehicle experience update failed', ['driver_id' => $driverId]);
                        Session::set('warning_message', 'Το προφίλ αποθηκεύτηκε, αλλά η προϋπηρεσία οχημάτων δεν ενημερώθηκε.');
                    }
                }

                /*
                 * Δεξιότητες (checkboxes) — ΜΟΝΟ όταν η καρτέλα ήρθε
                 * (skills_submitted). Χωρίς τον φρουρό, ένα POST χωρίς την
                 * καρτέλα θα μηδένιζε όλες τις δεξιότητες: unchecked
                 * checkbox και απών checkbox μοιάζουν ολόιδια στο POST.
                 */
                if (isset($_POST['skills_submitted'])) {
                    $certService = new \Drivejob\Services\Driver\DriverCertificationService($this->container->get('pdo'));
                    if (!$certService->updateSkillCheckboxes($driverId, $_POST)) {
                        Logger::error('Skill checkboxes update failed', ['driver_id' => $driverId]);
                        Session::set('warning_message', 'Το προφίλ αποθηκεύτηκε, αλλά οι δεξιότητες δεν ενημερώθηκαν.');
                    }
                }

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

        // Πίσω στη σελίδα απ' όπου ήρθε η αποθήκευση: η φόρμα προϋπηρεσίας
        // γυρίζει στη σελίδα της (να φανεί ο ενημερωμένος πίνακας), οι
        // υπόλοιπες στο προφίλ.
        if (isset($_POST['vehicle_experience_submitted'])) {
            header('Location: ' . BASE_URL . 'drivers/vehicle-experience');
        } else {
            header('Location: ' . BASE_URL . 'drivers/profile');
        }
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
        /*
         * ΕΛΕΓΧΟΣ ΠΡΟΣΒΑΣΗΣ — προστέθηκε στο πακέτο ορατότητας.
         *
         * Μέχρι τώρα αυτή η σελίδα ήταν εντελώς δημόσια: οποιοσδήποτε στο
         * διαδίκτυο, χωρίς λογαριασμό, έβλεπε ονοματεπώνυμο, email και
         * τηλέφωνο του οδηγού ως clickable mailto: και tel:. Ένα bot μπορούσε
         * να συλλέξει ολόκληρο τον κατάλογο.
         *
         * Πλέον το προφίλ ενός οδηγού το βλέπουν μόνο ο ίδιος, οι εταιρείες
         * που έλαβαν αίτησή του, και οι διαχειριστές.
         */
        $visibility = new \Drivejob\Services\Visibility($this->container->get('pdo'));

        if (!$visibility->canViewDriverProfile(
            Session::get('user_role'),
            Session::get('user_id'),
            (int) $id
        )) {
            Session::set('error_message', Session::has('user_id')
                ? 'Το προφίλ του οδηγού είναι διαθέσιμο μόνο σε εταιρείες που έχουν λάβει αίτησή του.'
                : 'Συνδέσου για να δεις προφίλ οδηγών.');
            header('Location: ' . BASE_URL . (Session::has('user_id') ? '' : 'login'));
            exit;
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό οδηγού');
            header('Location: ' . BASE_URL);
            exit;
        }

        // Ανάκτηση των στοιχείων του οδηγού με τη νέα υπηρεσία
        $driverProfile = $this->driverProfileService->getDriverProfile($id);

        if (!$driverProfile) {
            Session::set('error_message', 'Ο οδηγός δεν βρέθηκε');
            header('Location: ' . BASE_URL);
            exit;
        }

        // Αντιστοίχιση μεταβλητών για συμβατότητα με το view
        $driverData = $driverProfile;

        /*
         * ══════════════════════════════════════════════════════════════════
         *  ΜΑΣΚΑΡΙΣΜΑ ΕΠΙΚΟΙΝΩΝΙΑΣ — ΣΤΟΝ CONTROLLER, ΟΧΙ ΣΤΟ VIEW
         * ══════════════════════════════════════════════════════════════════
         *
         * Το view έδειχνε email και τηλέφωνο ως clickable mailto:/tel: σε
         * όποιον περνούσε τον έλεγχο προφίλ — δηλαδή σε κάθε εταιρεία με
         * μία απλή αίτηση, πριν από κάθε shortlist. Το συμφωνημένο μοντέλο
         * λέει: πλήρη στοιχεία ΜΟΝΟ μετά την προεπιλογή ή την αποδοχή
         * προσφοράς.
         *
         * Το μασκάρισμα γίνεται εδώ, πριν φτάσουν τα δεδομένα στο view,
         * ώστε ΚΑΝΕΝΑ μονοπάτι του template να μην μπορεί να δείξει την
         * πλήρη τιμή — ούτε το mailto, ούτε κάποιο σχόλιο, ούτε μελλοντική
         * προσθήκη που θα ξεχάσει τον κανόνα.
         */
        $canViewContact = $visibility->canViewDriverContact(
            Session::get('user_role'),
            Session::get('user_id'),
            (int) $id
        );

        if (!$canViewContact) {
            $driverData['email'] = \Drivejob\Services\Visibility::maskEmail($driverData['email'] ?? null);
            $driverData['phone'] = \Drivejob\Services\Visibility::maskPhone($driverData['phone'] ?? null);
            $driverData['landline'] = \Drivejob\Services\Visibility::maskPhone($driverData['landline'] ?? null);
            // Η ακριβής διεύθυνση δεν έχει καμία δουλειά πριν την πρόσληψη.
            unset($driverData['address'], $driverData['address_number'],
                  $driverData['house_number'], $driverData['latitude'], $driverData['longitude']);
        }

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
    /**
     * Τα δεδομένα της φόρμας επεξεργασίας προφίλ.
     *
     * ══════════════════════════════════════════════════════════════════════
     *  ΕΝΤΕΚΑ ΠΕΔΙΑ ΠΕΤΙΟΝΤΑΝ ΣΙΩΠΗΛΑ
     * ══════════════════════════════════════════════════════════════════════
     *
     * Η φόρμα ζητούσε ηλικία, οικογενειακή κατάσταση, στρατιωτικές
     * υποχρεώσεις, μόρφωση, χρόνια εμπειρίας, σύντομο βιογραφικό, σταθερό
     * τηλέφωνο, αριθμό οικίας και τέσσερα κοινωνικά δίκτυα. Ο χρήστης τα
     * συμπλήρωνε, πατούσε «Αποθήκευση Αλλαγών», έβλεπε μήνυμα επιτυχίας —
     * και τα πεδία γύριζαν κενά.
     *
     * Η αιτία: αυτή η μέθοδος συνέλεγε ΜΟΝΟ έντεκα από τα εικοσιδύο πεδία.
     * Τα υπόλοιπα δεν έφταναν ποτέ στο μοντέλο. Οι στήλες υπήρχαν κανονικά
     * στη βάση — απλά δεν τις έγραφε κανείς.
     *
     * ΚΑΙ ΕΝΑ ΔΕΥΤΕΡΟ, ΠΙΟ ΥΠΟΥΛΟ: η ημερομηνία γέννησης γραφόταν στη
     * στήλη `date_of_birth`, ενώ η φόρμα τη διάβαζε από τη στήλη
     * `birth_date`. Δύο στήλες, ίδιο νόημα. Η τιμή αποθηκευόταν σωστά και
     * δεν ξαναεμφανιζόταν ποτέ. Πλέον γράφονται ΚΑΙ ΟΙ ΔΥΟ, ώσπου να
     * καταργηθεί η μία με migration.
     *
     * ΚΑΝΟΝΑΣ ΓΙΑ ΤΟ ΜΕΛΛΟΝ: κάθε `name="..."` που προσθέτεις στη φόρμα
     * πρέπει να εμφανιστεί και εδώ. Αλλιώς το πεδίο υπάρχει μόνο οπτικά.
     */
    private function collectFormData()
    {
        $birthDate = $this->sanitizeDate($_POST['birth_date'] ?? null);

        $raw = [
            // ── Ταυτότητα & επικοινωνία ──────────────────────────────
            'email' => $this->sanitize($_POST['email'] ?? null),
            'first_name' => $this->sanitize($_POST['first_name'] ?? null),
            'last_name' => $this->sanitize($_POST['last_name'] ?? null),
            'phone' => $this->sanitize($_POST['phone'] ?? null),
            'landline' => $this->sanitize($_POST['landline'] ?? null),
            'address' => $this->sanitize($_POST['address'] ?? null),
            'house_number' => $this->sanitize($_POST['house_number'] ?? null),
            'city' => $this->sanitize($_POST['city'] ?? null),
            'country' => $this->sanitize($_POST['country'] ?? null),
            'postal_code' => $this->sanitize($_POST['postal_code'] ?? null),

            // ── Προσωπικά στοιχεία ───────────────────────────────────
            // Και οι δύο στήλες: η φόρμα διαβάζει birth_date, ο παλιός
            // κώδικας έγραφε date_of_birth.
            'birth_date' => $birthDate,
            'date_of_birth' => $birthDate,
            'marital_status' => $this->sanitize($_POST['marital_status'] ?? null),
            'military_service' => $this->sanitize($_POST['military_service'] ?? null),
            'education_level' => $this->sanitize($_POST['education_level'] ?? null),
            'about_me' => $this->sanitize($_POST['about_me'] ?? null),

            // Τα χρόνια εμπειρίας είναι αριθμός· κενό σημαίνει «δεν δήλωσε»,
            // όχι μηδέν — γι' αυτό μένει null αντί για 0.
            'experience_years' => ($_POST['experience_years'] ?? '') === ''
                ? null
                : max(0, (int) $_POST['experience_years']),

            // ── Κοινωνικά δίκτυα ─────────────────────────────────────
            'social_facebook' => $this->sanitizeUrl($_POST['social_facebook'] ?? null),
            'social_instagram' => $this->sanitizeUrl($_POST['social_instagram'] ?? null),
            'social_linkedin' => $this->sanitizeUrl($_POST['social_linkedin'] ?? null),
            'social_twitter' => $this->sanitizeUrl($_POST['social_twitter'] ?? null),

            // ── Κατάσταση ────────────────────────────────────────────
            'legal_status' => (($_POST['legal_status'] ?? '') === 'yes') ? 'yes' : 'no',
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,

            /*
             * ── Περιοχή εργασίας (30/08) ──────────────────────────────
             *
             * Στήλες που υπήρχαν στη βάση από την αρχή και τις διάβαζε το
             * ταίριασμα, αλλά ΔΕΝ γράφονταν από πουθενά: δεν υπήρχαν στη
             * φόρμα. Έμεναν 0 και το MatchingModel έπεφτε σε προεπιλογή
             * 50 χλμ για όλους.
             *
             * Τα δύο checkbox ακολουθούν τον κανόνα «ό,τι δεν ήρθε δεν το
             * αγγίζουμε» μέσω του κρυφού δείκτη reach_section: χωρίς
             * αυτόν, μια αποθήκευση από άλλη καρτέλα θα τα μηδένιζε.
             */
            'preferred_radius' => isset($_POST['preferred_radius'])
                ? max(0, (int) $_POST['preferred_radius'])
                : null,
            'willing_to_travel' => isset($_POST['reach_section'])
                ? (isset($_POST['willing_to_travel']) ? 1 : 0)
                : null,
            'willing_to_relocate' => isset($_POST['reach_section'])
                ? (isset($_POST['willing_to_relocate']) ? 1 : 0)
                : null,

            /*
             * Συντεταγμένες έδρας — τις γεμίζει ΜΟΝΟΣ του ο χάρτης της
             * ακτίνας (work-radius.js → Google Geocoder) και έρχονται σε
             * κρυφά πεδία. Ήταν NULL για κάθε οδηγό, ενώ το MatchingModel
             * τις διαβάζει για να υπολογίσει απόσταση.
             *
             * Δεν εμπιστευόμαστε ό,τι έρθει: εκτός εύρους → null. Ένα
             * χαλασμένο ζεύγος θα έβαζε τον οδηγό στη μέση του ωκεανού
             * και θα τον έκοβε από κάθε ταίριασμα.
             */
            'latitude' => $this->coordinate($_POST['latitude'] ?? null, 90),
            'longitude' => $this->coordinate($_POST['longitude'] ?? null, 180),
            // Το κλειδί που λείπει το βάζει ο server (31/08): αν ο
            // browser δεν έστειλε συντεταγμένες — JS απενεργοποιημένο,
            // παλιά συνεδρία, αποτυχία χάρτη — τις βρίσκουμε από την
            // πόλη. Χωρίς αυτές ο οδηγός είναι αόρατος στο ταίριασμα
            // απόστασης, και δεν το μαθαίνει ποτέ.
            '__geo_fallback' => true,

            /*
             * ── Στοιχεία εντύπου άδειας (πίνακας drivers) ─────────────
             *
             * ΓΙΑΤΙ ΕΔΩ ΚΑΙ ΟΧΙ ΜΟΝΟ ΣΤΟΝ DriverLicenseService: ο service
             * γράφει αριθμό/λήξη ΜΟΝΟ μέσα στις γραμμές του πίνακα
             * driver_licenses, και μόνο αν έχει τσεκαριστεί τουλάχιστον
             * μία κατηγορία. Ο οδηγός που σκάναρε το δίπλωμα, είδε τον
             * αριθμό και τη λήξη να συμπληρώνονται, και πάτησε αποθήκευση
             * ΧΩΡΙΣ να τσεκάρει κατηγορία, έχανε ΤΑ ΠΑΝΤΑ — τα πεδία δεν
             * γράφονταν πουθενά και η καρτέλα γύριζε άδεια.
             *
             * Οι στήλες υπάρχουν στον πίνακα drivers και το view διαβάζει
             * από εκεί ($driverData). Το license_codes (στήλη 12) δεν
             * αποθηκευόταν πουθενά, ποτέ.
             */
            // Η στήλη είναι UNIQUE: το κενό string πρέπει να γίνει null,
            // αλλιώς ο δεύτερος οδηγός με άδειο πεδίο σκάει σε duplicate ''.
            'license_number' => (trim((string) ($_POST['license_number'] ?? '')) === '')
                ? null
                : $this->sanitize($_POST['license_number']),
            'license_document_expiry' => $this->sanitizeDate($_POST['license_document_expiry'] ?? null),
            'license_codes' => $this->sanitize($_POST['license_codes'] ?? null),
            'driving_license' => isset($_POST['license_number']) || isset($_POST['license_types'])
                ? (isset($_POST['driving_license']) ? 1 : 0)
                : null,

            'updated_at' => date('Y-m-d H:i:s'),
        ];

        /*
         * ══════════════════════════════════════════════════════════════════
         *  ΠΕΔΙΟ ΠΟΥ ΔΕΝ ΣΤΑΛΘΗΚΕ ΔΕΝ ΣΗΜΑΙΝΕΙ «ΣΒΗΣΕ ΤΟ»
         * ══════════════════════════════════════════════════════════════════
         *
         * Η φόρμα έχει ΟΚΤΩ καρτέλες. Ο χρήστης ανοίγει μία, αλλάζει κάτι,
         * πατάει «Αποθήκευση». Τα πεδία των υπόλοιπων καρτελών δεν είναι
         * πάντα στο POST — και ο παλιός κώδικας τα έστελνε όλα ως `null`.
         *
         * Το αποτέλεσμα ήταν καταστροφικό και σιωπηλό:
         *
         *     SQLSTATE[23000]: Column 'email' cannot be null
         *
         * Η στήλη `email` είναι NOT NULL, οπότε ΟΛΟΚΛΗΡΗ η ενημέρωση
         * ματαιωνόταν. Ο χρήστης συμπλήρωνε ηλικία, οικογενειακή
         * κατάσταση, στρατιωτικές υποχρεώσεις, πατούσε αποθήκευση — και
         * τίποτα δεν γραφόταν. Ούτε ένα πεδίο.
         *
         * Και για τις στήλες που ΔΕΧΟΝΤΑΙ null, η ζημιά ήταν χειρότερη:
         * αποθήκευση από την καρτέλα «Προσωπικά» ΕΣΒΗΝΕ τη διεύθυνση, την
         * πόλη και τον ταχυδρομικό κώδικα που είχαν συμπληρωθεί αλλού.
         *
         * Ο κανόνας τώρα: γράφουμε μόνο ό,τι ΗΡΘΕ. Ό,τι λείπει μένει
         * ανέγγιχτο στη βάση.
         */
        /*
         * Server-side συμπλήρωση συντεταγμένων. Τρέχει ΜΟΝΟ όταν ο
         * browser δεν έστειλε — δεν ακυρώνει ποτέ ακριβέστερη τιμή που
         * ήρθε από τον χάρτη.
         */
        unset($raw['__geo_fallback']);
        if (empty($raw['latitude']) || empty($raw['longitude'])) {
            $place = \Drivejob\Helpers\GreekPlaces::locate(
                $raw['city'] ?? ($_POST['city'] ?? null)
            );
            if ($place) {
                $raw['latitude'] = $place[0];
                $raw['longitude'] = $place[1];
            }
        }

        $alwaysWrite = [
            // Checkbox: όταν δεν είναι τσεκαρισμένο ΔΕΝ έρχεται στο POST,
            // και τότε η τιμή 0 είναι η σωστή — όχι «μην το αγγίξεις».
            'available_for_work' => true,
            'legal_status' => true,
            'updated_at' => true,
        ];

        $data = [];

        foreach ($raw as $field => $value) {
            // Το driving_license υπολογίζεται παραπάνω: null = η καρτέλα
            // αδειών δεν ήταν στο POST, οπότε δεν το αγγίζουμε.
            if ($field === 'driving_license' && $value === null) {
                continue;
            }

            if (isset($alwaysWrite[$field])) {
                $data[$field] = $value;
                continue;
            }

            // Η ημερομηνία γέννησης γράφεται σε δύο στήλες από ένα πεδίο.
            $source = ($field === 'date_of_birth') ? 'birth_date' : $field;

            if (!array_key_exists($source, $_POST)) {
                continue; // δεν ήρθε — δεν το πειράζουμε
            }

            // Κενό κείμενο σε πεδίο NOT NULL θα έσπαγε την ενημέρωση.
            if ($value === null && ($_POST[$source] ?? '') === '') {
                continue;
            }

            $data[$field] = $value;
        }

        /*
         * Γλωσσικές ικανότητες: η φόρμα στέλνει languages[greek] κ.λπ.,
         * η βάση έχει στήλες language_greek κ.λπ. Η αντιστοίχιση ΔΕΝ
         * υπήρχε πουθενά στη ροή αποθήκευσης — τα επίπεδα γλωσσών
         * πετιούνταν σιωπηλά (25/08/2026). Γράφονται ΜΟΝΟ όταν η
         * ενότητα ήρθε στο POST, κατά τον κανόνα παραπάνω. Τα select
         * στέλνονται πάντα (και με κενή τιμή = «Δεν γνωρίζω»), οπότε
         * το κενό εδώ είναι συνειδητή επιλογή, όχι απουσία.
         */
        if (isset($_POST['languages']) && is_array($_POST['languages'])) {
            $langs = $_POST['languages'];

            /*
             * Οι στήλες είναι ENUM('native','fluent','good','basic') — το
             * κενό «Δεν γνωρίζω» ΔΕΝ είναι έγκυρη τιμή ENUM και με
             * STRICT_TRANS_TABLES η MariaDB απορρίπτει ΟΛΟΚΛΗΡΟ το UPDATE
             * (Data truncated), σιωπηλά για τον χρήστη — ίδιο μοτίβο με
             * το seasonal στα job_type. Λίστα επιτρεπτών, κενό → NULL.
             */
            $level = static function ($v) {
                return in_array($v, ['native', 'fluent', 'good', 'basic'], true) ? $v : null;
            };

            foreach (['greek', 'english', 'german', 'french', 'italian'] as $langKey) {
                if (!array_key_exists($langKey, $langs)) {
                    continue;
                }
                $value = $level($langs[$langKey]);
                // Το language_greek είναι NOT NULL — το «Δεν γνωρίζω» εκεί
                // σημαίνει «μην το αγγίξεις», όχι NULL.
                if ($langKey === 'greek' && $value === null) {
                    continue;
                }
                $data['language_' . $langKey] = $value;
            }
            if (array_key_exists('other_name', $langs)) {
                $name = trim($this->sanitize($langs['other_name']));
                $data['language_other_name'] = ($name !== '') ? $name : null;
            }
            if (array_key_exists('other_level', $langs)) {
                $data['language_other_level'] = $level($langs['other_level']);
            }
        }

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
    /**
     * Επεξεργασία της φόρμας εγγραφής.
     *
     * Υπάρχει ώστε ο τύπος χρήστη να είναι ρητός: η κληρονομούμενη
     * BaseUserController::processRegistration() έχει προεπιλογή 'driver',
     * που για τις εταιρείες θα ήταν λάθος.
     */
    public function processRegistration($userType = 'driver')
    {
        parent::processRegistration('driver');
    }

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
    /**
     * Αναζήτηση οδηγών — GET /drivers/search
     *
     * ══════════════════════════════════════════════════════════════════════
     *  ΓΙΑΤΙ ΓΥΡΙΖΕ ΠΑΝΤΑ ΚΕΝΗ
     * ══════════════════════════════════════════════════════════════════════
     *
     * Το ερώτημα έκανε `JOIN users u ON d.user_id = u.id` — αλλά οι οδηγοί
     * αυθεντικοποιούνται στον ΔΙΚΟ τους πίνακα και η στήλη user_id είναι
     * NULL και στους 32. Το JOIN έκοβε τους πάντες: μηδέν αποτελέσματα,
     * πάντα, χωρίς σφάλμα. (Το email που ζητούσε από τον users υπάρχει
     * ούτως ή άλλως στον drivers — το JOIN δεν χρειαζόταν καν.)
     *
     * Και δύο ακόμη προβλήματα που έκρυβε το κενό αποτέλεσμα:
     *
     *   1. ΚΑΜΙΑ προστασία: η σελίδα ήταν δημόσια. Με SELECT d.* το view
     *      έπαιρνε ονοματεπώνυμα, τηλέφωνα, ΚΑΙ password hashes για όποιον
     *      περνούσε — απλώς δεν τα τύπωνε ακόμη.
     *   2. Το φίλτρο άδειας (license_type) υπήρχε στη φόρμα και δεν
     *      διαβαζόταν πουθενά — οι άδειες ζουν στον πίνακα driver_licenses.
     *
     * ΤΩΡΑ: μόνο συνδεδεμένες εταιρείες (και admin), λίστα επιτρεπτών
     * πεδίων χωρίς κανένα προσωπικό στοιχείο, και ανώνυμες κάρτες — η
     * ταυτότητα ξεκλειδώνει μέσα από το προφίλ, με τους κανόνες του
     * Visibility, όχι από τη λίστα.
     */
    public function search()
    {
        try {
            AuthMiddleware::hasRole('company');
        } catch (\Exception $e) {
            if (Session::get('user_role') !== 'admin') {
                Session::set('error_message', Session::has('user_id')
                    ? 'Η αναζήτηση οδηγών είναι διαθέσιμη μόνο σε εταιρείες.'
                    : 'Συνδέσου ως εταιρεία για να αναζητήσεις οδηγούς.');
                header('Location: ' . BASE_URL . (Session::has('user_id') ? '' : 'login'));
                exit();
            }
        }

        $city = trim((string) ($_GET['city'] ?? ''));
        $license = strtoupper(trim((string) ($_GET['license_type'] ?? '')));
        $experience = (int) ($_GET['experience_years'] ?? 0);
        $availableOnly = isset($_GET['available_for_work']);

        /*
         * ΛΙΣΤΑ ΕΠΙΤΡΕΠΤΩΝ ΠΕΔΙΩΝ — όχι d.*.
         * Ό,τι δεν χρειάζεται η κάρτα, δεν φεύγει από τη βάση.
         */
        $query = "SELECT d.id, d.city, d.region, d.experience_years,
                         d.available_for_work, d.rating, d.rating_count,
                         d.preferred_job_type, d.willing_to_relocate,
                         d.adr_certificate,
                         GROUP_CONCAT(DISTINCT dl.license_type ORDER BY dl.license_type SEPARATOR ', ') AS licenses
                  FROM drivers d
                  LEFT JOIN driver_licenses dl ON dl.driver_id = d.id
                  WHERE d.is_active = 1";

        $params = [];

        if ($city !== '') {
            $query .= ' AND (d.city LIKE ? OR d.region LIKE ?)';
            $params[] = '%' . $city . '%';
            $params[] = '%' . $city . '%';
        }

        if ($availableOnly) {
            $query .= ' AND d.available_for_work = 1';
        }

        if ($experience > 0) {
            $query .= ' AND d.experience_years >= ?';
            $params[] = $experience;
        }

        if ($license !== '' && preg_match('/^[A-Z]{1,2}[0-9]?E?$/', $license)) {
            // Η άδεια ζει στον πίνακα driver_licenses — όχι στον drivers.
            $query .= ' AND EXISTS (SELECT 1 FROM driver_licenses dl2
                                    WHERE dl2.driver_id = d.id AND dl2.license_type = ?)';
            $params[] = $license;
        }

        $query .= ' GROUP BY d.id ORDER BY d.available_for_work DESC, d.rating DESC, d.created_at DESC LIMIT 30';

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $drivers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('Σφάλμα στην αναζήτηση οδηγών', ['message' => $e->getMessage()]);
            $drivers = [];
        }

        include ROOT_DIR . '/src/Views/drivers/search.php';
    }
}
