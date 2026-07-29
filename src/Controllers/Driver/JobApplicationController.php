<?php

namespace Drivejob\Controllers\Driver;

use Drivejob\Controllers\BaseJobApplicationController;
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

/**
 * Controller για τις αιτήσεις εργασίας από οδηγούς
 * 
 * Επεκτείνει τον BaseJobApplicationController για κοινές λειτουργίες
 */
class JobApplicationController extends BaseJobApplicationController
{
    /**
     * @var FileService Η υπηρεσία για τη διαχείριση αρχείων
     */
    private $fileService;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct($pdo);

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
     * Υποβολή αίτησης για μια αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function apply($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        try {
            AuthMiddleware::hasRole('driver');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως οδηγός για να υποβάλετε αίτηση.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job application');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/' . $id);
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

            // Έλεγχος αν η αγγελία είναι ενεργή και εγκεκριμένη
            if (!$listing['is_active'] || !$listing['is_approved']) {
                Session::set('error_message', 'Η αγγελία δεν είναι διαθέσιμη για αιτήσεις');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Έλεγχος αν η αγγελία είναι από εταιρεία (όχι από οδηγό)
            if (empty($listing['company_id'])) {
                Session::set('error_message', 'Δεν μπορείτε να υποβάλετε αίτηση σε αυτή την αγγελία');
                header('Location: ' . BASE_URL . 'job-listings/' . $id);
                exit;
            }

            // Έλεγχος αν ο οδηγός έχει ήδη υποβάλει αίτηση
            $driverId = Session::get('user_id');
            $existingApplication = $this->jobApplicationRepository->findByDriverAndListing($driverId, $id);

            if ($existingApplication) {
                Session::set('error_message', 'Έχετε ήδη υποβάλει αίτηση για αυτή την αγγελία');
                header('Location: ' . BASE_URL . 'job-listings/' . $id);
                exit;
            }

            // Συλλογή των δεδομένων από τη φόρμα
            $data = [
                'job_listing_id' => $id,
                'driver_id' => $driverId,
                'company_id' => $listing['company_id'],
                'message' => isset($_POST['message']) ? htmlspecialchars($_POST['message']) : null,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Επεξεργασία των αρχείων που ανεβάζονται
            if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
                $resumePath = $this->uploadFile($_FILES['resume_file'], 'resume_file', 'document');
                if ($resumePath) {
                    $data['resume_file'] = $resumePath;
                    Logger::info('Επιτυχές ανέβασμα βιογραφικού', [
                        'driver_id' => $driverId,
                        'file_path' => $resumePath
                    ]);
                } else {
                    Session::set('error_message', 'Υπήρξε ένα πρόβλημα κατά το ανέβασμα του βιογραφικού.');
                    header('Location: ' . BASE_URL . 'job-listings/' . $id);
                    exit();
                }
            }

            // Επεξεργασία άλλων αρχείων
            $fileTypes = [
                'cover_letter' => 'document',
                'additional_document' => 'document',
                'portfolio' => 'document'
            ];

            foreach ($fileTypes as $fileField => $category) {
                if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
                    $filePath = $this->uploadFile($_FILES[$fileField], $fileField, $category);
                    if ($filePath) {
                        $data[$fileField] = $filePath;
                        Logger::info('Επιτυχές ανέβασμα αρχείου', [
                            'driver_id' => $driverId,
                            'file_type' => $fileField,
                            'file_path' => $filePath
                        ]);
                    }
                }
            }

            // Δημιουργία της αίτησης
            $applicationId = $this->jobApplicationRepository->create($data);

            if ($applicationId) {
                // Ενημέρωση του μετρητή αιτήσεων της αγγελίας
                $this->jobListingRepository->incrementApplications($id);

                Logger::info('Job application successful', [
                    'driver_id' => $driverId,
                    'listing_id' => $id,
                    'application_id' => $applicationId
                ]);

                Session::set('success_message', 'Η αίτησή σας υποβλήθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-listings/' . $id);
                exit();
            } else {
                Logger::error('Job application failed', [
                    'driver_id' => $driverId,
                    'listing_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την υποβολή της αίτησης. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-listings/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job application', [
                'driver_id' => Session::get('user_id'),
                'listing_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job application', [
                'driver_id' => Session::get('user_id'),
                'listing_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/' . $id);
            exit();
        }
    }

    /**
     * Εμφανίζει τις αιτήσεις του συνδεδεμένου χρήστη
     */
    public function myApplications()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        try {
            AuthMiddleware::hasRole('driver');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως οδηγός για να δείτε τις αιτήσεις σας.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        try {
            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση αιτήσεων του οδηγού
            $driverId = Session::get('user_id');
            $result = $this->jobApplicationRepository->findByDriver($driverId, $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $applications = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-applications/my-applications.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in my applications', [
                'driver_id' => Session::get('user_id'),
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in my applications', [
                'driver_id' => Session::get('user_id'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }
    }

    /**
     * Εμφανίζει μια αίτηση
     * 
     * @param int $id Το ID της αίτησης
     */
    public function viewApplication($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε για να δείτε αυτή την αίτηση.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αίτησης');
            header('Location: ' . BASE_URL . 'job-applications/my-applications');
            exit;
        }

        try {
            // Ανάκτηση της αίτησης
            $application = $this->jobApplicationRepository->find($id);

            if (!$application) {
                Session::set('error_message', 'Η αίτηση δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-applications/my-applications');
                exit;
            }

            // Έλεγχος αν ο χρήστης έχει δικαίωμα προβολής της αίτησης
            $userRole = Session::get('user_role');
            $userId = Session::get('user_id');

            $hasAccess = false;
            if ($userRole === 'driver' && $application['driver_id'] == $userId) {
                $hasAccess = true;
            } else if ($userRole === 'company' && $application['company_id'] == $userId) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα προβολής αυτής της αίτησης');
                header('Location: ' . BASE_URL . 'home');
                exit;
            }

            // Ανάκτηση της αγγελίας
            $listing = $this->jobListingRepository->find($application['job_listing_id']);

            // Ανάκτηση του οδηγού
            $driver = $this->driversRepository->find($application['driver_id']);

            // Ανάκτηση της εταιρείας
            $company = $this->companiesRepository->find($application['company_id']);

            // Φόρτωση του view
            include ROOT_DIR . '/src/Views/job-applications/view.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job application view', [
                'id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/my-applications');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job application view', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/my-applications');
            exit();
        }
    }

    /**
     * Απόσυρση μιας αίτησης
     * 
     * @param int $id Το ID της αίτησης
     */
    public function withdraw($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        try {
            AuthMiddleware::hasRole('driver');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως οδηγός για να αποσύρετε μια αίτηση.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job application withdraw');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/my-applications');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αίτησης');
            header('Location: ' . BASE_URL . 'job-applications/my-applications');
            exit;
        }

        try {
            // Ανάκτηση της αίτησης
            $application = $this->jobApplicationRepository->find($id);

            if (!$application) {
                Session::set('error_message', 'Η αίτηση δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-applications/my-applications');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αίτησης
            $driverId = Session::get('user_id');
            if ($application['driver_id'] != $driverId) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα απόσυρσης αυτής της αίτησης');
                header('Location: ' . BASE_URL . 'job-applications/my-applications');
                exit;
            }

            // Έλεγχος αν η αίτηση μπορεί να αποσυρθεί
            if ($application['status'] !== 'pending') {
                Session::set('error_message', 'Δεν μπορείτε να αποσύρετε αυτή την αίτηση');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit;
            }

            // Ενημέρωση της κατάστασης της αίτησης
            $data = [
                'status' => 'withdrawn',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updateResult = $this->jobApplicationRepository->update($id, $data);

            if ($updateResult) {
                Logger::info('Job application withdrawn', [
                    'driver_id' => $driverId,
                    'application_id' => $id
                ]);

                Session::set('success_message', 'Η αίτησή σας αποσύρθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-applications/my-applications');
                exit();
            } else {
                Logger::error('Job application withdraw failed', [
                    'driver_id' => $driverId,
                    'application_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την απόσυρση της αίτησης. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job application withdraw', [
                'driver_id' => Session::get('user_id'),
                'application_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job application withdraw', [
                'driver_id' => Session::get('user_id'),
                'application_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
            exit();
        }
    }
}
