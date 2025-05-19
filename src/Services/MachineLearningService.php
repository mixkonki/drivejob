<?php

namespace Drivejob\Services;

use Drivejob\Core\Logger;
use Drivejob\Repositories\JobApplicationRepositoryInterface;
use Drivejob\Repositories\JobApplicationRepository;
use Drivejob\Repositories\MatchingRepositoryInterface;
use Drivejob\Repositories\MatchingRepository;
use PDO;

/**
 * Υπηρεσία για τη βελτίωση του ταιριάσματος αγγελιών με χρήση μηχανικής μάθησης
 */
class MachineLearningService
{
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    private $pdo;

    /**
     * @var JobApplicationRepositoryInterface Το repository για τις αιτήσεις εργασίας
     */
    private $jobApplicationRepository;

    /**
     * @var MatchingRepositoryInterface Το repository για το ταίριασμα αγγελιών
     */
    private $matchingRepository;

    /**
     * @var array Τα βάρη των κριτηρίων ταιριάσματος
     */
    private $weights = [
        'vehicle_type' => 0.25,
        'location' => 0.20,
        'job_type' => 0.15,
        'schedule' => 0.15,
        'salary' => 0.10,
        'skills' => 0.15
    ];

    /**
     * @var array Προσωρινή μνήμη για τα δεδομένα εκπαίδευσης
     */
    private $trainingDataCache = [];

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param JobApplicationRepositoryInterface|null $jobApplicationRepository Το repository για τις αιτήσεις εργασίας
     * @param MatchingRepositoryInterface|null $matchingRepository Το repository για το ταίριασμα αγγελιών
     */
    public function __construct(
        PDO $pdo,
        JobApplicationRepositoryInterface $jobApplicationRepository = null,
        MatchingRepositoryInterface $matchingRepository = null
    ) {
        $this->pdo = $pdo;
        $this->jobApplicationRepository = $jobApplicationRepository ?? new JobApplicationRepository($pdo);
        $this->matchingRepository = $matchingRepository ?? new MatchingRepository($pdo);
    }

    /**
     * Επιστρέφει τα τρέχοντα βάρη των κριτηρίων ταιριάσματος
     * 
     * @return array Τα βάρη των κριτηρίων
     */
    public function getWeights()
    {
        return $this->weights;
    }

    /**
     * Ορίζει τα βάρη των κριτηρίων ταιριάσματος
     * 
     * @param array $weights Τα νέα βάρη των κριτηρίων
     * @return bool Επιτυχία/αποτυχία
     */
    public function setWeights(array $weights)
    {
        // Έλεγχος αν τα βάρη είναι έγκυρα
        $validKeys = array_keys($this->weights);
        foreach ($weights as $key => $value) {
            if (!in_array($key, $validKeys)) {
                return false;
            }
            if (!is_numeric($value) || $value < 0 || $value > 1) {
                return false;
            }
        }

        // Ενημέρωση των βαρών
        foreach ($weights as $key => $value) {
            $this->weights[$key] = $value;
        }

        // Κανονικοποίηση των βαρών ώστε το άθροισμά τους να είναι 1
        $sum = array_sum($this->weights);
        if ($sum > 0) {
            foreach ($this->weights as $key => $value) {
                $this->weights[$key] = $value / $sum;
            }
        }

        return true;
    }

    /**
     * Συλλέγει δεδομένα εκπαίδευσης από τις επιτυχημένες αιτήσεις εργασίας
     * 
     * @param int $limit Ο μέγιστος αριθμός αιτήσεων που θα συλλεχθούν
     * @return array Τα δεδομένα εκπαίδευσης
     */
    public function collectTrainingData($limit = 1000)
    {
        try {
            // Έλεγχος αν τα δεδομένα υπάρχουν ήδη στην προσωρινή μνήμη
            $cacheKey = 'training_data_' . $limit;
            if (isset($this->trainingDataCache[$cacheKey])) {
                return $this->trainingDataCache[$cacheKey];
            }

            // Λήψη των επιτυχημένων αιτήσεων εργασίας
            $query = "SELECT ja.*, jl.company_id, jl.driver_id, jl.job_type, jl.vehicle_type, jl.location, jl.schedule, jl.salary_min, jl.salary_max, jl.required_skills
                      FROM job_applications ja
                      JOIN job_listings jl ON ja.job_listing_id = jl.id
                      WHERE ja.status = 'accepted'
                      ORDER BY ja.created_at DESC
                      LIMIT :limit";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Μετατροπή των αιτήσεων σε δεδομένα εκπαίδευσης
            $trainingData = [];
            foreach ($applications as $application) {
                // Λήψη των στοιχείων του οδηγού
                $driverQuery = "SELECT d.* FROM drivers d WHERE d.id = :driver_id";
                $driverStmt = $this->pdo->prepare($driverQuery);
                $driverStmt->bindValue(':driver_id', $application['driver_id'], PDO::PARAM_INT);
                $driverStmt->execute();
                $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);

                if (!$driver) {
                    continue;
                }

                // Λήψη των δεξιοτήτων του οδηγού
                $skillsQuery = "SELECT skill_name FROM driver_skills WHERE driver_id = :driver_id";
                $skillsStmt = $this->pdo->prepare($skillsQuery);
                $skillsStmt->bindValue(':driver_id', $driver['id'], PDO::PARAM_INT);
                $skillsStmt->execute();
                $skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

                // Λήψη των αδειών οδήγησης του οδηγού
                $licensesQuery = "SELECT license_type FROM driver_licenses WHERE driver_id = :driver_id";
                $licensesStmt = $this->pdo->prepare($licensesQuery);
                $licensesStmt->bindValue(':driver_id', $driver['id'], PDO::PARAM_INT);
                $licensesStmt->execute();
                $licenses = $licensesStmt->fetchAll(PDO::FETCH_COLUMN);

                // Δημιουργία των χαρακτηριστικών
                $features = [
                    'vehicle_type_match' => $this->calculateVehicleTypeMatch($licenses, $application['vehicle_type']),
                    'location_match' => $this->calculateLocationMatch($driver['city'], $driver['country'], $application['location']),
                    'job_type_match' => $this->calculateJobTypeMatch($driver['id'], $application['job_type']),
                    'schedule_match' => $this->calculateScheduleMatch($driver['availability'] ?? '', $application['schedule'] ?? ''),
                    'salary_match' => $this->calculateSalaryMatch($driver['min_salary'] ?? 0, $driver['max_salary'] ?? 0, $application['salary_min'] ?? 0, $application['salary_max'] ?? 0),
                    'skills_match' => $this->calculateSkillsMatch($skills, $application['required_skills'] ?? '')
                ];

                // Προσθήκη στα δεδομένα εκπαίδευσης
                $trainingData[] = [
                    'features' => $features,
                    'label' => 1 // Επιτυχημένο ταίριασμα
                ];
            }

            // Αποθήκευση στην προσωρινή μνήμη
            $this->trainingDataCache[$cacheKey] = $trainingData;

            return $trainingData;
        } catch (\Exception $e) {
            Logger::error('Error in collectTrainingData: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Εκπαιδεύει το μοντέλο με βάση τα δεδομένα εκπαίδευσης
     * 
     * @param array|null $trainingData Τα δεδομένα εκπαίδευσης (προαιρετικά)
     * @return bool Επιτυχία/αποτυχία
     */
    public function trainModel(array $trainingData = null)
    {
        try {
            // Λήψη των δεδομένων εκπαίδευσης αν δεν έχουν δοθεί
            if ($trainingData === null) {
                $trainingData = $this->collectTrainingData();
            }

            if (empty($trainingData)) {
                return false;
            }

            // Υπολογισμός των νέων βαρών με βάση τα δεδομένα εκπαίδευσης
            $featureSums = [
                'vehicle_type' => 0,
                'location' => 0,
                'job_type' => 0,
                'schedule' => 0,
                'salary' => 0,
                'skills' => 0
            ];

            $featureCounts = [
                'vehicle_type' => 0,
                'location' => 0,
                'job_type' => 0,
                'schedule' => 0,
                'salary' => 0,
                'skills' => 0
            ];

            // Άθροιση των τιμών των χαρακτηριστικών
            foreach ($trainingData as $data) {
                $features = $data['features'];

                if (isset($features['vehicle_type_match']) && $features['vehicle_type_match'] > 0) {
                    $featureSums['vehicle_type'] += $features['vehicle_type_match'];
                    $featureCounts['vehicle_type']++;
                }

                if (isset($features['location_match']) && $features['location_match'] > 0) {
                    $featureSums['location'] += $features['location_match'];
                    $featureCounts['location']++;
                }

                if (isset($features['job_type_match']) && $features['job_type_match'] > 0) {
                    $featureSums['job_type'] += $features['job_type_match'];
                    $featureCounts['job_type']++;
                }

                if (isset($features['schedule_match']) && $features['schedule_match'] > 0) {
                    $featureSums['schedule'] += $features['schedule_match'];
                    $featureCounts['schedule']++;
                }

                if (isset($features['salary_match']) && $features['salary_match'] > 0) {
                    $featureSums['salary'] += $features['salary_match'];
                    $featureCounts['salary']++;
                }

                if (isset($features['skills_match']) && $features['skills_match'] > 0) {
                    $featureSums['skills'] += $features['skills_match'];
                    $featureCounts['skills']++;
                }
            }

            // Υπολογισμός των μέσων τιμών
            $featureAverages = [];
            foreach ($featureSums as $key => $sum) {
                $featureAverages[$key] = $featureCounts[$key] > 0 ? $sum / $featureCounts[$key] : 0;
            }

            // Κανονικοποίηση των μέσων τιμών
            $sum = array_sum($featureAverages);
            if ($sum > 0) {
                foreach ($featureAverages as $key => $value) {
                    $this->weights[$key] = $value / $sum;
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in trainModel: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προβλέπει το σκορ ταιριάσματος για έναν οδηγό και μια αγγελία
     * 
     * @param array $driver Τα δεδομένα του οδηγού
     * @param array $jobListing Τα δεδομένα της αγγελίας
     * @return float Το σκορ ταιριάσματος (0-100)
     */
    public function predictMatchScore(array $driver, array $jobListing)
    {
        try {
            // Λήψη των δεξιοτήτων του οδηγού
            $skills = [];
            if (isset($driver['skills']) && is_array($driver['skills'])) {
                $skills = $driver['skills'];
            } else {
                // Λήψη των δεξιοτήτων από τη βάση δεδομένων
                $skillsQuery = "SELECT skill_name FROM driver_skills WHERE driver_id = :driver_id";
                $skillsStmt = $this->pdo->prepare($skillsQuery);
                $skillsStmt->bindValue(':driver_id', $driver['id'], PDO::PARAM_INT);
                $skillsStmt->execute();
                $skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);
            }

            // Λήψη των αδειών οδήγησης του οδηγού
            $licenses = [];
            if (isset($driver['licenses']) && is_array($driver['licenses'])) {
                foreach ($driver['licenses'] as $license) {
                    $licenses[] = $license['license_type'];
                }
            } else {
                // Λήψη των αδειών από τη βάση δεδομένων
                $licensesQuery = "SELECT license_type FROM driver_licenses WHERE driver_id = :driver_id";
                $licensesStmt = $this->pdo->prepare($licensesQuery);
                $licensesStmt->bindValue(':driver_id', $driver['id'], PDO::PARAM_INT);
                $licensesStmt->execute();
                $licenses = $licensesStmt->fetchAll(PDO::FETCH_COLUMN);
            }

            // Υπολογισμός των χαρακτηριστικών
            $features = [
                'vehicle_type_match' => $this->calculateVehicleTypeMatch($licenses, $jobListing['vehicle_type'] ?? ''),
                'location_match' => $this->calculateLocationMatch($driver['city'] ?? '', $driver['country'] ?? '', $jobListing['location'] ?? ''),
                'job_type_match' => $this->calculateJobTypeMatch($driver['id'], $jobListing['job_type'] ?? ''),
                'schedule_match' => $this->calculateScheduleMatch($driver['availability'] ?? '', $jobListing['schedule'] ?? ''),
                'salary_match' => $this->calculateSalaryMatch($driver['min_salary'] ?? 0, $driver['max_salary'] ?? 0, $jobListing['salary_min'] ?? 0, $jobListing['salary_max'] ?? 0),
                'skills_match' => $this->calculateSkillsMatch($skills, $jobListing['required_skills'] ?? '')
            ];

            // Υπολογισμός του σκορ ταιριάσματος
            $score = 0;
            $score += $features['vehicle_type_match'] * $this->weights['vehicle_type'];
            $score += $features['location_match'] * $this->weights['location'];
            $score += $features['job_type_match'] * $this->weights['job_type'];
            $score += $features['schedule_match'] * $this->weights['schedule'];
            $score += $features['salary_match'] * $this->weights['salary'];
            $score += $features['skills_match'] * $this->weights['skills'];

            // Κανονικοποίηση του σκορ σε κλίμακα 0-100
            $score = $score * 100;

            return $score;
        } catch (\Exception $e) {
            Logger::error('Error in predictMatchScore: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Υπολογίζει το ταίριασμα τύπου οχήματος
     * 
     * @param array $licenses Οι άδειες οδήγησης του οδηγού
     * @param string $vehicleType Ο τύπος οχήματος της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateVehicleTypeMatch(array $licenses, $vehicleType)
    {
        if (empty($vehicleType)) {
            return 1.0;
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

        // Έλεγχος αν ο οδηγός έχει την κατάλληλη άδεια
        if (isset($vehicleLicenseMap[$vehicleType])) {
            $requiredLicenses = $vehicleLicenseMap[$vehicleType];
            foreach ($requiredLicenses as $license) {
                if (in_array($license, $licenses)) {
                    return 1.0;
                }
            }
        }

        return 0.0;
    }

    /**
     * Υπολογίζει το ταίριασμα τοποθεσίας
     * 
     * @param string $driverCity Η πόλη του οδηγού
     * @param string $driverCountry Η χώρα του οδηγού
     * @param string $listingLocation Η τοποθεσία της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateLocationMatch($driverCity, $driverCountry, $listingLocation)
    {
        if (empty($listingLocation)) {
            return 1.0;
        }

        // Απλή υλοποίηση με βάση το όνομα της πόλης ή της χώρας
        if (stripos($listingLocation, $driverCity) !== false) {
            return 1.0;
        } elseif (stripos($listingLocation, $driverCountry) !== false) {
            return 0.7;
        } else {
            return 0.3;
        }
    }

    /**
     * Υπολογίζει το ταίριασμα τύπου εργασίας
     * 
     * @param int $driverId Το ID του οδηγού
     * @param string $jobType Ο τύπος εργασίας της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateJobTypeMatch($driverId, $jobType)
    {
        if (empty($jobType)) {
            return 1.0;
        }

        try {
            // Λήψη των προτιμήσεων του οδηγού για τον τύπο εργασίας
            $query = "SELECT job_type FROM driver_job_preferences WHERE driver_id = :driver_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':driver_id', $driverId, PDO::PARAM_INT);
            $stmt->execute();
            $jobTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Αν ο οδηγός δεν έχει προτιμήσεις, θεωρούμε ότι είναι συμβατός με όλους τους τύπους
            if (empty($jobTypes)) {
                return 1.0;
            }

            return in_array($jobType, $jobTypes) ? 1.0 : 0.0;
        } catch (\Exception $e) {
            Logger::error('Error in calculateJobTypeMatch: ' . $e->getMessage());
            return 0.5;
        }
    }

    /**
     * Υπολογίζει το ταίριασμα ωραρίου
     * 
     * @param string $driverAvailability Η διαθεσιμότητα του οδηγού
     * @param string $jobSchedule Το ωράριο της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateScheduleMatch($driverAvailability, $jobSchedule)
    {
        if (empty($jobSchedule) || empty($driverAvailability)) {
            return 1.0;
        }

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
     * Υπολογίζει το ταίριασμα μισθού
     * 
     * @param float $driverMinSalary Ο ελάχιστος μισθός του οδηγού
     * @param float $driverMaxSalary Ο μέγιστος μισθός του οδηγού
     * @param float $jobMinSalary Ο ελάχιστος μισθός της αγγελίας
     * @param float $jobMaxSalary Ο μέγιστος μισθός της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateSalaryMatch($driverMinSalary, $driverMaxSalary, $jobMinSalary, $jobMaxSalary)
    {
        // Αν δεν υπάρχουν δεδομένα μισθού, επιστρέφουμε 1.0
        if (
            empty($jobMinSalary) || empty($jobMaxSalary) ||
            empty($driverMinSalary) || empty($driverMaxSalary)
        ) {
            return 1.0;
        }

        // Υπολογισμός επικάλυψης
        $jobMin = $jobMinSalary;
        $jobMax = $jobMaxSalary;
        $driverMin = $driverMinSalary;
        $driverMax = $driverMaxSalary;

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
     * Υπολογίζει το ταίριασμα δεξιοτήτων
     * 
     * @param array $driverSkills Οι δεξιότητες του οδηγού
     * @param string $requiredSkills Οι απαιτούμενες δεξιότητες της αγγελίας
     * @return float Το ποσοστό ταιριάσματος (0-1)
     */
    private function calculateSkillsMatch(array $driverSkills, $requiredSkills)
    {
        // Αν δεν υπάρχουν απαιτούμενες δεξιότητες, επιστρέφουμε 1.0
        if (empty($requiredSkills)) {
            return 1.0;
        }

        // Μετατροπή των απαιτούμενων δεξιοτήτων σε πίνακα
        $requiredSkillsArray = is_array($requiredSkills) ?
            $requiredSkills : explode(',', $requiredSkills);

        // Αν ο οδηγός δεν έχει δεξιότητες, επιστρέφουμε 0.0
        if (empty($driverSkills)) {
            return 0.0;
        }

        // Υπολογισμός του ποσοστού ταιριάσματος
        $matchCount = 0;
        foreach ($requiredSkillsArray as $skill) {
            $skill = trim($skill);
            if (in_array($skill, $driverSkills)) {
                $matchCount++;
            }
        }

        return $matchCount / count($requiredSkillsArray);
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
            // Λήψη των στοιχείων του οδηγού
            $driverQuery = "SELECT * FROM drivers WHERE id = :driver_id";
            $driverStmt = $this->pdo->prepare($driverQuery);
            $driverStmt->bindValue(':driver_id', $driverId, PDO::PARAM_INT);
            $driverStmt->execute();
            $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver) {
                return [];
            }

            // Λήψη των ενεργών αγγελιών
            $listingsQuery = "SELECT jl.*, c.company_name, c.logo, c.city, c.country
                             FROM job_listings jl
                             JOIN companies c ON jl.company_id = c.id
                             WHERE jl.is_active = 1 AND jl.is_approved = 1
                             AND jl.company_id IS NOT NULL AND jl.driver_id IS NULL
                             AND jl.expires_at > NOW()";
            $listingsStmt = $this->pdo->prepare($listingsQuery);
            $listingsStmt->execute();
            $listings = $listingsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε αγγελία
            $scoredListings = [];
            foreach ($listings as $listing) {
                $score = $this->predictMatchScore($driver, $listing);
                $scoredListings[] = [
                    'listing' => $listing,
                    'score' => $score
                ];
            }

            // Ταξινόμηση των αγγελιών με βάση το σκορ
            usort($scoredListings, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Επιστροφή των καλύτερων αγγελιών
            $recommendations = array_slice($scoredListings, 0, $limit);

            // Μετατροπή σε μορφή αποτελέσματος
            $result = [];
            foreach ($recommendations as $recommendation) {
                $listing = $recommendation['listing'];
                $result[] = [
                    'id' => $listing['id'],
                    'title' => $listing['title'],
                    'company_name' => $listing['company_name'],
                    'logo' => $listing['logo'],
                    'location' => $listing['location'],
                    'job_type' => $listing['job_type'],
                    'vehicle_type' => $listing['vehicle_type'],
                    'salary_min' => $listing['salary_min'],
                    'salary_max' => $listing['salary_max'],
                    'salary_period' => $listing['salary_period'],
                    'match_score' => $recommendation['score']
                ];
            }

            return $result;
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
            // Λήψη των στοιχείων της αγγελίας
            $listingQuery = "SELECT * FROM job_listings WHERE id = :job_listing_id";
            $listingStmt = $this->pdo->prepare($listingQuery);
            $listingStmt->bindValue(':job_listing_id', $jobListingId, PDO::PARAM_INT);
            $listingStmt->execute();
            $jobListing = $listingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$jobListing || empty($jobListing['company_id'])) {
                return [];
            }

            // Λήψη των οδηγών
            $driversQuery = "SELECT * FROM drivers WHERE is_active = 1";
            $driversStmt = $this->pdo->prepare($driversQuery);
            $driversStmt->execute();
            $drivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε οδηγό
            $scoredDrivers = [];
            foreach ($drivers as $driver) {
                $score = $this->predictMatchScore($driver, $jobListing);
                $scoredDrivers[] = [
                    'driver' => $driver,
                    'score' => $score
                ];
            }

            // Ταξινόμηση των οδηγών με βάση το σκορ
            usort($scoredDrivers, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Επιστροφή των καλύτερων οδηγών
            $recommendations = array_slice($scoredDrivers, 0, $limit);

            // Μετατροπή σε μορφή αποτελέσματος
            $result = [];
            foreach ($recommendations as $recommendation) {
                $driver = $recommendation['driver'];
                $result[] = [
                    'id' => $driver['id'],
                    'first_name' => $driver['first_name'],
                    'last_name' => $driver['last_name'],
                    'profile_image' => $driver['profile_image'],
                    'city' => $driver['city'],
                    'country' => $driver['country'],
                    'experience_years' => $driver['experience_years'],
                    'match_score' => $recommendation['score']
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Logger::error('Error in recommendDriversForJobListing: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ενημερώνει τα βάρη των κριτηρίων με βάση την ανατροφοδότηση των χρηστών
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $jobListingId Το ID της αγγελίας
     * @param bool $isPositive Αν η ανατροφοδότηση είναι θετική
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateWeightsFromFeedback($driverId, $jobListingId, $isPositive)
    {
        try {
            // Λήψη των στοιχείων του οδηγού
            $driverQuery = "SELECT * FROM drivers WHERE id = :driver_id";
            $driverStmt = $this->pdo->prepare($driverQuery);
            $driverStmt->bindValue(':driver_id', $driverId, PDO::PARAM_INT);
            $driverStmt->execute();
            $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver) {
                return false;
            }

            // Λήψη των στοιχείων της αγγελίας
            $listingQuery = "SELECT * FROM job_listings WHERE id = :job_listing_id";
            $listingStmt = $this->pdo->prepare($listingQuery);
            $listingStmt->bindValue(':job_listing_id', $jobListingId, PDO::PARAM_INT);
            $listingStmt->execute();
            $jobListing = $listingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$jobListing) {
                return false;
            }

            // Λήψη των δεξιοτήτων του οδηγού
            $skillsQuery = "SELECT skill_name FROM driver_skills WHERE driver_id = :driver_id";
            $skillsStmt = $this->pdo->prepare($skillsQuery);
            $skillsStmt->bindValue(':driver_id', $driver['id'], PDO::PARAM_INT);
            $skillsStmt->execute();
            $skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

            // Λήψη των αδειών οδήγησης του οδηγού
            $licensesQuery = "SELECT license_type FROM driver_licenses WHERE driver_id = :driver_id";
            $licensesStmt = $this->pdo->prepare($licensesQuery);
            $licensesStmt->bindValue(':driver_id', $driver['id'], PDO::PARAM_INT);
            $licensesStmt->execute();
            $licenses = $licensesStmt->fetchAll(PDO::FETCH_COLUMN);

            // Υπολογισμός των χαρακτηριστικών
            $features = [
                'vehicle_type_match' => $this->calculateVehicleTypeMatch($licenses, $jobListing['vehicle_type'] ?? ''),
                'location_match' => $this->calculateLocationMatch($driver['city'] ?? '', $driver['country'] ?? '', $jobListing['location'] ?? ''),
                'job_type_match' => $this->calculateJobTypeMatch($driver['id'], $jobListing['job_type'] ?? ''),
                'schedule_match' => $this->calculateScheduleMatch($driver['availability'] ?? '', $jobListing['schedule'] ?? ''),
                'salary_match' => $this->calculateSalaryMatch($driver['min_salary'] ?? 0, $driver['max_salary'] ?? 0, $jobListing['salary_min'] ?? 0, $jobListing['salary_max'] ?? 0),
                'skills_match' => $this->calculateSkillsMatch($skills, $jobListing['required_skills'] ?? '')
            ];

            // Ενημέρωση των βαρών με βάση την ανατροφοδότηση
            $learningRate = 0.1; // Ρυθμός μάθησης

            foreach ($features as $key => $value) {
                $weightKey = str_replace('_match', '', $key);

                if (isset($this->weights[$weightKey])) {
                    if ($isPositive) {
                        // Αύξηση του βάρους για τα χαρακτηριστικά με υψηλή τιμή
                        $this->weights[$weightKey] += $learningRate * $value * (1 - $this->weights[$weightKey]);
                    } else {
                        // Μείωση του βάρους για τα χαρακτηριστικά με υψηλή τιμή
                        $this->weights[$weightKey] -= $learningRate * $value * $this->weights[$weightKey];
                    }
                }
            }

            // Κανονικοποίηση των βαρών
            $sum = array_sum($this->weights);
            if ($sum > 0) {
                foreach ($this->weights as $key => $value) {
                    $this->weights[$key] = $value / $sum;
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateWeightsFromFeedback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αναλύει το ιστορικό αναζήτησης ενός οδηγού για να βελτιώσει τις προτάσεις
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Τα δεδομένα ανάλυσης
     */
    public function analyzeDriverSearchHistory($driverId)
    {
        try {
            // Λήψη του ιστορικού αναζήτησης του οδηγού
            $query = "SELECT * FROM driver_search_history WHERE driver_id = :driver_id ORDER BY created_at DESC LIMIT 100";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':driver_id', $driverId, PDO::PARAM_INT);
            $stmt->execute();
            $searchHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($searchHistory)) {
                return [];
            }

            // Ανάλυση των κριτηρίων αναζήτησης
            $locationCounts = [];
            $jobTypeCounts = [];
            $vehicleTypeCounts = [];
            $salaryCounts = [];

            foreach ($searchHistory as $search) {
                // Ανάλυση τοποθεσίας
                if (!empty($search['location'])) {
                    $location = $search['location'];
                    if (!isset($locationCounts[$location])) {
                        $locationCounts[$location] = 0;
                    }
                    $locationCounts[$location]++;
                }

                // Ανάλυση τύπου εργασίας
                if (!empty($search['job_type'])) {
                    $jobType = $search['job_type'];
                    if (!isset($jobTypeCounts[$jobType])) {
                        $jobTypeCounts[$jobType] = 0;
                    }
                    $jobTypeCounts[$jobType]++;
                }

                // Ανάλυση τύπου οχήματος
                if (!empty($search['vehicle_type'])) {
                    $vehicleType = $search['vehicle_type'];
                    if (!isset($vehicleTypeCounts[$vehicleType])) {
                        $vehicleTypeCounts[$vehicleType] = 0;
                    }
                    $vehicleTypeCounts[$vehicleType]++;
                }

                // Ανάλυση μισθού
                if (!empty($search['min_salary'])) {
                    $minSalary = $search['min_salary'];
                    $maxSalary = $search['max_salary'] ?? $minSalary;
                    $salaryRange = $minSalary . '-' . $maxSalary;
                    if (!isset($salaryCounts[$salaryRange])) {
                        $salaryCounts[$salaryRange] = 0;
                    }
                    $salaryCounts[$salaryRange]++;
                }
            }

            // Ταξινόμηση των μετρήσεων
            arsort($locationCounts);
            arsort($jobTypeCounts);
            arsort($vehicleTypeCounts);
            arsort($salaryCounts);

            // Επιστροφή των αποτελεσμάτων
            return [
                'locations' => $locationCounts,
                'job_types' => $jobTypeCounts,
                'vehicle_types' => $vehicleTypeCounts,
                'salary_ranges' => $salaryCounts
            ];
        } catch (\Exception $e) {
            Logger::error('Error in analyzeDriverSearchHistory: ' . $e->getMessage());
            return [];
        }
    }
}
