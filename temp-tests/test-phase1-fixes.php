<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🧪 ΤΕΣΤ ΦΑΣΗΣ 1 - ΔΙΟΡΘΩΣΕΙΣ MATCHING SYSTEM\n";
echo "=============================================\n\n";

// Test 1: AI Widget Fix
echo "1️⃣ Testing AI Widget Fix\n";
echo "-------------------------\n";

try {
    require_once ROOT_DIR . '/src/Services/EnhancedMatchingService.php';
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Test με τον συγκεκριμένο οδηγό
    $driverId = 26;
    $matches = $enhancedService->getTopMatchesForDriver($driverId, 5);

    echo "✅ Enhanced Matching Service: Λειτουργεί\n";
    echo "📊 Matches βρέθηκαν: " . count($matches) . "\n";

    if (!empty($matches)) {
        echo "🎯 Top Match: {$matches[0]['title']} - Score: {$matches[0]['overall_score']}%\n";
    }
} catch (Exception $e) {
    echo "❌ Enhanced Matching Service Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: API Endpoint Fix
echo "2️⃣ Testing API Endpoint Fix\n";
echo "----------------------------\n";

try {
    // Simulate API call
    $driverId = 26;
    $limit = 5;

    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();
    $enhancedMatches = $enhancedService->getTopMatchesForDriver($driverId, $limit);

    // Format response like the API does
    $formattedMatches = [];
    foreach ($enhancedMatches as $match) {
        $score = ($match['overall_score'] ?? 0) / 100;
        $baseFactor = max(0.2, min(0.95, $score));

        $formattedMatches[] = [
            'job_id' => $match['id'],
            'score' => $score,
            'job' => [
                'id' => $match['id'],
                'title' => $match['title'],
                'company_name' => $match['company_name'],
                'location' => $match['location'] ?? $match['company_city']
            ]
        ];
    }

    echo "✅ API Endpoint Format: Λειτουργεί\n";
    echo "📊 Formatted Matches: " . count($formattedMatches) . "\n";

    if (!empty($formattedMatches)) {
        $topMatch = $formattedMatches[0];
        echo "🎯 Top API Match: {$topMatch['job']['title']} - Score: " . round($topMatch['score'] * 100) . "%\n";
    }
} catch (Exception $e) {
    echo "❌ API Endpoint Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Score Consistency Check
echo "3️⃣ Testing Score Consistency\n";
echo "-----------------------------\n";

try {
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();
    $matches = $enhancedService->getTopMatchesForDriver(26, 3);

    echo "✅ Score Consistency Check:\n";
    foreach ($matches as $i => $match) {
        $score = $match['overall_score'] ?? 0;
        $realistic = ($score >= 30 && $score <= 90) ? "✅ Realistic" : "❌ Unrealistic";
        echo "   Match " . ($i + 1) . ": {$score}% - {$realistic}\n";
    }
} catch (Exception $e) {
    echo "❌ Score Consistency Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Data Quality Verification
echo "4️⃣ Testing Data Quality Fixes\n";
echo "------------------------------\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check Job 16 fix
    $stmt = $pdo->prepare("SELECT id, title, vehicle_type FROM job_listings WHERE id = 16");
    $stmt->execute();
    $job16 = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job16 && $job16['vehicle_type'] === 'van') {
        echo "✅ Job 16: vehicle_type διορθώθηκε σε 'van'\n";
    } else {
        echo "❌ Job 16: vehicle_type δεν διορθώθηκε\n";
    }

    // Check Job 21 fix
    $stmt = $pdo->prepare("SELECT id, title, vehicle_type, preferred_schedule FROM job_listings WHERE id = 21");
    $stmt->execute();
    $job21 = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job21 && $job21['vehicle_type'] === 'bus' && $job21['preferred_schedule'] === 'full_time') {
        echo "✅ Job 21: vehicle_type και preferred_schedule διορθώθηκαν\n";
    } else {
        echo "❌ Job 21: Δεν διορθώθηκαν πλήρως\n";
    }

    // Check Company fix
    $stmt = $pdo->prepare("SELECT id, company_name, city FROM companies WHERE id = 2");
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company && $company['city'] === 'Θεσσαλονίκη') {
        echo "✅ Company 2: city διορθώθηκε σε 'Θεσσαλονίκη'\n";
    } else {
        echo "❌ Company 2: city δεν διορθώθηκε\n";
    }
} catch (Exception $e) {
    echo "❌ Data Quality Check Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Overall System Health
echo "5️⃣ Overall System Health Check\n";
echo "-------------------------------\n";

$healthScore = 0;
$totalTests = 5;

// Test Enhanced Matching Service
try {
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();
    $matches = $enhancedService->getTopMatchesForDriver(26, 1);
    if (!empty($matches)) {
        $healthScore++;
        echo "✅ Enhanced Matching Service: Healthy\n";
    }
} catch (Exception $e) {
    echo "❌ Enhanced Matching Service: Unhealthy\n";
}

// Test Score Realism
try {
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();
    $matches = $enhancedService->getTopMatchesForDriver(26, 3);
    $realisticCount = 0;
    foreach ($matches as $match) {
        $score = $match['overall_score'] ?? 0;
        if ($score >= 30 && $score <= 90) {
            $realisticCount++;
        }
    }
    if ($realisticCount >= 2) {
        $healthScore++;
        echo "✅ Score Realism: Healthy\n";
    } else {
        echo "❌ Score Realism: Needs improvement\n";
    }
} catch (Exception $e) {
    echo "❌ Score Realism: Error\n";
}

// Test Data Completeness
try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN vehicle_type IS NOT NULL THEN 1 ELSE 0 END) as with_vehicle_type,
            SUM(CASE WHEN location IS NOT NULL THEN 1 ELSE 0 END) as with_location
        FROM job_listings 
        WHERE is_active = 1
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $completeness = (($stats['with_vehicle_type'] + $stats['with_location']) / ($stats['total'] * 2)) * 100;

    if ($completeness >= 80) {
        $healthScore++;
        echo "✅ Data Completeness: {$completeness}% - Healthy\n";
    } else {
        echo "❌ Data Completeness: {$completeness}% - Needs improvement\n";
    }
} catch (Exception $e) {
    echo "❌ Data Completeness: Error\n";
}

// Calculate overall health
$overallHealth = ($healthScore / $totalTests) * 100;
echo "\n🏥 ΣΥΝΟΛΙΚΗ ΥΓΕΙΑ ΣΥΣΤΗΜΑΤΟΣ: {$overallHealth}%\n";

if ($overallHealth >= 80) {
    echo "🎉 Το σύστημα είναι σε καλή κατάσταση!\n";
} elseif ($overallHealth >= 60) {
    echo "⚠️ Το σύστημα χρειάζεται μικρές βελτιώσεις\n";
} else {
    echo "🚨 Το σύστημα χρειάζεται σημαντικές διορθώσεις\n";
}

echo "\n✅ Φάση 1 testing ολοκληρώθηκε!\n";
