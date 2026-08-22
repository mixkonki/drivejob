<?php

namespace Drivejob\Controllers;

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
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\JobApplicationRepository;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\CompaniesRepository;
use Drivejob\Helpers\JsonHelper;

/**
 * Βασικός Controller για τις αιτήσεις εργασίας
 * 
 * Περιέχει κοινές λειτουργίες για τις αιτήσεις εργασίας από οδηγούς και εταιρείες
 */
class BaseJobApplicationController extends BaseController
{
    /**
     * @var JobApplicationRepository Το repository για τις αιτήσεις εργασίας
     */
    protected $jobApplicationRepository;

    /**
     * @var JobListingRepository Το repository για τις αγγελίες εργασίας
     */
    protected $jobListingRepository;

    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    protected $driversRepository;

    /**
     * @var CompaniesRepository Το repository για τις εταιρείες
     */
    protected $companiesRepository;

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
        $container = Container::getInstance();

        // Αν δεν έχει παραχθεί PDO, πάρε το από το container
        if ($pdo === null) {
            $pdo = $container->get('pdo');
        }

        // Αρχικοποίηση των repositories
        $this->jobApplicationRepository = new JobApplicationRepository($pdo);
        $this->jobListingRepository = new JobListingRepository($pdo);
        $this->driversRepository = new DriversRepository($pdo);
        $this->companiesRepository = new CompaniesRepository($pdo);
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

            // Ανάκτηση της αγγελίας — από εκεί προκύπτει η εταιρεία,
            // ο πίνακας job_applications δεν έχει στήλη company_id.
            $listing = $this->jobListingRepository->find($application['job_listing_id']);
            $listingCompanyId = $listing['company_id'] ?? null;

            // Έλεγχος αν ο χρήστης έχει δικαίωμα προβολής της αίτησης
            $userRole = Session::get('user_role');
            $userId = Session::get('user_id');

            $hasAccess = false;
            if ($userRole === 'driver' && $application['driver_id'] == $userId) {
                $hasAccess = true;
            } else if ($userRole === 'company' && $listingCompanyId !== null && $listingCompanyId == $userId) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                Session::set('error_message', 'Δεν έχετε δικαίωμα προβολής αυτής της αίτησης');
                header('Location: ' . BASE_URL);
                exit;
            }

            // Ανάκτηση του οδηγού
            $driver = $this->driversRepository->find($application['driver_id']);

            // Ανάκτηση της εταιρείας
            $company = $listingCompanyId !== null
                ? $this->companiesRepository->find($listingCompanyId)
                : null;

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
     * Ενημερώνει την κατάσταση μιας αίτησης
     * 
     * @param int $id Το ID της αίτησης
     * @param string $status Η νέα κατάσταση της αίτησης
     * @return bool Επιτυχία/αποτυχία
     */
    protected function updateApplicationStatus($id, $status)
    {
        try {
            // Ανάκτηση της αίτησης
            $application = $this->jobApplicationRepository->find($id);

            if (!$application) {
                return false;
            }

            // Ενημέρωση της κατάστασης της αίτησης
            $data = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->jobApplicationRepository->update($id, $data);
        } catch (\Exception $e) {
            Logger::error('Exception in updateApplicationStatus', [
                'id' => $id,
                'status' => $status,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Ελέγχει αν ο χρήστης έχει δικαίωμα πρόσβασης σε μια αίτηση
     * 
     * @param array $application Η αίτηση
     * @return bool Αν ο χρήστης έχει δικαίωμα πρόσβασης
     */
    protected function hasAccessToApplication($application)
    {
        $userRole = Session::get('user_role');
        $userId = Session::get('user_id');

        if ($userRole === 'driver' && $application['driver_id'] == $userId) {
            return true;
        }

        if ($userRole === 'company') {
            $companyId = $this->companyIdOfApplication($application);

            return $companyId !== null && $companyId == $userId;
        }

        return false;
    }

    /**
     * Επιστρέφει το ID της εταιρείας στην οποία ανήκει μια αίτηση.
     *
     * Ο πίνακας job_applications δεν έχει στήλη company_id — η εταιρεία
     * προκύπτει μόνο μέσω της αγγελίας (job_listings.company_id).
     *
     * @param array $application Η αίτηση
     * @return int|null Το ID της εταιρείας ή null αν η αγγελία δεν υπάρχει
     */
    protected function companyIdOfApplication($application): ?int
    {
        if (empty($application['job_listing_id'])) {
            return null;
        }

        $listing = $this->jobListingRepository->find($application['job_listing_id']);

        return isset($listing['company_id']) ? (int) $listing['company_id'] : null;
    }
}
