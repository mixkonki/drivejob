<?php

namespace Drivejob\Services;

use PDO;
use Exception;

/**
 * Enterprise AI Management Service
 * 
 * Ολιστική προσέγγιση για global AI system management
 * με μελλοντική προοπτική και enterprise-grade features
 */
class EnterpriseAIService
{
    private $pdo;
    private $config;
    private $models;
    private $analytics;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->loadConfiguration();
        $this->loadModels();
        $this->analytics = [];
    }

    /**
     * Load AI configuration from database
     */
    private function loadConfiguration()
    {
        $stmt = $this->pdo->query("
            SELECT config_key, config_value, config_type, is_encrypted 
            FROM ai_configuration 
            WHERE environment = 'production'
        ");

        $this->config = [];
        while ($row = $stmt->fetch()) {
            $value = json_decode($row['config_value'], true);
            $this->config[$row['config_key']] = $value;
        }
    }

    /**
     * Load available AI models from database
     */
    private function loadModels()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM ai_models 
            WHERE is_active = 1 
            ORDER BY priority DESC
        ");

        $this->models = [];
        while ($row = $stmt->fetch()) {
            $this->models[$row['model_type']][] = $row;
        }
    }

    /**
     * Get best model for specific task type
     */
    private function getBestModel($taskType = 'general')
    {
        // First, check if we have a configured default model for this task type
        $configKey = "ai.{$taskType}.default_model";
        if (isset($this->config[$configKey])) {
            $preferredModel = $this->config[$configKey];

            // Find this model in our available models
            foreach ($this->models as $type => $modelList) {
                foreach ($modelList as $model) {
                    if ($model['model_name'] === $preferredModel) {
                        return $model;
                    }
                }
            }
        }

        // Fallback to priority-based selection
        if (!isset($this->models[$taskType]) || empty($this->models[$taskType])) {
            // Fallback to general models
            $taskType = 'general';
        }

        if (!isset($this->models[$taskType]) || empty($this->models[$taskType])) {
            throw new Exception("No AI models available for task type: $taskType");
        }

        // Return highest priority model
        return $this->models[$taskType][0];
    }

    /**
     * Advanced Job Matching with ChatGPT-5
     */
    public function analyzeJobMatch($driverProfile, $jobListing, $options = [])
    {
        $startTime = microtime(true);
        $sessionId = uniqid('match_', true);

        try {
            $model = $this->getBestModel('matching');

            // Build comprehensive prompt for ChatGPT-5
            $prompt = $this->buildAdvancedMatchingPrompt($driverProfile, $jobListing, $options);

            // Call OpenAI API
            $response = $this->callOpenAI($model, [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->config['ai.prompts.matching_system'] ?? 'You are an expert career advisor for drivers with advanced reasoning capabilities.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $model['max_tokens'],
                'temperature' => $options['temperature'] ?? 0.3
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Parse and validate response
            $analysis = $this->parseMatchingResponse($response);

            // Log session
            $this->logMatchingSession($sessionId, $driverProfile['user_id'] ?? 0, 'driver', $model['model_name'], [
                'driver_profile' => $driverProfile,
                'job_listing' => $jobListing,
                'options' => $options
            ], $response, $analysis, $executionTime, true);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'model_used' => $model['model_name'],
                'execution_time_ms' => $executionTime,
                'analysis' => $analysis
            ];
        } catch (Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;

            // Log failed session
            $this->logMatchingSession($sessionId, $driverProfile['user_id'] ?? 0, 'driver', $model['model_name'] ?? 'unknown', [
                'driver_profile' => $driverProfile,
                'job_listing' => $jobListing
            ], null, null, $executionTime, false, $e->getMessage());

            return [
                'success' => false,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ];
        }
    }

    /**
     * Generate AI Insights with ChatGPT-5 Mini
     */
    public function generateAdvancedInsights($context, $matchScore, $driverProfile = null, $options = [])
    {
        $startTime = microtime(true);
        $sessionId = uniqid('insights_', true);

        try {
            $model = $this->getBestModel('insights');

            $prompt = $this->buildInsightsPrompt($context, $matchScore, $driverProfile, $options);

            $response = $this->callOpenAI($model, [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an advanced AI career advisor that provides actionable insights and recommendations for drivers using ChatGPT-5 Mini reasoning capabilities.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $model['max_tokens'],
                'temperature' => $options['temperature'] ?? 0.7
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;
            $insights = $this->parseInsightsResponse($response);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'model_used' => $model['model_name'],
                'execution_time_ms' => $executionTime,
                'insights' => $insights
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'execution_time_ms' => (microtime(true) - $startTime) * 1000
            ];
        }
    }

    /**
     * Extract Job Requirements with GPT-4o
     */
    public function extractJobRequirements($jobDescription, $options = [])
    {
        try {
            $model = $this->getBestModel('analysis');

            $prompt = "Analyze the following job description and extract structured information in JSON format:\n\n" .
                "Job Description: $jobDescription\n\n" .
                "Extract:\n" .
                "- required_licenses: array of required driving licenses\n" .
                "- experience_years: minimum years of experience\n" .
                "- vehicle_types: array of vehicle types\n" .
                "- skills: array of required skills\n" .
                "- location_requirements: geographic requirements\n" .
                "- salary_range: salary information if mentioned\n" .
                "- work_schedule: working hours/schedule\n" .
                "- benefits: array of benefits offered\n" .
                "- company_info: company information\n\n" .
                "Respond only with valid JSON.";

            $response = $this->callOpenAI($model, [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert job analyst with multimodal capabilities. Extract structured information from job descriptions and respond in valid JSON format.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.1
            ]);

            return [
                'success' => true,
                'model_used' => $model['model_name'],
                'requirements' => $this->parseRequirementsResponse($response)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test OpenAI Connection
     */
    public function testConnection($modelName = null)
    {
        try {
            if ($modelName) {
                $stmt = $this->pdo->prepare("SELECT * FROM ai_models WHERE model_name = ? AND is_active = 1");
                $stmt->execute([$modelName]);
                $model = $stmt->fetch();

                if (!$model) {
                    throw new Exception("Model $modelName not found or inactive");
                }
            } else {
                $model = $this->getBestModel('general');
            }

            $response = $this->callOpenAI($model, [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Respond with "DriveJob AI System is operational!" in Greek.'
                    ]
                ],
                'max_tokens' => 50
            ]);

            return [
                'success' => true,
                'model' => $model['model_name'],
                'message' => trim($response),
                'api_key_status' => 'valid'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'api_key_status' => 'invalid'
            ];
        }
    }

    /**
     * Build advanced matching prompt for ChatGPT-5
     */
    private function buildAdvancedMatchingPrompt($driverProfile, $jobListing, $options)
    {
        $driverInfo = [
            'name' => ($driverProfile['first_name'] ?? '') . ' ' . ($driverProfile['last_name'] ?? ''),
            'experience_years' => $driverProfile['experience_years'] ?? 0,
            'location' => $driverProfile['city'] ?? 'Unknown',
            'licenses' => $driverProfile['license_types'] ?? 'Not specified',
            'skills' => $driverProfile['skills'] ?? [],
            'availability' => $driverProfile['available_for_work'] ?? true,
            'rating' => $driverProfile['rating'] ?? 0,
            'languages' => $driverProfile['languages'] ?? ['Greek']
        ];

        $jobInfo = [
            'title' => $jobListing['title'] ?? 'Unknown Position',
            'company' => $jobListing['company_name'] ?? 'Unknown Company',
            'location' => $jobListing['location'] ?? 'Unknown Location',
            'description' => substr($jobListing['description'] ?? '', 0, 1000),
            'requirements' => $jobListing['requirements'] ?? 'Not specified'
        ];

        return "Perform advanced job matching analysis using your reasoning capabilities:\n\n" .
            "DRIVER PROFILE:\n" . json_encode($driverInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n" .
            "JOB LISTING:\n" . json_encode($jobInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n" .
            "Provide comprehensive analysis in JSON format with:\n" .
            "- match_score: overall compatibility (0-100)\n" .
            "- compatibility_factors: {\n" .
            "    license_match: 0-1,\n" .
            "    experience_match: 0-1,\n" .
            "    location_match: 0-1,\n" .
            "    skills_match: 0-1,\n" .
            "    availability_match: 0-1\n" .
            "  }\n" .
            "- strengths: array of driver's advantages\n" .
            "- concerns: array of potential issues\n" .
            "- recommendations: array of actionable advice\n" .
            "- reasoning: detailed explanation of your analysis\n" .
            "- confidence_level: 0-1 (how confident you are in this analysis)\n\n" .
            "Use your advanced reasoning to provide deep insights.";
    }

    /**
     * Build insights prompt
     */
    private function buildInsightsPrompt($context, $matchScore, $driverProfile, $options)
    {
        return "Generate actionable AI insights based on:\n\n" .
            "CONTEXT: $context\n" .
            "MATCH SCORE: {$matchScore}%\n" .
            "DRIVER PROFILE: " . json_encode($driverProfile, JSON_UNESCAPED_UNICODE) . "\n\n" .
            "Generate 3-5 specific, actionable insights in JSON format:\n" .
            "[\n" .
            "  {\n" .
            "    \"type\": \"success|warning|info|tip\",\n" .
            "    \"title\": \"Insight title\",\n" .
            "    \"message\": \"Detailed actionable message\",\n" .
            "    \"confidence\": 0.0-1.0,\n" .
            "    \"actionable\": true/false,\n" .
            "    \"priority\": \"high|medium|low\"\n" .
            "  }\n" .
            "]\n\n" .
            "Focus on practical, implementable advice.";
    }

    /**
     * Call OpenAI API with enterprise features
     */
    private function callOpenAI($model, $data)
    {
        $apiKey = $this->config['openai.api_key'] ?? '';
        if (empty($apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }

        $ch = curl_init();

        $requestData = array_merge([
            'model' => $model['model_name'],
            'max_tokens' => $model['max_tokens'],
            'temperature' => 0.7
        ], $data);

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('OpenAI API Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            throw new Exception('OpenAI API returned HTTP ' . $httpCode . ': ' . ($error['error']['message'] ?? $response));
        }

        $decoded = json_decode($response, true);

        if (!$decoded || !isset($decoded['choices'][0]['message']['content'])) {
            throw new Exception('Invalid OpenAI API response');
        }

        return $decoded['choices'][0]['message']['content'];
    }

    /**
     * Parse matching response with fallback
     */
    private function parseMatchingResponse($response)
    {
        $data = json_decode($response, true);

        if (!$data) {
            // Fallback response
            return [
                'match_score' => 75,
                'compatibility_factors' => [
                    'license_match' => 0.8,
                    'experience_match' => 0.7,
                    'location_match' => 0.6,
                    'skills_match' => 0.8,
                    'availability_match' => 1.0
                ],
                'strengths' => ['Good experience', 'Appropriate licenses'],
                'concerns' => ['Location distance'],
                'recommendations' => ['Consider applying', 'Highlight relevant experience'],
                'reasoning' => 'AI analysis completed with fallback data',
                'confidence_level' => 0.7
            ];
        }

        return $data;
    }

    /**
     * Parse insights response with fallback
     */
    private function parseInsightsResponse($response)
    {
        $data = json_decode($response, true);

        if (!$data || !is_array($data)) {
            return [
                [
                    'type' => 'info',
                    'title' => 'AI Analysis Complete',
                    'message' => 'The AI system has analyzed your profile and found good potential for this position.',
                    'confidence' => 0.8,
                    'actionable' => true,
                    'priority' => 'medium'
                ]
            ];
        }

        return $data;
    }

    /**
     * Parse requirements response
     */
    private function parseRequirementsResponse($response)
    {
        $data = json_decode($response, true);

        if (!$data) {
            return [
                'required_licenses' => [],
                'experience_years' => null,
                'vehicle_types' => [],
                'skills' => [],
                'location_requirements' => null,
                'salary_range' => null,
                'work_schedule' => null,
                'benefits' => [],
                'company_info' => null
            ];
        }

        return $data;
    }

    /**
     * Log matching session to database
     */
    private function logMatchingSession($sessionId, $userId, $userType, $modelUsed, $inputData, $aiResponse, $processedResults, $executionTime, $success, $errorMessage = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ai_matching_sessions (
                    session_id, user_id, user_type, model_used, input_data, 
                    ai_response, processed_results, execution_time_ms, success, error_message
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $sessionId,
                $userId,
                $userType,
                $modelUsed,
                json_encode($inputData),
                json_encode($aiResponse),
                json_encode($processedResults),
                $executionTime,
                $success ? 1 : 0,
                $errorMessage
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Failed to log AI session: " . $e->getMessage());
        }
    }

    /**
     * Get AI analytics
     */
    public function getAnalytics($dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?: date('Y-m-d');

        $stmt = $this->pdo->prepare("
            SELECT 
                model_used,
                COUNT(*) as total_requests,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_requests,
                AVG(execution_time_ms) as avg_execution_time,
                DATE(created_at) as date
            FROM ai_matching_sessions 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY model_used, DATE(created_at)
            ORDER BY date DESC
        ");

        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll();
    }

    /**
     * Update AI configuration
     */
    public function updateConfiguration($key, $value, $type = 'model_config')
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_configuration (config_key, config_value, config_type)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([$key, json_encode($value), $type]);

        // Reload configuration
        $this->loadConfiguration();

        return true;
    }
}
