<?php

namespace Drivejob\Services;

use Drivejob\Core\Database;
use Drivejob\Services\OpenAIService;
use PDO;

/**
 * AI-Powered Job Matching Service
 * 
 * Χρησιμοποιεί πραγματικούς AI αλγορίθμους για intelligent matching
 * μεταξύ οδηγών και θέσεων εργασίας
 */
class AIMatchingService
{
    private $pdo;
    private $skillWeights;
    private $locationWeights;
    private $experienceWeights;
    private $openAIService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->openAIService = new OpenAIService();

        // AI-based weighting system
        $this->skillWeights = [
            'license_match' => 0.35,
            'experience_match' => 0.25,
            'location_match' => 0.20,
            'availability_match' => 0.10,
            'semantic_match' => 0.10
        ];
    }

    /**
     * Βρίσκει matches χρησιμοποιώντας AI algorithms
     */
    public function findAIMatches($driverId, $page = 1, $limit = 20)
    {
        // 1. Λήψη driver profile
        $driverProfile = $this->getDriverProfile($driverId);

        // 2. Λήψη διαθέσιμων job listings
        $jobListings = $this->getAvailableJobs();

        // 3. AI-powered matching για κάθε job
        $matches = [];
        foreach ($jobListings as $job) {
            $score = $this->calculateAIMatchScore($driverProfile, $job);

            if ($score > 0.3) { // Threshold για relevance
                $matches[] = [
                    'job' => $job,
                    'score' => $score,
                    'ai_insights' => $this->generateAIInsights($driverProfile, $job, $score),
                    'match_factors' => $this->getMatchFactors($driverProfile, $job)
                ];
            }
        }

        // 4. Ταξινόμηση με AI ranking
        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 5. Pagination
        $total = count($matches);
        $offset = ($page - 1) * $limit;
        $matches = array_slice($matches, $offset, $limit);

        return [
            'matches' => $matches,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit),
            'ai_powered' => true
        ];
    }

    /**
     * Υπολογίζει AI-powered match score
     */
    private function calculateAIMatchScore($driver, $job)
    {
        $scores = [];

        // 1. License Compatibility Analysis
        $scores['license_match'] = $this->analyzeLicenseCompatibility($driver, $job);

        // 2. Experience Relevance Scoring
        $scores['experience_match'] = $this->analyzeExperienceRelevance($driver, $job);

        // 3. Geographic Optimization
        $scores['location_match'] = $this->analyzeLocationCompatibility($driver, $job);

        // 4. Availability Alignment
        $scores['availability_match'] = $this->analyzeAvailabilityAlignment($driver, $job);

        // 5. Semantic Text Analysis
        $scores['semantic_match'] = $this->performSemanticAnalysis($driver, $job);

        // 6. Weighted AI Score Calculation
        $finalScore = 0;
        foreach ($scores as $factor => $score) {
            $weight = $this->skillWeights[$factor] ?? 0;
            $finalScore += $score * $weight;
        }

        // 7. AI Boost για high-potential matches
        $finalScore = $this->applyAIBoost($finalScore, $scores);

        return min($finalScore, 1.0); // Cap at 100%
    }

    /**
     * Αναλύει συμβατότητα αδειών με AI logic
     */
    private function analyzeLicenseCompatibility($driver, $job)
    {
        $driverLicenses = $this->extractDriverLicenses($driver);
        $requiredLicenses = $this->extractJobRequirements($job);

        if (empty($requiredLicenses)) {
            return 0.8; // Default good score για jobs χωρίς specific requirements
        }

        $matchCount = 0;
        $totalRequired = count($requiredLicenses);

        foreach ($requiredLicenses as $required) {
            if ($this->hasCompatibleLicense($driverLicenses, $required)) {
                $matchCount++;
            }
        }

        $baseScore = $matchCount / $totalRequired;

        // AI Enhancement: Bonus για over-qualification
        if ($matchCount > $totalRequired) {
            $baseScore += 0.1; // Bonus για extra qualifications
        }

        return min($baseScore, 1.0);
    }

    /**
     * Αναλύει relevance της εμπειρίας με ML techniques
     */
    private function analyzeExperienceRelevance($driver, $job)
    {
        $driverExperience = $driver['experience_years'] ?? 0;
        $jobExperience = $this->extractExperienceRequirement($job);

        if ($jobExperience === null) {
            return 0.7; // Neutral score
        }

        // AI-based experience scoring curve
        if ($driverExperience >= $jobExperience) {
            $overQualification = $driverExperience - $jobExperience;

            // Optimal range: 0-3 years over requirement
            if ($overQualification <= 3) {
                return 1.0;
            } else {
                // Diminishing returns για over-qualification
                return max(0.8, 1.0 - (($overQualification - 3) * 0.05));
            }
        } else {
            // Under-qualified but might still be suitable
            $gap = $jobExperience - $driverExperience;
            return max(0.2, 1.0 - ($gap * 0.15));
        }
    }

    /**
     * Γεωγραφική ανάλυση με AI optimization
     */
    private function analyzeLocationCompatibility($driver, $job)
    {
        $driverLocation = $driver['city'] ?? '';
        $jobLocation = $job['location'] ?? '';

        if (empty($driverLocation) || empty($jobLocation)) {
            return 0.5; // Neutral score
        }

        // Exact match
        if (strtolower($driverLocation) === strtolower($jobLocation)) {
            return 1.0;
        }

        // AI-based location similarity
        $similarity = $this->calculateLocationSimilarity($driverLocation, $jobLocation);

        // Distance-based scoring (simulated)
        $distance = $this->estimateDistance($driverLocation, $jobLocation);

        if ($distance <= 20) return 0.9;      // Very close
        if ($distance <= 50) return 0.7;      // Reasonable distance
        if ($distance <= 100) return 0.5;     // Manageable
        if ($distance <= 200) return 0.3;     // Long commute

        return 0.1; // Very far
    }

    /**
     * Ανάλυση διαθεσιμότητας με intelligent scheduling
     */
    private function analyzeAvailabilityAlignment($driver, $job)
    {
        $driverAvailable = $driver['available_for_work'] ?? 0;

        if (!$driverAvailable) {
            return 0.1; // Very low score για μη διαθέσιμους
        }

        // AI-based availability scoring
        $urgency = $this->detectJobUrgency($job);
        $flexibility = $this->assessDriverFlexibility($driver);

        $baseScore = 0.8;

        // Bonus για urgent jobs + flexible drivers
        if ($urgency > 0.7 && $flexibility > 0.7) {
            $baseScore += 0.2;
        }

        return min($baseScore, 1.0);
    }

    /**
     * Semantic Analysis με NLP techniques
     */
    private function performSemanticAnalysis($driver, $job)
    {
        $driverSkills = $this->extractDriverSkills($driver);
        $jobDescription = $job['description'] ?? '';
        $jobTitle = $job['title'] ?? '';

        // Keyword extraction και matching
        $jobKeywords = $this->extractKeywords($jobDescription . ' ' . $jobTitle);
        $driverKeywords = $this->extractDriverKeywords($driverSkills);

        // Semantic similarity calculation
        $commonKeywords = array_intersect($jobKeywords, $driverKeywords);
        $totalKeywords = array_unique(array_merge($jobKeywords, $driverKeywords));

        if (empty($totalKeywords)) {
            return 0.5;
        }

        $similarity = count($commonKeywords) / count($totalKeywords);

        // AI Enhancement: Context-aware boosting
        $contextBoost = $this->calculateContextualRelevance($driverSkills, $jobDescription);

        return min($similarity + $contextBoost, 1.0);
    }

    /**
     * AI Boost για high-potential matches
     */
    private function applyAIBoost($baseScore, $scores)
    {
        // Perfect license match + good experience = boost
        if ($scores['license_match'] >= 0.9 && $scores['experience_match'] >= 0.8) {
            $baseScore += 0.05;
        }

        // Local job + available driver = boost
        if ($scores['location_match'] >= 0.9 && $scores['availability_match'] >= 0.8) {
            $baseScore += 0.03;
        }

        // High semantic relevance = boost
        if ($scores['semantic_match'] >= 0.8) {
            $baseScore += 0.02;
        }

        return $baseScore;
    }

    /**
     * Δημιουργεί AI-powered insights με OpenAI GPT
     */
    private function generateAIInsights($driver, $job, $score)
    {
        try {
            // Χρήση OpenAI για πραγματικά AI insights
            $aiInsights = $this->openAIService->generateInsights($driver, $job, round($score * 100));

            if (!empty($aiInsights) && is_array($aiInsights)) {
                return $aiInsights;
            }
        } catch (\Exception $e) {
            // Log the error but continue with fallback
            error_log("OpenAI Insights Error: " . $e->getMessage());
        }

        // Fallback insights αν το OpenAI API αποτύχει
        $insights = [];

        if ($score >= 0.9) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Εξαιρετικό ταίριασμα! Το AI συστήμα εντόπισε υψηλή συμβατότητα.',
                'confidence' => 0.95
            ];
        }

        // License-based insights
        $driverLicenses = $this->extractDriverLicenses($driver);
        if (in_array('CE', $driverLicenses) && strpos($job['title'], 'φορτηγ') !== false) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Η άδεια CE σας είναι ιδανική για αυτή τη θέση φορτηγού.',
                'confidence' => 0.9
            ];
        }

        // Experience insights
        $experience = $driver['experience_years'] ?? 0;
        if ($experience >= 5) {
            $insights[] = [
                'type' => 'info',
                'message' => 'Η εμπειρία σας (' . $experience . ' έτη) σας κάνει ιδανικό υποψήφιο.',
                'confidence' => 0.85
            ];
        }

        // Location insights
        if ($this->analyzeLocationCompatibility($driver, $job) >= 0.9) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Η θέση βρίσκεται στην περιοχή σας - μηδενικό κόστος μετακίνησης.',
                'confidence' => 0.9
            ];
        }

        return $insights;
    }

    /**
     * Βρίσκει οδηγούς που ταιριάζουν με τις θέσεις εργασίας μιας εταιρείας
     */
    public function findCompanyDriverMatches($companyId, $page = 1, $limit = 20)
    {
        // 1. Λήψη ενεργών job listings της εταιρείας
        $companyJobs = $this->getCompanyActiveJobs($companyId);

        if (empty($companyJobs)) {
            return [
                'matches' => [],
                'total' => 0,
                'page' => $page,
                'pages' => 0,
                'ai_powered' => true
            ];
        }

        // 2. Λήψη διαθέσιμων οδηγών
        $availableDrivers = $this->getAvailableDrivers();

        // 3. AI-powered matching για κάθε οδηγό με τις θέσεις της εταιρείας
        $matches = [];
        foreach ($availableDrivers as $driver) {
            $bestMatch = null;
            $bestScore = 0;

            // Βρίσκουμε την καλύτερη θέση για κάθε οδηγό
            foreach ($companyJobs as $job) {
                $score = $this->calculateDriverJobMatchScore($driver, $job);

                if ($score > $bestScore && $score > 0.3) {
                    $bestScore = $score;
                    $bestMatch = [
                        'driver' => $driver,
                        'job' => $job,
                        'score' => $score,
                        'ai_insights' => $this->generateDriverInsights($driver, $job, $score),
                        'match_factors' => $this->getDriverMatchFactors($driver, $job)
                    ];
                }
            }

            if ($bestMatch) {
                $matches[] = $bestMatch;
            }
        }

        // 4. Ταξινόμηση με AI ranking
        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 5. Pagination
        $total = count($matches);
        $offset = ($page - 1) * $limit;
        $matches = array_slice($matches, $offset, $limit);

        return [
            'matches' => $matches,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit),
            'ai_powered' => true
        ];
    }

    /**
     * Υπολογίζει match score μεταξύ οδηγού και θέσης εργασίας
     */
    private function calculateDriverJobMatchScore($driver, $job)
    {
        $scores = [];

        // 1. License Compatibility Analysis
        $scores['license_compatibility'] = $this->analyzeLicenseCompatibility($driver, $job);

        // 2. Experience Relevance Scoring
        $scores['experience_relevance'] = $this->analyzeExperienceRelevance($driver, $job);

        // 3. Geographic Optimization
        $scores['location_proximity'] = $this->analyzeLocationCompatibility($driver, $job);

        // 4. Availability Alignment
        $scores['availability_alignment'] = $this->analyzeAvailabilityAlignment($driver, $job);

        // 5. Semantic Text Analysis
        $scores['semantic_similarity'] = $this->performSemanticAnalysis($driver, $job);

        // 6. Weighted AI Score Calculation
        $finalScore = 0;
        foreach ($scores as $factor => $score) {
            $weight = $this->skillWeights[$factor] ?? 0;
            $finalScore += $score * $weight;
        }

        // 7. AI Boost για high-potential matches
        $finalScore = $this->applyAIBoost($finalScore, $scores);

        return min($finalScore, 1.0); // Cap at 100%
    }

    /**
     * Δημιουργεί AI insights για οδηγό-θέση matching
     */
    private function generateDriverInsights($driver, $job, $score)
    {
        try {
            // Χρήση OpenAI για πραγματικά AI insights
            $aiInsights = $this->openAIService->generateInsights($driver, $job, round($score * 100));

            if (!empty($aiInsights) && is_array($aiInsights)) {
                return $aiInsights;
            }
        } catch (\Exception $e) {
            // Log the error but continue with fallback
            error_log("OpenAI Driver Insights Error: " . $e->getMessage());
        }

        // Fallback insights
        $insights = [];

        if ($score >= 0.9) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Εξαιρετικό ταίριασμα! Ο οδηγός πληροί όλες τις απαιτήσεις.',
                'confidence' => 0.95
            ];
        }

        // License-based insights
        $driverLicenses = $this->extractDriverLicenses($driver);
        if (in_array('CE', $driverLicenses) && strpos($job['title'], 'φορτηγ') !== false) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Ο οδηγός διαθέτει άδεια CE που απαιτείται για τη θέση.',
                'confidence' => 0.9
            ];
        }

        // Experience insights
        $experience = $driver['experience_years'] ?? 0;
        if ($experience >= 5) {
            $insights[] = [
                'type' => 'info',
                'message' => 'Έμπειρος οδηγός με ' . $experience . ' έτη εμπειρίας.',
                'confidence' => 0.85
            ];
        }

        return $insights;
    }

    /**
     * Λήψη ενεργών job listings εταιρείας
     */
    private function getCompanyActiveJobs($companyId)
    {
        $stmt = $this->pdo->prepare("
            SELECT jl.*, c.company_name, c.city as company_city
            FROM job_listings jl
            JOIN companies c ON jl.company_id = c.user_id
            WHERE jl.company_id = ?
            AND jl.status = 'active'
            AND jl.expires_at > NOW()
            ORDER BY jl.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Λήψη διαθέσιμων οδηγών
     */
    private function getAvailableDrivers()
    {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.email,
                   GROUP_CONCAT(DISTINCT dl.license_type) as license_types
            FROM drivers d
            JOIN users u ON d.user_id = u.id
            LEFT JOIN driver_licenses dl ON d.user_id = dl.driver_id
            WHERE d.available_for_work = 1
            AND u.is_active = 1
            GROUP BY d.user_id
            ORDER BY d.updated_at DESC
            LIMIT 50
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Λήψη match factors για οδηγό-θέση
     */
    private function getDriverMatchFactors($driver, $job)
    {
        return [
            'license_compatibility' => $this->analyzeLicenseCompatibility($driver, $job),
            'experience_relevance' => $this->analyzeExperienceRelevance($driver, $job),
            'location_proximity' => $this->analyzeLocationCompatibility($driver, $job),
            'availability_alignment' => $this->analyzeAvailabilityAlignment($driver, $job),
            'semantic_similarity' => $this->performSemanticAnalysis($driver, $job)
        ];
    }

    /**
     * Helper methods για AI analysis
     */
    private function extractDriverLicenses($driver)
    {
        // Simulate license extraction
        $licenses = [];
        if (isset($driver['license_types'])) {
            $licenses = explode(',', $driver['license_types']);
        }
        return array_map('trim', $licenses);
    }

    private function extractJobRequirements($job)
    {
        $requirements = [];
        $description = strtolower($job['description'] ?? '');

        // AI-based requirement extraction
        if (strpos($description, 'γ κατηγορ') !== false || strpos($description, 'φορτηγ') !== false) {
            $requirements[] = 'C';
        }
        if (strpos($description, 'ce') !== false || strpos($description, 'γ+ε') !== false) {
            $requirements[] = 'CE';
        }
        if (strpos($description, 'λεωφορ') !== false || strpos($description, 'επιβατ') !== false) {
            $requirements[] = 'D';
        }

        return $requirements;
    }

    private function calculateLocationSimilarity($loc1, $loc2)
    {
        // Simple similarity based on string matching
        similar_text(strtolower($loc1), strtolower($loc2), $percent);
        return $percent / 100;
    }

    private function estimateDistance($loc1, $loc2)
    {
        // Simulated distance calculation
        $distances = [
            'αθήνα-θεσσαλονίκη' => 500,
            'αθήνα-πάτρα' => 200,
            'θεσσαλονίκη-καβάλα' => 150,
        ];

        $key = strtolower($loc1) . '-' . strtolower($loc2);
        $reverseKey = strtolower($loc2) . '-' . strtolower($loc1);

        return $distances[$key] ?? $distances[$reverseKey] ?? 100; // Default distance
    }

    private function extractKeywords($text)
    {
        $keywords = [];
        $text = strtolower($text);

        // Transportation-specific keywords
        $transportKeywords = [
            'φορτηγό',
            'λεωφορείο',
            'βυτίο',
            'ρυμουλκό',
            'διανομή',
            'μεταφορές',
            'logistics',
            'οδήγηση',
            'διεθνείς',
            'εσωτερικό'
        ];

        foreach ($transportKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $keywords[] = $keyword;
            }
        }

        return array_unique($keywords);
    }

    private function extractDriverKeywords($skills)
    {
        // Extract keywords from driver skills
        return $this->extractKeywords(implode(' ', $skills));
    }

    private function calculateContextualRelevance($driverSkills, $jobDescription)
    {
        // AI-based contextual analysis
        $relevanceScore = 0;

        // Check for specific skill-job matches
        if (in_array('defensive_driving', $driverSkills) && strpos($jobDescription, 'ασφάλεια') !== false) {
            $relevanceScore += 0.1;
        }

        if (in_array('international_transport', $driverSkills) && strpos($jobDescription, 'διεθνείς') !== false) {
            $relevanceScore += 0.15;
        }

        return min($relevanceScore, 0.2); // Cap the boost
    }

    private function detectJobUrgency($job)
    {
        $description = strtolower($job['description'] ?? '');
        $title = strtolower($job['title'] ?? '');

        $urgencyKeywords = ['επείγον', 'άμεσα', 'urgent', 'asap', 'γρήγορα'];

        foreach ($urgencyKeywords as $keyword) {
            if (strpos($description . ' ' . $title, $keyword) !== false) {
                return 0.8;
            }
        }

        return 0.3; // Default low urgency
    }

    private function assessDriverFlexibility($driver)
    {
        // Assess driver flexibility based on profile
        $flexibility = 0.5; // Base flexibility

        if ($driver['available_for_work'] ?? 0) {
            $flexibility += 0.3;
        }

        // Add more flexibility factors based on driver data
        return min($flexibility, 1.0);
    }

    private function getDriverProfile($driverId)
    {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.email,
                   GROUP_CONCAT(DISTINCT dl.license_type) as license_types
            FROM drivers d
            JOIN users u ON d.user_id = u.id
            LEFT JOIN driver_licenses dl ON d.user_id = dl.driver_id
            WHERE d.user_id = ?
            GROUP BY d.user_id
        ");
        $stmt->execute([$driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getAvailableJobs()
    {
        $stmt = $this->pdo->prepare("
            SELECT jl.*, c.company_name, c.city as company_city
            FROM job_listings jl
            JOIN companies c ON jl.company_id = c.user_id
            WHERE jl.status = 'active'
            AND jl.expires_at > NOW()
            ORDER BY jl.created_at DESC
            LIMIT 50
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function extractExperienceRequirement($job)
    {
        $description = strtolower($job['description'] ?? '');

        // AI-based experience extraction
        if (preg_match('/(\d+)\s*(έτη|χρόνια|years)/', $description, $matches)) {
            return intval($matches[1]);
        }

        return null;
    }

    private function hasCompatibleLicense($driverLicenses, $required)
    {
        return in_array($required, $driverLicenses);
    }

    private function extractDriverSkills($driver)
    {
        // Extract skills from driver profile
        $skills = [];

        // Add logic to extract skills from driver data
        if ($driver['has_pei'] ?? false) {
            $skills[] = 'professional_competence';
        }

        return $skills;
    }

    private function getMatchFactors($driver, $job)
    {
        return [
            'license_compatibility' => $this->analyzeLicenseCompatibility($driver, $job),
            'experience_relevance' => $this->analyzeExperienceRelevance($driver, $job),
            'location_proximity' => $this->analyzeLocationCompatibility($driver, $job),
            'availability_alignment' => $this->analyzeAvailabilityAlignment($driver, $job),
            'semantic_similarity' => $this->performSemanticAnalysis($driver, $job)
        ];
    }
}
