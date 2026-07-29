<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Repositories\MatchingRepositoryInterface;
use Drivejob\Repositories\MatchingRepository;
use Drivejob\Repositories\DriversRepositoryInterface;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\JobListingRepositoryInterface;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Services\GeoLocationService;
use Drivejob\Services\MachineLearningService;

/**
 * Υπηρεσία για το ταίριασμα αγγελιών
 */
class MatchingService
{
    /**
     * @var MatchingRepositoryInterface Το repository για το ταίριασμα αγγελιών
     */
    private $matchingRepository;

    /**
     * @var DriversRepositoryInterface Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var JobListingRepositoryInterface Το repository για τις αγγελίες
     */
    private $jobListingRepository;

    /**
     * @var GeoLocationService Η υπηρεσία για τη γεωγραφική αναζήτηση
     */
    private $geoLocationService;

    /**
     * @var MachineLearningService Η υπηρεσία για τη μηχανική μάθηση
     */
    private $machineLearningService;

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param MatchingRepositoryInterface|null $matchingRepository Το repository για το ταίριασμα αγγελιών
     * @param DriversRepositoryInterface|null $driversRepository Το repository για τους οδηγούς
     * @param JobListingRepositoryInterface|null $jobListingRepository Το repository για τις αγγελίες
     * @param GeoLocationService|null $geoLocationService Η υπηρεσία για τη γεωγραφική αναζήτηση
     * @param MachineLearningService|null $machineLearningService Η υπηρεσία για τη μηχανική μάθηση
     */
    public function __construct(
        PDO $pdo,
        MatchingRepositoryInterface $matchingRepository = null,
        DriversRepositoryInterface $driversRepository = null,
        JobListingRepositoryInterface $jobListingRepository = null,
        GeoLocationService $geoLocationService = null,
        MachineLearningService $machineLearningService = null
    ) {
        $this->matchingRepository = $matchingRepository ?? new MatchingRepository($pdo);
        $this->driversRepository = $driversRepository ?? new DriversRepository($pdo);
        $this->jobListingRepository = $jobListingRepository ?? new JobListingRepository($pdo);
        $this->geoLocationService = $geoLocationService ?? new GeoLocationService();
        $this->machineLearningService = $machineLearningService ?? new MachineLearningService($pdo);
    }

    /**
     * Υπολογίζει το σκορ ταιριάσματος μεταξύ ενός οδηγού και μιας αγγελίας εταιρείας
     * 
     * @param int|array $driver Το ID του οδηγού ή τα δεδομένα του οδηγού
     * @param int|array $jobListing Το ID της αγγελίας ή τα δεδομένα της αγγελίας
     * @return float Το σκορ ταιριάσματος (0-100)
     */
    public function calculateMatchScore($driver, $jobListing)
    {
        try {
            // Έλεγχος αν τα δεδομένα είναι IDs ή πλήρη δεδομένα
            $driverId = is_array($driver) ? $driver['id'] : $driver;
            $jobListingId = is_array($jobListing) ? $jobListing['id'] : $jobListing;

            // Λήψη των πλήρων δεδομένων αν έχουμε μόνο τα IDs
            $driverData = is_array($driver) ? $driver : $this->driversRepository->find($driverId);
            $jobListingData = is_array($jobListing) ? $jobListing : $this->jobListingRepository->find($jobListingId);

            if (!$driverData || !$jobListingData) {
                return 0;
            }

            // Υπολογισμός του σκορ ταιριάσματος
            $score = 0;
            $totalWeight = 0;

            // Τύπος οχήματος (βάρος: 25%)
            $vehicleTypeWeight = 25;
            $totalWeight += $vehicleTypeWeight;
            if ($this->isCompatibleVehicleType($driverData, $jobListingData)) {
                $score += $vehicleTypeWeight;
            }

            // Τοποθεσία (βάρος: 20%)
            $locationWeight = 20;
            $totalWeight += $locationWeight;
            $locationScore = $this->calculateLocationScore($driverData, $jobListingData);
            $score += $locationWeight * $locationScore;

            // Τύπος εργασίας (βάρος: 15%)
            $jobTypeWeight = 15;
            $totalWeight += $jobTypeWeight;
            if ($this->isCompatibleJobType($driverData, $jobListingData)) {
                $score += $jobTypeWeight;
            }

            // Ωράριο (βάρος: 15%)
            $scheduleWeight = 15;
            $totalWeight += $scheduleWeight;
            $scheduleScore = $this->calculateScheduleCompatibility($driverData, $jobListingData);
            $score += $scheduleWeight * $scheduleScore;

            // Μισθός (βάρος: 10%)
            $salaryWeight = 10;
            $totalWeight += $salaryWeight;
            $salaryScore = $this->calculateSalaryOverlap($driverData, $jobListingData);
            $score += $salaryWeight * $salaryScore;

            // Δεξιότητες και πιστοποιήσεις (βάρος: 15%)
            $skillsWeight = 15;
            $totalWeight += $skillsWeight;
            $skillsScore = $this->calculateSkillsMatch($driverData, $jobListingData);
            $score += $skillsWeight * $skillsScore;

            // Κανονικοποίηση του σκορ σε κλίμακα 0-100
            $finalScore = ($score / $totalWeight) * 100;

            // Αποθήκευση του σκορ στη βάση δεδομένων
            $this->matchingRepository->saveMatch($driverId, $jobListingId, $finalScore);

            return $finalScore;
        } catch (\Exception $e) {
            Logger::error('Error in calculateMatchScore: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ελέγχει αν ο τύπος οχήματος της αγγελίας είναι συμβατός με τις άδειες του οδηγού
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return bool Αν είναι συμβατός ο τύπος οχήματος
     */
    private function isCompatibleVehicleType($driver, $jobListing)
    {
        if (empty($jobListing['vehicle_type'])) {
            return true;
        }

        // Αντιστοίχιση τύπων οχημάτων με άδειες οδήγησης
        $vehicleLicenseMap = [
            'car' => ['B'],
            'van' => ['B'],
            'truck' => ['C', 'C1', 'CE', 'C1E'],
            'bus' => ['D', 'D1', 'DE', 'D1E'],
            'motorcycle' => ['A', 'A1', 'A2'],
            'tractor' => ['T'],
            'forklift' => ['T'],
            'crane' => ['T'],
            'excavator' => ['T']
        ];

        // Λήψη των αδειών οδήγησης του οδηγού
        $driverLicenses = [];
        if (isset($driver['licenses']) && is_array($driver['licenses'])) {
            foreach ($driver['licenses'] as $license) {
                $driverLicenses[] = $license['license_type'];
            }
        }

        // Έλεγχος αν ο οδηγός έχει την κατάλληλη άδεια
        if (isset($vehicleLicenseMap[$jobListing['vehicle_type']])) {
            $requiredLicenses = $vehicleLicenseMap[$jobListing['vehicle_type']];
            foreach ($requiredLicenses as $license) {
                if (in_array($license, $driverLicenses)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Υπολογίζει το σκορ συμβατότητας τοποθεσίας
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return float Το σκορ συμβατότητας (0-1)
     */
    private function calculateLocationScore($driver, $jobListing)
    {
        if (empty($jobListing['location'])) {
            return 1.0;
        }

        // Απλή υλοποίηση με βάση το όνομα της πόλης ή της χώρας
        // Σε μια πραγματική εφαρμογή θα χρησιμοποιούσαμε γεωγραφικές συντεταγμένες
        $driverCity = $driver['city'] ?? '';
        $driverCountry = $driver['country'] ?? '';
        $listingLocation = $jobListing['location'];

        if (stripos($listingLocation, $driverCity) !== false) {
            return 1.0;
        } elseif (stripos($listingLocation, $driverCountry) !== false) {
            return 0.7;
        } else {
            // Υπολογισμός απόστασης με βάση τις συντεταγμένες
            // Για τώρα επιστρέφουμε μια προεπιλεγμένη τιμή
            return 0.3;
        }
    }

    /**
     * Ελέγχει αν ο τύπος εργασίας της αγγελίας είναι συμβατός με τις προτιμήσεις του οδηγού
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return bool Αν είναι συμβατός ο τύπος εργασίας
     */
    private function isCompatibleJobType($driver, $jobListing)
    {
        if (empty($jobListing['job_type'])) {
            return true;
        }

        // Λήψη των προτιμήσεων του οδηγού για τον τύπο εργασίας
        $driverJobTypes = [];
        if (isset($driver['job_preferences']) && is_array($driver['job_preferences'])) {
            $driverJobTypes = $driver['job_preferences'];
        }

        // Αν ο οδηγός δεν έχει προτιμήσεις, θεωρούμε ότι είναι συμβατός με όλους τους τύπους
        if (empty($driverJobTypes)) {
            return true;
        }

        return in_array($jobListing['job_type'], $driverJobTypes);
    }

    /**
     * Υπολογίζει το σκορ συμβατότητας ωραρίου
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return float Το σκορ συμβατότητας (0-1)
     */
    private function calculateScheduleCompatibility($driver, $jobListing)
    {
        if (empty($jobListing['schedule']) || empty($driver['availability'])) {
            return 1.0;
        }

        // Απλή υλοποίηση με βάση τις προτιμήσεις ωραρίου
        // Σε μια πραγματική εφαρμογή θα είχαμε πιο λεπτομερή δεδομένα
        $jobSchedule = $jobListing['schedule'];
        $driverAvailability = $driver['availability'];

        if ($jobSchedule === $driverAvailability) {
            return 1.0;
        } elseif (($jobSchedule === 'full_time' && $driverAvailability === 'part_time') ||
            ($jobSchedule === 'part_time' && $driverAvailability === 'full_time')
        ) {
            return 0.5;
        } else {
            return 0.0;
        }
    }

    /**
     * Υπολογίζει το ποσοστό επικάλυψης μισθού
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return float Το ποσοστό επικάλυψης (0-1)
     */
    private function calculateSalaryOverlap($driver, $jobListing)
    {
        // Αν δεν υπάρχουν δεδομένα μισθού, επιστρέφουμε 1.0
        if (
            empty($jobListing['salary_min']) || empty($jobListing['salary_max']) ||
            empty($driver['min_salary']) || empty($driver['max_salary'])
        ) {
            return 1.0;
        }

        // Υπολογισμός επικάλυψης
        $jobMin = $jobListing['salary_min'];
        $jobMax = $jobListing['salary_max'];
        $driverMin = $driver['min_salary'];
        $driverMax = $driver['max_salary'];

        // Αν δεν υπάρχει επικάλυψη
        if ($jobMax < $driverMin || $jobMin > $driverMax) {
            return 0.0;
        }

        // Υπολογισμός του εύρους επικάλυψης
        $overlapMin = max($jobMin, $driverMin);
        $overlapMax = min($jobMax, $driverMax);
        $overlapRange = $overlapMax - $overlapMin;

        // Υπολογισμός του συνολικού εύρους
        $totalRange = max($jobMax, $driverMax) - min($jobMin, $driverMin);

        // Επιστροφή του ποσοστού επικάλυψης
        return $totalRange > 0 ? $overlapRange / $totalRange : 1.0;
    }

    /**
     * Υπολογίζει το ποσοστό ταιριάσματος δεξιοτήτων
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateSkillsMatch($driver, $jobListing)
    {
        // Αν δεν υπάρχουν απαιτούμενες δεξιότητες, επιστρέφουμε 1.0
        if (empty($jobListing['required_skills'])) {
            return 1.0;
        }

        // Λήψη των απαιτούμενων δεξιοτήτων
        $requiredSkills = is_array($jobListing['required_skills']) ?
            $jobListing['required_skills'] : explode(',', $jobListing['required_skills']);

        // Λήψη των δεξιοτήτων του οδηγού
        $driverSkills = [];
        if (isset($driver['skills']) && is_array($driver['skills'])) {
            foreach ($driver['skills'] as $skill => $value) {
                if ($value == 1) {
                    $driverSkills[] = $skill;
                }
            }
        }

        // Αν ο οδηγός δεν έχει δεξιότητες, επιστρέφουμε 0.0
        if (empty($driverSkills)) {
            return 0.0;
        }

        // Υπολογισμός του ποσοστού ταιριάσματος
        $matchCount = 0;
        foreach ($requiredSkills as $skill) {
            if (in_array($skill, $driverSkills)) {
                $matchCount++;
            }
        }

        return $matchCount / count($requiredSkills);
    }

    /**
     * Βρίσκει αγγελίες εταιρειών που ταιριάζουν με τα κριτήρια ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingJobsForDriver($driverId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            return $this->matchingRepository->findMatchingJobsForDriver($driverId, $criteria, $page, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in findMatchingJobsForDriver: ' . $e->getMessage());
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
     * Βρίσκει αγγελίες οδηγών που ταιριάζουν με τα κριτήρια μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingDriversForCompany($companyId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            return $this->matchingRepository->findMatchingDriversForCompany($companyId, $criteria, $page, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in findMatchingDriversForCompany: ' . $e->getMessage());
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
     * Βρίσκει αγγελίες εταιρειών που ταιριάζουν με μια αγγελία οδηγού
     * 
     * @param int $driverListingId Το ID της αγγελίας του οδηγού
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingJobsForDriverListing($driverListingId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            return $this->matchingRepository->findMatchingJobsForDriverListing($driverListingId, $criteria, $page, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in findMatchingJobsForDriverListing: ' . $e->getMessage());
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
     * Βρίσκει αγγελίες οδηγών που ταιριάζουν με μια αγγελία εταιρείας
     * 
     * @param int $companyListingId Το ID της αγγελίας της εταιρείας
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingDriversForCompanyListing($companyListingId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            return $this->matchingRepository->findMatchingDriversForCompanyListing($companyListingId, $criteria, $page, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in findMatchingDriversForCompanyListing: ' . $e->getMessage());
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
     * Βρίσκει τα ταιριάσματα ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findDriverMatches($driverId, $page = 1, $limit = 10)
    {
        try {
            return $this->matchingRepository->findDriverMatches($driverId, $page, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in findDriverMatches: ' . $e->getMessage());
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
     * Βρίσκει τα ταιριάσματα μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findCompanyMatches($companyId, $page = 1, $limit = 10)
    {
        try {
            return $this->matchingRepository->findCompanyMatches($companyId, $page, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in findCompanyMatches: ' . $e->getMessage());
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
     * Ενημερώνει τα ταιριάσματα για έναν οδηγό
     * 
     * @param int $driverId Το ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverMatches($driverId)
    {
        try {
            // Λήψη των στοιχείων του οδηγού
            $driver = $this->driversRepository->find($driverId);
            if (!$driver) {
                return false;
            }

            // Λήψη των ενεργών αγγελιών εταιρειών
            $activeListings = $this->jobListingRepository->findAll(['is_active' => 1, 'company_id IS NOT NULL' => true]);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε αγγελία
            foreach ($activeListings as $listing) {
                $this->calculateMatchScore($driver, $listing);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateDriverMatches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει τα ταιριάσματα για μια αγγελία εταιρείας
     * 
     * @param int $companyListingId Το ID της αγγελίας της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateCompanyListingMatches($companyListingId)
    {
        try {
            // Λήψη των στοιχείων της αγγελίας
            $listing = $this->jobListingRepository->find($companyListingId);
            if (!$listing || empty($listing['company_id'])) {
                return false;
            }

            // Λήψη όλων των οδηγών
            $drivers = $this->driversRepository->findAll();

            // Υπολογισμός του σκορ ταιριάσματος για κάθε οδηγό
            foreach ($drivers as $driver) {
                $this->calculateMatchScore($driver, $listing);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateCompanyListingMatches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει τα ταιριάσματα για μια αγγελία οδηγού
     * 
     * @param int $driverListingId Το ID της αγγελίας του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverListingMatches($driverListingId)
    {
        try {
            // Λήψη των στοιχείων της αγγελίας
            $listing = $this->jobListingRepository->find($driverListingId);
            if (!$listing || empty($listing['driver_id'])) {
                return false;
            }

            // Λήψη των ενεργών αγγελιών εταιρειών
            $activeListings = $this->jobListingRepository->findAll(['is_active' => 1, 'company_id IS NOT NULL' => true]);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε αγγελία
            foreach ($activeListings as $companyListing) {
                $this->calculateMatchScoreForListings($listing, $companyListing);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateDriverListingMatches: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Υπολογίζει το σκορ ταιριάσματος μεταξύ δύο αγγελιών
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return float Το σκορ ταιριάσματος (0-100)
     */
    public function calculateMatchScoreForListings($driverListing, $companyListing)
    {
        try {
            // Έλεγχος αν τα δεδομένα είναι έγκυρα
            if (empty($driverListing['driver_id']) || empty($companyListing['company_id'])) {
                return 0;
            }

            // Υπολογισμός του σκορ ταιριάσματος
            $score = 0;
            $totalWeight = 0;

            // Τύπος οχήματος (βάρος: 25%)
            $vehicleTypeWeight = 25;
            $totalWeight += $vehicleTypeWeight;
            if ($this->isCompatibleVehicleTypeForListings($driverListing, $companyListing)) {
                $score += $vehicleTypeWeight;
            }

            // Τοποθεσία (βάρος: 20%)
            $locationWeight = 20;
            $totalWeight += $locationWeight;
            $locationScore = $this->calculateLocationScoreForListings($driverListing, $companyListing);
            $score += $locationWeight * $locationScore;

            // Τύπος εργασίας (βάρος: 15%)
            $jobTypeWeight = 15;
            $totalWeight += $jobTypeWeight;
            if ($this->isCompatibleJobTypeForListings($driverListing, $companyListing)) {
                $score += $jobTypeWeight;
            }

            // Ωράριο (βάρος: 15%)
            $scheduleWeight = 15;
            $totalWeight += $scheduleWeight;
            $scheduleScore = $this->calculateScheduleCompatibilityForListings($driverListing, $companyListing);
            $score += $scheduleWeight * $scheduleScore;

            // Μισθός (βάρος: 10%)
            $salaryWeight = 10;
            $totalWeight += $salaryWeight;
            $salaryScore = $this->calculateSalaryOverlapForListings($driverListing, $companyListing);
            $score += $salaryWeight * $salaryScore;

            // Δεξιότητες και πιστοποιήσεις (βάρος: 15%)
            $skillsWeight = 15;
            $totalWeight += $skillsWeight;
            $skillsScore = $this->calculateSkillsMatchForListings($driverListing, $companyListing);
            $score += $skillsWeight * $skillsScore;

            // Κανονικοποίηση του σκορ σε κλίμακα 0-100
            $finalScore = ($score / $totalWeight) * 100;

            // Αποθήκευση του σκορ στη βάση δεδομένων
            $this->matchingRepository->saveMatch($driverListing['driver_id'], $companyListing['id'], $finalScore);

            return $finalScore;
        } catch (\Exception $e) {
            Logger::error('Error in calculateMatchScoreForListings: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ελέγχει αν ο τύπος οχήματος των δύο αγγελιών είναι συμβατός
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return bool Αν είναι συμβατός ο τύπος οχήματος
     */
    private function isCompatibleVehicleTypeForListings($driverListing, $companyListing)
    {
        if (empty($companyListing['vehicle_type']) || empty($driverListing['vehicle_type'])) {
            return true;
        }

        return $companyListing['vehicle_type'] === $driverListing['vehicle_type'];
    }

    /**
     * Υπολογίζει το σκορ συμβατότητας τοποθεσίας μεταξύ δύο αγγελιών
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return float Το σκορ συμβατότητας (0-1)
     */
    private function calculateLocationScoreForListings($driverListing, $companyListing)
    {
        if (empty($companyListing['location']) || empty($driverListing['location'])) {
            return 1.0;
        }

        // Απλή υλοποίηση με βάση το όνομα της τοποθεσίας
        // Σε μια πραγματική εφαρμογή θα χρησιμοποιούσαμε γεωγραφικές συντεταγμένες
        $driverLocation = $driverListing['location'];
        $companyLocation = $companyListing['location'];

        if (stripos($companyLocation, $driverLocation) !== false || stripos($driverLocation, $companyLocation) !== false) {
            return 1.0;
        } else {
            // Υπολογισμός απόστασης με βάση τις συντεταγμένες
            // Για τώρα επιστρέφουμε μια προεπιλεγμένη τιμή
            return 0.3;
        }
    }

    /**
     * Ελέγχει αν ο τύπος εργασίας των δύο αγγελιών είναι συμβατός
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return bool Αν είναι συμβατός ο τύπος εργασίας
     */
    private function isCompatibleJobTypeForListings($driverListing, $companyListing)
    {
        if (empty($companyListing['job_type']) || empty($driverListing['job_type'])) {
            return true;
        }

        return $companyListing['job_type'] === $driverListing['job_type'];
    }

    /**
     * Υπολογίζει το σκορ συμβατότητας ωραρίου μεταξύ δύο αγγελιών
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return float Το σκορ συμβατότητας (0-1)
     */
    private function calculateScheduleCompatibilityForListings($driverListing, $companyListing)
    {
        if (empty($companyListing['schedule']) || empty($driverListing['schedule'])) {
            return 1.0;
        }

        // Απλή υλοποίηση με βάση τις προτιμήσεις ωραρίου
        $driverSchedule = $driverListing['schedule'];
        $companySchedule = $companyListing['schedule'];

        if ($driverSchedule === $companySchedule) {
            return 1.0;
        } elseif (($driverSchedule === 'full_time' && $companySchedule === 'part_time') ||
            ($driverSchedule === 'part_time' && $companySchedule === 'full_time')
        ) {
            return 0.5;
        } else {
            return 0.0;
        }
    }

    /**
     * Υπολογίζει το ποσοστό επικάλυψης μισθού μεταξύ δύο αγγελιών
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return float Το ποσοστό επικάλυψης (0-1)
     */
    private function calculateSalaryOverlapForListings($driverListing, $companyListing)
    {
        // Αν δεν υπάρχουν δεδομένα μισθού, επιστρέφουμε 1.0
        if (
            empty($companyListing['salary_min']) || empty($companyListing['salary_max']) ||
            empty($driverListing['salary_min']) || empty($driverListing['salary_max'])
        ) {
            return 1.0;
        }

        // Υπολογισμός επικάλυψης
        $companyMin = $companyListing['salary_min'];
        $companyMax = $companyListing['salary_max'];
        $driverMin = $driverListing['salary_min'];
        $driverMax = $driverListing['salary_max'];

        // Αν δεν υπάρχει επικάλυψη
        if ($companyMax < $driverMin || $companyMin > $driverMax) {
            return 0.0;
        }

        // Υπολογισμός του εύρους επικάλυψης
        $overlapMin = max($companyMin, $driverMin);
        $overlapMax = min($companyMax, $driverMax);
        $overlapRange = $overlapMax - $overlapMin;

        // Υπολογισμός του συνολικού εύρους
        $totalRange = max($companyMax, $driverMax) - min($companyMin, $driverMin);

        // Επιστροφή του ποσοστού επικάλυψης
        return $totalRange > 0 ? $overlapRange / $totalRange : 1.0;
    }

    /**
     * Υπολογίζει το ποσοστό ταιριάσματος δεξιοτήτων μεταξύ δύο αγγελιών
     * 
     * @param array $driverListing Τα δεδομένα της αγγελίας του οδηγού
     * @param array $companyListing Τα δεδομένα της αγγελίας της εταιρείας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateSkillsMatchForListings($driverListing, $companyListing)
    {
        // Αν δεν υπάρχουν απαιτούμενες δεξιότητες, επιστρέφουμε 1.0
        if (empty($companyListing['required_skills']) || empty($driverListing['skills'])) {
            return 1.0;
        }

        // Λήψη των απαιτούμενων δεξιοτήτων
        $requiredSkills = is_array($companyListing['required_skills']) ?
            $companyListing['required_skills'] : explode(',', $companyListing['required_skills']);

        // Λήψη των δεξιοτήτων του οδηγού
        $driverSkills = is_array($driverListing['skills']) ?
            $driverListing['skills'] : explode(',', $driverListing['skills']);

        // Υπολογισμός του ποσοστού ταιριάσματος
        $matchCount = 0;
        foreach ($requiredSkills as $skill) {
            if (in_array($skill, $driverSkills)) {
                $matchCount++;
            }
        }

        return $matchCount / count($requiredSkills);
    }

    /**
     * Προτείνει αγγελίες για έναν οδηγό με βάση τις προτιμήσεις του
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $limit Ο μέγιστος αριθμός προτάσεων
     * @return array Οι προτεινόμενες αγγελίες
     */
    public function recommendJobsForDriver($driverId, $limit = 10)
    {
        try {
            return $this->machineLearningService->recommendJobsForDriver($driverId, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in recommendJobsForDriver: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Προτείνει οδηγούς για μια αγγελία εταιρείας με βάση τα κριτήρια της αγγελίας
     * 
     * @param int $jobListingId Το ID της αγγελίας
     * @param int $limit Ο μέγιστος αριθμός προτάσεων
     * @return array Οι προτεινόμενοι οδηγοί
     */
    public function recommendDriversForJobListing($jobListingId, $limit = 10)
    {
        try {
            return $this->machineLearningService->recommendDriversForJobListing($jobListingId, $limit);
        } catch (\Exception $e) {
            Logger::error('Error in recommendDriversForJobListing: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Αναζητά τοποθεσίες κοντά σε μια δεδομένη τοποθεσία
     * 
     * @param string $location Η τοποθεσία αναφοράς
     * @param float $maxDistance Η μέγιστη απόσταση σε χιλιόμετρα
     * @param array $locations Οι τοποθεσίες προς αναζήτηση
     * @return array Οι τοποθεσίες που βρίσκονται εντός της μέγιστης απόστασης
     */
    public function findNearbyLocations($location, $maxDistance, array $locations)
    {
        try {
            return $this->geoLocationService->findNearbyLocations($location, $maxDistance, $locations);
        } catch (\Exception $e) {
            Logger::error('Error in findNearbyLocations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Υπολογίζει την απόσταση μεταξύ δύο τοποθεσιών
     * 
     * @param string $location1 Η πρώτη τοποθεσία
     * @param string $location2 Η δεύτερη τοποθεσία
     * @return float|false Η απόσταση σε χιλιόμετρα ή false σε περίπτωση αποτυχίας
     */
    public function calculateDistanceBetweenLocations($location1, $location2)
    {
        try {
            return $this->geoLocationService->calculateDistanceBetweenAddresses($location1, $location2);
        } catch (\Exception $e) {
            Logger::error('Error in calculateDistanceBetweenLocations: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει τα βάρη των κριτηρίων ταιριάσματος με βάση την ανατροφοδότηση των χρηστών
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $jobListingId Το ID της αγγελίας
     * @param bool $isPositive Αν η ανατροφοδότηση είναι θετική
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateMatchingWeightsFromFeedback($driverId, $jobListingId, $isPositive)
    {
        try {
            return $this->machineLearningService->updateWeightsFromFeedback($driverId, $jobListingId, $isPositive);
        } catch (\Exception $e) {
            Logger::error('Error in updateMatchingWeightsFromFeedback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Εκπαιδεύει το μοντέλο ταιριάσματος με βάση τα ιστορικά δεδομένα
     * 
     * @return bool Επιτυχία/αποτυχία
     */
    public function trainMatchingModel()
    {
        try {
            return $this->machineLearningService->trainModel();
        } catch (\Exception $e) {
            Logger::error('Error in trainMatchingModel: ' . $e->getMessage());
            return false;
        }
    }
}
