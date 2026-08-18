<?php

namespace Drivejob\Controllers\Api;

use Drivejob\Core\Controller;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Session;
use Drivejob\Services\Matching\MatchingEngine;
use Drivejob\Services\AI\MatchingCacheService;
use Drivejob\Middleware\AuthenticationMiddleware as Auth;

class MatchingController extends Controller
{
    private $matchingService;
    private $cacheService;

    public function __construct()
    {
        $this->matchingService = new MatchingEngine();
        $this->cacheService = new MatchingCacheService();
    }

    /**
     * Get top job matches for a driver
     */
    public function getDriverMatches()
    {
        // Έλεγχος authentication
        if (!Auth::requireDriver(true)) {
            return; // Το middleware θα στείλει την απάντηση
        }

        $user = Auth::getCurrentUser();
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
        // Έλεγχος authentication για εταιρείες
        if (!Auth::requireCompany(true)) {
            return; // Το middleware θα στείλει την απάντηση
        }

        $user = Auth::getCurrentUser();
        $companyId = $user['id'];
        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        if (!$jobId) {
            return JsonResponse::error('Job ID is required', 400);
        }

        try {
            // Έλεγχος ότι η αγγελία ανήκει στην εταιρεία
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ? AND is_active = 1");
            $stmt->execute([$jobId, $companyId]);

            if (!$stmt->fetch()) {
                return JsonResponse::error('Job not found or access denied', 404);
            }

            // Έλεγχος cache πρώτα
            $cacheKey = $this->cacheService->getJobCandidatesKey($jobId, $limit);
            $cachedCandidates = $this->cacheService->get($cacheKey);

            if ($cachedCandidates !== null) {
                return JsonResponse::success([
                    'candidates' => $cachedCandidates,
                    'count' => count($cachedCandidates),
                    'cached' => true
                ]);
            }

            // Cache miss - λήψη από database
            $stmt = $pdo->prepare("
                SELECT 
                    ms.*,
                    d.id as driver_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    d.city,
                    d.experience_years,
                    d.available_for_work,
                    d.rating
                FROM matching_scores ms
                JOIN drivers d ON ms.driver_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE ms.job_id = ?
                AND d.available_for_work = 1
                ORDER BY ms.overall_score DESC
                LIMIT ?
            ");
            $stmt->execute([$jobId, $limit]);
            $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Μορφοποίηση των αποτελεσμάτων
            $formattedCandidates = [];
            foreach ($candidates as $candidate) {
                $formattedCandidates[] = [
                    'driver_id' => $candidate['driver_id'],
                    'name' => $candidate['first_name'] . ' ' . $candidate['last_name'],
                    'email' => $candidate['email'],
                    'city' => $candidate['city'],
                    'experience_years' => $candidate['experience_years'],
                    'rating' => round($candidate['rating'], 1),
                    'match_score' => round($candidate['overall_score'] * 100, 1),
                    'recommendation' => $this->getRecommendation($candidate['overall_score']),
                    'scores' => [
                        'skill_match' => round($candidate['skill_match_score'] * 100, 1),
                        'location_match' => round($candidate['location_match_score'] * 100, 1),
                        'experience_match' => round($candidate['experience_match_score'] * 100, 1),
                        'availability_match' => round($candidate['availability_match_score'] * 100, 1)
                    ]
                ];
            }

            // Αποθήκευση στο cache
            $this->cacheService->set($cacheKey, $formattedCandidates);

            return JsonResponse::success([
                'candidates' => $formattedCandidates,
                'count' => count($formattedCandidates),
                'cached' => false
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
        // Έλεγχος authentication
        if (!Auth::requireDriverOrCompany(true)) {
            return; // Το middleware θα στείλει την απάντηση
        }

        $user = Auth::getCurrentUser();
        $driverId = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;
        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

        if (!$driverId || !$jobId) {
            return JsonResponse::error('Driver ID and Job ID are required', 400);
        }

        // Αν ο χρήστης είναι οδηγός, μπορεί να δει μόνο τα δικά του matches
        if ($user['role'] === 'driver' && $driverId != $user['id']) {
            return JsonResponse::error('Unauthorized', 401);
        }

        // Αν ο χρήστης είναι εταιρεία, πρέπει να ελέγξουμε ότι η αγγελία της ανήκει
        if ($user['role'] === 'company') {
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ?");
            $stmt->execute([$jobId, $user['id']]);

            if (!$stmt->fetch()) {
                return JsonResponse::error('Job not found or access denied', 404);
            }
        }

        try {
            $result = $this->matchingService->matchDetails($driverId, $jobId);

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
        // Έλεγχος authentication
        if (!Auth::requireDriverOrCompany(true)) {
            return; // Το middleware θα στείλει την απάντηση
        }

        $user = Auth::getCurrentUser();
        $driverId = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 0;
        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

        if (!$driverId || !$jobId) {
            return JsonResponse::error('Driver ID and Job ID are required', 400);
        }

        // Έλεγχοι πρόσβασης όπως στην calculateMatch
        if ($user['role'] === 'driver' && $driverId != $user['id']) {
            return JsonResponse::error('Unauthorized', 401);
        }

        if ($user['role'] === 'company') {
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ?");
            $stmt->execute([$jobId, $user['id']]);

            if (!$stmt->fetch()) {
                return JsonResponse::error('Job not found or access denied', 404);
            }
        }

        try {
            $result = $this->matchingService->matchDetails($driverId, $jobId);

            if (!$result['success']) {
                return JsonResponse::error($result['error'], 500);
            }

            // Generate insights με τον Advanced Score Calculator
            $advancedCalculator = new \Drivejob\Services\AI\AdvancedScoreCalculator();
            $featureExtractor = new \Drivejob\Services\AI\FeatureExtractor();

            $driverFeatures = $featureExtractor->extractDriverFeatures($driverId);
            $jobFeatures = $featureExtractor->extractJobFeatures($jobId);

            $insights = $advancedCalculator->generateAdvancedInsights(
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

    /**
     * Trigger batch matching for all active jobs
     * (Admin only)
     */
    public function triggerBatchMatching()
    {
        // Έλεγχος authentication για admin
        if (!Auth::requireAdmin(true)) {
            return; // Το middleware θα στείλει την απάντηση
        }

        try {
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

            // Λήψη όλων των ενεργών αγγελιών
            $stmt = $pdo->query("SELECT id FROM job_listings WHERE is_active = 1");
            $jobs = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $totalMatches = 0;
            $processedJobs = 0;

            foreach ($jobs as $jobId) {
                // Υπολογισμός matches για κάθε αγγελία
                $matches = $this->matchingService->recomputeJobMatches($jobId);
                $totalMatches += $matches;
                $processedJobs++;

                // Invalidate cache για αυτή την αγγελία
                $this->cacheService->invalidateJobCache($jobId);
            }

            return JsonResponse::success([
                'message' => 'Batch matching completed',
                'processed_jobs' => $processedJobs,
                'total_matches' => $totalMatches
            ]);
        } catch (\Exception $e) {
            error_log("Error in batch matching: " . $e->getMessage());
            return JsonResponse::error('Failed to complete batch matching', 500);
        }
    }
}
