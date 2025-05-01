<?php

namespace Drivejob\Controllers;


use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\JobListingModel;
use Drivejob\Models\MatchingModel;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;

class MatchingController
{
    private $jobListingModel;
    private $matchingModel;
    
    private $licenseModel;
    private $skillModel;
    private $ratingModel;
    private $pdo;

    public function __construct($pdo = null)
    {
        if ($pdo === null && isset($GLOBALS['pdo'])) {
            $pdo = $GLOBALS['pdo'];
        }

        $this->pdo = $pdo;
        $this->jobListingModel = new JobListingModel($pdo);
        $this->matchingModel = new MatchingModel($pdo);
        $this->profileModel = new ProfileModel($pdo);  // Αλλαγή
        $this->licenseModel = new LicenseModel($pdo);
        $this->skillModel = new SkillModel($pdo);
        $this->ratingModel = new RatingModel($pdo);
    }

    /**
     * Εμφανίζει τις προτεινόμενες αγγελίες για έναν οδηγό με βάση το προφίλ του
     */
    public function driverMatches()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Λήψη του ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Λήψη του προφίλ του οδηγού
        $driverProfile = $this->profileModel->getDriverById($driverId);

        // Παράμετροι για την αναζήτηση αγγελιών που ταιριάζουν με το προφίλ
        $params = $this->getMatchingParamsForDriver($driverProfile);

        // Λήψη των αγγελιών που ταιριάζουν με τα κριτήρια
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $listings = $this->jobListingModel->getActiveListings($params, $page, $limit);

        // Υπολογισμός ποσοστού ταιριάσματος για κάθε αγγελία
        $matchedListings = $this->calculateMatchPercentage($listings['results'], $driverProfile);

        // Ταξινόμηση των αγγελιών με βάση το ποσοστό ταιριάσματος (φθίνουσα σειρά)
        usort($matchedListings, function ($a, $b) {
            return $b['match_percentage'] <=> $a['match_percentage'];
        });

        // Ενημέρωση του πίνακα αποτελεσμάτων
        $listings['results'] = $matchedListings;

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/driver-matches.php';
    }

    /**
     * Εμφανίζει τους προτεινόμενους οδηγούς για μια εταιρεία με βάση τις αγγελίες της
     */
    public function companyMatches()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Λήψη του ID της συνδεδεμένης εταιρείας
        $companyId = $_SESSION['user_id'];

        // Λήψη του προφίλ της εταιρείας
        $companyProfile = $this->companiesModel->getCompanyById($companyId);

        // Λήψη των ενεργών αγγελιών της εταιρείας
        $companyListings = $this->jobListingModel->getCompanyListings($companyId, true, 1, 100);

        // Λήψη των παραμέτρων αναζήτησης για όλες τις αγγελίες της εταιρείας
        $matchingParams = [];
        foreach ($companyListings['results'] as $listing) {
            $matchingParams[] = $this->getMatchingParamsForListing($listing);
        }

        // Λήψη οδηγών που ταιριάζουν με τις αγγελίες της εταιρείας
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $matchedDrivers = $this->findMatchingDrivers($matchingParams, $page, $limit);

        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/job-listings/company-matches.php';
    }

    /**
     * Δημιουργεί παραμέτρους αναζήτησης με βάση το προφίλ του οδηγού
     *
     * @param array $driverProfile Το προφίλ του οδηγού
     * @return array Παράμετροι αναζήτησης
     */
    private function getMatchingParamsForDriver($driverProfile)
    {
        $params = [];

        // Τύπος αγγελίας: Για οδηγούς ψάχνουμε προσφορές εργασίας
        $params['listing_type'] = 'job_offer';

        // Προτιμώμενος τύπος απασχόλησης
        if (!empty($driverProfile['preferred_job_type'])) {
            $params['job_type'] = $driverProfile['preferred_job_type'];
        }

        // Προτιμώμενος τύπος οχήματος
        if (!empty($driverProfile['preferred_vehicle_type'])) {
            $params['vehicle_type'] = $driverProfile['preferred_vehicle_type'];
        }

        // Γεωγραφική τοποθεσία και ακτίνα αναζήτησης
        if (!empty($driverProfile['latitude']) && !empty($driverProfile['longitude'])) {
            $params['latitude'] = $driverProfile['latitude'];
            $params['longitude'] = $driverProfile['longitude'];
            $params['search_radius'] = $driverProfile['preferred_radius'] ?? 50; // Default 50km αν δεν έχει οριστεί
        }

        // Ειδικές απαιτήσεις σύμφωνα με το προφίλ του οδηγού
        if (!empty($driverProfile['adr_certificate']) && $driverProfile['adr_certificate'] == 1) {
            $params['adr_certificate'] = true;
        }

        if (!empty($driverProfile['operator_license']) && $driverProfile['operator_license'] == 1) {
            $params['operator_license'] = true;
        }

        // Εμπειρία οδηγού
        if (!empty($driverProfile['experience_years'])) {
            $params['min_experience'] = $driverProfile['experience_years'];
        }

        return $params;
    }

    /**
     * Δημιουργεί παραμέτρους αναζήτησης οδηγών με βάση μια αγγελία
     *
     * @param array $listing Η αγγελία
     * @return array Παράμετροι αναζήτησης
     */
    private function getMatchingParamsForListing($listing)
    {
        $params = [];

        // Τύπος απασχόλησης
        if (!empty($listing['job_type'])) {
            $params['job_type'] = $listing['job_type'];
        }

        // Τύπος οχήματος
        if (!empty($listing['vehicle_type'])) {
            $params['vehicle_type'] = $listing['vehicle_type'];
        }

        // Γεωγραφική τοποθεσία και ακτίνα αναζήτησης
        if (!empty($listing['latitude']) && !empty($listing['longitude'])) {
            $params['latitude'] = $listing['latitude'];
            $params['longitude'] = $listing['longitude'];
            $params['search_radius'] = $listing['radius'] ?? 50; // Default 50km αν δεν έχει οριστεί
        }

        // Ειδικές απαιτήσεις
        if (!empty($listing['adr_certificate']) && $listing['adr_certificate'] == 1) {
            $params['adr_certificate'] = true;
        }

        if (!empty($listing['operator_license']) && $listing['operator_license'] == 1) {
            $params['operator_license'] = true;
        }

        // Εμπειρία οδηγού
        if (!empty($listing['experience_years'])) {
            $params['min_experience'] = $listing['experience_years'];
        }

        return $params;
    }

    /**
     * Υπολογίζει το ποσοστό ταιριάσματος για κάθε αγγελία με βάση το προφίλ του οδηγού
     *
     * @param array $listings Οι αγγελίες
     * @param array $driverProfile Το προφίλ του οδηγού
     * @return array Οι αγγελίες με το ποσοστό ταιριάσματος
     */
    private function calculateMatchPercentage($listings, $driverProfile)
    {
        $matchedListings = [];

        foreach ($listings as $listing) {
            $score = 0;
            $maxScore = 0;

            // Τύπος απασχόλησης (20 βαθμοί)
            if (!empty($driverProfile['preferred_job_type']) && !empty($listing['job_type'])) {
                $maxScore += 20;
                if ($driverProfile['preferred_job_type'] === $listing['job_type']) {
                    $score += 20;
                }
            }

            // Τύπος οχήματος (20 βαθμοί)
            if (!empty($driverProfile['preferred_vehicle_type']) && !empty($listing['vehicle_type'])) {
                $maxScore += 20;
                if ($driverProfile['preferred_vehicle_type'] === $listing['vehicle_type']) {
                    $score += 20;
                }
            }

            // Απόσταση (30 βαθμοί)
            if (
                !empty($driverProfile['latitude']) && !empty($driverProfile['longitude']) &&
                !empty($listing['latitude']) && !empty($listing['longitude'])
            ) {
                $maxScore += 30;

                // Υπολογισμός απόστασης σε χιλιόμετρα
                $distance = $this->calculateDistance(
                    $driverProfile['latitude'],
                    $driverProfile['longitude'],
                    $listing['latitude'],
                    $listing['longitude']
                );

                // Προτιμώμενη ακτίνα του οδηγού
                $preferredRadius = $driverProfile['preferred_radius'] ?? 50;

                if ($distance <= $preferredRadius) {
                    // Όσο πιο κοντά, τόσο υψηλότερο το σκορ
                    $score += 30 * (1 - ($distance / $preferredRadius));
                }
            }

            // Ειδικές απαιτήσεις (20 βαθμοί)
            // ADR (10 βαθμοί)
            if (!empty($listing['adr_certificate']) && $listing['adr_certificate'] == 1) {
                $maxScore += 10;
                if (!empty($driverProfile['adr_certificate']) && $driverProfile['adr_certificate'] == 1) {
                    $score += 10;
                }
            }

            // Άδεια χειριστή (10 βαθμοί)
            if (!empty($listing['operator_license']) && $listing['operator_license'] == 1) {
                $maxScore += 10;
                if (!empty($driverProfile['operator_license']) && $driverProfile['operator_license'] == 1) {
                    $score += 10;
                }
            }

            // Εμπειρία (10 βαθμοί)
            if (!empty($listing['experience_years'])) {
                $maxScore += 10;
                if (!empty($driverProfile['experience_years']) && $driverProfile['experience_years'] >= $listing['experience_years']) {
                    $score += 10;
                }
            }

            // Υπολογισμός τελικού ποσοστού
            $matchPercentage = ($maxScore > 0) ? round(($score / $maxScore) * 100) : 0;

            // Προσθήκη του ποσοστού ταιριάσματος στην αγγελία
            $listing['match_percentage'] = $matchPercentage;
            $matchedListings[] = $listing;
        }

        return $matchedListings;
    }

    /**
     * Υπολογίζει την απόσταση μεταξύ δύο σημείων με τον τύπο Haversine
     *
     * @param float $lat1 Γεωγραφικό πλάτος σημείου 1
     * @param float $lon1 Γεωγραφικό μήκος σημείου 1
     * @param float $lat2 Γεωγραφικό πλάτος σημείου 2
     * @param float $lon2 Γεωγραφικό μήκος σημείου 2
     * @return float Απόσταση σε χιλιόμετρα
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c; // Distance in km

        return $distance;
    }

    /**
     * Βρίσκει οδηγούς που ταιριάζουν με τις παραμέτρους αναζήτησης
     *
     * @param array $params Παράμετροι αναζήτησης
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Οδηγοί που ταιριάζουν
     */
    private function findMatchingDrivers($params, $page, $limit)
    {
        // Συνδυασμός παραμέτρων από όλες τις αγγελίες
        $combinedParams = [];

        // Συνδυασμός παραμέτρων αναζήτησης
        foreach ($params as $param) {
            foreach ($param as $key => $value) {
                if ($key === 'job_type' || $key === 'vehicle_type') {
                    if (!isset($combinedParams[$key])) {
                        $combinedParams[$key] = [];
                    }
                    if (!in_array($value, $combinedParams[$key])) {
                        $combinedParams[$key][] = $value;
                    }
                } else {
                    // Για τις υπόλοιπες παραμέτρους, παίρνουμε τη μέγιστη τιμή
                    if (!isset($combinedParams[$key]) || $value > $combinedParams[$key]) {
                        $combinedParams[$key] = $value;
                    }
                }
            }
        }

        // Αναζήτηση οδηγών με βάση τις συνδυασμένες παραμέτρους
        return $this->profileModel->searchDrivers($combinedParams, $page, $limit);
    }

    /**
     * Αποθηκεύει μια αίτηση για μια αγγελία
     *
     * @param int $listingId ID της αγγελίας
     */
    public function applyToListing($listingId)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως οδηγός
        AuthMiddleware::hasRole('driver');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
            exit();
        }

        // Λήψη του ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];

        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($listingId);

        if (!$listing) {
            $_SESSION['error_message'] = 'Η αγγελία δεν βρέθηκε.';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Έλεγχος αν η αγγελία είναι προσφορά εργασίας
        if ($listing['listing_type'] !== 'job_offer') {
            $_SESSION['error_message'] = 'Μπορείτε να υποβάλετε αίτηση μόνο σε αγγελίες προσφοράς εργασίας.';
            header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
            exit();
        }

        // Αποθήκευση της αίτησης στο μοντέλο αιτήσεων όταν δημιουργηθεί
        // $this->applicationsModel->create([
        //     'job_listing_id' => $listingId,
        //     'driver_id' => $driverId,
        //     'message' => $_POST['message'] ?? '',
        //     'status' => 'pending'
        // ]);

        $_SESSION['success_message'] = 'Η αίτησή σας υποβλήθηκε με επιτυχία.';
        header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
        exit();
    }

    /**
     * Επικοινωνία με έναν οδηγό που έχει δημοσιεύσει αγγελία αναζήτησης εργασίας
     *
     * @param int $listingId ID της αγγελίας
     */
    public function contactDriver($listingId)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος ως εταιρεία
        AuthMiddleware::hasRole('company');

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
            exit();
        }

        // Λήψη του ID της συνδεδεμένης εταιρείας
        $companyId = $_SESSION['user_id'];

        // Λήψη της αγγελίας
        $listing = $this->jobListingModel->getById($listingId);

        if (!$listing) {
            $_SESSION['error_message'] = 'Η αγγελία δεν βρέθηκε.';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Έλεγχος αν η αγγελία είναι αναζήτηση εργασίας
        if ($listing['listing_type'] !== 'job_search') {
            $_SESSION['error_message'] = 'Μπορείτε να επικοινωνήσετε μόνο με οδηγούς που αναζητούν εργασία.';
            header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
            exit();
        }

        // Αποθήκευση του μηνύματος σε ένα μοντέλο επικοινωνίας όταν δημιουργηθεί
        // $this->messagesModel->create([
        //     'job_listing_id' => $listingId,
        //     'company_id' => $companyId,
        //     'driver_id' => $listing['driver_id'],
        //     'message' => $_POST['message'] ?? '',
        //     'status' => 'unread'
        // ]);

        $_SESSION['success_message'] = 'Το μήνυμά σας στάλθηκε με επιτυχία.';
        header('Location: ' . BASE_URL . 'job-listings/show/' . $listingId);
        exit();
    }
}
