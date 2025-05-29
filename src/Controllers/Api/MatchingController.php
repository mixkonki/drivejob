<?php

namespace Drivejob\Controllers\Api;

use Drivejob\Core\Controller;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Session;
use Drivejob\Services\AI\MatchingService;
use Drivejob\Services\AI\MatchingCacheService;
use Drivejob\Middleware\ApiAuthMiddleware;

class MatchingController extends Controller
{
    private $matchingService;
    private $cacheService;

    public function __construct()
    {
        $this->matchingService = new MatchingService();
        $this->cacheService = new MatchingCacheService();
    }

    /**
     * Get top job matches for a driver
     */
    public function getDriverMatches()
    {
        // Έλεγχος authentication
        if (!ApiAuthMiddleware::check(['driver'])) {
            return; // Το middleware θα στείλει την απάντηση
        }

        $user = ApiAuthMiddleware::getUser();
        $driverId = $user['id'];
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        try {
            // Έλεγχος cache πρώτα
            $cacheKey = $this->cacheService->getDriverMatchesKey($driverId, $limit);
            $cachedMatches = $this->cacheService->get($cacheKey);

            if ($cachedMatches !== null) {
                // Cache hit - επιστροφή cached αποτελεσμάτων
                return JsonResponse::success([
                    'matches' => $cachedMatches,
                    'count' => count($cachedMatches),
                    'cached' => true
                ]);
            }

            // Cache miss - query από database
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    ms.*,
                    jl.id as job_id,
                    jl.title as job_title,
                    jl.description,
                    jl.location,
                    c.company_name,
                    c.id as company_id
                FROM matching_scores ms
                JOIN job_listings jl ON ms.job_id = jl.id
                LEFT JOIN companies c ON jl.company_id = c.id
                WHERE ms.driver_id = ?
                AND jl.is_active = 1
                ORDER BY ms.overall_score DESC
                LIMIT ?
            ");
            $stmt->execute([$driverId, $limit]);
            $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Μορφοποίηση των αποτελεσμάτων
            $formattedMatches = [];
            foreach ($matches as $match) {
                $formattedMatches[] = [
                    'job_id' => $match['job_id'],
                    'title' => $match['job_title'],
                    'company' => $match['company_name'] ?? 'Unknown Company',
                    'location' => $match['location'],
                    'match_score' => round($match['overall_score'] * 100, 1),
                    'recommendation' => $this->getRecommendation($match['overall_score']),
                    'scores' => [
                        'skill_match' => round($match['skill_match_score'] * 100, 1),
                        'location_match' => round($match['location_match_score'] * 100, 1),
                        'experience_match' => round($match['experience_match_score'] * 100, 1),
                        'availability_match' => round($match['availability_match_score'] * 100, 1)
                    ]
                ];
            }

            // Αποθήκευση στο cache
            $this->cacheService->set($cacheKey, $formattedMatches);

            return JsonResponse::success([
                'matches' => $formattedMatches,
                'count' => count($formattedMatches),
                'cached' => false
            ]);
        } catch (\Exception $e) {
            error_log("Error getting driver matches: " . $e->getMessage());
            return JsonResponse::error('Failed to get matches', 500);
        }
    }

    /**
     * Get recommendation based on score
     */
    private function getRecommendation($score)
    {
        if ($score >= 0.9) return 'Εξαιρετική αντιστοιχία!';
        if ($score >= 0.75) return 'Πολύ καλή αντιστοιχία';
        if ($score >= 0.6) return 'Καλή αντιστοιχία';
        if ($score >= 0.4) return 'Μέτρια αντιστοιχία';
        return 'Χαμηλή αντιστοιχία';
    }

    /**
     * Get top candidates for a job
     */
    public function getJobCandidates()
    {
        Session::start();

        // Check if user is logged in as company
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
            return JsonResponse::error('Unauthorized', 401);
        }

        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        if (!$jobId) {
            return JsonResponse::error('Job ID is required', 400);
        }

        // TODO: Verify that the job belongs to the logged-in company

        try {
            $candidates = $this->matchingService->getTopCandidatesForJob($jobId, $limit);

            return JsonResponse::success([
                'candidates' => $candidates,
                'count' => count($candidates)
            ]);
        } catch (\Exception $e) {
            error_log("Error getting job candidates: " . $e->getMessage());
            return JsonResponse::error('Failed to get candidates', 500);
        }
    }

    /**
     * Calculate match score between a driver and a job
     */
    public function calculateMatch()
    {
        Session::start();

        // Allow both drivers and companies to calculate matches
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['driver', 'company'])) {
            return JsonResponse::error('Unauthorized', 401);
        }

        $driverId = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;
        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

        if (!$driverId || !$jobId) {
            return JsonResponse::error('Driver ID and Job ID are required', 400);
        }

        // If user is a driver, they can only check their own matches
        if ($_SESSION['user_role'] === 'driver' && $driverId != $_SESSION['user_id']) {
            return JsonResponse::error('Unauthorized', 401);
        }

        try {
            $result = $this->matchingService->calculateMatch($driverId, $jobId);

            if ($result['success']) {
                return JsonResponse::success($result);
            } else {
                return JsonResponse::error($result['error'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error calculating match: " . $e->getMessage());
            return JsonResponse::error('Failed to calculate match', 500);
        }
    }

    /**
     * Get match insights and recommendations
     */
    public function getMatchInsights()
    {
        Session::start();

        if (!isset($_SESSION['user_id'])) {
            return JsonResponse::error('Unauthorized', 401);
        }

        $driverId = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;
        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

        if (!$driverId || !$jobId) {
            return JsonResponse::error('Driver ID and Job ID are required', 400);
        }

        try {
            $result = $this->matchingService->calculateMatch($driverId, $jobId);

            if (!$result['success']) {
                return JsonResponse::error($result['error'], 500);
            }

            // Generate insights
            $scoreCalculator = new \Drivejob\Services\AI\ScoreCalculator();
            $featureExtractor = new \Drivejob\Services\AI\FeatureExtractor();

            $driverFeatures = $featureExtractor->extractDriverFeatures($driverId);
            $jobFeatures = $featureExtractor->extractJobFeatures($jobId);

            $insights = $scoreCalculator->generateInsights(
                $result['scores'],
                $driverFeatures,
                $jobFeatures
            );

            return JsonResponse::success([
                'match_score' => $result['overall_score'],
                'recommendation' => $result['recommendation'],
                'insights' => $insights,
                'details' => $result['scores']
            ]);
        } catch (\Exception $e) {
            error_log("Error getting match insights: " . $e->getMessage());
            return JsonResponse::error('Failed to get insights', 500);
        }
    }
}
