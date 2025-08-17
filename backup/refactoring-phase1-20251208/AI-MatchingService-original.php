<?php

namespace Drivejob\Services\AI;

use Drivejob\Core\Database;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\JobListingsRepository;

class MatchingService
{
    private $pdo;
    private $driversRepo;
    private $jobsRepo;
    private $featureExtractor;
    private $scoreCalculator;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->driversRepo = new DriversRepository($this->pdo);
        $this->jobsRepo = new JobListingsRepository($this->pdo);
        $this->featureExtractor = new FeatureExtractor();
        $this->scoreCalculator = new ScoreCalculator();
    }

    /**
     * Calculate match score between a driver and a job
     */
    public function calculateMatch(int $driverId, int $jobId): array
    {
        try {
            // 1. Extract features
            $driverFeatures = $this->featureExtractor->extractDriverFeatures($driverId);
            $jobFeatures = $this->featureExtractor->extractJobFeatures($jobId);

            // 2. Calculate individual scores
            $scores = [
                'skill_match' => $this->calculateSkillMatch($driverFeatures, $jobFeatures),
                'location_match' => $this->calculateLocationMatch($driverFeatures, $jobFeatures),
                'experience_match' => $this->calculateExperienceMatch($driverFeatures, $jobFeatures),
                'availability_match' => $this->calculateAvailabilityMatch($driverFeatures, $jobFeatures)
            ];

            // 3. Calculate overall score
            $overallScore = $this->scoreCalculator->calculateOverallScore($scores);

            // 4. Store the match score
            $this->storeMatchScore($driverId, $jobId, $overallScore, $scores);

            return [
                'success' => true,
                'overall_score' => $overallScore,
                'scores' => $scores,
                'recommendation' => $this->getRecommendation($overallScore)
            ];
        } catch (\Exception $e) {
            error_log("Matching error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to calculate match score'
            ];
        }
    }

    /**
     * Get top matches for a driver
     */
    public function getTopMatchesForDriver(int $driverId, int $limit = 10): array
    {
        try {
            // Get active job listings
            $stmt = $this->pdo->prepare("
                SELECT j.* 
                FROM job_listings j
                WHERE j.is_active = 1
                AND j.id NOT IN (
                    SELECT job_id FROM job_applications WHERE driver_id = ?
                )
                ORDER BY j.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$driverId]);
            $jobs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate scores for each job
            $matches = [];
            foreach ($jobs as $job) {
                $result = $this->calculateMatch($driverId, $job['id']);
                if ($result['success']) {
                    $matches[] = [
                        'job' => $job,
                        'score' => $result['overall_score'],
                        'details' => $result['scores']
                    ];
                }
            }

            // Sort by score
            usort($matches, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Return top matches
            return array_slice($matches, 0, $limit);
        } catch (\Exception $e) {
            error_log("Error getting top matches: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top candidates for a job
     */
    public function getTopCandidatesForJob(int $jobId, int $limit = 20): array
    {
        try {
            // Get active drivers
            $stmt = $this->pdo->prepare("
                SELECT d.* 
                FROM drivers d
                WHERE d.is_available = 1
                AND d.id NOT IN (
                    SELECT driver_id FROM job_applications WHERE job_id = ?
                )
                ORDER BY d.last_login DESC
                LIMIT 200
            ");
            $stmt->execute([$jobId]);
            $drivers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate scores for each driver
            $candidates = [];
            foreach ($drivers as $driver) {
                $result = $this->calculateMatch($driver['id'], $jobId);
                if ($result['success']) {
                    $candidates[] = [
                        'driver' => $driver,
                        'score' => $result['overall_score'],
                        'details' => $result['scores']
                    ];
                }
            }

            // Sort by score
            usort($candidates, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Return top candidates
            return array_slice($candidates, 0, $limit);
        } catch (\Exception $e) {
            error_log("Error getting top candidates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate matches for all available drivers for a specific job
     * Used for batch processing
     */
    public function calculateMatchesForJob(int $jobId): int
    {
        try {
            // Get all available drivers
            $stmt = $this->pdo->prepare("
                SELECT d.id 
                FROM drivers d
                JOIN users u ON d.user_id = u.id
                WHERE d.available_for_work = 1
                AND u.is_active = 1
            ");
            $stmt->execute();
            $drivers = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $matchCount = 0;
            foreach ($drivers as $driverId) {
                $result = $this->calculateMatch($driverId, $jobId);
                if ($result['success']) {
                    $matchCount++;
                }
            }

            return $matchCount;
        } catch (\Exception $e) {
            error_log("Error calculating matches for job: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate skill match score
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

        return $totalWeight > 0 ? $score / $totalWeight : 0;
    }

    /**
     * Calculate location match score
     */
    private function calculateLocationMatch(array $driverFeatures, array $jobFeatures): float
    {
        if (!isset($driverFeatures['location']) || !isset($jobFeatures['location'])) {
            return 0.5; // Default score if location data missing
        }

        // Calculate distance (simplified - in production use proper geospatial calculation)
        $distance = $this->calculateDistance(
            $driverFeatures['location']['lat'],
            $driverFeatures['location']['lng'],
            $jobFeatures['location']['lat'],
            $jobFeatures['location']['lng']
        );

        // Score based on distance
        if ($distance <= 10) return 1.0;
        if ($distance <= 25) return 0.8;
        if ($distance <= 50) return 0.6;
        if ($distance <= 100) return 0.4;
        return 0.2;
    }

    /**
     * Calculate experience match score
     */
    private function calculateExperienceMatch(array $driverFeatures, array $jobFeatures): float
    {
        $driverExperience = $driverFeatures['years_experience'] ?? 0;
        $requiredExperience = $jobFeatures['min_experience'] ?? 0;

        if ($driverExperience >= $requiredExperience) {
            // More experience than required
            $extraYears = $driverExperience - $requiredExperience;
            return min(1.0, 0.8 + ($extraYears * 0.05)); // Cap at 1.0
        } else {
            // Less experience than required
            $deficit = $requiredExperience - $driverExperience;
            return max(0, 0.8 - ($deficit * 0.2)); // Don't go below 0
        }
    }

    /**
     * Calculate availability match score
     */
    private function calculateAvailabilityMatch(array $driverFeatures, array $jobFeatures): float
    {
        // Check immediate availability
        if ($jobFeatures['urgent'] ?? false) {
            return ($driverFeatures['available_immediately'] ?? false) ? 1.0 : 0.3;
        }

        // Check schedule compatibility
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
     * Store match score in database
     */
    private function storeMatchScore(int $driverId, int $jobId, float $overallScore, array $scores): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO matching_scores 
            (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
             experience_match_score, availability_match_score, factors, ml_confidence)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                overall_score = VALUES(overall_score),
                skill_match_score = VALUES(skill_match_score),
                location_match_score = VALUES(location_match_score),
                experience_match_score = VALUES(experience_match_score),
                availability_match_score = VALUES(availability_match_score),
                factors = VALUES(factors),
                ml_confidence = VALUES(ml_confidence),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            $driverId,
            $jobId,
            $overallScore,
            $scores['skill_match'],
            $scores['location_match'],
            $scores['experience_match'],
            $scores['availability_match'],
            \json_encode($scores),
            0.85 // Placeholder ML confidence - will be replaced with actual ML model output
        ]);
    }

    /**
     * Get recommendation based on score
     */
    private function getRecommendation(float $score): string
    {
        if ($score >= 0.9) return 'Εξαιρετική αντιστοιχία!';
        if ($score >= 0.75) return 'Πολύ καλή αντιστοιχία';
        if ($score >= 0.6) return 'Καλή αντιστοιχία';
        if ($score >= 0.4) return 'Μέτρια αντιστοιχία';
        return 'Χαμηλή αντιστοιχία';
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
}
