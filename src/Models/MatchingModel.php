<?php

namespace Drivejob\Models;

use Drivejob\Core\Logger;

/**
 * Μοντέλο για τη διαχείριση του ταιριάσματος μεταξύ οδηγών και αγγελιών
 */
class MatchingModel
{
    private $pdo;
    private $jobListingModel;
    private $profileModel;

    /**
     * Κατασκευαστής του μοντέλου
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->jobListingModel = new JobListingModel($pdo);
        $this->profileModel = new ProfileModel($pdo);
    }

    /**
     * Βρίσκει αγγελίες που ταιριάζουν με το προφίλ ενός οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @param int $matchThreshold Ελάχιστο ποσοστό ταιριάσματος (0-100)
     * @return array Αγγελίες που ταιριάζουν με το προφίλ του οδηγού
     */
    public function findMatchingListingsForDriver($driverId, $page = 1, $limit = 10, $matchThreshold = 0)
    {
        try {
            // Λήψη του προφίλ του οδηγού
            $driverProfile = $this->profileModel->getDriverById($driverId);

            if (!$driverProfile) {
                return [
                    'results' => [],
                    'pagination' => [
                        'total' => 0,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => 0
                    ]
                ];
            }

            // Παράμετροι για την αναζήτηση αγγελιών που ταιριάζουν με το προφίλ
            $params = $this->getMatchingParamsForDriver($driverProfile);

            // Λήψη των αγγελιών που ταιριάζουν με τα κριτήρια
            $listings = $this->jobListingModel->getActiveListings($params, $page, $limit);

            // Υπολογισμός ποσοστού ταιριάσματος για κάθε αγγελία
            $matchedListings = $this->calculateMatchPercentage($listings['results'], $driverProfile);

            // Φιλτράρισμα αγγελιών με βάση το ελάχιστο ποσοστό ταιριάσματος
            if ($matchThreshold > 0) {
                $matchedListings = array_filter($matchedListings, function ($listing) use ($matchThreshold) {
                    return $listing['match_percentage'] >= $matchThreshold;
                });
            }

            // Ταξινόμηση των αγγελιών με βάση το ποσοστό ταιριάσματος (φθίνουσα σειρά)
            usort($matchedListings, function ($a, $b) {
                return $b['match_percentage'] <=> $a['match_percentage'];
            });

            // Ενημέρωση του πίνακα αποτελεσμάτων
            $listings['results'] = array_values($matchedListings); // Reset array keys
            $listings['pagination']['total'] = count($matchedListings);
            $listings['pagination']['pages'] = ceil(count($matchedListings) / $limit);

            return $listings;
        } catch (\Exception $e) {
            Logger::error('Error in findMatchingListingsForDriver: ' . $e->getMessage());

            return [
                'results' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }

    /**
     * Βρίσκει οδηγούς που ταιριάζουν με μια συγκεκριμένη αγγελία
     *
     * @param int $listingId ID της αγγελίας
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @param int $matchThreshold Ελάχιστο ποσοστό ταιριάσματος (0-100)
     * @return array Οδηγοί που ταιριάζουν με την αγγελία
     */
    public function findMatchingDriversForListing($listingId, $page = 1, $limit = 10, $matchThreshold = 0)
    {
        try {
            // Λήψη της αγγελίας
            $listing = $this->jobListingModel->getById($listingId);

            if (!$listing) {
                return [
                    'results' => [],
                    'pagination' => [
                        'total' => 0,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => 0
                    ]
                ];
            }

            // Παράμετροι για την αναζήτηση οδηγών που ταιριάζουν με την αγγελία
            $params = $this->getMatchingParamsForListing($listing);

            // Λήψη των οδηγών που ταιριάζουν με τα κριτήρια
            $drivers = $this->profileModel->searchDrivers($params, $page, $limit);

            // Υπολογισμός ποσοστού ταιριάσματος για κάθε οδηγό
            $matchedDrivers = $this->calculateDriverMatchPercentage($drivers['results'], $listing);

            // Φιλτράρισμα οδηγών με βάση το ελάχιστο ποσοστό ταιριάσματος
            if ($matchThreshold > 0) {
                $matchedDrivers = array_filter($matchedDrivers, function ($driver) use ($matchThreshold) {
                    return $driver['match_percentage'] >= $matchThreshold;
                });
            }

            // Ταξινόμηση των οδηγών με βάση το ποσοστό ταιριάσματος (φθίνουσα σειρά)
            usort($matchedDrivers, function ($a, $b) {
                return $b['match_percentage'] <=> $a['match_percentage'];
            });

            // Ενημέρωση του πίνακα αποτελεσμάτων
            $drivers['results'] = array_values($matchedDrivers); // Reset array keys
            $drivers['pagination']['total'] = count($matchedDrivers);
            $drivers['pagination']['pages'] = ceil(count($matchedDrivers) / $limit);

            return $drivers;
        } catch (\Exception $e) {
            Logger::error('Error in findMatchingDriversForListing: ' . $e->getMessage());

            return [
                'results' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
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
            if (!empty($driverProfile['preferred_vehicle_type']) && !empty($listing['vehicle_types'])) {
                $maxScore += 20;
                if (in_array($driverProfile['preferred_vehicle_type'], $listing['vehicle_types'])) {
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
     * Υπολογίζει το ποσοστό ταιριάσματος για κάθε οδηγό με βάση μια αγγελία
     *
     * @param array $drivers Οι οδηγοί
     * @param array $listing Η αγγελία
     * @return array Οι οδηγοί με το ποσοστό ταιριάσματος
     */
    private function calculateDriverMatchPercentage($drivers, $listing)
    {
        $matchedDrivers = [];

        foreach ($drivers as $driver) {
            $driver['match_percentage'] = $this->calculateSingleDriverMatchPercentage($driver, $listing);
            $matchedDrivers[] = $driver;
        }

        return $matchedDrivers;
    }

    /**
     * Υπολογίζει το ποσοστό ταιριάσματος για έναν οδηγό με βάση μια αγγελία
     *
     * @param array $driver Ο οδηγός
     * @param array $listing Η αγγελία
     * @return int Το ποσοστό ταιριάσματος (0-100)
     */
    private function calculateSingleDriverMatchPercentage($driver, $listing)
    {
        $score = 0;
        $maxScore = 0;

        // Τύπος απασχόλησης (20 βαθμοί)
        if (!empty($driver['preferred_job_type']) && !empty($listing['job_type'])) {
            $maxScore += 20;
            if ($driver['preferred_job_type'] === $listing['job_type']) {
                $score += 20;
            }
        }

        // Τύπος οχήματος (20 βαθμοί)
        if (!empty($driver['preferred_vehicle_type']) && !empty($listing['vehicle_types'])) {
            $maxScore += 20;
            if (in_array($driver['preferred_vehicle_type'], $listing['vehicle_types'])) {
                $score += 20;
            }
        }

        // Απόσταση (30 βαθμοί)
        if (
            !empty($driver['latitude']) && !empty($driver['longitude']) &&
            !empty($listing['latitude']) && !empty($listing['longitude'])
        ) {
            $maxScore += 30;

            // Υπολογισμός απόστασης σε χιλιόμετρα
            $distance = $this->calculateDistance(
                $driver['latitude'],
                $driver['longitude'],
                $listing['latitude'],
                $listing['longitude']
            );

            // Ακτίνα της αγγελίας
            $listingRadius = $listing['radius'] ?? 50;

            if ($distance <= $listingRadius) {
                // Όσο πιο κοντά, τόσο υψηλότερο το σκορ
                $score += 30 * (1 - ($distance / $listingRadius));
            }
        }

        // Ειδικές απαιτήσεις (20 βαθμοί)
        // ADR (10 βαθμοί)
        if (!empty($listing['adr_certificate']) && $listing['adr_certificate'] == 1) {
            $maxScore += 10;
            if (!empty($driver['adr_certificate']) && $driver['adr_certificate'] == 1) {
                $score += 10;
            }
        }

        // Άδεια χειριστή (10 βαθμοί)
        if (!empty($listing['operator_license']) && $listing['operator_license'] == 1) {
            $maxScore += 10;
            if (!empty($driver['operator_license']) && $driver['operator_license'] == 1) {
                $score += 10;
            }
        }

        // Εμπειρία (10 βαθμοί)
        if (!empty($listing['experience_years'])) {
            $maxScore += 10;
            if (!empty($driver['experience_years']) && $driver['experience_years'] >= $listing['experience_years']) {
                $score += 10;
            }
        }

        // Υπολογισμός τελικού ποσοστού
        $matchPercentage = ($maxScore > 0) ? round(($score / $maxScore) * 100) : 0;

        return $matchPercentage;
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
     * Αποθηκεύει τα σκορ ταιριάσματος στη βάση δεδομένων
     *
     * @param array $matchingScores Πίνακας με τα σκορ ταιριάσματος
     * @return bool Επιτυχία/αποτυχία
     */
    public function saveMatchingScores($matchingScores)
    {
        try {
            // Έλεγχος αν υπάρχει ο πίνακας στη βάση δεδομένων
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'matching_scores'");

            if ($tableCheck->rowCount() == 0) {
                // Δημιουργία του πίνακα αν δεν υπάρχει
                $createTable = "CREATE TABLE matching_scores (
                    id INT NOT NULL AUTO_INCREMENT,
                    driver_id INT NOT NULL,
                    job_listing_id INT NULL,
                    match_percentage INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY (driver_id),
                    KEY (job_listing_id)
                )";

                $this->pdo->exec($createTable);
            }

            // Εισαγωγή των σκορ ταιριάσματος
            $stmt = $this->pdo->prepare("INSERT INTO matching_scores (driver_id, job_listing_id, match_percentage) VALUES (?, ?, ?)");

            foreach ($matchingScores as $score) {
                $stmt->execute([
                    $score['driver_id'],
                    $score['listing_id'] ?? null,
                    $score['match_percentage']
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in saveMatchingScores: ' . $e->getMessage());
            return false;
        }
    }
}
