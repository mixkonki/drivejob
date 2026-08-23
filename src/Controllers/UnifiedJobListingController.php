<?php

namespace Drivejob\Controllers;

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

/**
 * Ενιαίος Controller για τις αγγελίες εργασίας
 * 
 * Διαχειρίζεται τις αγγελίες τόσο από οδηγούς όσο και από επιχειρήσεις
 */
class UnifiedJobListingController extends BaseJobListingController
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
     * @var \Drivejob\Repositories\JobApplicationRepositoryInterface
     */
    protected $jobApplicationRepository;

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
        $this->jobApplicationRepository = $container->get('JobApplicationRepository');

        // Αρχικοποίηση του FileService
        $this->fileService = new FileService();

        // Αρχικοποίηση του JobListingService
        $this->jobListingService = new JobListingService(
            $this->jobListingRepository
        );
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
            // Ανάκτηση της αγγελίας με το service
            $listing = $this->jobListingService->findJobListing($id);

            if (!$listing) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν η αγγελία είναι ενεργή και εγκεκριμένη
            $isActive = isset($listing['is_active']) ? $listing['is_active'] : false;
            $isApproved = isset($listing['is_approved']) ? $listing['is_approved'] : true;

            if (!$isActive || !$isApproved) {
                // Αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας, επιτρέπουμε την προβολή
                $isOwner = false;

                if (Session::has('user_id')) {
                    if (Session::get('user_role') === 'company' && !empty($listing['company_id']) && Session::get('user_id') == $listing['company_id']) {
                        $isOwner = true;
                    } elseif (Session::get('user_role') === 'driver' && !empty($listing['driver_id']) && Session::get('user_id') == $listing['driver_id']) {
                        $isOwner = true;
                    }
                }

                if (!$isOwner) {
                    Session::set('error_message', 'Η αγγελία δεν είναι διαθέσιμη');
                    header('Location: ' . BASE_URL . 'job-listings');
                    exit;
                }
            }

            /*
             * ΟΡΑΤΟΤΗΤΑ ΣΤΗ ΣΕΛΙΔΑ ΑΓΓΕΛΙΑΣ.
             *
             * Το `$listing` έρχεται με `SELECT j.*`, άρα φέρει contact_email
             * και contact_phone. Ο καθαρισμός είναι ο ίδιος με τη λίστα.
             */
            $visibility = new \Drivejob\Services\Visibility(
                \Drivejob\Core\Container::getInstance()->get('pdo')
            );
            $viewerRole = Session::get('user_role') ?? Session::get('role');
            $viewerId = Session::get('user_id');

            $listing = $visibility->sanitiseListing($viewerRole, $viewerId, $listing);

            // Ανάκτηση της εταιρείας ή του οδηγού ανάλογα με τον τύπο της αγγελίας
            $company = null;
            $driver = null;
            $author = null;

            if (!empty($listing['company_id'])) {
                $companyId = (int) $listing['company_id'];
                $company = $this->companiesRepository->find($companyId);

                /*
                 * Ο repository επιστρέφει ΟΛΟΚΛΗΡΗ την εγγραφή: email,
                 * τηλέφωνο, ακριβή διεύθυνση, ΑΦΜ, συντεταγμένες, ακόμη και
                 * το hash του συνθηματικού. Το σημερινό view δείχνει μόνο
                 * την επωνυμία — αλλά τα υπόλοιπα κάθονται στη μνήμη, ένα
                 * `<?php echo ?>` μακριά από το να δημοσιευτούν.
                 *
                 * Κρατάμε ρητά ΜΟΝΟ όσα επιτρέπεται να φανούν. Ό,τι δεν
                 * είναι στη λίστα δεν φτάνει ποτέ στο view.
                 */
                if ($company) {
                    $mayContact = $visibility->canViewCompanyContact($viewerRole, $viewerId, $companyId);

                    $safe = [
                        'id' => $company['id'] ?? null,
                        'company_name' => $visibility->companyNameFor($viewerRole, $viewerId, $company),
                        'company_logo' => $company['company_logo'] ?? null,
                        'logo' => $company['logo'] ?? $company['company_logo'] ?? null,
                        'description' => $company['description'] ?? null,
                        'industry' => $company['industry'] ?? null,
                        'fleet_size' => $company['fleet_size'] ?? null,
                        'founded_year' => $company['founded_year'] ?? $company['foundation_year'] ?? null,
                        'rating' => $company['rating'] ?? null,
                        'rating_count' => $company['rating_count'] ?? null,
                        'is_verified' => $company['is_verified'] ?? null,
                        'location' => $visibility->locationFor($viewerRole, $viewerId, $companyId, $company),
                        'identity_hidden' => !$visibility->canRevealCompanyIdentity($viewerRole, $viewerId),
                        'contact_locked' => !$mayContact,
                    ];

                    if ($mayContact) {
                        $safe['email'] = $company['email'] ?? null;
                        $safe['phone'] = $company['phone'] ?? null;
                        $safe['address'] = $company['address'] ?? null;
                        $safe['website'] = $company['website'] ?? null;
                    } else {
                        $safe['contact_hint'] = $visibility->companyContactHint($viewerRole, $viewerId, $companyId);
                    }

                    $company = $safe;
                }

                $author = $company;
            } elseif (!empty($listing['driver_id'])) {
                $driverId = (int) $listing['driver_id'];
                $driver = $this->driversRepository->find($driverId);

                // Το ίδιο για αγγελίες που δημοσιεύει οδηγός («ζητώ εργασία»).
                if ($driver && !$visibility->canViewDriverContact($viewerRole, $viewerId, $driverId)) {
                    unset(
                        $driver['email'], $driver['phone'], $driver['landline'],
                        $driver['address'], $driver['password'], $driver['postal_code']
                    );
                    $driver['contact_locked'] = true;
                }

                $author = $driver;
            }

            /*
             * ΕΧΕΙ ΗΔΗ ΚΑΝΕΙ ΑΙΤΗΣΗ;
             *
             * Ήταν σχολιασμένο με «Προσωρινά απενεργοποιημένο μέχρι να
             * υλοποιηθεί η μέθοδος hasApplied» — και έμενε μόνιμα false.
             * Ο οδηγός έβλεπε πάντα το κουμπί «Υποβολή αίτησης», ακόμη κι
             * αν είχε ήδη κάνει αίτηση, και μπορούσε να υποβάλει ξανά.
             *
             * Η μέθοδος υπάρχει: Visibility::driverHasAppliedTo().
             */
            $hasApplied = false;
            if ($viewerRole === 'driver' && $viewerId && !empty($listing['company_id'])) {
                $hasApplied = $visibility->driverHasAppliedTo((int) $viewerId, (int) $id);
            }

            // Αύξηση των προβολών με το service (αγνοούμε το αποτέλεσμα)
            try {
                $this->jobListingService->incrementViews($id);
            } catch (\Exception $e) {
                // Αγνοούμε τυχόν σφάλματα κατά την αύξηση των προβολών
                Logger::error('Error incrementing views', [
                    'id' => $id,
                    'message' => $e->getMessage()
                ]);
            }

            // Ανάκτηση των τύπων οχημάτων
            $vehicleTypes = [];
            if (isset($listing['vehicle_types']) && !empty($listing['vehicle_types'])) {
                $vehicleTypes = explode(',', $listing['vehicle_types']);
            }

            // (ο έλεγχος αίτησης έγινε παραπάνω, μαζί με την ορατότητα)

            // Παρόμοιες αγγελίες
            $similarListings = ['results' => []];
            // Προσωρινά απενεργοποιημένο μέχρι να υλοποιηθεί η μέθοδος findSimilar
            /*
            $similarCriteria = [
                'job_category' => $listing['job_category'] ?? null,
                'job_type' => $listing['job_type'] ?? null,
                'exclude_id' => $id,
                'limit' => 5
            ];
            $similarListings = $this->jobListingRepository->findSimilar($similarCriteria);
            */

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
     * Εμφανίζει τη λίστα αγγελιών
     */
    public function index()
    {
        // Συλλογή των κριτηρίων αναζήτησης
        $criteria = [
            'title' => $_GET['title'] ?? null,
            'location' => $_GET['location'] ?? null,
            'job_type' => $_GET['job_type'] ?? null,
            'job_category' => $_GET['job_category'] ?? null,
            'vehicle_types' => isset($_GET['vehicle_types']) ? explode(',', $_GET['vehicle_types']) : null,
            'listing_type' => $_GET['listing_type'] ?? null, // job_offer ή job_search
            'sort_by' => $_GET['sort_by'] ?? 'created_at',
            'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
        ];

        // Λήψη της τρέχουσας σελίδας και του ορίου
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        try {
            // Αναζήτηση αγγελιών με το service
            $result = $this->jobListingService->searchJobListings($criteria, $page, $limit);

            /*
             * ΚΑΘΑΡΙΣΜΟΣ ΠΡΙΝ ΑΠΟ ΚΑΘΕ ΕΞΟΔΟ — δεν είναι προαιρετικός.
             *
             * Το ερώτημα κάνει `SELECT j.*`, οπότε το αποτέλεσμα φέρνει μαζί
             * τα contact_email / contact_phone της αγγελίας. Μέχρι σήμερα
             * έφευγαν αυτούσια στην απόκριση JSON: ένα curl χωρίς λογαριασμό
             * επέστρεφε ολόκληρο τον κατάλογο τηλεφώνων.
             *
             * Ο καθαρισμός γίνεται ΜΙΑ φορά, εδώ, πριν χωρίσουν οι δρόμοι
             * JSON και view — ώστε να μην μπορεί να ξεχαστεί στο ένα από τα δύο.
             */
            $visibility = new \Drivejob\Services\Visibility(
                \Drivejob\Core\Container::getInstance()->get('pdo')
            );
            $viewerRole = Session::get('user_role') ?? Session::get('role');
            $viewerId = Session::get('user_id');

            $result['results'] = $visibility->sanitiseListings(
                $viewerRole,
                $viewerId,
                $result['results'] ?? []
            );

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
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
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
                exit();
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
                exit();
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Εμφανίζει τη φόρμα δημιουργίας αγγελίας ανάλογα με τον τύπο χρήστη
     */
    public function create()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να δημιουργήσετε αγγελία.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        try {
            if ($userRole === 'company') {
                // Λήψη των στοιχείων της εταιρείας
                $companyData = $this->companiesRepository->find($userId);

                if (!$companyData) {
                    Session::set('error_message', 'Τα στοιχεία της εταιρείας δεν βρέθηκαν.');
                    header('Location: ' . BASE_URL . 'companies/profile');
                    exit();
                }

                // Φόρτωση του view για εταιρείες
                include ROOT_DIR . '/src/Views/job-listings/Company/create.php';
            } elseif ($userRole === 'driver') {
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
            } else {
                Session::set('error_message', 'Δεν έχετε δικαίωμα δημιουργίας αγγελίας.');
                header('Location: ' . BASE_URL);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing create', [
                'user_id' => $userId,
                'user_role' => $userRole,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing create', [
                'user_id' => $userId,
                'user_role' => $userRole,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Αποθηκεύει μια νέα αγγελία
     */
    public function store()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να δημιουργήσετε αγγελία.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing store');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/create');
            exit();
        }

        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        try {
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

            // Συλλογή των δεδομένων από τη φόρμα
            $data = $this->collectFormData();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $data['views_count'] = 0;
            $data['applications'] = 0;
            $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
            $data['is_approved'] = 1; // Αυτόματη έγκριση για τώρα

            // Ανάλογα με τον τύπο του χρήστη, προσθέτουμε τα κατάλληλα πεδία
            if ($userRole === 'driver') {
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
            } elseif ($userRole === 'company') {
                // Αγγελία προσφοράς εργασίας από εταιρεία
                $data['company_id'] = $userId;
                $data['driver_id'] = null;
                $data['listing_type'] = 'job_offer';

                // Οι απαιτήσεις (δίπλωμα, ΠΕΙ, ADR, ταχογράφος) συλλέγονται
                // πλέον στο collectFormData(), που δέχεται και τα ονόματα της
                // φόρμας (has_*) και αυτά του σχήματος.

                Logger::info('Starting company job listing creation', ['company_id' => $userId]);
            } else {
                Session::set('error_message', 'Δεν έχετε δικαίωμα δημιουργίας αγγελίας.');
                header('Location: ' . BASE_URL);
                exit();
            }

            // Οι τύποι οχημάτων ΔΕΝ είναι στήλη του job_listings — ζουν στον
            // πίνακα job_listing_vehicle_types. Κρατιούνται χωριστά και
            // γράφονται μετά τη δημιουργία της αγγελίας.
            $vehicleTypes = [];
            if (isset($_POST['vehicle_types'])) {
                $vehicleTypes = is_array($_POST['vehicle_types'])
                    ? $_POST['vehicle_types']
                    : array_filter(array_map('trim', explode(',', (string) $_POST['vehicle_types'])));
            }
            $data['vehicle_types'] = $vehicleTypes; // για τον έλεγχο εγκυρότητας

            Logger::info('Collected form data for job listing', ['data_keys' => array_keys($data)]);

            // Δημιουργία της αγγελίας με το service
            try {
                $listingId = $this->jobListingService->createJobListing($data);

                // Οι τύποι οχημάτων γράφονται στον δικό τους πίνακα
                if (!empty($vehicleTypes)) {
                    $this->jobListingRepository->setVehicleTypes((int) $listingId, $vehicleTypes);
                }

                Logger::info('Job listing creation successful', ['listing_id' => $listingId]);
                Session::set('success_message', 'Η αγγελία δημιουργήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
                exit();
            } catch (ValidationException $e) {
                Logger::error('Validation exception in job listing creation', [
                    'user_id' => $userId,
                    'errors' => $e->getErrors()
                ]);

                Session::set('errors', $e->getErrors());
                Session::set('old_input', $_POST);
                header('Location: ' . BASE_URL . 'job-listings/create');
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing store', [
                'user_id' => $userId,
                'user_role' => $userRole,
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
            Logger::error('Exception in job listing store', [
                'user_id' => $userId,
                'user_role' => $userRole,
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
     * Εμφανίζει τη φόρμα επεξεργασίας αγγελίας
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function edit($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να επεξεργαστείτε αγγελία.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        $userRole = Session::get('user_role');
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
            $isOwner = false;
            if ($userRole === 'company' && !empty($listing['company_id']) && $userId == $listing['company_id']) {
                $isOwner = true;
            } elseif ($userRole === 'driver' && !empty($listing['driver_id']) && $userId == $listing['driver_id']) {
                $isOwner = true;
            }

            if (!$isOwner) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα επεξεργασίας αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Οι τύποι οχημάτων ζουν σε δικό τους πίνακα — η φόρμα τους χρειάζεται
            // για να δείξει τα σωστά κουτάκια επιλεγμένα.
            $listingVehicleTypes = $this->jobListingRepository->vehicleTypesFor((int) $id);

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/edit.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing edit', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing edit', [
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
     * Ενημερώνει μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function update($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να επεξεργαστείτε αγγελία.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing update');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
            exit();
        }

        $userRole = Session::get('user_role');
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
            $isOwner = false;
            if ($userRole === 'company' && !empty($listing['company_id']) && $userId == $listing['company_id']) {
                $isOwner = true;
            } elseif ($userRole === 'driver' && !empty($listing['driver_id']) && $userId == $listing['driver_id']) {
                $isOwner = true;
            }

            if (!$isOwner) {
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
                Logger::error('Validation failed in job listing update', [
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
            $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

            // Οι τύποι οχημάτων ΔΕΝ είναι στήλη του job_listings — ζουν στον
            // πίνακα job_listing_vehicle_types. Κρατιούνται χωριστά και
            // γράφονται μετά τη δημιουργία της αγγελίας.
            $vehicleTypes = [];
            if (isset($_POST['vehicle_types'])) {
                $vehicleTypes = is_array($_POST['vehicle_types'])
                    ? $_POST['vehicle_types']
                    : array_filter(array_map('trim', explode(',', (string) $_POST['vehicle_types'])));
            }
            $data['vehicle_types'] = $vehicleTypes; // για τον έλεγχο εγκυρότητας

            // Οι απαιτήσεις (δίπλωμα, ΠΕΙ, ADR, ταχογράφος) έχουν ήδη συλλεχθεί
            // στο collectFormData(). Η παλιά έκδοση τις ξανάγραφε εδώ από
            // $_POST['requires_*'] — ονόματα που η φόρμα δεν στέλνει — και έτσι
            // μηδένιζε ό,τι είχε μόλις διαβαστεί σωστά.

            // Ενημέρωση της αγγελίας με το service
            try {
                $success = $this->jobListingService->updateJobListing($id, $data);

                // Οι τύποι οχημάτων αντικαθίστανται πλήρως στον δικό τους πίνακα
                $this->jobListingRepository->setVehicleTypes((int) $id, $vehicleTypes);

                Session::set('success_message', 'Η αγγελία ενημερώθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
                exit();
            } catch (ValidationException $e) {
                Logger::error('Validation exception in job listing update', [
                    'id' => $id,
                    'user_id' => $userId,
                    'errors' => $e->getErrors()
                ]);

                Session::set('errors', $e->getErrors());
                Session::set('old_input', $_POST);
                header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing update', [
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
            Logger::error('Exception in job listing update', [
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
     * Εμφανίζει τη σελίδα επιβεβαίωσης διαγραφής αγγελίας
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function delete($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να διαγράψετε αγγελία.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        $userRole = Session::get('user_role');
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
            $isOwner = false;
            if ($userRole === 'company' && !empty($listing['company_id']) && $userId == $listing['company_id']) {
                $isOwner = true;
            } elseif ($userRole === 'driver' && !empty($listing['driver_id']) && $userId == $listing['driver_id']) {
                $isOwner = true;
            }

            if (!$isOwner) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-listings/delete.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing delete', [
                'id' => $id,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job listing delete', [
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
     * Διαγράφει μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function destroy($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να διαγράψετε αγγελία.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job listing destroy');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
            exit();
        }

        $userRole = Session::get('user_role');
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
            $isOwner = false;
            if ($userRole === 'company' && !empty($listing['company_id']) && $userId == $listing['company_id']) {
                $isOwner = true;
            } elseif ($userRole === 'driver' && !empty($listing['driver_id']) && $userId == $listing['driver_id']) {
                $isOwner = true;
            }

            if (!$isOwner) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα διαγραφής αυτής της αγγελίας.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            // Διαγραφή της αγγελίας με το service
            try {
                $success = $this->jobListingService->deleteJobListing($id);

                Session::set('success_message', 'Η αγγελία διαγράφηκε με επιτυχία.');

                // Ανακατεύθυνση στη λίστα αγγελιών του χρήστη
                if ($userRole === 'company') {
                    header('Location: ' . BASE_URL . 'companies/profile');
                } else {
                    header('Location: ' . BASE_URL . 'drivers/profile');
                }
                exit();
            } catch (\Exception $e) {
                Logger::error('Exception in job listing deletion', [
                    'id' => $id,
                    'user_id' => $userId,
                    'message' => $e->getMessage()
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά τη διαγραφή της αγγελίας. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job listing destroy', [
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
            Logger::error('Exception in job listing destroy', [
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
     * Εμφανίζει τις αγγελίες του χρήστη
     */
    public function myListings()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        // Λήψη του ID και του ρόλου του χρήστη
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['role'];

        // Λήψη των αγγελιών του χρήστη με το service
        $listings = [];

        if ($userRole === 'driver') {
            $listings = $this->jobListingService->searchJobListings(['driver_id' => $userId], 1, 10);
            include ROOT_DIR . '/src/Views/job-listings/Driver/my-listings.php';
        } else if ($userRole === 'company') {
            $listings = $this->jobListingService->searchJobListings(['company_id' => $userId], 1, 10);
            include ROOT_DIR . '/src/Views/job-listings/Company/my-listings.php';
        } else {
            header('Location: ' . BASE_URL);
            exit();
        }
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
     * Συλλέγει τα δεδομένα από τη φόρμα
     * 
     * @return array Τα δεδομένα της φόρμας
     */
    /**
     * Συλλέγει τα δεδομένα της φόρμας και τα μεταφράζει στα ονόματα του σχήματος.
     *
     * ΓΙΑΤΙ Η ΜΕΤΑΦΡΑΣΗ: η φόρμα δημιουργίας και αυτή η μέθοδος είχαν αποκλίνει
     * τελείως. Η φόρμα στέλνει salary_range («1500-2000»), has_adr, has_pei,
     * has_tachograph και availability· η μέθοδος διάβαζε salary_min/salary_max,
     * requires_adr, requires_pei, requires_tachograph. Κοινά ήταν μόνο τα
     * βασικά πεδία, οπότε κάθε νέα αγγελία έχανε μισθό, απαιτήσεις και
     * διαθεσιμότητα χωρίς κανένα μήνυμα.
     *
     * Δέχεται και τις δύο μορφές: ό,τι στέλνει η φόρμα και ό,τι θα έστελνε ένα
     * API με τα ονόματα του σχήματος.
     */
    protected function collectFormData()
    {
        $post = $_POST;

        // --- Μισθός: είτε εύρος από τη φόρμα, είτε min/max απευθείας ---
        [$salaryMin, $salaryMax] = $this->parseSalary($post);

        // --- Απαιτήσεις πιστοποιητικών: has_* από τη φόρμα, requires_* από API ---
        $flag = static fn(string ...$keys): int => (int) (bool) array_reduce(
            $keys,
            static fn($carry, $k) => $carry ?: ($post[$k] ?? null),
            null
        );

        $data = [
            'title' => parent::sanitize($post['title'] ?? ''),
            'description' => parent::sanitizeHtml($post['description'] ?? ''),
            'location' => parent::sanitize($post['location'] ?? ''),
            'job_type' => parent::sanitize($post['job_type'] ?? ''),
            'job_category' => parent::sanitize($post['job_category'] ?? ''),
            'transport_type' => $this->transportTypeFrom($post['job_category'] ?? ''),
            'vehicle_type' => \Drivejob\Helpers\VehicleTypes::normalise($post['vehicle_type'] ?? ''),
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'salary_type' => parent::sanitize($post['salary_type'] ?? $post['salary_period'] ?? 'monthly'),
            'experience_years' => parent::sanitizeInt($post['experience_years'] ?? 0),
            'min_experience' => parent::sanitizeInt($post['experience_years'] ?? 0),
            'requirements' => parent::sanitizeHtml($post['requirements'] ?? ''),
            'benefits' => parent::sanitizeHtml($post['benefits'] ?? ''),
            'additional_info' => parent::sanitizeHtml($post['additional_info'] ?? ''),
            'contact_email' => parent::sanitizeEmail($post['contact_email'] ?? ''),
            'contact_phone' => parent::sanitize($post['contact_phone'] ?? ''),
            'preferred_schedule' => parent::sanitize($post['availability'] ?? $post['preferred_schedule'] ?? ''),
            'adr_certificate' => $flag('has_adr', 'requires_adr', 'adr_certificate'),
            'requires_pei' => $flag('has_pei', 'requires_pei', 'pei_required'),
            'requires_tachograph' => $flag('has_tachograph', 'requires_tachograph', 'tachograph_required'),
            'operator_license' => $flag('has_operator_license', 'requires_operator_license', 'operator_license'),
            'is_urgent' => $flag('is_urgent'),
        ];

        // Απαιτούμενο δίπλωμα: η στήλη είναι ενικός, η φόρμα μπορεί να στείλει λίστα
        $licenses = $post['required_licenses'] ?? $post['required_license'] ?? null;
        if (is_array($licenses)) {
            $licenses = implode(',', array_map('trim', $licenses));
        }
        $data['required_license'] = parent::sanitize((string) ($licenses ?? ''));

        // Μηχανήματα έργου — ελεύθερο κείμενο ή λίστα από τα κουτάκια
        $machinery = $post['machinery_types'] ?? null;
        if (is_array($machinery)) {
            $machinery = implode(',', array_map('trim', $machinery));
        }
        $data['machinery_types'] = parent::sanitize((string) ($machinery ?? ''));

        // Λήξη: αν δεν δοθεί, ενενήντα ημέρες από σήμερα
        $expires = parent::sanitizeDate($post['expires_at'] ?? '');
        $data['expires_at'] = $expires ?: date('Y-m-d H:i:s', strtotime('+90 days'));

        // Κενά αλφαριθμητικά γίνονται null ώστε να μη γεμίζει η βάση με ''
        foreach (['requirements', 'benefits', 'additional_info', 'contact_email',
                  'contact_phone', 'preferred_schedule', 'required_license',
                  'machinery_types', 'job_category', 'vehicle_type'] as $key) {
            if ($data[$key] === '') {
                $data[$key] = null;
            }
        }

        // Επεξεργασία των αρχείων που ανεβάζονται
        if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadFile($_FILES['job_image'], 'job_image', 'image');
            if ($imagePath) {
                $data['image'] = $imagePath;
                Logger::info('Επιτυχές ανέβασμα εικόνας αγγελίας', [
                    'file_path' => $imagePath
                ]);
            }
        }

        // Επεξεργασία άλλων αρχείων
        $fileTypes = [
            'job_attachment' => 'document',
            'job_brochure' => 'document',
            'job_description_file' => 'document'
        ];

        foreach ($fileTypes as $fileField => $category) {
            if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
                $filePath = $this->uploadFile($_FILES[$fileField], $fileField, $category);
                if ($filePath) {
                    $data[$fileField] = $filePath;
                    Logger::info('Επιτυχές ανέβασμα αρχείου', [
                        'file_type' => $fileField,
                        'file_path' => $filePath
                    ]);
                }
            }
        }

        // Καταγραφή των δεδομένων για αποσφαλμάτωση
        Logger::debug('Collected form data', [
            'post_data' => $_POST,
            'sanitized_data' => $data
        ]);

        return $data;
    }

    /**
     * Μεταφράζει το εύρος μισθού της φόρμας σε δύο αριθμούς.
     *
     * Η φόρμα στέλνει τιμές όπως «1500-2000» ή «2500+». Ένα API μπορεί να
     * στείλει salary_min και salary_max απευθείας — αυτά έχουν προτεραιότητα.
     *
     * @return array{0: float|null, 1: float|null}
     */
    protected function parseSalary(array $post): array
    {
        $min = isset($post['salary_min']) && $post['salary_min'] !== ''
            ? (float) $post['salary_min'] : null;
        $max = isset($post['salary_max']) && $post['salary_max'] !== ''
            ? (float) $post['salary_max'] : null;

        if ($min !== null || $max !== null) {
            return [$min, $max];
        }

        $range = trim((string) ($post['salary_range'] ?? ''));
        if ($range === '') {
            return [null, null];
        }

        if (str_ends_with($range, '+')) {
            return [(float) rtrim($range, '+'), null];
        }

        if (str_contains($range, '-')) {
            [$from, $to] = array_pad(explode('-', $range, 2), 2, null);

            return [
                is_numeric($from) ? (float) $from : null,
                is_numeric($to) ? (float) $to : null,
            ];
        }

        return [is_numeric($range) ? (float) $range : null, null];
    }

    /**
     * Το transport_type προκύπτει από την κατηγορία εργασίας της φόρμας.
     *
     * Η φόρμα ρωτά job_category (τέσσερις τιμές, ξεχωρίζει χειριστή από βοηθό
     * χειριστή)· η στήλη transport_type έχει τρεις. Κρατάμε και τα δύο: το
     * transport_type το χρησιμοποιούν τα φίλτρα και το ταίριασμα.
     */
    protected function transportTypeFrom(string $jobCategory): ?string
    {
        return match ($jobCategory) {
            'cargo_transport' => 'freight',
            'passenger_transport' => 'passenger',
            'machinery_operator', 'machinery_assistant' => 'machinery',
            default => null,
        };
    }
}
