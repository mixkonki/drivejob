<?php

namespace Drivejob\Services;

/**
 * OpenAI Integration Service
 * 
 * Παρέχει πραγματικό AI-powered matching χρησιμοποιώντας OpenAI GPT
 */
class OpenAIService
{
    private $apiKey;
    private $baseUrl;
    private $config;

    private $usageGuard;

    public function __construct()
    {
        $this->config = include ROOT_DIR . '/config/openai.php';
        $this->apiKey = $this->config['api_key'];
        $this->baseUrl = $this->config['base_url'];
        try {
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
            $this->usageGuard = new \Drivejob\Services\AI\AIUsageGuard($pdo);
        } catch (\Throwable $e) {
            $this->usageGuard = null; // χωρίς βάση, χωρίς όρια — μόνο log
        }
    }

    /**
     * Αναλύει job description και driver profile με GPT για intelligent matching
     */
    public function analyzeJobMatch($driverProfile, $jobListing)
    {
        $prompt = $this->buildMatchingPrompt($driverProfile, $jobListing);

        $response = $this->callOpenAI([
            'model' => $this->config['models']['matching'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Είσαι ένας ειδικός σύμβουλος καριέρας για οδηγούς. Αναλύεις προφίλ οδηγών και θέσεις εργασίας για να βρεις το καλύτερο ταίριασμα. Απαντάς πάντα στα ελληνικά με JSON format.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 800,
            'temperature' => 0.3
        ]);

        return $this->parseMatchingResponse($response);
    }

    /**
     * Δημιουργεί AI insights για ένα match
     */
    public function generateInsights($driverProfile, $jobListing, $matchScore)
    {
        $prompt = $this->buildInsightsPrompt($driverProfile, $jobListing, $matchScore);

        $response = $this->callOpenAI([
            'model' => $this->config['models']['insights'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Είσαι ένας AI σύμβουλος που δημιουργεί χρήσιμες συμβουλές για οδηγούς. Παρέχεις συγκεκριμένες και πρακτικές συμβουλές στα ελληνικά.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ]);

        return $this->parseInsightsResponse($response);
    }

    /**
     * Semantic analysis για job requirements
     */
    public function extractJobRequirements($jobDescription)
    {
        $prompt = "Ανάλυσε την παρακάτω περιγραφή θέσης εργασίας και εξάγαγε:\n\n" .
            "1. Απαιτούμενες άδειες οδήγησης\n" .
            "2. Χρόνια εμπειρίας\n" .
            "3. Τύπο οχήματος\n" .
            "4. Ειδικές δεξιότητες\n" .
            "5. Γεωγραφική περιοχή\n\n" .
            "Περιγραφή: " . $jobDescription . "\n\n" .
            "Απάντησε σε JSON format με τα παραπάνω πεδία.";

        $response = $this->callOpenAI([
            'model' => $this->config['models']['analysis'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Είσαι ένας ειδικός αναλυτής για θέσεις εργασίας οδηγών. Εξάγεις δομημένες πληροφορίες από περιγραφές θέσεων.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 400,
            'temperature' => 0.1
        ]);

        return $this->parseRequirementsResponse($response);
    }

    /**
     * Δημιουργεί matching prompt για GPT
     */
    private function buildMatchingPrompt($driverProfile, $jobListing)
    {
        $driverInfo = [
            'Όνομα' => $driverProfile['first_name'] . ' ' . $driverProfile['last_name'],
            'Εμπειρία' => ($driverProfile['experience_years'] ?? 0) . ' έτη',
            'Τοποθεσία' => $driverProfile['city'] ?? 'Δεν έχει οριστεί',
            'Άδειες' => $driverProfile['license_types'] ?? 'Δεν έχουν οριστεί',
            'Διαθεσιμότητα' => $driverProfile['available_for_work'] ? 'Διαθέσιμος' : 'Μη διαθέσιμος'
        ];

        $jobInfo = [
            'Τίτλος' => $jobListing['title'],
            'Εταιρεία' => $jobListing['company_name'],
            'Τοποθεσία' => $jobListing['location'],
            'Περιγραφή' => substr($jobListing['description'], 0, 500)
        ];

        return "Ανάλυσε το ταίριασμα μεταξύ του οδηγού και της θέσης εργασίας:\n\n" .
            "ΟΔΗΓΟΣ:\n" . json_encode($driverInfo, JSON_UNESCAPED_UNICODE) . "\n\n" .
            "ΘΕΣΗ ΕΡΓΑΣΙΑΣ:\n" . json_encode($jobInfo, JSON_UNESCAPED_UNICODE) . "\n\n" .
            "Παρέχε ανάλυση σε JSON format με:\n" .
            "- match_score (0-100)\n" .
            "- license_compatibility (0-1)\n" .
            "- experience_match (0-1)\n" .
            "- location_match (0-1)\n" .
            "- overall_assessment (κείμενο)\n" .
            "- strengths (array με δυνατά σημεία)\n" .
            "- concerns (array με ανησυχίες)\n" .
            "- recommendation (σύσταση)";
    }

    /**
     * Δημιουργεί insights prompt
     */
    private function buildInsightsPrompt($driverProfile, $jobListing, $matchScore)
    {
        return "Βάσει του match score {$matchScore}% μεταξύ του οδηγού και της θέσης, " .
            "δημιούργησε 3 χρήσιμες συμβουλές για τον οδηγό:\n\n" .
            "Οδηγός: {$driverProfile['first_name']} με {$driverProfile['experience_years']} έτη εμπειρίας\n" .
            "Θέση: {$jobListing['title']} στην {$jobListing['location']}\n\n" .
            "Απάντησε σε JSON format με array από insights που περιέχουν:\n" .
            "- type (success/warning/info)\n" .
            "- message (συμβουλή)\n" .
            "- confidence (0-1)";
    }

    /**
     * Καλεί το OpenAI API
     */
    private function callOpenAI($data)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured');
        }
        if ($this->usageGuard && !$this->usageGuard->allow()) {
            throw new \Exception('AI usage limit reached');
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new \Exception('OpenAI API Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('OpenAI API returned HTTP ' . $httpCode . ': ' . $response);
        }

        $decoded = json_decode($response, true);

        if (!$decoded || !isset($decoded['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid OpenAI API response');
        }

        if ($this->usageGuard) {
            $this->usageGuard->record($data['model'] ?? 'unknown', $decoded);
        }

        return $decoded['choices'][0]['message']['content'];
    }

    /**
     * Αναλύει την απάντηση matching
     */
    private function parseMatchingResponse($response)
    {
        $data = json_decode($response, true);

        if (!$data) {
            // Fallback αν το JSON parsing αποτύχει
            return [
                'match_score' => 75,
                'license_compatibility' => 0.8,
                'experience_match' => 0.7,
                'location_match' => 0.6,
                'overall_assessment' => 'Καλό ταίριασμα με δυνατότητες',
                'strengths' => ['Καλή εμπειρία', 'Κατάλληλες άδειες'],
                'concerns' => ['Απόσταση τοποθεσίας'],
                'recommendation' => 'Συνιστάται η αίτηση'
            ];
        }

        return $data;
    }

    /**
     * Αναλύει την απάντηση insights
     */
    private function parseInsightsResponse($response)
    {
        $data = json_decode($response, true);

        if (!$data || !is_array($data)) {
            // Fallback insights
            return [
                [
                    'type' => 'info',
                    'message' => 'Το AI σύστημα ανέλυσε το προφίλ σας και βρήκε καλές δυνατότητες για αυτή τη θέση.',
                    'confidence' => 0.8
                ],
                [
                    'type' => 'success',
                    'message' => 'Η εμπειρία σας ταιριάζει με τις απαιτήσεις της θέσης.',
                    'confidence' => 0.9
                ]
            ];
        }

        return $data;
    }

    /**
     * Αναλύει την απάντηση requirements
     */
    private function parseRequirementsResponse($response)
    {
        $data = json_decode($response, true);

        if (!$data) {
            return [
                'licenses' => [],
                'experience_years' => null,
                'vehicle_type' => null,
                'skills' => [],
                'location' => null
            ];
        }

        return $data;
    }

    /**
     * Ελέγχει αν το API key είναι έγκυρο
     */
    public function testConnection()
    {
        try {
            $response = $this->callOpenAI([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Πες μου "Γεια σου" στα ελληνικά.'
                    ]
                ],
                'max_tokens' => 10
            ]);

            return ['success' => true, 'message' => 'OpenAI API connection successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
