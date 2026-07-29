<?php

namespace Drivejob\Controllers;

use Drivejob\Controllers\BaseController;
use Drivejob\Core\Session;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Services\AI\MatchingService;

/**
 * Controller για το ταίριασμα αγγελιών και οδηγών
 */
class MatchingController extends BaseController
{
    /**
     * @var MatchingService Η υπηρεσία ταιριάσματος
     */
    private $matchingService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Αρχικοποίηση του MatchingService
        $this->matchingService = new MatchingService();
    }

    /**
     * Εμφανίζει τα ταιριάσματα αγγελιών για έναν οδηγό
     */
    public function driverMatches()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Λήψη του ID του οδηγού
        $driverId = Session::get('user_id');

        // Λήψη των κριτηρίων αναζήτησης
        $criteria = [
            'location' => $_GET['location'] ?? null,
            'job_type' => $_GET['job_type'] ?? null,
            'vehicle_type' => $_GET['vehicle_type'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'match_score',
            'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
        ];

        // Λήψη της τρέχουσας σελίδας και του ορίου
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        // Εύρεση ταιριασμάτων για τον οδηγό
        $result = $this->matchingService->findMatchesForDriver($driverId, $criteria, $page, $limit);

        // Φόρτωση του view
        $matches = $result['results'];
        $pagination = $result['pagination'];

        // Καταγραφή των προβολών των αγγελιών
        foreach ($matches as $match) {
            $this->matchingService->logMatchAction(
                $driverId,
                $match['id'],
                $match['match_score'],
                'viewed',
                'no_action'
            );
        }

        include ROOT_DIR . '/src/Views/matching/driver-matches.php';
    }

    /**
     * Εμφανίζει τα ταιριάσματα οδηγών για μια αγγελία
     * 
     * @param int $jobListingId Το ID της αγγελίας
     */
    public function jobListingMatches($jobListingId)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Λήψη του ID της εταιρείας
        $companyId = Session::get('user_id');

        // Λήψη των κριτηρίων αναζήτησης
        $criteria = [
            'location' => $_GET['location'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'match_score',
            'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
        ];

        // Λήψη της τρέχουσας σελίδας και του ορίου
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        // Εύρεση ταιριασμάτων για την αγγελία
        $result = $this->matchingService->findMatchesForJobListing($jobListingId, $criteria, $page, $limit);

        // Φόρτωση του view
        $matches = $result['results'];
        $pagination = $result['pagination'];

        // Καταγραφή των προβολών των οδηγών
        foreach ($matches as $match) {
            $this->matchingService->logMatchAction(
                $match['id'],
                $jobListingId,
                $match['match_score'],
                'no_action',
                'viewed'
            );
        }

        include ROOT_DIR . '/src/Views/matching/job-listing-matches.php';
    }

    /**
     * Εμφανίζει τα ταιριάσματα οδηγών για μια εταιρεία
     */
    public function companyMatches()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Λήψη του ID της εταιρείας
        $companyId = Session::get('user_id');

        // Λήψη των κριτηρίων αναζήτησης
        $criteria = [
            'location' => $_GET['location'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'match_score',
            'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
        ];

        // Λήψη της τρέχουσας σελίδας και του ορίου
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

        // Εύρεση ταιριασμάτων για την εταιρεία
        $result = $this->matchingService->findMatchesForCompany($companyId, $criteria, $page, $limit);

        // Φόρτωση του view
        $matches = $result['results'];
        $pagination = $result['pagination'];

        include ROOT_DIR . '/src/Views/matching/company-matches.php';
    }

    /**
     * Αποθηκεύει τις προτιμήσεις ταιριάσματος ενός χρήστη
     */
    public function savePreferences()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::isLoggedIn();

        // Λήψη του ID και του τύπου του χρήστη
        $userId = Session::get('user_id');
        $userType = Session::get('user_role');

        // Λήψη των προτιμήσεων από το POST
        $preferences = [
            'location_weight' => isset($_POST['location_weight']) ? (float)$_POST['location_weight'] : null,
            'job_type_weight' => isset($_POST['job_type_weight']) ? (float)$_POST['job_type_weight'] : null,
            'vehicle_type_weight' => isset($_POST['vehicle_type_weight']) ? (float)$_POST['vehicle_type_weight'] : null,
            'license_weight' => isset($_POST['license_weight']) ? (float)$_POST['license_weight'] : null,
            'experience_weight' => isset($_POST['experience_weight']) ? (float)$_POST['experience_weight'] : null,
            'skills_weight' => isset($_POST['skills_weight']) ? (float)$_POST['skills_weight'] : null,
            'schedule_weight' => isset($_POST['schedule_weight']) ? (float)$_POST['schedule_weight'] : null,
            'rating_weight' => isset($_POST['rating_weight']) ? (float)$_POST['rating_weight'] : null
        ];

        // Αποθήκευση των προτιμήσεων
        $success = $this->matchingService->saveMatchPreferences($userId, $userType, $preferences);

        if ($success) {
            Session::set('success_message', 'Οι προτιμήσεις ταιριάσματος αποθηκεύτηκαν με επιτυχία.');
        } else {
            Session::set('error_message', 'Σφάλμα κατά την αποθήκευση των προτιμήσεων ταιριάσματος.');
        }

        // Ανακατεύθυνση στην προηγούμενη σελίδα
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        header('Location: ' . $referer);
        exit();
    }

    /**
     * Εμφανίζει τη σελίδα προτιμήσεων ταιριάσματος
     */
    public function preferences()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::isLoggedIn();

        // Λήψη του ID και του τύπου του χρήστη
        $userId = Session::get('user_id');
        $userType = Session::get('user_role');

        // Λήψη των προτιμήσεων ταιριάσματος του χρήστη
        $preferences = $this->matchingService->getMatchPreferences($userId, $userType);

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/matching/preferences.php';
    }

    /**
     * Καταγράφει μια ενέργεια ταιριάσματος (αίτηση, αποδοχή, απόρριψη)
     */
    public function logAction()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::isLoggedIn();

        // Λήψη των παραμέτρων από το POST
        $driverId = $_POST['driver_id'] ?? null;
        $jobListingId = $_POST['job_listing_id'] ?? null;
        $matchScore = $_POST['match_score'] ?? 0;
        $driverAction = $_POST['driver_action'] ?? 'no_action';
        $companyAction = $_POST['company_action'] ?? 'no_action';

        // Έλεγχος αν έχουν οριστεί τα απαραίτητα πεδία
        if (!$driverId || !$jobListingId) {
            Session::set('error_message', 'Λείπουν απαραίτητα πεδία.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Καταγραφή της ενέργειας
        $success = $this->matchingService->logMatchAction(
            $driverId,
            $jobListingId,
            $matchScore,
            $driverAction,
            $companyAction
        );

        if ($success) {
            Session::set('success_message', 'Η ενέργεια καταγράφηκε με επιτυχία.');
        } else {
            Session::set('error_message', 'Σφάλμα κατά την καταγραφή της ενέργειας.');
        }

        // Ανακατεύθυνση στην προηγούμενη σελίδα
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        header('Location: ' . $referer);
        exit();
    }
}
