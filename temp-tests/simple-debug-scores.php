<?php
// Disable output buffering and error handling to avoid header issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/bootstrap.php';

echo "🔍 SIMPLE DEBUGGING MATCHING SCORES\n";
echo "===================================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Test specific driver-job combination
    $driverId = 26;
    $jobId = 22;

    echo "🎯 Testing Driver {$driverId} vs Job {$jobId}\n\n";

    // Get driver data directly
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driverData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "👤 DRIVER DATA:\n";
    echo "- ID: {$driverData['id']}\n";
    echo "- Name: " . ($driverData['first_name'] ?? 'N/A') . " " . ($driverData['last_name'] ?? 'N/A') . "\n";
    echo "- Email: " . ($driverData['email'] ?? 'N/A') . "\n";
    echo "- City: " . ($driverData['city'] ?? 'N/A') . "\n";
    echo "- Country: " . ($driverData['country'] ?? 'N/A') . "\n";
    echo "- Years Experience: " . ($driverData['years_experience'] ?? 'N/A') . "\n";
    echo "- Available: " . ($driverData['available_for_work'] ? 'Yes' : 'No') . "\n\n";

    // Get job data
    $stmt = $pdo->prepare("SELECT j.*, c.company_name FROM job_listings j JOIN companies c ON j.company_id = c.id WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "💼 JOB DATA:\n";
    echo "- ID: {$jobData['id']}\n";
    echo "- Title: {$jobData['title']}\n";
    echo "- Company: {$jobData['company_name']}\n";
    echo "- Location: {$jobData['location']}\n";
    echo "- Vehicle Type: " . ($jobData['vehicle_type'] ?? 'N/A') . "\n";
    echo "- License Required: " . ($jobData['license_required'] ?? 'N/A') . "\n";
    echo "- Experience Years: " . ($jobData['experience_years'] ?? 'N/A') . "\n";
    echo "- Salary Min: " . ($jobData['salary_min'] ?? 'N/A') . "\n";
    echo "- Salary Max: " . ($jobData['salary_max'] ?? 'N/A') . "\n\n";

    // Check driver licenses
    echo "🪪 DRIVER LICENSES:\n";
    $stmt = $pdo->prepare("SELECT * FROM driver_licenses WHERE driver_id = ?");
    $stmt->execute([$driverId]);
    $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($licenses)) {
        echo "- No licenses found\n";
    } else {
        foreach ($licenses as $license) {
            echo "- {$license['license_type']} (expires: {$license['expiry_date']})\n";
        }
    }
    echo "\n";

    // Check driver certifications
    echo "📜 DRIVER CERTIFICATIONS:\n";
    $stmt = $pdo->prepare("SELECT * FROM driver_certifications WHERE driver_id = ?");
    $stmt->execute([$driverId]);
    $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($certifications)) {
        echo "- No certifications found\n";
    } else {
        foreach ($certifications as $cert) {
            $certName = $cert['certification_name'] ?? $cert['name'] ?? 'Unknown';
            echo "- {$cert['certification_type']}: {$certName}\n";
        }
    }
    echo "\n";

    // Check vehicle experience
    echo "🚛 VEHICLE EXPERIENCE:\n";
    $stmt = $pdo->prepare("SELECT * FROM driver_vehicle_experience WHERE driver_id = ?");
    $stmt->execute([$driverId]);
    $vehicleExp = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($vehicleExp)) {
        echo "- No vehicle experience found\n";
    } else {
        foreach ($vehicleExp as $exp) {
            $years = $exp['years_experience'] ?? $exp['experience_years'] ?? 'Unknown';
            echo "- {$exp['vehicle_type']}: {$years} years\n";
        }
    }
    echo "\n";

    // Test location matching manually
    echo "📍 LOCATION MATCHING TEST:\n";
    $driverCity = strtolower(trim($driverData['city'] ?? ''));
    $jobLocation = strtolower(trim($jobData['location'] ?? ''));

    echo "- Driver City: '{$driverCity}'\n";
    echo "- Job Location: '{$jobLocation}'\n";

    // Simple location score calculation
    $locationScore = 0.0;
    if ($driverCity === 'θεσσαλονίκη' && stripos($jobLocation, 'αττική') !== false) {
        $locationScore = 0.4; // Different regions
        echo "- Match Type: Different regions (Thessaloniki -> Attica)\n";
    } elseif ($driverCity === $jobLocation) {
        $locationScore = 1.0; // Same city
        echo "- Match Type: Same city\n";
    } else {
        $locationScore = 0.3; // Default
        echo "- Match Type: Default\n";
    }
    echo "- Location Score: " . round($locationScore * 100, 2) . "%\n\n";

    // Test experience matching
    echo "💪 EXPERIENCE MATCHING TEST:\n";
    $driverExp = $driverData['years_experience'] ?? 0;
    $requiredExp = $jobData['experience_years'] ?? 0;

    echo "- Driver Experience: {$driverExp} years\n";
    echo "- Required Experience: {$requiredExp} years\n";

    $experienceScore = 0.0;
    if ($driverExp >= $requiredExp) {
        $experienceScore = 0.8 + min(0.2, ($driverExp - $requiredExp) * 0.05);
        echo "- Match Type: Meets requirements\n";
    } else {
        $deficit = $requiredExp - $driverExp;
        $experienceScore = max(0, 0.8 - ($deficit * 0.2));
        echo "- Match Type: Below requirements (deficit: {$deficit} years)\n";
    }
    echo "- Experience Score: " . round($experienceScore * 100, 2) . "%\n\n";

    // Calculate simple overall score
    echo "🎯 SIMPLE OVERALL CALCULATION:\n";
    $simpleScore = ($locationScore * 0.4) + ($experienceScore * 0.6);
    echo "- Location (40%): " . round($locationScore * 100, 2) . "%\n";
    echo "- Experience (60%): " . round($experienceScore * 100, 2) . "%\n";
    echo "- Simple Overall: " . round($simpleScore * 100, 2) . "%\n\n";

    // Check what's actually stored in database
    echo "💾 DATABASE STORED SCORE:\n";
    $stmt = $pdo->prepare("
        SELECT * FROM matching_scores 
        WHERE driver_id = ? AND job_id = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$driverId, $jobId]);
    $storedScore = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($storedScore) {
        echo "- Stored Overall Score: {$storedScore['overall_score']}%\n";
        echo "- Skill Match: " . ($storedScore['skill_match_score'] ?? 'N/A') . "\n";
        echo "- Location Match: " . ($storedScore['location_match_score'] ?? 'N/A') . "\n";
        echo "- Experience Match: " . ($storedScore['experience_match_score'] ?? 'N/A') . "\n";
        echo "- Availability Match: " . ($storedScore['availability_match_score'] ?? 'N/A') . "\n";
        echo "- Created: " . ($storedScore['created_at'] ?? 'N/A') . "\n";
        echo "- Updated: " . ($storedScore['updated_at'] ?? 'N/A') . "\n";
    } else {
        echo "- No stored score found!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n🏁 Debug completed\n";
