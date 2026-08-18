<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Database;
use Drivejob\Repositories\MatchingRepositoryInterface;
use Drivejob\Repositories\MatchingRepository;
use Drivejob\Repositories\DriversRepositoryInterface;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\JobListingRepositoryInterface;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Services\GeoLocationService;
use Drivejob\Services\MachineLearningService;
use Drivejob\Services\AI\FeatureExtractor;
use Drivejob\Services\AI\ScoreCalculator;
use Drivejob\Services\Matching\MatchingServiceInterface;
use Drivejob\Helpers\JsonHelper;

/**
 * Enhanced Matching Service - Ενοποιημένη υπηρεσία ταιριάσματος
 * Συνδυάζει τη λογική από το κύριο MatchingService και το AI MatchingService
 */
class EnhancedMatchingService implements MatchingServiceInterface
{
    private PDO $pdo;
    private MatchingRepositoryInterface $matchingRepository;
    private DriversRepositoryInterface $driversRepository;
    private JobListingRepositoryInterface $jobListingRepository;
    private GeoLocationService $geoLocationService;
    private MachineLearningService $machineLearningService;
    private FeatureExtractor $featureExtractor;
    private ScoreCalculator $scoreCalculator;

    /**
     * Βάρη για τα διάφορα κριτήρια ταιριάσματος
     */
    private array $weights = [
        'vehicle_type' => 0.25,
        'location' => 0.20,
        'job_type' => 0.15,
        'schedule' => 0.15,
        'salary' => 0.10,
        'skills' => 0.15
    ];

    public function __construct(
        ?PDO $pdo = null,
        ?MatchingRepositoryInterface $matchingRepository = null,
        ?DriversRepositoryInterface $driversRepository = null,
        ?JobListingRepositoryInterface $jobListingRepository = null,
        ?GeoLocationService $geoLocationService = null,
        ?MachineLearningService $machineLearningService = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
        $this->matchingRepository = $matchingRepository ?? new MatchingRepository($this->pdo);
        $this->driversRepository = $driversRepository ?? new DriversRepository($this->pdo);
        $this->jobListingRepository = $jobListingRepository ?? new JobListingRepository($this->pdo);
        $this->geoLocationService = $geoLocationService ?? new GeoLocationService();
        $this->machineLearningService = $machineLearningService ?? new MachineLearningService($this->pdo);
        $this->featureExtractor = new FeatureExtractor();
        $this->scoreCalculator = new ScoreCalculator();
    }

    /**
     * Υπολογίζει το σκορ ταιριάσματος μεταξύ ενός οδηγού και μιας αγγελίας
     * Χρησιμοποιεί τόσο παραδοσιακή λογική όσο και AI features
     */
    public function calculateMatchScore($driver, $jobListing): float
    {
        try {
            // Έλεγχος αν τα δεδομένα είναι IDs ή πλήρη δεδομένα
            $driverId = is_array($driver) ? $driver['id'] : $driver;
            $jobListingId = is_array($jobListing) ? $jobListing['id'] : $jobListing;

            // Λήψη των πλήρων δεδομένων αν έχουμε μόνο τα IDs
            $driverData = is_array($driver) ? $driver : $this->driversRepository->find($driverId);
            $jobListingData = is_array($jobListing) ? $jobListing : $this->jobListingRepository->find($jobListingId);

            if (!$driverData || !$jobListingData) {
                return 0.0;
            }

            // Try OpenAI Advanced Matching first
            // ΜΟΝΟ σε CLI (cron/scripts): το AI παίρνει ~10s/αγγελία και δεν πρέπει
            // να μπλοκάρει web requests (αποθήκευση προφίλ κ.λπ.). Στο web τρέχει
            // ο γρήγορος rule-based και το cron εμπλουτίζει τα σκορ στο παρασκήνιο.
            try {
                if (PHP_SAPI !== 'cli' || defined('DRIVEJOB_DISABLE_AI')) {
                    throw new \Exception('AI matching deferred (web request or bulk mode)');
                }
                $openAIService = new \Drivejob\Services\AI\OpenAIMatchingService();
                $aiResult = $openAIService->calculateAdvancedMatchScore($driverId, $jobListingId);

                if ($aiResult['overall_score'] > 0) {
                    // Use AI result if available
                    $this->storeMatchScore($driverId, $jobListingId, $aiResult['overall_score'], $aiResult);
                    return $aiResult['overall_score'];
                }
            } catch (\Exception $aiError) {
                Logger::warning('OpenAI matching failed, falling back to enhanced algorithm: ' . $aiError->getMessage());
            }

            // Fallback to enhanced algorithm
            $aiResult = $this->calculateAIMatch($driverId, $jobListingId);
            $traditionalScore = $this->calculateTraditionalMatch($driverData, $jobListingData);

            // Improved scoring combination (60% AI, 40% traditional for better balance)
            $finalScore = ($aiResult['overall_score'] * 0.6) + ($traditionalScore * 0.4);

            // Ensure realistic score range (minimum 15% for any valid match)
            if ($finalScore > 0 && $finalScore < 15) {
                $finalScore = 15 + ($finalScore * 2); // Boost low scores
            }

            // Cap maximum score at 95% (no perfect matches)
            if ($finalScore > 95) {
                $finalScore = 95;
            }

            // Αποθήκευση του σκορ
            $this->storeMatchScore($driverId, $jobListingId, $finalScore, $aiResult['scores'] ?? []);

            return $finalScore;
        } catch (\Exception $e) {
            Logger::error('Error in calculateMatchScore: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Υπολογισμός με AI features
     */
    private function calculateAIMatch(int $driverId, int $jobId): array
    {
        try {
            // Extract features
            $driverFeatures = $this->featureExtractor->extractDriverFeatures($driverId);
            $jobFeatures = $this->featureExtractor->extractJobFeatures($jobId);

            // Calculate individual scores
            $scores = [
                'skill_match' => $this->calculateSkillMatch($driverFeatures, $jobFeatures),
                'location_match' => $this->calculateLocationMatch($driverFeatures, $jobFeatures),
                'experience_match' => $this->calculateExperienceMatch($driverFeatures, $jobFeatures),
                'availability_match' => $this->calculateAvailabilityMatch($driverFeatures, $jobFeatures)
            ];

            // Calculate overall score
            $overallScore = $this->scoreCalculator->calculateOverallScore($scores);

            return [
                'success' => true,
                'overall_score' => $overallScore,
                'scores' => $scores
            ];
        } catch (\Exception $e) {
            Logger::error('Error in calculateAIMatch: ' . $e->getMessage());
            return [
                'success' => false,
                'overall_score' => 0.0,
                'scores' => []
            ];
        }
    }

    /**
     * Υπολογισμός με παραδοσιακή μέθοδο
     */
    private function calculateTraditionalMatch(array $driverData, array $jobListingData): float
    {
        $score = 0.0;
        $totalWeight = 0.0;

        // Τύπος οχήματος
        $vehicleTypeWeight = $this->weights['vehicle_type'];
        $totalWeight += $vehicleTypeWeight;
        if ($this->isCompatibleVehicleType($driverData, $jobListingData)) {
            $score += $vehicleTypeWeight;
        }

        // Τοποθεσία
        $locationWeight = $this->weights['location'];
        $totalWeight += $locationWeight;
        $locationScore = $this->calculateLocationScore($driverData, $jobListingData);
        $score += $locationWeight * $locationScore;

        // Τύπος εργασίας
        $jobTypeWeight = $this->weights['job_type'];
        $totalWeight += $jobTypeWeight;
        if ($this->isCompatibleJobType($driverData, $jobListingData)) {
            $score += $jobTypeWeight;
        }

        // Ωράριο
        $scheduleWeight = $this->weights['schedule'];
        $totalWeight += $scheduleWeight;
        $scheduleScore = $this->calculateScheduleCompatibility($driverData, $jobListingData);
        $score += $scheduleWeight * $scheduleScore;

        // Μισθός
        $salaryWeight = $this->weights['salary'];
        $totalWeight += $salaryWeight;
        $salaryScore = $this->calculateSalaryOverlap($driverData, $jobListingData);
        $score += $salaryWeight * $salaryScore;

        // Δεξιότητες
        $skillsWeight = $this->weights['skills'];
        $totalWeight += $skillsWeight;
        $skillsScore = $this->calculateSkillsMatch($driverData, $jobListingData);
        $score += $skillsWeight * $skillsScore;

        return $totalWeight > 0 ? ($score / $totalWeight) * 100 : 0.0;
    }

    /**
     * AI Skill match calculation
     */
    private function calculateSkillMatch(array $driverFeatures, array $jobFeatures): float
    {
        $score = 0.0;
        $totalWeight = 0.0;

        // License type match
        if (isset($driverFeatures['licenses']) && isset($jobFeatures['required_license'])) {
            $hasRequiredLicense = in_array($jobFeatures['required_license'], $driverFeatures['licenses']);
            $score += $hasRequiredLicense ? 1.5 : 0;
            $totalWeight += 1.5;
        }

        // Certifications match
        if (isset($driverFeatures['certifications']) && isset($jobFeatures['required_certifications'])) {
            $matchedCerts = array_intersect($driverFeatures['certifications'], $jobFeatures['required_certifications']);
            $certScore = count($matchedCerts) / max(count($jobFeatures['required_certifications']), 1);
            $score += $certScore * 1.2;
            $totalWeight += 1.2;
        }

        // Vehicle type experience
        if (isset($driverFeatures['vehicle_types']) && isset($jobFeatures['vehicle_type'])) {
            $hasVehicleExperience = in_array($jobFeatures['vehicle_type'], $driverFeatures['vehicle_types']);
            $score += $hasVehicleExperience ? 1.4 : 0;
            $totalWeight += 1.4;
        }

        return $totalWeight > 0 ? $score / $totalWeight : 0.0;
    }

    /**
     * AI Location match calculation
     */
    private function calculateLocationMatch(array $driverFeatures, array $jobFeatures): float
    {
        // Use the enhanced location scoring logic
        $driverData = [
            'city' => $driverFeatures['city'] ?? '',
            'country' => $driverFeatures['country'] ?? ''
        ];

        $jobData = [
            'location' => $jobFeatures['location'] ?? ''
        ];

        return $this->calculateLocationScore($driverData, $jobData);
    }

    /**
     * AI Experience match calculation
     */
    private function calculateExperienceMatch(array $driverFeatures, array $jobFeatures): float
    {
        $driverExperience = $driverFeatures['years_experience'] ?? 0;
        $requiredExperience = $jobFeatures['min_experience'] ?? 0;

        if ($driverExperience >= $requiredExperience) {
            $extraYears = $driverExperience - $requiredExperience;
            return min(1.0, 0.8 + ($extraYears * 0.05));
        } else {
            $deficit = $requiredExperience - $driverExperience;
            return max(0, 0.8 - ($deficit * 0.2));
        }
    }

    /**
     * AI Availability match calculation
     */
    private function calculateAvailabilityMatch(array $driverFeatures, array $jobFeatures): float
    {
        if ($jobFeatures['urgent'] ?? false) {
            return ($driverFeatures['available_immediately'] ?? false) ? 1.0 : 0.3;
        }

        $driverSchedule = $driverFeatures['preferred_schedule'] ?? 'any';
        $jobSchedule = $jobFeatures['schedule_type'] ?? 'full_time';

        $scheduleCompatibility = [
            'any' => ['full_time' => 1.0, 'part_time' => 1.0, 'flexible' => 1.0],
            'full_time' => ['full_time' => 1.0, 'part_time' => 0.5, 'flexible' => 0.7],
            'part_time' => ['full_time' => 0.5, 'part_time' => 1.0, 'flexible' => 0.8],
            'flexible' => ['full_time' => 0.7, 'part_time' => 0.8, 'flexible' => 1.0]
        ];

        return $scheduleCompatibility[$driverSchedule][$jobSchedule] ?? 0.5;
    }

    /**
     * Traditional compatibility checks
     */
    private function isCompatibleVehicleType(array $driver, array $jobListing): bool
    {
        if (empty($jobListing['vehicle_type'])) {
            return true;
        }

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

        $driverLicenses = [];
        if (isset($driver['licenses']) && is_array($driver['licenses'])) {
            foreach ($driver['licenses'] as $license) {
                $driverLicenses[] = $license['license_type'];
            }
        }

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

    private function calculateLocationScore(array $driver, array $jobListing): float
    {
        if (empty($jobListing['location'])) {
            return 1.0;
        }

        // Αμυντικά: τα features μπορεί να δίνουν location ως array (π.χ. από FeatureExtractor)
        $rawDriverCity = $driver['city'] ?? '';
        $rawDriverCountry = $driver['country'] ?? '';
        $rawLocation = $jobListing['location'];
        if (is_array($rawDriverCity)) { $rawDriverCity = implode(' ', array_filter(array_map('strval', $rawDriverCity))); }
        if (is_array($rawDriverCountry)) { $rawDriverCountry = implode(' ', array_filter(array_map('strval', $rawDriverCountry))); }
        if (is_array($rawLocation)) { $rawLocation = implode(' ', array_filter(array_map('strval', $rawLocation))); }

        $driverCity = strtolower(trim((string) $rawDriverCity));
        $driverCountry = strtolower(trim((string) $rawDriverCountry));
        $listingLocation = strtolower(trim((string) $rawLocation));

        // Normalize Greek city names
        $cityNormalizations = [
            'θεσσαλονίκη' => ['thessaloniki', 'salonica', 'θεσσαλονικη'],
            'αθήνα' => ['athens', 'αθηνα', 'attiki', 'αττική'],
            'πάτρα' => ['patras', 'πατρα'],
            'λάρισα' => ['larissa', 'λαρισα'],
            'ηράκλειο' => ['heraklion', 'ηρακλειο'],
            'βόλος' => ['volos', 'βολος']
        ];

        // Check for exact city match first
        if (!empty($driverCity)) {
            // Direct match
            if (stripos($listingLocation, $driverCity) !== false) {
                return 1.0;
            }

            // Check normalized variations
            foreach ($cityNormalizations as $greekCity => $variations) {
                if ($driverCity === $greekCity || in_array($driverCity, $variations)) {
                    // Check if any variation appears in job location
                    if (stripos($listingLocation, $greekCity) !== false) {
                        return 1.0;
                    }
                    foreach ($variations as $variation) {
                        if (stripos($listingLocation, $variation) !== false) {
                            return 1.0;
                        }
                    }
                }
            }
        }

        // Regional matching for Greece - FIXED LOGIC
        if (!empty($driverCity) && !empty($listingLocation)) {
            // Special case: Thessaloniki -> Attica (different regions)
            if ($driverCity === 'θεσσαλονίκη' && stripos($listingLocation, 'αττική') !== false) {
                return 0.4; // Different regions but same country
            }

            // Special case: Athens/Attica -> Thessaloniki
            if (($driverCity === 'αθήνα' || stripos($driverCity, 'αττική') !== false) &&
                stripos($listingLocation, 'θεσσαλονίκη') !== false
            ) {
                return 0.4; // Different regions but same country
            }

            $regionalMatches = [
                'θεσσαλονίκη' => ['μακεδονία', 'βόρεια ελλάδα', 'κεντρική μακεδονία'],
                'αθήνα' => ['αττική', 'στερεά ελλάδα', 'κεντρική ελλάδα'],
                'πάτρα' => ['αχαΐα', 'πελοπόννησος', 'δυτική ελλάδα'],
                'λάρισα' => ['θεσσαλία', 'κεντρική ελλάδα'],
                'ηράκλειο' => ['κρήτη', 'νότια ελλάδα'],
                'βόλος' => ['μαγνησία', 'θεσσαλία']
            ];

            if (isset($regionalMatches[$driverCity])) {
                foreach ($regionalMatches[$driverCity] as $region) {
                    if (stripos($listingLocation, $region) !== false) {
                        return 0.8;
                    }
                }
            }
        }

        // Country match
        if (!empty($driverCountry)) {
            $countryVariations = ['ελλάδα', 'greece', 'gr', 'ελλαδα', 'greek'];
            foreach ($countryVariations as $country) {
                if (stripos($listingLocation, $country) !== false) {
                    return 0.6;
                }
            }
        }

        // Distance-based scoring for specific locations
        $locationDistances = [
            'θεσσαλονίκη' => [
                'λάρισα' => 0.7,      // ~160km
                'βόλος' => 0.6,       // ~220km
                'αθήνα' => 0.4,       // ~500km
                'αττική' => 0.4,      // Same as Athens
                'πάτρα' => 0.3,       // ~600km
                'ηράκλειο' => 0.2     // ~700km+
            ],
            'αθήνα' => [
                'πάτρα' => 0.7,       // ~200km
                'λάρισα' => 0.6,      // ~350km
                'βόλος' => 0.5,       // ~320km
                'θεσσαλονίκη' => 0.4, // ~500km
                'ηράκλειο' => 0.3     // ~600km+
            ]
        ];

        if (isset($locationDistances[$driverCity])) {
            foreach ($locationDistances[$driverCity] as $city => $score) {
                if (stripos($listingLocation, $city) !== false) {
                    return $score;
                }
            }
        }

        // Default for any Greek location
        return 0.3;
    }

    private function isCompatibleJobType(array $driver, array $jobListing): bool
    {
        if (empty($jobListing['job_type'])) {
            return true;
        }

        $driverJobTypes = [];
        if (isset($driver['job_preferences']) && is_array($driver['job_preferences'])) {
            $driverJobTypes = $driver['job_preferences'];
        }

        if (empty($driverJobTypes)) {
            return true;
        }

        return in_array($jobListing['job_type'], $driverJobTypes);
    }

    private function calculateScheduleCompatibility(array $driver, array $jobListing): float
    {
        if (empty($jobListing['schedule']) || empty($driver['availability'])) {
            return 1.0;
        }

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

    private function calculateSalaryOverlap(array $driver, array $jobListing): float
    {
        if (
            empty($jobListing['salary_min']) || empty($jobListing['salary_max']) ||
            empty($driver['min_salary']) || empty($driver['max_salary'])
        ) {
            return 1.0;
        }

        $jobMin = $jobListing['salary_min'];
        $jobMax = $jobListing['salary_max'];
        $driverMin = $driver['min_salary'];
        $driverMax = $driver['max_salary'];

        if ($jobMax < $driverMin || $jobMin > $driverMax) {
            return 0.0;
        }

        $overlapMin = max($jobMin, $driverMin);
        $overlapMax = min($jobMax, $driverMax);
        $overlapRange = $overlapMax - $overlapMin;

        $totalRange = max($jobMax, $driverMax) - min($jobMin, $driverMin);

        return $totalRange > 0 ? $overlapRange / $totalRange : 1.0;
    }

    private function calculateSkillsMatch(array $driver, array $jobListing): float
    {
        if (empty($jobListing['required_skills'])) {
            return 1.0;
        }

        $requiredSkills = is_array($jobListing['required_skills']) ?
            $jobListing['required_skills'] : explode(',', $jobListing['required_skills']);

        $driverSkills = [];
        if (isset($driver['skills']) && is_array($driver['skills'])) {
            foreach ($driver['skills'] as $skill => $value) {
                if ($value == 1) {
                    $driverSkills[] = $skill;
                }
            }
        }

        if (empty($driverSkills)) {
            return 0.0;
        }

        $matchCount = 0;
        foreach ($requiredSkills as $skill) {
            if (in_array($skill, $driverSkills)) {
                $matchCount++;
            }
        }

        return $matchCount / count($requiredSkills);
    }

    /**
     * Calculate distance between two points (Haversine formula)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Store match score in database
     */
    private function storeMatchScore(int $driverId, int $jobId, float $overallScore, array $scores): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO matching_scores 
                (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
                 experience_match_score, availability_match_score, factors, ml_confidence, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    overall_score = VALUES(overall_score),
                    skill_match_score = VALUES(skill_match_score),
                    location_match_score = VALUES(location_match_score),
                    experience_match_score = VALUES(experience_match_score),
                    availability_match_score = VALUES(availability_match_score),
                    factors = VALUES(factors),
                    ml_confidence = VALUES(ml_confidence),
                    updated_at = NOW()
            ");

            $stmt->execute([
                $driverId,
                $jobId,
                $overallScore,
                $scores['skill_match'] ?? 0,
                $scores['location_match'] ?? 0,
                $scores['experience_match'] ?? 0,
                $scores['availability_match'] ?? 0,
                JsonHelper::encode($scores),
                0.85 // ML confidence placeholder
            ]);
        } catch (\Exception $e) {
            Logger::error('Error storing match score: ' . $e->getMessage());
        }
    }

    /**
     * Public API methods
     */
    public function findMatchingJobsForDriver(int $driverId, array $criteria = [], int $page = 1, int $limit = 10): array
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

    public function findMatchingDriversForCompany(int $companyId, array $criteria = [], int $page = 1, int $limit = 10): array
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

    public function getTopMatchesForDriver(int $driverId, int $limit = 10): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT j.*, c.company_name, ms.overall_score
                FROM job_listings j
                JOIN companies c ON j.company_id = c.id
                LEFT JOIN matching_scores ms ON j.id = ms.job_id AND ms.driver_id = ?
                WHERE j.is_active = 1
                AND j.id NOT IN (
                    SELECT COALESCE(job_id, 0) FROM job_applications WHERE driver_id = ? AND job_id IS NOT NULL
                )
                ORDER BY ms.overall_score DESC, j.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$driverId, $driverId, $limit]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no results with scores, get jobs without score filtering
            if (empty($results)) {
                $stmt = $this->pdo->prepare("
                    SELECT j.*, c.company_name, ms.overall_score
                    FROM job_listings j
                    JOIN companies c ON j.company_id = c.id
                    LEFT JOIN matching_scores ms ON j.id = ms.job_id AND ms.driver_id = ?
                    WHERE j.is_active = 1
                    ORDER BY j.created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$driverId, $limit]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $results;
        } catch (\Exception $e) {
            Logger::error('Error getting top matches: ' . $e->getMessage());
            return [];
        }
    }

    public function getTopCandidatesForJob(int $jobId, int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, d.first_name, d.last_name, ms.overall_score
                FROM drivers d
                LEFT JOIN matching_scores ms ON d.id = ms.driver_id AND ms.job_id = ?
                WHERE d.available_for_work = 1
                AND d.is_verified = 1
                AND d.id NOT IN (
                    SELECT driver_id FROM job_applications WHERE job_id = ?
                )
                ORDER BY ms.overall_score DESC, d.last_login DESC
                LIMIT ?
            ");
            $stmt->execute([$jobId, $jobId, $limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('Error getting top candidates: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Backward compatibility methods
     */
    public function findJobsForDriver(int $driverId, int $page = 1, int $limit = 10): array
    {
        return $this->findMatchingJobsForDriver($driverId, [], $page, $limit);
    }

    public function findDriversForJob(int $jobListingId, int $page = 1, int $limit = 10): array
    {
        return $this->findMatchingDriversForCompany($jobListingId, [], $page, $limit);
    }

    /**
     * Update weights based on feedback
     */
    public function updateWeights(array $newWeights): void
    {
        $this->weights = array_merge($this->weights, $newWeights);
    }

    /**
     * Get current weights
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Implementation of MatchingServiceInterface methods
     */
    public function findMatchesForDriver(int $driverId, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        return $this->findMatchingJobsForDriver($driverId, $criteria, $page, $limit);
    }

    public function findMatchesForJobListing(int $jobListingId, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        return $this->findMatchingDriversForCompany($jobListingId, $criteria, $page, $limit);
    }

    public function findMatchesForCompany(int $companyId, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        return $this->findMatchingDriversForCompany($companyId, $criteria, $page, $limit);
    }

    public function saveMatchPreferences(int $userId, string $userType, array $preferences): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO match_preferences (user_id, user_type, preferences, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    preferences = VALUES(preferences),
                    updated_at = NOW()
            ");

            return $stmt->execute([
                $userId,
                $userType,
                JsonHelper::encode($preferences)
            ]);
        } catch (\Exception $e) {
            Logger::error('Error saving match preferences: ' . $e->getMessage());
            return false;
        }
    }

    public function getMatchPreferences(int $userId, string $userType): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT preferences FROM match_preferences 
                WHERE user_id = ? AND user_type = ?
            ");
            $stmt->execute([$userId, $userType]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? JsonHelper::decode($result['preferences']) : [];
        } catch (\Exception $e) {
            Logger::error('Error getting match preferences: ' . $e->getMessage());
            return [];
        }
    }

    public function logMatchAction(int $driverId, int $jobListingId, float $matchScore, string $driverAction = 'no_action', string $companyAction = 'no_action'): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO match_history 
                (driver_id, job_listing_id, match_score, driver_action, company_action, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([
                $driverId,
                $jobListingId,
                $matchScore,
                $driverAction,
                $companyAction
            ]);
        } catch (\Exception $e) {
            Logger::error('Error logging match action: ' . $e->getMessage());
            return false;
        }
    }

    public function getDriverMatchHistory(int $driverId, int $page = 1, int $limit = 10): array
    {
        try {
            $offset = ($page - 1) * $limit;

            $stmt = $this->pdo->prepare("
                SELECT mh.*, j.title as job_title, c.company_name
                FROM match_history mh
                LEFT JOIN job_listings j ON mh.job_listing_id = j.id
                LEFT JOIN companies c ON j.company_id = c.id
                WHERE mh.driver_id = ?
                ORDER BY mh.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$driverId, $limit, $offset]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countStmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM match_history WHERE driver_id = ?
            ");
            $countStmt->execute([$driverId]);
            $total = $countStmt->fetchColumn();

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('Error getting driver match history: ' . $e->getMessage());
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

    public function getJobListingMatchHistory(int $jobListingId, int $page = 1, int $limit = 10): array
    {
        try {
            $offset = ($page - 1) * $limit;

            $stmt = $this->pdo->prepare("
                SELECT mh.*, d.first_name, d.last_name
                FROM match_history mh
                LEFT JOIN drivers d ON mh.driver_id = d.id
                WHERE mh.job_listing_id = ?
                ORDER BY mh.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$jobListingId, $limit, $offset]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countStmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM match_history WHERE job_listing_id = ?
            ");
            $countStmt->execute([$jobListingId]);
            $total = $countStmt->fetchColumn();

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('Error getting job listing match history: ' . $e->getMessage());
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
}
