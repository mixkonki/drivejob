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
    /**
     * Επεξεργασία της φόρμας εγγραφής.
     *
     * Υπάρχει ώστε ο τύπος χρήστη να είναι ρητός: η κληρονομούμενη
     * BaseUserController::processRegistration() έχει προεπιλογή 'driver',
     * που για τις εταιρείες θα ήταν λάθος.
     */
    public function processRegistration($userType = 'company')
    {
        parent::processRegistration('company');
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

        /*
         * ══════════════════════════════════════════════════════════════════
         *  ΤΑ ΣΤΑΤΙΣΤΙΚΑ ΕΔΕΙΧΝΑΝ ΠΑΝΤΑ ΜΗΔΕΝ
         * ══════════════════════════════════════════════════════════════════
         *
         * Το view διαβάζει `$companyStats['active_jobs']`,
         * `['total_applications']` και `['hired_drivers']` — και ο
         * controller ΔΕΝ όριζε ποτέ τη μεταβλητή. Το `?? 0` του view έκανε
         * τη ζημιά αόρατη: αντί για σφάλμα, τρία καθαρά μηδενικά.
         *
         * Για την Εταιρία 1 τα πραγματικά νούμερα ήταν 3 ενεργές αγγελίες,
         * 7 αιτήσεις και 1 πρόσληψη. Η εταιρεία έβλεπε μια πλατφόρμα όπου
         * δεν είχε συμβεί τίποτα, ενώ επτά οδηγοί περίμεναν απάντηση.
         *
         * Οι αιτήσεις μετρώνται ΜΕΣΩ των αγγελιών: ο πίνακας
         * job_applications δεν έχει στήλη company_id.
         */
        $pdo = $this->container->get('pdo');

        $stats = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM job_listings
                  WHERE company_id = :c1 AND is_active = 1)          AS active_jobs,
                (SELECT COUNT(*) FROM job_applications ja
                    JOIN job_listings jl ON jl.id = ja.job_listing_id
                  WHERE jl.company_id = :c2)                          AS total_applications,
                (SELECT COUNT(*) FROM job_applications ja
                    JOIN job_listings jl ON jl.id = ja.job_listing_id
                  WHERE jl.company_id = :c3 AND ja.status = :hired)   AS hired_drivers,
                (SELECT COUNT(*) FROM job_applications ja
                    JOIN job_listings jl ON jl.id = ja.job_listing_id
                  WHERE jl.company_id = :c4 AND ja.status = :pending) AS pending_applications'
        );
        $stats->execute([
            ':c1' => $companyId, ':c2' => $companyId,
            ':c3' => $companyId, ':c4' => $companyId,
            ':hired' => 'hired', ':pending' => 'pending',
        ]);

        $companyStats = $stats->fetch(\PDO::FETCH_ASSOC) ?: [
            'active_jobs' => 0,
            'total_applications' => 0,
            'hired_drivers' => 0,
            'pending_applications' => 0,
        ];

        // Φόρτωση του view
        /*
         * Οι πρόσφατες αιτήσεις για την καρτέλα «Υποψήφιοι» (01/09):
         * πραγματικά δεδομένα στη θέση του «AI Matching Widget». Οι
         * νεότερες πρώτες — η εκκρεμής αίτηση είναι αυτή που περιμένει
         * απάντηση ανθρώπου.
         */
        try {
            $appsStmt = $pdo->prepare(
                'SELECT ja.id, ja.driver_id, ja.job_listing_id, ja.status, ja.created_at,
                        d.first_name, d.last_name, jl.title AS listing_title
                 FROM job_applications ja
                 JOIN job_listings jl ON jl.id = ja.job_listing_id
                 JOIN drivers d ON d.id = ja.driver_id
                 WHERE jl.company_id = ?
                 ORDER BY (ja.status = \'pending\') DESC, ja.created_at DESC
                 LIMIT 15'
            );
            $appsStmt->execute([$companyId]);
            $recentApplications = $appsStmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $recentApplications = [];
        }

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
            header('Location: ' . BASE_URL);
            exit;
        }

        /*
         * ΟΡΑΤΟΤΗΤΑ — Ο ΕΛΕΓΧΟΣ ΠΡΕΠΕΙ ΝΑ ΕΙΝΑΙ ΠΡΩΤΟΣ.
         *
         * Ήταν γραμμένος στο ΤΕΛΟΣ της μεθόδου, αφού είχαν ήδη εκτελεστεί
         * όλα τα ερωτήματα: στοιχεία εταιρείας, αγγελίες, αξιολογήσεις,
         * μέσος όρος βαθμολογίας. Ένας ανώνυμος επισκέπτης απορριπτόταν
         * σωστά — αλλά μόνο αφού είχε κοστίσει πέντε ερωτήματα, και με τα
         * δεδομένα ήδη φορτωμένα στη μνήμη. Κάθε μελλοντική προσθήκη πριν
         * τον έλεγχο (ένα echo, ένα log, μια κεφαλίδα) θα γινόταν διαρροή.
         *
         * Ο έλεγχος πρόσβασης μπαίνει ΠΑΝΤΑ πριν τα δεδομένα.
         *
         * Το προφίλ (περιγραφή, στόλος, αξιολογήσεις) είναι εμπορική
         * πληροφορία και ανοίγει σε κάθε συνδεδεμένο χρήστη. Το email, το
         * τηλέφωνο, η ακριβής διεύθυνση και ο χάρτης αποκαλύπτονται μόνο σε
         * οδηγό του οποίου κάποια αίτηση έχει προχωρήσει σε προεπιλογή ή
         * πρόσληψη — αλλιώς αρκούσαν είκοσι αιτήσεις για είκοσι τηλέφωνα.
         */
        $visibility = new \Drivejob\Services\Visibility($this->container->get('pdo'));
        $viewerRole = Session::get('user_role') ?? Session::get('role');
        $viewerId = Session::get('user_id');

        if (!$visibility->canViewCompanyProfile($viewerRole, $viewerId, (int) $id)) {
            Session::set('error_message', 'Συνδέσου για να δεις το προφίλ της εταιρείας.');
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $canSeeContact = $visibility->canViewCompanyContact($viewerRole, $viewerId, (int) $id);
        $contactHint = $visibility->companyContactHint($viewerRole, $viewerId, (int) $id);

        // Ανάκτηση των στοιχείων της εταιρείας
        $companyData = $this->companiesRepository->find($id);

        if (!$companyData) {
            Session::set('error_message', 'Η εταιρεία δεν βρέθηκε');
            header('Location: ' . BASE_URL);
            exit;
        }

        // Λήψη των αγγελιών της εταιρείας
        $jobListingRepository = new \Drivejob\Repositories\JobListingRepository($this->container->get('pdo'));
        $listings = $jobListingRepository->searchListings(['company_id' => $id], 1, 5);

        // Καθαρισμός: το προφίλ δείχνει τις αγγελίες της εταιρείας, και το
        // `SELECT j.*` φέρνει μαζί contact_email/contact_phone.
        $listings['results'] = $visibility->sanitiseListings(
            $viewerRole,
            $viewerId,
            $listings['results'] ?? []
        );

        // Λήψη των αξιολογήσεων της εταιρείας
        $companyReviews = $this->ratingService->getCompanyReviews($id);
        $averageRating = $this->ratingService->getCompanyRating($id);

        /*
         * ══════════════════════════════════════════════════════════════════
         *  ΠΟΙΟΣ ΕΠΙΤΡΕΠΕΤΑΙ ΝΑ ΑΞΙΟΛΟΓΗΣΕΙ
         * ══════════════════════════════════════════════════════════════════
         *
         * Ο κανόνας ήταν μία γραμμή:
         *
         *     $canReview = !$hasReviewed;
         *
         * Δηλαδή ΚΑΘΕ συνδεδεμένος οδηγός μπορούσε να βαθμολογήσει ΚΑΘΕ
         * εταιρεία, χωρίς να έχει καμία σχέση μαζί της — χωρίς αίτηση, χωρίς
         * συνέντευξη, χωρίς να έχει δουλέψει ούτε μία μέρα.
         *
         * Ένα σύστημα αξιολόγησης με αυτόν τον κανόνα δεν είναι ελλιπές·
         * είναι όπλο. Δέκα λογαριασμοί οδηγών αρκούν για να καταστραφεί η
         * βαθμολογία ενός ανταγωνιστή, και η εταιρεία δεν έχει τρόπο να
         * αμυνθεί ούτε να αποδείξει ότι δεν συνάντησε ποτέ αυτούς τους
         * ανθρώπους.
         *
         * Ο ΝΕΟΣ ΚΑΝΟΝΑΣ: αξιολογεί μόνο όποιος έχει προχωρήσει σε
         * προεπιλογή ή πρόσληψη — δηλαδή όποιος έχει πραγματική εμπειρία
         * από την εταιρεία. Είναι η ΙΔΙΑ προϋπόθεση που ξεκλειδώνει και τα
         * στοιχεία επικοινωνίας (Visibility::ENGAGED_STATUSES), οπότε η
         * πλατφόρμα λέει το ίδιο πράγμα παντού: η σχέση γεννιέται από τη
         * διαδικασία, όχι από την περιέργεια.
         *
         * Ίδια λογική με Booking και Airbnb: κριτική αφήνει μόνο όποιος
         * έμεινε.
         */
        $canReview = false;
        $hasReviewed = false;
        $reviewBlockedReason = '';

        if (Session::has('user_id') && Session::get('user_role') === 'driver') {
            $driverId = (int) Session::get('user_id');

            foreach ($companyReviews as $review) {
                if ($review['driver_id'] == $driverId) {
                    $hasReviewed = true;
                    break;
                }
            }

            $isEngaged = $visibility->driverIsEngagedWith($driverId, (int) $id);

            if ($hasReviewed) {
                $reviewBlockedReason = '';       // το view δείχνει «έχεις ήδη αξιολογήσει»
            } elseif (!$isEngaged) {
                $reviewBlockedReason = 'Μπορείς να αξιολογήσεις μια εταιρεία αφού '
                    . 'προχωρήσει η αίτησή σου σε προεπιλογή ή πρόσληψη.';
            }

            $canReview = !$hasReviewed && $isEngaged;
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

            /*
             * ══════════════════════════════════════════════════════════════
             *  ΕΔΩ ΔΙΕΡΡΕΕ ΟΛΟΚΛΗΡΟΣ Ο ΠΙΝΑΚΑΣ companies
             * ══════════════════════════════════════════════════════════════
             *
             * Το ερώτημα είναι `SELECT c.*`. Ο παλιός κώδικας έχτιζε το JSON
             * χειροκίνητα με έναν βρόχο `foreach ($company as $key => $value)`
             * — δηλαδή σέρβιρε ΚΑΘΕ στήλη της εγγραφής. Χωρίς σύνδεση, με
             * μία εντολή:
             *
             *     curl -H "X-Requested-With: XMLHttpRequest" \
             *          https://drivejob.gr/companies/search
             *
             * Στην απόκριση υπήρχαν:
             *
             *     "password"            → το bcrypt hash του συνθηματικού
             *     "reset_token"         → το token επαναφοράς συνθηματικού
             *     "verification_token"  → το token επαλήθευσης email
             *     "vat_number"          → ΑΦΜ
             *     email, τηλέφωνο, διεύθυνση, συντεταγμένες
             *
             * Το `reset_token` είναι το σοβαρότερο: αρκεί για να αλλάξει
             * κάποιος το συνθηματικό ενός λογαριασμού εταιρείας χωρίς να
             * γνωρίζει τίποτε άλλο.
             *
             * Η ΔΙΟΡΘΩΣΗ ΕΙΝΑΙ ΛΙΣΤΑ ΕΠΙΤΡΕΠΤΩΝ, ΟΧΙ ΑΦΑΙΡΕΣΗ.
             *
             * Το να σβήνει κανείς τα «κακά» πεδία (unset password, unset
             * token…) αποτυγχάνει την πρώτη φορά που θα προστεθεί νέα στήλη
             * στον πίνακα: το νέο πεδίο φεύγει σιωπηλά προς τα έξω, γιατί
             * κανείς δεν θυμήθηκε να το προσθέσει στη λίστα αφαίρεσης.
             * Με λίστα επιτρεπτών, το προεπιλεγμένο είναι η σιωπή.
             *
             * Και το JSON παράγεται πλέον με json_encode: ο χειροποίητος
             * βρόχος έσπαγε σε κάθε τιμή που δεν ήταν string/number/bool
             * (πίνακες, JSON στήλες, ημερομηνίες), παράγοντας άκυρο JSON.
             */
            $publicFields = [
                'id', 'company_name', 'city', 'country', 'industry',
                'description', 'company_logo', 'website', 'fleet_size',
                'founded_year', 'rating', 'rating_count', 'is_verified',
                'transport_types', 'specializations',
            ];

            $visibility = new \Drivejob\Services\Visibility($this->container->get('pdo'));
            $viewerRole = Session::get('user_role') ?? Session::get('role');
            $viewerId = Session::get('user_id');

            $safeCompanies = [];
            foreach ($result['results'] ?? [] as $company) {
                $row = array_intersect_key($company, array_flip($publicFields));

                // Η επωνυμία και η τοποθεσία περνούν από τους ίδιους κανόνες
                // ορατότητας με τη λίστα αγγελιών.
                $row['company_name'] = $visibility->companyNameFor($viewerRole, $viewerId, $company);
                $row['location'] = $visibility->locationFor(
                    $viewerRole,
                    $viewerId,
                    isset($company['id']) ? (int) $company['id'] : null,
                    $company
                );
                unset($row['city'], $row['country']);

                $safeCompanies[] = $row;
            }

            $result['results'] = $safeCompanies;

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'results' => $result['results'],
                    'pagination' => $result['pagination'] ?? new \stdClass(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            header('Location: ' . BASE_URL);
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
            header('Location: ' . BASE_URL);
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
            header('Location: ' . BASE_URL);
            exit;
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in company review');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/profile/' . $id);
            exit();
        }

        /*
         * Ο ΕΛΕΓΧΟΣ ΤΟΥ VIEW ΔΕΝ ΕΙΝΑΙ ΕΛΕΓΧΟΣ.
         *
         * Η φόρμα αξιολόγησης εμφανίζεται μόνο σε οδηγό που έχει προχωρήσει
         * σε προεπιλογή ή πρόσληψη — αλλά αυτό αφορά μόνο όποιον ανοίγει τη
         * σελίδα σε browser. Ένα POST απευθείας στο endpoint δεν βλέπει ποτέ
         * το view, και ο έλεγχος δεν υπήρχε ΠΟΥΘΕΝΑ εδώ.
         *
         * Το ίδιο λάθος με το checkbox «Δεν είμαι ρομπότ»: κανόνας που ζει
         * μόνο στο HTML είναι διακόσμηση. Ο κανόνας ζει στον server.
         */
        $visibility = new \Drivejob\Services\Visibility($this->container->get('pdo'));
        $driverId = (int) Session::get('user_id');

        if (!$visibility->driverIsEngagedWith($driverId, (int) $id)) {
            Logger::warning('Απόπειρα αξιολόγησης χωρίς σχέση με την εταιρεία', [
                'driver_id' => $driverId,
                'company_id' => $id,
            ]);
            Session::set('error_message',
                'Μπορείς να αξιολογήσεις μια εταιρεία αφού προχωρήσει η αίτησή '
                . 'σου σε προεπιλογή ή πρόσληψη.');
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

    /**
     * POST /companies/message-driver — άνοιγμα συνομιλίας από το προφίλ
     * υποψηφίου. (01/09/2026 — Φάση Α)
     *
     * Οι συνομιλίες δένονται με αγγελία (conversations.job_id), οπότε το
     * πλαίσιο είναι η ΠΙΟ ΠΡΟΣΦΑΤΗ αίτηση του οδηγού προς την εταιρεία —
     * αυτή που την έφερε στο προφίλ του. Αν υπάρχει ήδη συνομιλία για το
     * ζευγάρι, συνεχίζεται· δεν ανοίγουμε δεύτερο νήμα για το ίδιο θέμα.
     */
    public function messageDriver()
    {
        \Drivejob\Core\AuthMiddleware::hasRole('company');
        $companyId = (int) \Drivejob\Core\Session::get('user_id');

        if (!\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            \Drivejob\Core\Session::set('error_message', 'Η φόρμα έληξε. Δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/profile');
            exit;
        }

        $driverId = (int) ($_POST['driver_id'] ?? 0);
        $pdo = $this->container->get('pdo');

        // Το πλαίσιο: η τελευταία αίτηση του οδηγού σε αγγελία της εταιρείας.
        $stmt = $pdo->prepare(
            'SELECT ja.job_listing_id, jl.title
             FROM job_applications ja
             JOIN job_listings jl ON jl.id = ja.job_listing_id
             WHERE ja.driver_id = ? AND jl.company_id = ?
             ORDER BY ja.created_at DESC LIMIT 1'
        );
        $stmt->execute([$driverId, $companyId]);
        $app = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$app) {
            // Χωρίς αίτηση δεν υπάρχει κανάλι: ίδιος κανόνας με την ορατότητα
            // προφίλ — η επικοινωνία ξεκινά από τον οδηγό, με την αίτησή του.
            \Drivejob\Core\Session::set('error_message', 'Ο οδηγός δεν έχει αίτηση σε αγγελία σας.');
            header('Location: ' . BASE_URL . 'companies/profile');
            exit;
        }

        // Υπάρχουσα συνομιλία για το ζευγάρι και την αγγελία;
        $existing = $pdo->prepare(
            'SELECT id FROM conversations
             WHERE company_id = ? AND driver_id = ? AND job_id = ?
             ORDER BY id DESC LIMIT 1'
        );
        $existing->execute([$companyId, $driverId, (int) $app['job_listing_id']]);
        $conversationId = $existing->fetchColumn();

        if (!$conversationId) {
            $svc = new \Drivejob\Services\MessagingService();
            $conversationId = $svc->startConversation(
                $companyId,
                $driverId,
                (int) $app['job_listing_id'],
                'Σχετικά με την αίτησή σας: ' . $app['title'],
                'Καλησπέρα! Είδαμε την αίτησή σας για τη θέση «' . $app['title'] . '» και θα θέλαμε να συζητήσουμε.'
            );
        }

        header('Location: ' . BASE_URL . 'companies/conversation?id=' . (int) $conversationId);
        exit;
    }
}
