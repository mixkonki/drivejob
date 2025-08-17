<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔍 DEBUGGING MATCHING SCORES\n";
echo "============================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Test specific driver-job combination
    $driverId = 26;
    $jobId = 22;

    echo "🎯 Testing Driver {$driverId} vs Job {$jobId}\n";
    echo "==========================================\n\n";

    // Get driver data
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driverData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "👤 DRIVER DATA:\n";
    echo "- Name: " . ($driverData['first_name'] ?? 'N/A') . " " . ($driverData['last_name'] ?? 'N/A') . "\n";
    echo "- Email: " . ($driverData['email'] ?? 'N/A') . "\n";
    echo "- City: " . ($driverData['city'] ?? 'N/A') . "\n";
    echo "- Country: " . ($driverData['country'] ?? 'N/A') . "\n";
    echo "- Years Experience: " . ($driverData['years_experience'] ?? 'N/A') . "\n";
    echo "- Available for work: " . ($driverData['available_for_work'] ? 'Yes' : 'No') . "\n\n";

    // Get job data
    $stmt = $pdo->prepare("SELECT j.*, c.company_name FROM job_listings j JOIN companies c ON j.company_id = c.id WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "💼 JOB DATA:\n";
    echo "- Title: {$jobData['title']}\n";
    echo "- Company: {$jobData['company_name']}\n";
    echo "- Location: {$jobData['location']}\n";
    echo "- Vehicle Type: " . ($jobData['vehicle_type'] ?? 'N/A') . "\n";
    echo "- License Required: " . ($jobData['license_required'] ?? 'N/A') . "\n";
    echo "- Experience Years: " . ($jobData['experience_years'] ?? 'N/A') . "\n\n";

    // Test Feature Extraction
    echo "🔧 FEATURE EXTRACTION:\n";
    echo "======================\n";

    $featureExtractor = new \Drivejob\Services\AI\FeatureExtractor();

    $driverFeatures = $featureExtractor->extractDriverFeatures($driverId);
    echo "👤 Driver Features:\n";
    print_r($driverFeatures);
    echo "\n";

    $jobFeatures = $featureExtractor->extractJobFeatures($jobId);
    echo "💼 Job Features:\n";
    print_r($jobFeatures);
    echo "\n";

    // Test Individual Score Calculations
    echo "📊 INDIVIDUAL SCORE CALCULATIONS:\n";
    echo "=================================\n";

    // Create enhanced service instance to access private methods via reflection
    $reflection = new ReflectionClass($enhancedService);

    // Test skill match
    $skillMatchMethod = $reflection->getMethod('calculateSkillMatch');
    $skillMatchMethod->setAccessible(true);
    $skillScore = $skillMatchMethod->invoke($enhancedService, $driverFeatures, $jobFeatures);
    echo "🎯 Skill Match Score: " . round($skillScore * 100, 2) . "%\n";

    // Test location match
    $locationMatchMethod = $reflection->getMethod('calculateLocationMatch');
    $locationMatchMethod->setAccessible(true);
    $locationScore = $locationMatchMethod->invoke($enhancedService, $driverFeatures, $jobFeatures);
    echo "📍 Location Match Score: " . round($locationScore * 100, 2) . "%\n";

    // Test experience match
    $experienceMatchMethod = $reflection->getMethod('calculateExperienceMatch');
    $experienceMatchMethod->setAccessible(true);
    $experienceScore = $experienceMatchMethod->invoke($enhancedService, $driverFeatures, $jobFeatures);
    echo "💪 Experience Match Score: " . round($experienceScore * 100, 2) . "%\n";

    // Test availability match
    $availabilityMatchMethod = $reflection->getMethod('calculateAvailabilityMatch');
    $availabilityMatchMethod->setAccessible(true);
    $availabilityScore = $availabilityMatchMethod->invoke($enhancedService, $driverFeatures, $jobFeatures);
    echo "⏰ Availability Match Score: " . round($availabilityScore * 100, 2) . "%\n\n";

    // Test AI Match calculation
    echo "🤖 AI MATCH CALCULATION:\n";
    echo "========================\n";

    $aiMatchMethod = $reflection->getMethod('calculateAIMatch');
    $aiMatchMethod->setAccessible(true);
    $aiResult = $aiMatchMethod->invoke($enhancedService, $driverId, $jobId);

    echo "AI Result:\n";
    print_r($aiResult);
    echo "\n";

    // Test Traditional Match calculation
    echo "🏛️ TRADITIONAL MATCH CALCULATION:\n";
    echo "=================================\n";

    $traditionalMatchMethod = $reflection->getMethod('calculateTraditionalMatch');
    $traditionalMatchMethod->setAccessible(true);
    $traditionalScore = $traditionalMatchMethod->invoke($enhancedService, $driverData, $jobData);
    echo "Traditional Score: " . round($traditionalScore, 2) . "%\n\n";

    // Test Final Score Calculation
    echo "🎯 FINAL SCORE CALCULATION:\n";
    echo "===========================\n";

    $finalScore = $enhancedService->calculateMatchScore($driverId, $jobId);
    echo "Final Score: " . round($finalScore, 2) . "%\n\n";

    // Check what's stored in database
    echo "💾 DATABASE STORED SCORE:\n";
    echo "=========================\n";

    $stmt = $pdo->prepare("
        SELECT * FROM matching_scores 
        WHERE driver_id = ? AND job_id = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$driverId, $jobId]);
    $storedScore = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($storedScore) {
        echo "Stored Overall Score: {$storedScore['overall_score']}%\n";
        echo "Skill Match: {$storedScore['skill_match_score']}\n";
        echo "Location Match: {$storedScore['location_match_score']}\n";
        echo "Experience Match: {$storedScore['experience_match_score']}\n";
        echo "Availability Match: {$storedScore['availability_match_score']}\n";
        echo "Factors: {$storedScore['factors']}\n";
    } else {
        echo "No stored score found!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Debug completed\n";
