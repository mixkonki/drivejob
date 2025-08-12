<?php
session_start();
require_once '../../src/bootstrap.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['api_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing API key']);
    exit;
}

$apiKey = $input['api_key'];
$model = $input['model'] ?? 'gpt-4o-mini';

// Test OpenAI connection
function testOpenAIConnection($apiKey, $model)
{
    try {
        $ch = curl_init();

        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Πες μου "Γεια σου από το DriveJob AI System!" στα ελληνικά.'
                ]
            ],
            'max_tokens' => 50,
            'temperature' => 0.7
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $curlError
            ];
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);

            if (isset($data['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'message' => $data['choices'][0]['message']['content'],
                    'model' => $model,
                    'usage' => $data['usage'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid response format from OpenAI'
                ];
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = 'HTTP ' . $httpCode;

            if (isset($errorData['error']['message'])) {
                $errorMessage .= ': ' . $errorData['error']['message'];
            }

            // Provide specific error messages for common issues
            switch ($httpCode) {
                case 401:
                    $errorMessage = 'Μη έγκυρο API key. Παρακαλώ ελέγξτε το API key σας.';
                    break;
                case 403:
                    $errorMessage = 'Δεν έχετε πρόσβαση σε αυτό το μοντέλο. Ελέγξτε τις άδειες του API key σας.';
                    break;
                case 429:
                    $errorMessage = 'Πολλά αιτήματα. Παρακαλώ δοκιμάστε ξανά σε λίγο.';
                    break;
                case 500:
                    $errorMessage = 'Σφάλμα διακομιστή OpenAI. Δοκιμάστε ξανά αργότερα.';
                    break;
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'http_code' => $httpCode,
                'raw_response' => $response
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// Perform the test
$result = testOpenAIConnection($apiKey, $model);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
