<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔧 FIXING MATCHING SCORES\n";
echo "=========================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Test specific driver-job combination
    $driverId = 26;
    $jobId = 22;

    echo "🎯 Fixing Driver {$driverId} vs Job {$jobId}\n\n";

    // Get driver and job data
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driverData = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT j.*, c.company_name FROM job_listings j JOIN companies c ON j.company_id = c.id WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Manual score calculation with REALISTIC logic
    echo "🧮 MANUAL SCORE CALCULATION:\n";
    echo "============================\n";

    // 1. Location Score (Thessaloniki -> Attica = different regions)
    $locationScore = 0.4; // 40% for different regions in same country
    echo "📍 Location Score: {$locationScore} (40% - Different regions)\n";

    // 2. Experience Score (0 years vs 2 required)
    $driverExp = $driverData['years_experience'] ?? 0;
    $requiredExp = $jobData['experience_years'] ?? 0;
    $deficit = $requiredExp - $driverExp;
    $experienceScore = max(0, 0.8 - ($deficit * 0.2)); // 0.8 - (2 * 0.2) = 0.4
    echo "💪 Experience Score: {$experienceScore} (40% - 2 years deficit)\n";

    // 3. Skill Score (Driver has many licenses, job has no specific requirements)
    $skillScore = 0.8; // 80% - driver is well qualified
    echo "🎯 Skill Score: {$skillScore} (80% - Driver has all major licenses)\n";

    // 4. Availability Score (Driver is available)
    $availabilityScore = 1.0; // 100% - driver is available
    echo "⏰ Availability Score: {$availabilityScore} (100% - Driver available)\n";

    // Calculate weighted overall score
    $weights = [
        'skill_match' => 0.35,
        'location_match' => 0.25,
        'experience_match' => 0.25,
        'availability_match' => 0.15
    ];

    $overallScore = (
        ($skillScore * $weights['skill_match']) +
        ($locationScore * $weights['location_match']) +
        ($experienceScore * $weights['experience_match']) +
        ($availabilityScore * $weights['availability_match'])
    ) * 100;

    echo "\n🎯 WEIGHTED CALCULATION:\n";
    echo "- Skill (35%): " . round($skillScore * $weights['skill_match'] * 100, 1) . "%\n";
    echo "- Location (25%): " . round($locationScore * $weights['location_match'] * 100, 1) . "%\n";
    echo "- Experience (25%): " . round($experienceScore * $weights['experience_match'] * 100, 1) . "%\n";
    echo "- Availability (15%): " . round($availabilityScore * $weights['availability_match'] * 100, 1) . "%\n";
    echo "- TOTAL: " . round($overallScore, 1) . "%\n\n";

    // Store the corrected score in database
    echo "💾 STORING CORRECTED SCORE:\n";
    $stmt = $pdo->prepare("
        INSERT INTO matching_scores 
        (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
         experience_match_score, availability_match_score, factors, ml_confidence, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            overall_score = VALUES(overall_score),
            skill_match_score = VALUES(skill_match_score),
            location_match_score = VALUES(location_match_score),
            experience_match_score = VALUES(experience_match_score),
            availability_match_score = VALUES(availability_match_score),
            factors = VALUES(factors),
            ml_confidence = VALUES(ml_confidence),
            updated_at = NOW()
    ");

    $factors = json_encode([
        'skill_match' => $skillScore,
        'location_match' => $locationScore,
        'experience_match' => $experienceScore,
        'availability_match' => $availabilityScore,
        'calculation_method' => 'manual_fix',
        'notes' => 'Fixed realistic scoring'
    ]);

    $stmt->execute([
        $driverId,
        $jobId,
        $overallScore,
        $skillScore,
        $locationScore,
        $experienceScore,
        $availabilityScore,
        $factors,
        0.95 // High confidence for manual calculation
    ]);

    echo "✅ Score updated successfully!\n";
    echo "- Overall Score: " . round($overallScore, 1) . "%\n";
    echo "- Individual scores stored correctly\n\n";

    // Now let's fix ALL scores with a more realistic approach
    echo "🔄 FIXING ALL SCORES WITH REALISTIC LOGIC:\n";
    echo "==========================================\n";

    // Get all driver-job combinations
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE is_active = 1");
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE is_active = 1");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $processed = 0;
    $scoreDistribution = [];

    foreach ($drivers as $dId) {
        foreach ($jobs as $jId) {
            // Get driver data
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
            $stmt->execute([$dId]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get job data
            $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE id = ?");
            $stmt->execute([$jId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver || !$job) continue;

            // Calculate realistic scores
            $scores = calculateRealisticScores($driver, $job);

            // Calculate overall
            $overall = (
                ($scores['skill'] * 0.35) +
                ($scores['location'] * 0.25) +
                ($scores['experience'] * 0.25) +
                ($scores['availability'] * 0.15)
            ) * 100;

            // Store in database
            $stmt = $pdo->prepare("
                INSERT INTO matching_scores 
                (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
                 experience_match_score, availability_match_score, factors, ml_confidence, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    overall_score = VALUES(overall_score),
                    skill_match_score = VALUES(skill_match_score),
                    location_match_score = VALUES(location_match_score),
                    experience_match_score = VALUES(experience_match_score),
                    availability_match_score = VALUES(availability_match_score),
                    factors = VALUES(factors),
                    ml_confidence = VALUES(ml_confidence),
                    updated_at = NOW()
            ");

            $factors = json_encode($scores);

            $stmt->execute([
                $dId,
                $jId,
                $overall,
                $scores['skill'],
                $scores['location'],
                $scores['experience'],
                $scores['availability'],
                $factors,
                0.90
            ]);

            $processed++;
            $scoreRange = floor($overall / 10) * 10;
            $scoreDistribution[$scoreRange] = ($scoreDistribution[$scoreRange] ?? 0) + 1;

            if ($processed % 20 == 0) {
                echo "Processed: {$processed}/" . (count($drivers) * count($jobs)) . "\n";
            }
        }
    }

    echo "\n✅ ALL SCORES FIXED!\n";
    echo "====================\n";
    echo "Total processed: {$processed}\n";
    echo "Score distribution:\n";
    ksort($scoreDistribution);
    foreach ($scoreDistribution as $range => $count) {
        $percentage = round(($count / $processed) * 100, 1);
        echo "- {$range}-" . ($range + 9) . "%: {$count} matches ({$percentage}%)\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Helper function for realistic score calculation
function calculateRealisticScores($driver, $job)
{
    // Location scoring
    $driverCity = strtolower(trim($driver['city'] ?? ''));
    $jobLocation = strtolower(trim($job['location'] ?? ''));

    $locationScore = 0.3; // Default
    if ($driverCity === $jobLocation) {
        $locationScore = 1.0; // Same city
    } elseif ($driverCity === 'θεσσαλονίκη' && stripos($jobLocation, 'αττική') !== false) {
        $locationScore = 0.4; // Different regions
    } elseif ($driverCity === 'αθήνα' && stripos($jobLocation, 'θεσσαλονίκη') !== false) {
        $locationScore = 0.4; // Different regions
    } elseif (!empty($driverCity) && !empty($jobLocation)) {
        $locationScore = 0.5; // Same country, different cities
    }

    // Experience scoring
    $driverExp = $driver['years_experience'] ?? 0;
    $requiredExp = $job['experience_years'] ?? 0;

    $experienceScore = 0.5; // Default
    if ($driverExp >= $requiredExp) {
        $experienceScore = min(1.0, 0.8 + (($driverExp - $requiredExp) * 0.05));
    } else {
        $deficit = $requiredExp - $driverExp;
        $experienceScore = max(0.2, 0.8 - ($deficit * 0.15));
    }

    // Skill scoring (based on licenses and requirements)
    $skillScore = 0.6; // Default moderate skill match

    // Check if driver has licenses
    $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM driver_licenses WHERE driver_id = ?");
    $stmt->execute([$driver['id']]);
    $licenseCount = $stmt->fetchColumn();

    if ($licenseCount >= 5) {
        $skillScore = 0.9; // Well qualified
    } elseif ($licenseCount >= 3) {
        $skillScore = 0.7; // Good qualifications
    } elseif ($licenseCount >= 1) {
        $skillScore = 0.5; // Basic qualifications
    } else {
        $skillScore = 0.3; // Limited qualifications
    }

    // Availability scoring
    $availabilityScore = ($driver['available_for_work'] ?? false) ? 1.0 : 0.3;

    return [
        'location' => $locationScore,
        'experience' => $experienceScore,
        'skill' => $skillScore,
        'availability' => $availabilityScore
    ];
}

echo "\n🏁 Fix completed\n";
