<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;
use Drivejob\Services\MatchingService;

echo "=== COMPREHENSIVE AI MATCHING DIAGNOSIS ===\n\n";

// 1. Test Database Connection
echo "1. TESTING DATABASE CONNECTION:\n";
try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// 2. Check if matches exist in database
echo "\n2. CHECKING DATABASE MATCHES:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_matches WHERE driver_id = 26");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Matches in database for driver 26: {$count['count']}\n";

    if ($count['count'] > 0) {
        $stmt = $pdo->query("SELECT jm.id, jm.company_listing_id, jm.match_score, jl.title 
                            FROM job_matches jm 
                            LEFT JOIN job_listings jl ON jm.company_listing_id = jl.id 
                            WHERE jm.driver_id = 26 
                            ORDER BY jm.match_score DESC LIMIT 3");
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matches as $match) {
            echo "  - Match ID: {$match['id']}, Job ID: {$match['company_listing_id']}, Score: {$match['match_score']}, Title: " . ($match['title'] ?? 'NULL') . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Database query failed: " . $e->getMessage() . "\n";
}

// 3. Test MatchingService directly
echo "\n3. TESTING MATCHING SERVICE:\n";
try {
    $matchingService = new MatchingService($pdo);
    $result = $matchingService->findDriverMatches(26, 1, 3);

    echo "✅ MatchingService works\n";
    echo "  - Total matches: {$result['pagination']['total']}\n";
    echo "  - Results returned: " . count($result['results']) . "\n";

    if (!empty($result['results'])) {
        foreach ($result['results'] as $i => $match) {
            echo "  - Result " . ($i + 1) . ": Job ID {$match['company_listing_id']}, Score {$match['match_score']}, Title: {$match['title']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ MatchingService failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// 4. Test Session functionality
echo "\n4. TESTING SESSION:\n";
Session::start();
echo "✅ Session started\n";
echo "  - Session ID: " . session_id() . "\n";
echo "  - Has user_id: " . (Session::has('user_id') ? 'YES (' . Session::get('user_id') . ')' : 'NO') . "\n";
echo "  - Has user_role: " . (Session::has('user_role') ? 'YES (' . Session::get('user_role') . ')' : 'NO') . "\n";

// Simulate login
$_SESSION['user_id'] = 26;
$_SESSION['user_role'] = 'driver';
echo "  - Simulated login for driver 26\n";
echo "  - Now has user_id: " . (Session::has('user_id') ? 'YES (' . Session::get('user_id') . ')' : 'NO') . "\n";
echo "  - Now has user_role: " . (Session::has('user_role') ? 'YES (' . Session::get('user_role') . ')' : 'NO') . "\n";

// 5. Test API endpoint logic (without HTTP)
echo "\n5. TESTING API ENDPOINT LOGIC:\n";
try {
    // Simulate the API endpoint logic
    if (!Session::has('user_id') || Session::get('user_role') !== 'driver') {
        echo "❌ Session check failed\n";
    } else {
        echo "✅ Session check passed\n";

        $driverId = Session::get('user_id');
        $limit = 3;
        $page = 1;

        $matchingService = new MatchingService($pdo);
        $result = $matchingService->findDriverMatches($driverId, $page, $limit);

        // Format response like the API does
        $formattedMatches = [];
        foreach ($result['results'] as $match) {
            $formattedMatches[] = [
                'job_id' => $match['company_listing_id'],
                'score' => floatval($match['match_score']) / 100,
                'job' => [
                    'id' => $match['company_listing_id'],
                    'title' => $match['title'],
                    'description' => $match['description'],
                    'location' => $match['location'],
                    'company_name' => $match['company_name'],
                    'company_city' => $match['city'] ?? '',
                    'salary_min' => $match['salary_min'],
                    'salary_max' => $match['salary_max'],
                    'created_at' => $match['listing_created_at']
                ]
            ];
        }

        $apiResponse = [
            'success' => true,
            'data' => [
                'matches' => $formattedMatches,
                'total' => $result['pagination']['total']
            ]
        ];

        echo "✅ API logic works\n";
        echo "  - Formatted matches: " . count($formattedMatches) . "\n";
        echo "  - Sample JSON structure: " . (count($formattedMatches) > 0 ? 'Valid' : 'Empty') . "\n";
    }
} catch (Exception $e) {
    echo "❌ API logic failed: " . $e->getMessage() . "\n";
}

// 6. Check if the matching widget file exists and is included
echo "\n6. CHECKING MATCHING WIDGET:\n";
$widgetPath = __DIR__ . '/src/Views/drivers/partials/matching-widget.php';
if (file_exists($widgetPath)) {
    echo "✅ Matching widget file exists\n";
    echo "  - Path: $widgetPath\n";
} else {
    echo "❌ Matching widget file not found\n";
    echo "  - Expected path: $widgetPath\n";
}

// 7. Check driver profile file
echo "\n7. CHECKING DRIVER PROFILE:\n";
$profilePath = __DIR__ . '/src/Views/drivers/driver-profile.php';
if (file_exists($profilePath)) {
    echo "✅ Driver profile file exists\n";

    // Check if it includes the matching widget
    $content = file_get_contents($profilePath);
    if (strpos($content, 'matching-widget.php') !== false) {
        echo "✅ Driver profile includes matching widget\n";
    } else {
        echo "❌ Driver profile does NOT include matching widget\n";
    }
} else {
    echo "❌ Driver profile file not found\n";
}

// 8. Test the actual API endpoint via curl
echo "\n8. TESTING API ENDPOINT VIA CURL:\n";
$cookieFile = tempnam(sys_get_temp_dir(), 'session_cookie');

// First, simulate a login to get session cookie
$loginUrl = 'http://localhost/drivejob/public/login-process.php';
$loginData = [
    'email' => 'kostas.michailidis@hotmail.gr',
    'password' => '123456'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  - Login attempt: HTTP $loginHttpCode\n";

// Now test the API endpoint with the session cookie
$apiUrl = 'http://localhost/drivejob/public/api/matching/driver/matches.php?limit=3';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$apiResponse = curl_exec($ch);
$apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  - API call: HTTP $apiHttpCode\n";
if ($apiResponse) {
    echo "  - Response length: " . strlen($apiResponse) . " chars\n";
    echo "  - Response preview: " . substr($apiResponse, 0, 200) . "...\n";

    $jsonData = json_decode($apiResponse, true);
    if ($jsonData) {
        echo "  - Valid JSON: YES\n";
        echo "  - Success: " . ($jsonData['success'] ?? 'undefined') . "\n";
        if (isset($jsonData['data']['matches'])) {
            echo "  - Matches in response: " . count($jsonData['data']['matches']) . "\n";
        }
    } else {
        echo "  - Valid JSON: NO\n";
    }
} else {
    echo "  - No response received\n";
}

// Cleanup
unlink($cookieFile);

echo "\n=== DIAGNOSIS COMPLETE ===\n";
