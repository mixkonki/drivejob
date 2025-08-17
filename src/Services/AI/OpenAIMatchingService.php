<?php

namespace Drivejob\Services\AI;

use PDO;
use Exception;
use Drivejob\Core\Logger;
use Drivejob\Core\Database;
use Drivejob\Helpers\JsonHelper;

/**
 * OpenAI-Powered Advanced Matching Service
 * Χρησιμοποιεί ChatGPT-5 (o1-preview) για προηγμένο matching
 */
class OpenAIMatchingService
{
    private PDO $pdo;
    private array $config;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->config = require __DIR__ . '/../../../config/openai.php';
        $this->apiKey = $this->config['api_key'];
        $this->baseUrl = $this->config['base_url'];
    }

    /**
     * Υπολογίζει advanced matching score με ChatGPT-5
     */
    public function calculateAdvancedMatchScore(int $driverId, int $jobId): array
    {
        try {
            // Λήψη δεδομένων οδηγού
            $driverData = $this->getDriverData($driverId);
            if (!$driverData) {
                throw new Exception("Driver not found: {$driverId}");
            }

            // Λήψη δεδομένων εργασίας
            $jobData = $this->getJobData($jobId);
            if (!$jobData) {
                throw new Exception("Job not found: {$jobId}");
            }

            // Δημιουργία prompt για ChatGPT-5
            $prompt = $this->buildMatchingPrompt($driverData, $jobData);

            // Κλήση ChatGPT-5 o1-preview
            $response = $this->callOpenAI($prompt, 'o1-preview');

            // Επεξεργασία απάντησης
            $result = $this->parseMatchingResponse($response);

            // Αποθήκευση στη βάση
            $this->storeAIMatchingResult($driverId, $jobId, $result);

            return $result;
        } catch (Exception $e) {
            Logger::error("OpenAI Matching Error: " . $e->getMessage());
            return $this->getFallbackScore($driverId, $jobId);
        }
    }

    /**
     * Δημιουργεί προηγμένες AI insights για οδηγό
     */
    public function generateDriverInsights(int $driverId): array
    {
        try {
            $driverData = $this->getDriverData($driverId);
            $recentMatches = $this->getRecentMatches($driverId);

            $prompt = $this->buildInsightsPrompt($driverData, $recentMatches);
            $response = $this->callOpenAI($prompt, 'o1-mini');

            return $this->parseInsightsResponse($response);
        } catch (Exception $e) {
            Logger::error("OpenAI Insights Error: " . $e->getMessage());
            return ['insights' => [], 'recommendations' => []];
        }
    }

    /**
     * Αναλύει job description με AI
     */
    public function analyzeJobDescription(string $description, array $jobData): array
    {
        try {
            $prompt = $this->buildAnalysisPrompt($description, $jobData);
            $response = $this->callOpenAI($prompt, 'gpt-4o');

            return $this->parseAnalysisResponse($response);
        } catch (Exception $e) {
            Logger::error("OpenAI Analysis Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Δημιουργεί matching prompt για ChatGPT-5
     */
    private function buildMatchingPrompt(array $driverData, array $jobData): string
    {
        $systemPrompt = $this->config['prompts']['matching_system'];

        return $systemPrompt . "\n\n" .
            "ΠΡΟΦΙΛ ΟΔΗΓΟΥ:\n" .
            "- Όνομα: {$driverData['first_name']} {$driverData['last_name']}\n" .
            "- Τοποθεσία: {$driverData['city']}, {$driverData['country']}\n" .
            "- Εμπειρία: {$driverData['years_experience']} έτη\n" .
            "- Άδειες: " . implode(', ', $driverData['licenses'] ?? []) . "\n" .
            "- Πιστοποιήσεις: " . implode(', ', $driverData['certifications'] ?? []) . "\n" .
            "- Διαθεσιμότητα: {$driverData['availability']}\n" .
            "- Προτιμώμενος μισθός: {$driverData['min_salary']}-{$driverData['max_salary']} €\n\n" .

            "ΘΕΣΗ ΕΡΓΑΣΙΑΣ:\n" .
            "- Τίτλος: {$jobData['title']}\n" .
            "- Εταιρία: {$jobData['company_name']}\n" .
            "- Τοποθεσία: {$jobData['location']}\n" .
            "- Τύπος οχήματος: {$jobData['vehicle_type']}\n" .
            "- Μισθός: {$jobData['salary_min']}-{$jobData['salary_max']} €\n" .
            "- Περιγραφή: {$jobData['description']}\n" .
            "- Απαιτήσεις: {$jobData['requirements']}\n\n" .

            "ΑΝΑΛΥΣΗ:\n" .
            "Χρησιμοποιώντας προηγμένη λογική, αναλύεις όλους τους παράγοντες και υπολογίζεις:\n" .
            "1. Overall Match Score (0-100%)\n" .
            "2. Location Compatibility (0-100%)\n" .
            "3. Experience Match (0-100%)\n" .
            "4. License Compatibility (0-100%)\n" .
            "5. Salary Alignment (0-100%)\n" .
            "6. Schedule Compatibility (0-100%)\n" .
            "7. Growth Potential (0-100%)\n" .
            "8. Risk Assessment (Low/Medium/High)\n" .
            "9. Recommendation Strength (Weak/Moderate/Strong/Excellent)\n" .
            "10. Key Insights (3-5 bullet points)\n" .
            "11. Improvement Suggestions (2-3 suggestions)\n\n" .

            "Απάντησε σε JSON format με όλα τα παραπάνω πεδία.";
    }

    /**
     * Δημιουργεί insights prompt
     */
    private function buildInsightsPrompt(array $driverData, array $recentMatches): string
    {
        $systemPrompt = $this->config['prompts']['insights_system'];

        $matchesText = '';
        foreach ($recentMatches as $match) {
            $matchesText .= "- {$match['title']} ({$match['company_name']}) - Score: {$match['overall_score']}%\n";
        }

        return $systemPrompt . "\n\n" .
            "ΠΡΟΦΙΛ ΟΔΗΓΟΥ:\n" .
            "- Όνομα: {$driverData['first_name']} {$driverData['last_name']}\n" .
            "- Εμπειρία: {$driverData['years_experience']} έτη\n" .
            "- Τοποθεσία: {$driverData['city']}\n" .
            "- Άδειες: " . implode(', ', $driverData['licenses'] ?? []) . "\n\n" .

            "ΠΡΌΣΦΑΤΑ ΤΑΙΡΙΆΣΜΑΤΑ:\n" .
            $matchesText . "\n" .

            "Δημιούργησε:\n" .
            "1. Career Insights (5 key insights)\n" .
            "2. Market Analysis (τάσεις αγοράς)\n" .
            "3. Skill Recommendations (δεξιότητες προς ανάπτυξη)\n" .
            "4. Salary Optimization (συμβουλές μισθού)\n" .
            "5. Location Strategy (στρατηγική τοποθεσίας)\n" .
            "6. Next Steps (συγκεκριμένες ενέργειες)\n\n" .

            "Απάντησε σε JSON format.";
    }

    /**
     * Δημιουργεί analysis prompt
     */
    private function buildAnalysisPrompt(string $description, array $jobData): string
    {
        $systemPrompt = $this->config['prompts']['analysis_system'];

        return $systemPrompt . "\n\n" .
            "ΠΕΡΙΓΡΑΦΗ ΘΕΣΗΣ:\n" .
            $description . "\n\n" .

            "ΥΠΑΡΧΟΝΤΑ ΔΕΔΟΜΕΝΑ:\n" .
            JsonHelper::encode($jobData) . "\n\n" .

            "Εξάγεις:\n" .
            "1. Required Skills (λίστα δεξιοτήτων)\n" .
            "2. Experience Level (Junior/Mid/Senior)\n" .
            "3. Vehicle Types (τύποι οχημάτων)\n" .
            "4. Work Schedule (ωράριο εργασίας)\n" .
            "5. Travel Requirements (απαιτήσεις ταξιδιού)\n" .
            "6. Physical Demands (φυσικές απαιτήσεις)\n" .
            "7. Company Culture (κουλτούρα εταιρίας)\n" .
            "8. Growth Opportunities (ευκαιρίες ανάπτυξης)\n\n" .

            "Απάντησε σε JSON format.";
    }

    /**
     * Κλήση OpenAI API
     */
    private function callOpenAI(string $prompt, string $model): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $this->config['available_models'][$model]['max_tokens'] ?? 2000,
            'temperature' => 0.3 // Lower temperature for more consistent results
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, JsonHelper::encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("OpenAI API Error: HTTP {$httpCode}");
        }

        $decoded = JsonHelper::decode($response);
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new Exception("Invalid OpenAI response format");
        }

        return $decoded;
    }

    /**
     * Επεξεργασία matching response
     */
    private function parseMatchingResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'];

        // Try to extract JSON from response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonData = JsonHelper::decode($matches[0]);
            if ($jsonData) {
                return [
                    'overall_score' => $jsonData['overall_score'] ?? 0,
                    'location_score' => $jsonData['location_compatibility'] ?? 0,
                    'experience_score' => $jsonData['experience_match'] ?? 0,
                    'license_score' => $jsonData['license_compatibility'] ?? 0,
                    'salary_score' => $jsonData['salary_alignment'] ?? 0,
                    'schedule_score' => $jsonData['schedule_compatibility'] ?? 0,
                    'growth_potential' => $jsonData['growth_potential'] ?? 0,
                    'risk_assessment' => $jsonData['risk_assessment'] ?? 'Medium',
                    'recommendation' => $jsonData['recommendation_strength'] ?? 'Moderate',
                    'insights' => $jsonData['key_insights'] ?? [],
                    'suggestions' => $jsonData['improvement_suggestions'] ?? [],
                    'ai_analysis' => $content
                ];
            }
        }

        // Fallback parsing
        return [
            'overall_score' => 50,
            'ai_analysis' => $content,
            'insights' => ['AI analysis completed'],
            'suggestions' => ['Review match details']
        ];
    }

    /**
     * Επεξεργασία insights response
     */
    private function parseInsightsResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'];

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonData = JsonHelper::decode($matches[0]);
            if ($jsonData) {
                return $jsonData;
            }
        }

        return [
            'career_insights' => ['AI insights generated'],
            'market_analysis' => 'Market analysis completed',
            'recommendations' => ['Continue professional development']
        ];
    }

    /**
     * Επεξεργασία analysis response
     */
    private function parseAnalysisResponse(array $response): array
    {
        $content = $response['choices'][0]['message']['content'];

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonData = JsonHelper::decode($matches[0]);
            if ($jsonData) {
                return $jsonData;
            }
        }

        return ['analysis' => $content];
    }

    /**
     * Λήψη δεδομένων οδηγού
     */
    private function getDriverData(int $driverId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT d.*, 
                   GROUP_CONCAT(DISTINCT dl.license_type) as licenses,
                   GROUP_CONCAT(DISTINCT dc.certification_name) as certifications
            FROM drivers d
            LEFT JOIN driver_licenses dl ON d.id = dl.driver_id
            LEFT JOIN driver_certifications dc ON d.id = dc.driver_id
            WHERE d.id = ? AND d.is_active = 1
            GROUP BY d.id
        ");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driver) {
            $driver['licenses'] = $driver['licenses'] ? explode(',', $driver['licenses']) : [];
            $driver['certifications'] = $driver['certifications'] ? explode(',', $driver['certifications']) : [];
        }

        return $driver ?: null;
    }

    /**
     * Λήψη δεδομένων εργασίας
     */
    private function getJobData(int $jobId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT j.*, c.company_name, c.description as company_description
            FROM job_listings j
            JOIN companies c ON j.company_id = c.id
            WHERE j.id = ? AND j.is_active = 1
        ");
        $stmt->execute([$jobId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Λήψη πρόσφατων ταιριασμάτων
     */
    private function getRecentMatches(int $driverId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT j.title, c.company_name, ms.overall_score
            FROM matching_scores ms
            JOIN job_listings j ON ms.job_id = j.id
            JOIN companies c ON j.company_id = c.id
            WHERE ms.driver_id = ?
            ORDER BY ms.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$driverId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Αποθήκευση AI matching result
     */
    private function storeAIMatchingResult(int $driverId, int $jobId, array $result): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ai_matching_results 
                (driver_id, job_id, overall_score, detailed_scores, ai_insights, ai_suggestions, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    overall_score = VALUES(overall_score),
                    detailed_scores = VALUES(detailed_scores),
                    ai_insights = VALUES(ai_insights),
                    ai_suggestions = VALUES(ai_suggestions),
                    updated_at = NOW()
            ");

            $stmt->execute([
                $driverId,
                $jobId,
                $result['overall_score'],
                JsonHelper::encode($result),
                JsonHelper::encode($result['insights'] ?? []),
                JsonHelper::encode($result['suggestions'] ?? [])
            ]);
        } catch (Exception $e) {
            Logger::error('Error storing AI matching result: ' . $e->getMessage());
        }
    }

    /**
     * Fallback score όταν το AI αποτύχει
     */
    private function getFallbackScore(int $driverId, int $jobId): array
    {
        return [
            'overall_score' => 50,
            'location_score' => 50,
            'experience_score' => 50,
            'license_score' => 50,
            'salary_score' => 50,
            'schedule_score' => 50,
            'growth_potential' => 50,
            'risk_assessment' => 'Medium',
            'recommendation' => 'Moderate',
            'insights' => ['Fallback analysis - AI temporarily unavailable'],
            'suggestions' => ['Review match manually']
        ];
    }
}
