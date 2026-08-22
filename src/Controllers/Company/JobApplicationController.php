<?php

namespace Drivejob\Controllers\Company;

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

/**
 * Controller για τις αιτήσεις εργασίας από την πλευρά των επιχειρήσεων
 * 
 * Επεκτείνει τον BaseJobApplicationController για κοινές λειτουργίες
 */
class JobApplicationController extends BaseJobApplicationController
{
    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct($pdo);
    }

    /**
     * Εμφανίζει τις αιτήσεις για τις αγγελίες της συνδεδεμένης εταιρείας
     */
    public function myApplications()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        try {
            AuthMiddleware::hasRole('company');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως εταιρεία για να δείτε τις αιτήσεις σας.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        try {
            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση αιτήσεων για τις αγγελίες της εταιρείας
            $companyId = Session::get('user_id');
            $result = $this->jobApplicationRepository->findByCompany($companyId, $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $applications = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-applications/company-applications.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in company applications', [
                'company_id' => Session::get('user_id'),
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/profile');
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in company applications', [
                'company_id' => Session::get('user_id'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'companies/profile');
            exit();
        }
    }

    /**
     * Εμφανίζει τις αιτήσεις για μια συγκεκριμένη αγγελία
     * 
     * @param int $id Το ID της αγγελίας
     */
    public function listingApplications($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        try {
            AuthMiddleware::hasRole('company');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως εταιρεία για να δείτε τις αιτήσεις.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

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
            $companyId = Session::get('user_id');
            if ($listing['company_id'] != $companyId) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα προβολής των αιτήσεων για αυτή την αγγελία');
                header('Location: ' . BASE_URL . 'job-listings');
                exit;
            }

            // Λήψη της τρέχουσας σελίδας και του ορίου
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

            // Αναζήτηση αιτήσεων για την αγγελία
            $result = $this->jobApplicationRepository->findByListing($id, $page, $limit);

            // Αν είναι AJAX αίτημα, επιστροφή JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::response($result);
            }

            // Αλλιώς, φόρτωση του view
            $applications = $result['results'];
            $pagination = $result['pagination'];
            include ROOT_DIR . '/src/Views/job-applications/listing-applications.php';
        } catch (DatabaseException $e) {
            Logger::error('Database exception in listing applications', [
                'company_id' => Session::get('user_id'),
                'listing_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in listing applications', [
                'company_id' => Session::get('user_id'),
                'listing_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Αν είναι AJAX αίτημα, επιστροφή JSON με σφάλμα
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                JsonHelper::error('Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            }

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-listings/show/' . $id);
            exit();
        }
    }

    /**
     * Αποδοχή μιας αίτησης
     * 
     * @param int $id Το ID της αίτησης
     */
    public function accept($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        try {
            AuthMiddleware::hasRole('company');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως εταιρεία για να αποδεχτείτε μια αίτηση.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job application accept');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/company-applications');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αίτησης');
            header('Location: ' . BASE_URL . 'job-applications/company-applications');
            exit;
        }

        try {
            // Ανάκτηση της αίτησης
            $application = $this->jobApplicationRepository->find($id);

            if (!$application) {
                Session::set('error_message', 'Η αίτηση δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-applications/company-applications');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας.
            // Η εταιρεία προκύπτει μέσω της αγγελίας — δεν υπάρχει
            // στήλη company_id στον πίνακα job_applications.
            $companyId = Session::get('user_id');
            $ownerCompanyId = $this->companyIdOfApplication($application);
            if ($ownerCompanyId === null || $ownerCompanyId != $companyId) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα αποδοχής αυτής της αίτησης');
                header('Location: ' . BASE_URL . 'job-applications/company-applications');
                exit;
            }

            // Έλεγχος αν η αίτηση μπορεί να γίνει αποδεκτή
            if ($application['status'] !== 'pending') {
                Session::set('error_message', 'Δεν μπορείτε να αποδεχτείτε αυτή την αίτηση');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit;
            }

            // Ενημέρωση της κατάστασης της αίτησης
            // 'accepted' δεν υπάρχει στο enum της στήλης — το 'hired' εκφράζει το ίδιο.
            $updateResult = $this->updateApplicationStatus($id, 'hired');

            if ($updateResult) {
                Logger::info('Job application accepted', [
                    'company_id' => $companyId,
                    'application_id' => $id
                ]);

                Session::set('success_message', 'Η αίτηση έγινε αποδεκτή με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit();
            } else {
                Logger::error('Job application accept failed', [
                    'company_id' => $companyId,
                    'application_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την αποδοχή της αίτησης. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job application accept', [
                'company_id' => Session::get('user_id'),
                'application_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job application accept', [
                'company_id' => Session::get('user_id'),
                'application_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
            exit();
        }
    }

    /**
     * Απόρριψη μιας αίτησης
     * 
     * @param int $id Το ID της αίτησης
     */
    public function reject($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        try {
            AuthMiddleware::hasRole('company');
        } catch (AuthException $e) {
            Session::set('error_message', 'Πρέπει να συνδεθείτε ως εταιρεία για να απορρίψετε μια αίτηση.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Logger::error('CSRF token validation failed in job application reject');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/company-applications');
            exit();
        }

        // Έλεγχος αν το ID είναι έγκυρο
        if (!$id || !is_numeric($id)) {
            Session::set('error_message', 'Μη έγκυρο αναγνωριστικό αίτησης');
            header('Location: ' . BASE_URL . 'job-applications/company-applications');
            exit;
        }

        try {
            // Ανάκτηση της αίτησης
            $application = $this->jobApplicationRepository->find($id);

            if (!$application) {
                Session::set('error_message', 'Η αίτηση δεν βρέθηκε');
                header('Location: ' . BASE_URL . 'job-applications/company-applications');
                exit;
            }

            // Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας.
            // Η εταιρεία προκύπτει μέσω της αγγελίας — δεν υπάρχει
            // στήλη company_id στον πίνακα job_applications.
            $companyId = Session::get('user_id');
            $ownerCompanyId = $this->companyIdOfApplication($application);
            if ($ownerCompanyId === null || $ownerCompanyId != $companyId) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα απόρριψης αυτής της αίτησης');
                header('Location: ' . BASE_URL . 'job-applications/company-applications');
                exit;
            }

            // Έλεγχος αν η αίτηση μπορεί να απορριφθεί
            if ($application['status'] !== 'pending') {
                Session::set('error_message', 'Δεν μπορείτε να απορρίψετε αυτή την αίτηση');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit;
            }

            // Ενημέρωση της κατάστασης της αίτησης
            $updateResult = $this->updateApplicationStatus($id, 'rejected');

            if ($updateResult) {
                Logger::info('Job application rejected', [
                    'company_id' => $companyId,
                    'application_id' => $id
                ]);

                Session::set('success_message', 'Η αίτηση απορρίφθηκε με επιτυχία.');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit();
            } else {
                Logger::error('Job application reject failed', [
                    'company_id' => $companyId,
                    'application_id' => $id
                ]);

                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την απόρριψη της αίτησης. Παρακαλώ δοκιμάστε ξανά.');
                header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
                exit();
            }
        } catch (DatabaseException $e) {
            Logger::error('Database exception in job application reject', [
                'company_id' => Session::get('user_id'),
                'application_id' => $id,
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);

            Session::set('error_message', 'Υπήρξε ένα σφάλμα βάσης δεδομένων. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'job-applications/view/' . $id);
            exit();
        } catch (\Exception $e) {
            Logger::error('Exception in job application reject', [
                'company_id' => Session::get('user_id'),
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
