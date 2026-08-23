<?php

namespace Drivejob\Controllers\Driver;

use PDO;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Container;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Repositories\JobOfferRepository;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\CompaniesRepository;
use Drivejob\Helpers\JsonHelper;
use Drivejob\Services\FileService;

/**
 * Controller για τις προσφορές εργασίας
 */
class JobOfferController extends \Drivejob\Controllers\BaseController
{
    /**
     * @var JobOfferRepository Το repository για τις προσφορές εργασίας
     */
    private $jobOfferRepository;

    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var CompaniesRepository Το repository για τις εταιρείες
     */
    private $companiesRepository;

    /**
     * @var FileService Η υπηρεσία για τη διαχείριση αρχείων
     */
    private $fileService;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    /** @var \Drivejob\Repositories\JobListingRepository */
    private $jobListingRepository;

    /*
     * ΚΑΜΙΑ ΕΠΑΝΑΔΗΛΩΣΗ ΤΟΥ $pdo.
     *
     * Ο BaseController το έχει ήδη ως protected. Μια δεύτερη δήλωση εδώ —
     * private ή έστω protected με τύπο — ρίχνει την κλάση με fatal error
     * πριν καν τρέξει μέθοδος:
     *
     *   Access level to …::$pdo must be protected … or weaker
     *
     * Ακριβώς το ίδιο λάθος είχε ρίξει και τον FleetController.
     */

    public function __construct($pdo = null)
    {
        // Λήψη του container
        $container = Container::getInstance();

        // Αν δεν έχει παραχθεί PDO, πάρε το από το container
        if ($pdo === null) {
            $pdo = $container->get('pdo');
        }

        // Η σύνδεση κρατιέται: η create() τη χρειάζεται για το Visibility.
        $this->pdo = $pdo;

        // Αρχικοποίηση των repositories
        $this->jobOfferRepository = new JobOfferRepository($pdo);
        $this->driversRepository = new DriversRepository($pdo);
        $this->companiesRepository = new CompaniesRepository($pdo);

        // Χρειάζεται για τη μετάφραση «αγγελία → οδηγός» στην create().
        $this->jobListingRepository = new \Drivejob\Repositories\JobListingRepository($pdo);

        // Αρχικοποίηση του FileService
        $this->fileService = new FileService();
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
     * Αποστολή προσφοράς εργασίας σε έναν οδηγό
     * 
     * @param int $id Το ID του οδηγού
     */
    /**
     * Φόρμα προσφοράς προς οδηγό που δημοσίευσε «ζητώ εργασία».
     *
     * ══════════════════════════════════════════════════════════════════════
     *  Η ΓΕΦΥΡΑ ΠΟΥ ΕΛΕΙΠΕ
     * ══════════════════════════════════════════════════════════════════════
     *
     * Η σελίδα μιας αγγελίας οδηγού έχει κουμπί «Αποστολή Προσφοράς» που
     * δείχνει στο /job-offers/create/{id} — διαδρομή που ΔΕΝ υπήρχε, με
     * αποτέλεσμα 404. Ολόκληρη η αντίστροφη κατεύθυνση της πλατφόρμας
     * (οδηγός ψάχνει → εταιρεία προσφέρει) σταματούσε εδώ.
     *
     * ΠΡΟΣΟΧΗ ΣΤΟ ΑΝΑΓΝΩΡΙΣΤΙΚΟ: το κουμπί στέλνει το id της ΑΓΓΕΛΙΑΣ, ενώ
     * η send() περιμένει το id του ΟΔΗΓΟΥ. Η μετάφραση γίνεται εδώ, μία
     * φορά — αλλιώς θα έπρεπε κάθε view που δείχνει αγγελία οδηγού να
     * ξέρει και τα δύο.
     */
    /*
     * Η παράμετρος ΠΡΕΠΕΙ να λέγεται $id.
     *
     * Ο Router περνάει τα placeholders της διαδρομής ως ΟΝΟΜΑΤΙΣΜΕΝΑ
     * ορίσματα: το {id} γίνεται id:. Αν η μέθοδος τη λέει αλλιώς, η PHP
     * απαντάει «Unknown named parameter $id» και η σελίδα βγάζει 500 —
     * χωρίς καμία ένδειξη ότι φταίει το όνομα.
     */
    public function create($id)
    {
        $listingId = $id;
        try {
            AuthMiddleware::hasRole('company');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως εταιρεία για να στείλετε προσφορά.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        if (!$listingId || !is_numeric($listingId)) {
            Session::set('error_message', 'Μη έγκυρη αγγελία.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        try {
            $listing = $this->jobListingRepository->find((int) $listingId);

            if (!$listing || empty($listing['driver_id'])) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε ή δεν ανήκει σε οδηγό.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            $driver = $this->driversRepository->find((int) $listing['driver_id']);

            if (!$driver) {
                Session::set('error_message', 'Ο οδηγός δεν βρέθηκε.');
                header('Location: ' . BASE_URL . 'job-listings');
                exit();
            }

            if (empty($driver['available_for_work'])) {
                Session::set('error_message', 'Ο οδηγός δεν είναι διαθέσιμος για εργασία αυτή τη στιγμή.');
                header('Location: ' . BASE_URL . 'job-listings/show/' . (int) $listingId);
                exit();
            }

            /*
             * Μία εκκρεμής προσφορά τη φορά.
             *
             * Χωρίς αυτόν τον έλεγχο, μια εταιρεία μπορεί να γεμίσει τα
             * εισερχόμενα του οδηγού με δεκάδες προσφορές — το ίδιο
             * πρόβλημα που λύνει το unique_application στις αιτήσεις.
             */
            $companyId = (int) Session::get('user_id');
            $existing = $this->jobOfferRepository->findByCompanyAndDriver($companyId, (int) $driver['id']);

            if ($existing && ($existing['status'] ?? '') === 'pending') {
                Session::set('error_message',
                    'Έχεις ήδη στείλει προσφορά σε αυτόν τον οδηγό και είναι σε αναμονή.');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit();
            }

            /*
             * Ο οδηγός δεν ταυτοποιείται πριν δεχτεί.
             *
             * Η εταιρεία στέλνει προσφορά χωρίς να ξέρει ονοματεπώνυμο ή
             * στοιχεία επικοινωνίας — ακριβώς όπως και στο ταίριασμα. Αυτά
             * ξεκλειδώνουν όταν ο οδηγός αποδεχθεί.
             */
            $visibility = new \Drivejob\Services\Visibility($this->pdo);
            $driverLabel = $visibility->canViewDriverContact('company', $companyId, (int) $driver['id'])
                ? trim(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? ''))
                : 'Οδηγός #' . (int) $driver['id'];

            $pageTitle = 'Αποστολή προσφοράς';

            include ROOT_DIR . '/src/Views/job-offers/create.php';
        } catch (\Exception $e) {
            Logger::error('Σφάλμα στη φόρμα προσφοράς', [
                'listing_id' => $listingId,
                'message' => $e->getMessage(),
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }
    }

    public function send($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        try {
            AuthMiddleware::hasRole('company');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως εταιρεία για να στείλετε προσφορά εργασίας.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        /*
         * ΠΟΥ ΓΥΡΝΑΕΙ Ο ΧΡΗΣΤΗΣ ΟΤΑΝ ΚΑΤΙ ΠΑΕΙ ΣΤΡΑΒΑ.
         *
         * Η μέθοδος έστελνε πάντα στο προφίλ του οδηγού — σελίδα που δεν
         * έχει φόρμα. Αποτέλεσμα: τα μηνύματα λάθους και το old_input
         * αποθηκεύονταν και δεν τα έβλεπε ποτέ κανείς, ενώ ό,τι είχε
         * γράψει η εταιρεία χανόταν.
         *
         * Η φόρμα στέλνει κρυφά το id της αγγελίας ώστε να μπορούμε να
         * επιστρέψουμε ακριβώς εκεί απ' όπου ήρθε.
         */
        $listingId = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $backUrl = $listingId > 0
            ? BASE_URL . 'job-offers/create/' . $listingId
            : BASE_URL . 'drivers/profile/' . (int) $id;

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job offer send');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . $backUrl);
            exit();
        }

        try {
            // Ανάκτηση του οδηγού
            $driver = $this->driversRepository->find($id);

            if (!$driver) {
                Session::set('error_message', 'Ο οδηγός δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'drivers/search');
                exit;
            }

            // Έλεγχος αν ο οδηγός είναι διαθέσιμος για εργασία
            if (!$driver['available_for_work']) {
                Session::set('error_message', 'Ο οδηγός δεν είναι διαθέσιμος για εργασία');
                header('Location: ' . $backUrl);
                exit;
            }

            // Έλεγχος αν η εταιρεία έχει ήδη στείλει προσφορά στον οδηγό
            $companyId = Session::get('user_id');
            $existingOffer = $this->jobOfferRepository->findByCompanyAndDriver($companyId, $id);

            if ($existingOffer && $existingOffer['status'] === 'pending') {
                Session::set('error_message', 'Έχετε ήδη στείλει προσφορά εργασίας σε αυτόν τον οδηγό');
                header('Location: ' . $backUrl);
                exit;
            }

            // Επικύρωση των δεδομένων της φόρμας
            $validator = new Validator($_POST);
            $validator->required('title', 'Ο τίτλος είναι υποχρεωτικός.')
                ->required('description', 'Η περιγραφή είναι υποχρεωτική.')
                ->required('location', 'Η τοποθεσία είναι υποχρεωτική.')
                ->required('job_type', 'Ο τύπος εργασίας είναι υποχρεωτικός.');

            if (!$validator->isValid()) {
                Logger::error('Validation failed in job offer send', [
                    'errors' => $validator->getErrors(),
                    'post_data' => $_POST
                ]);
                Session::set('errors', $validator->getErrors());
                Session::set('old_input', $_POST);
                header('Location: ' . $backUrl);
                exit();
            }

            // Συλλογή των δεδομένων από τη φόρμα
            $data = [
                'company_id' => $companyId,
                'driver_id' => $id,
                'title' => htmlspecialchars($_POST['title']),
                'description' => htmlspecialchars($_POST['description']),
                'location' => htmlspecialchars($_POST['location']),
                'job_type' => htmlspecialchars($_POST['job_type']),
                'vehicle_type' => isset($_POST['vehicle_type']) ? htmlspecialchars($_POST['vehicle_type']) : null,
                'salary_min' => isset($_POST['salary_min']) ? (float)$_POST['salary_min'] : null,
                'salary_max' => isset($_POST['salary_max']) ? (float)$_POST['salary_max'] : null,
                'salary_period' => isset($_POST['salary_period']) ? htmlspecialchars($_POST['salary_period']) : null,
                'benefits' => isset($_POST['benefits']) ? htmlspecialchars($_POST['benefits']) : null,
                'start_date' => isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : null,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Επεξεργασία των αρχείων που ανεβάζονται
            if (isset($_FILES['offer_document']) && $_FILES['offer_document']['error'] === UPLOAD_ERR_OK) {
                $documentPath = $this->uploadFile($_FILES['offer_document'], 'offer_document', 'document');
                if ($documentPath) {
                    $data['document_path'] = $documentPath;
                    Logger::info('Επιτυχές ανέβασμα εγγράφου προσφοράς', [
                        'company_id' => $companyId,
                        'driver_id' => $id,
                        'file_path' => $documentPath
                    ]);
                } else {
                    Session::set('error_message', 'Υπήρξε ένα πρόβλημα κατά το ανέβασμα του εγγράφου προσφοράς.');
                    header('Location: ' . $backUrl);
                    exit();
                }
            }

            // Επεξεργασία άλλων αρχείων
            $fileTypes = [
                'contract_template' => 'document',
                'job_description' => 'document',
                'company_brochure' => 'document'
            ];

            foreach ($fileTypes as $fileField => $category) {
                if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
                    $filePath = $this->uploadFile($_FILES[$fileField], $fileField, $category);
                    if ($filePath) {
                        $data[$fileField . '_path'] = $filePath;
                        Logger::info('Επιτυχές ανέβασμα αρχείου', [
                            'company_id' => $companyId,
                            'driver_id' => $id,
                            'file_type' => $fileField,
                            'file_path' => $filePath
                        ]);
                    }
                }
            }

            // Δημιουργία της προσφοράς
            $offerId = $this->jobOfferRepository->create($data);

            if ($offerId) {
                Logger::info('Job offer sent successfully', [
                    'company_id' => $companyId,
                    'driver_id' => $id,
                    'offer_id' => $offerId
                ]);

                Session::set('success_message', 'Η προσφορά εργασίας στάλθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit();
            } else {
                Logger::error('Job offer send failed', [
                    'company_id' => $companyId,
                    'driver_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την αποστολή της προσφοράς. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . $backUrl);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job offer send', [
                'company_id' => Session::get('user_id'),
                'driver_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . $backUrl);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job offer send', [
                'company_id' => Session::get('user_id'),
                'driver_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . $backUrl);
            exit();
        }
    }

    /**
     * Εμφανίζει τις προσφορές του συνδεδεμένου χρήστη
     */
    public function myOffers()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να δείτε τις προσφορές σας.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        try {
            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση προσφορών ανάλογα με τον τύπο του χρήστη
            $userId = Session::get('user_id');
            $userRole = Session::get('user_role');
            $result = [];

            if ($userRole === 'company') {
                $result = $this->jobOfferRepository->findByCompany($userId, $page, $limit);
            } else if ($userRole === 'driver') {
                $result = $this->jobOfferRepository->findByDriver($userId, $page, $limit);
            } else {
                Session::set('error_message', 'Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.');
                header('Location: ' . BASE_URL);
                exit();
            }

            /*
             * ══════════════════════════════════════════════════════════════
             *  Η JSON ΑΠΟΚΡΙΣΗ ΠΕΡΝΑΕΙ ΑΠΟ ΛΙΣΤΑ ΕΠΙΤΡΕΠΤΩΝ ΠΕΔΙΩΝ
             * ══════════════════════════════════════════════════════════════
             *
             * Το ερώτημα κάνει JOIN με drivers/companies και επιστρέφει
             * ονοματεπώνυμο. Στην HTML το κρύβει το view — στο JSON τίποτα
             * δεν το έκρυβε. Ακριβώς έτσι διέρρευσαν και οι τρεις
             * προηγούμενες περιπτώσεις: η σελίδα ήταν καθαρή, η απόκριση όχι.
             *
             * ΛΙΣΤΑ ΕΠΙΤΡΕΠΤΩΝ, ΟΧΙ ΑΦΑΙΡΕΣΗ: το unset() των «κακών» πεδίων
             * αποτυγχάνει σιωπηλά την πρώτη φορά που θα προστεθεί στήλη.
             */
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                $public = [
                    'id', 'company_id', 'driver_id', 'title', 'location',
                    'job_type', 'vehicle_type', 'salary_min', 'salary_max',
                    'salary_period', 'start_date', 'status', 'created_at',
                ];

                $visibility = $userRole === 'company'
                    ? new \Drivejob\Services\Visibility($this->pdo)
                    : null;

                $safe = [];
                foreach (($result['results'] ?? []) as $row) {
                    $item = array_intersect_key($row, array_flip($public));

                    if ($userRole === 'company') {
                        $did = (int) ($row['driver_id'] ?? 0);
                        $reveal = ($row['status'] ?? '') === 'accepted'
                            || ($did && $visibility->canViewDriverContact('company', $userId, $did));

                        $item['driver_label'] = $reveal
                            ? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))
                            : 'Οδηγός #' . $did;
                    } else {
                        // Η επωνυμία της εταιρείας είναι εμπορική πληροφορία.
                        $item['company_name'] = $row['company_name'] ?? null;
                    }

                    $safe[] = $item;
                }

                JsonHelper::response([
                    'results' => $safe,
                    'pagination' => $result['pagination'] ?? [],
                ]);
            }

            // Αλλιώς, φόρτωση του view
            $offers = $result['results'];
            $pagination = $result['pagination'];

            /*
             * ΠΟΙΟΙ ΟΔΗΓΟΙ ΕΧΟΥΝ ΗΔΗ ΞΕΚΛΕΙΔΩΣΕΙ.
             *
             * Η αποδοχή της προσφοράς δεν είναι ο μόνος τρόπος: αν ο οδηγός
             * έχει ήδη προσληφθεί από αυτή την εταιρεία μέσω αίτησης, η
             * ταυτότητά του είναι ήδη γνωστή. Να τον δείχνει η μία σελίδα
             * ονομαστικά και η άλλη ως «Οδηγός #84» δεν προστατεύει κανέναν
             * — απλώς μπερδεύει.
             *
             * Η απόφαση παίρνεται σε ΕΝΑ σημείο, το Visibility, και τα views
             * απλώς την εφαρμόζουν.
             */
            $revealedDriverIds = [];
            if ($userRole === 'company') {
                $visibility = new \Drivejob\Services\Visibility($this->pdo);
                foreach ($offers as $offer) {
                    $did = (int) ($offer['driver_id'] ?? 0);
                    if ($did && $visibility->canViewDriverContact('company', $userId, $did)) {
                        $revealedDriverIds[$did] = true;
                    }
                }
            }

            include ROOT_DIR . '/src/Views/job-offers/my-offers.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in my offers', [
                'user_id' => Session::get('user_id'),
                'user_role' => Session::get('user_role'),
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in my offers', [
                'user_id' => Session::get('user_id'),
                'user_role' => Session::get('user_role'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * Εμφανίζει μια προσφορά εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     */
    public function viewOffer($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να δείτε αυτή την προσφορά.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό προσφοράς');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit;
        }

        try {
            // Ανάκτηση της προσφοράς
            $offer = $this->jobOfferRepository->find($id);

            if (!$offer) {
                Session::set('error_message', 'Η προσφορά δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit;
            }

            // Έλεγχος αν ο χρήστης έχει δικαίωμα προβολής της προσφοράς
            $userRole = Session::get('user_role');
            $userId = Session::get('user_id');

            $hasAccess = false;
            if ($userRole === 'driver' && $offer['driver_id'] == $userId) {
                $hasAccess = true;
            } else if ($userRole === 'company' && $offer['company_id'] == $userId) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα προβολής αυτής της προσφοράς');
                header('Location: ' . BASE_URL);
                exit;
            }

            // Ανάκτηση του οδηγού
            $driver = $this->driversRepository->find($offer['driver_id']);

            // Ανάκτηση της εταιρείας
            $company = $this->companiesRepository->find($offer['company_id']);

            /*
             * Η ΠΡΟΣΦΟΡΑ ΠΟΥ ΔΙΑΒΑΣΤΗΚΕ ΤΟ ΛΕΕΙ.
             *
             * Το enum έχει «viewed» και καμία γραμμή κώδικα δεν το έθετε:
             * κάθε προσφορά έμενε «Σε αναμονή» για πάντα, ακόμη κι όταν ο
             * οδηγός την είχε ανοίξει. Η εταιρεία δεν μπορούσε να ξεχωρίσει
             * τη σιωπή από τη μη ανάγνωση — που είναι δύο πολύ διαφορετικά
             * πράγματα όταν αποφασίζεις αν θα ξαναστείλεις.
             *
             * Ίδια συμπεριφορά με τις αιτήσεις: το βλέμμα καταγράφεται, δεν
             * δεσμεύει.
             */
            if ($userRole === 'driver' && ($offer['status'] ?? '') === 'pending') {
                try {
                    $this->jobOfferRepository->updateStatus($id, 'viewed');
                    $offer['status'] = 'viewed';
                } catch (\Exception $e) {
                    // Η ένδειξη ανάγνωσης δεν αξίζει να ρίξει τη σελίδα.
                    Logger::error('Αποτυχία σήμανσης προσφοράς ως αναγνωσμένης', [
                        'offer_id' => $id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // Ίδιος κανόνας με τη λίστα: αποδοχή Ή προϋπάρχουσα σχέση.
            $visibility = new \Drivejob\Services\Visibility($this->pdo);
            $canRevealDriver = ($offer['status'] ?? '') === 'accepted'
                || $visibility->canViewDriverContact($userRole, $userId, (int) $offer['driver_id']);

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-offers/view.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job offer view', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job offer view', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit();
        }
    }

    /**
     * Αποδοχή μιας προσφοράς εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     */
    public function accept($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        try {
            AuthMiddleware::hasRole('driver');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως οδηγός για να αποδεχτείτε μια προσφορά.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job offer accept');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό προσφοράς');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit;
        }

        try {
            // Ανάκτηση της προσφοράς
            $offer = $this->jobOfferRepository->find($id);

            if (!$offer) {
                Session::set('error_message', 'Η προσφορά δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο παραλήπτης της προσφοράς
            $driverId = Session::get('user_id');
            if ($offer['driver_id'] != $driverId) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα αποδοχής αυτής της προσφοράς');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit;
            }

            // Έλεγχος αν η προσφορά μπορεί να γίνει αποδεκτή
            /*
             * Δεκτή και η «viewed».
             *
             * Μόλις το άνοιγμα της προσφοράς άρχισε να τη σημαίνει ως
             * αναγνωσμένη, ο έλεγχος «μόνο pending» έκλεινε την πόρτα σε
             * κάθε οδηγό που είχε την ευγένεια να τη διαβάσει πρώτα.
             */
            if (!in_array($offer['status'], ['pending', 'viewed'], true)) {
                Session::set('error_message', 'Δεν μπορείτε να αποδεχτείτε αυτή την προσφορά');
                header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
                exit;
            }

            // Ενημέρωση της κατάστασης της προσφοράς
            $data = [
                'status' => 'accepted',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updateResult = $this->jobOfferRepository->update($id, $data);

            if ($updateResult) {
                Logger::info('Job offer accepted', [
                    'driver_id' => $driverId,
                    'offer_id' => $id
                ]);

                Session::set('success_message', 'Η προσφορά εργασίας έγινε αποδεκτή με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
                exit();
            } else {
                Logger::error('Job offer accept failed', [
                    'driver_id' => $driverId,
                    'offer_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την αποδοχή της προσφοράς. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job offer accept', [
                'driver_id' => Session::get('user_id'),
                'offer_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job offer accept', [
                'driver_id' => Session::get('user_id'),
                'offer_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
            exit();
        }
    }

    /**
     * Απόρριψη μιας προσφοράς εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     */
    public function reject($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        try {
            AuthMiddleware::hasRole('driver');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως οδηγός για να απορρίψετε μια προσφορά.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job offer reject');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό προσφοράς');
            header('Location: ' . BASE_URL . 'job-offers/my-offers');
            exit;
        }

        try {
            // Ανάκτηση της προσφοράς
            $offer = $this->jobOfferRepository->find($id);

            if (!$offer) {
                Session::set('error_message', 'Η προσφορά δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο παραλήπτης της προσφοράς
            $driverId = Session::get('user_id');
            if ($offer['driver_id'] != $driverId) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα απόρριψης αυτής της προσφοράς');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit;
            }

            // Έλεγχος αν η προσφορά μπορεί να απορριφθεί
            /*
             * Δεκτή και η «viewed».
             *
             * Μόλις το άνοιγμα της προσφοράς άρχισε να τη σημαίνει ως
             * αναγνωσμένη, ο έλεγχος «μόνο pending» έκλεινε την πόρτα σε
             * κάθε οδηγό που είχε την ευγένεια να τη διαβάσει πρώτα.
             */
            if (!in_array($offer['status'], ['pending', 'viewed'], true)) {
                Session::set('error_message', 'Δεν μπορείτε να απορρίψετε αυτή την προσφορά');
                header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
                exit;
            }

            // Ενημέρωση της κατάστασης της προσφοράς
            $data = [
                'status' => 'rejected',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updateResult = $this->jobOfferRepository->update($id, $data);

            if ($updateResult) {
                Logger::info('Job offer rejected', [
                    'driver_id' => $driverId,
                    'offer_id' => $id
                ]);

                Session::set('success_message', 'Η προσφορά εργασίας απορρίφθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-offers/my-offers');
                exit();
            } else {
                Logger::error('Job offer reject failed', [
                    'driver_id' => $driverId,
                    'offer_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την απόρριψη της προσφοράς. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job offer reject', [
                'driver_id' => Session::get('user_id'),
                'offer_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job offer reject', [
                'driver_id' => Session::get('user_id'),
                'offer_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-offers/view/' . $id);
            exit();
        }
    }
}
