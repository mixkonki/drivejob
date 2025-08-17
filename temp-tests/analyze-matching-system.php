<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔍 ΑΝΑΛΥΣΗ MATCHING SYSTEM\n";
echo "=========================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check drivers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM drivers WHERE is_active = 1");
    $stmt->execute();
    $activeDrivers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Check jobs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM job_listings WHERE is_active = 1");
    $stmt->execute();
    $activeJobs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Check companies
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM companies WHERE is_active = 1");
    $stmt->execute();
    $activeCompanies = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo "📊 ΣΤΑΤΙΣΤΙΚΑ:\n";
    echo "Ενεργοί Οδηγοί: {$activeDrivers}\n";
    echo "Ενεργές Αγγελίες: {$activeJobs}\n";
    echo "Ενεργές Εταιρίες: {$activeCompanies}\n\n";

    // Check matching scores
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matching_scores");
    $stmt->execute();
    $totalScores = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT driver_id) as drivers, COUNT(DISTINCT job_id) as jobs FROM matching_scores");
    $stmt->execute();
    $scoreStats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "🎯 MATCHING SCORES:\n";
    echo "Συνολικά Scores: {$totalScores}\n";
    echo "Οδηγοί με Scores: {$scoreStats['drivers']}\n";
    echo "Jobs με Scores: {$scoreStats['jobs']}\n\n";

    // Potential matches calculation
    $potentialMatches = $activeDrivers * $activeJobs;
    $coverage = $totalScores > 0 ? round(($totalScores / $potentialMatches) * 100, 2) : 0;

    echo "📈 COVERAGE ANALYSIS:\n";
    echo "Πιθανά Ταιριάσματα: {$potentialMatches}\n";
    echo "Υπολογισμένα Scores: {$totalScores}\n";
    echo "Coverage: {$coverage}%\n\n";

    // Analyze score distribution
    $stmt = $pdo->prepare("
        SELECT 
            MIN(overall_score) as min_score,
            MAX(overall_score) as max_score,
            AVG(overall_score) as avg_score,
            COUNT(*) as total_scores
        FROM matching_scores 
        WHERE overall_score > 0
    ");
    $stmt->execute();
    $scoreDistribution = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "📊 SCORE DISTRIBUTION:\n";
    if ($scoreDistribution['total_scores'] > 0) {
        echo "Min Score: " . round($scoreDistribution['min_score'], 2) . "%\n";
        echo "Max Score: " . round($scoreDistribution['max_score'], 2) . "%\n";
        echo "Avg Score: " . round($scoreDistribution['avg_score'], 2) . "%\n";
        echo "Valid Scores: {$scoreDistribution['total_scores']}\n\n";
    } else {
        echo "No valid scores found\n\n";
    }

    // Check driver data quality
    echo "🔍 DRIVER DATA QUALITY:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN city IS NOT NULL AND city != '' THEN 1 END) as has_city,
            COUNT(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 END) as has_phone,
            COUNT(CASE WHEN years_experience IS NOT NULL THEN 1 END) as has_experience
        FROM drivers WHERE is_active = 1
    ");
    $stmt->execute();
    $driverQuality = $stmt->fetch(PDO::FETCH_ASSOC);

    $cityPercent = round(($driverQuality['has_city'] / $driverQuality['total']) * 100, 1);
    $phonePercent = round(($driverQuality['has_phone'] / $driverQuality['total']) * 100, 1);
    $expPercent = round(($driverQuality['has_experience'] / $driverQuality['total']) * 100, 1);

    echo "City Data: {$cityPercent}% ({$driverQuality['has_city']}/{$driverQuality['total']})\n";
    echo "Phone Data: {$phonePercent}% ({$driverQuality['has_phone']}/{$driverQuality['total']})\n";
    echo "Experience Data: {$expPercent}% ({$driverQuality['has_experience']}/{$driverQuality['total']})\n\n";

    // Check job data quality
    echo "🔍 JOB DATA QUALITY:\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN location IS NOT NULL AND location != '' THEN 1 END) as has_location,
            COUNT(CASE WHEN vehicle_type IS NOT NULL AND vehicle_type != '' THEN 1 END) as has_vehicle_type,
            COUNT(CASE WHEN salary_min IS NOT NULL THEN 1 END) as has_salary
        FROM job_listings WHERE is_active = 1
    ");
    $stmt->execute();
    $jobQuality = $stmt->fetch(PDO::FETCH_ASSOC);

    $locationPercent = round(($jobQuality['has_location'] / $jobQuality['total']) * 100, 1);
    $vehiclePercent = round(($jobQuality['has_vehicle_type'] / $jobQuality['total']) * 100, 1);
    $salaryPercent = round(($jobQuality['has_salary'] / $jobQuality['total']) * 100, 1);

    echo "Location Data: {$locationPercent}% ({$jobQuality['has_location']}/{$jobQuality['total']})\n";
    echo "Vehicle Type Data: {$vehiclePercent}% ({$jobQuality['has_vehicle_type']}/{$jobQuality['total']})\n";
    echo "Salary Data: {$salaryPercent}% ({$jobQuality['has_salary']}/{$jobQuality['total']})\n\n";

    // Sample matching analysis for specific driver
    echo "🎯 SAMPLE MATCHING ANALYSIS (Driver ID 26):\n";
    $stmt = $pdo->prepare("
        SELECT 
            j.id, j.title, j.location, j.vehicle_type,
            ms.overall_score, ms.location_match_score, ms.skill_match_score
        FROM job_listings j
        LEFT JOIN matching_scores ms ON j.id = ms.job_id AND ms.driver_id = 26
        WHERE j.is_active = 1
        ORDER BY ms.overall_score DESC
        LIMIT 5
    ");
    $stmt->execute();
    $sampleMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sampleMatches as $match) {
        $score = $match['overall_score'] ? round($match['overall_score'], 1) . '%' : 'No Score';
        $locationScore = $match['location_match_score'] ? round($match['location_match_score'] * 100, 1) . '%' : 'N/A';
        echo "- Job {$match['id']}: {$match['title']} ({$match['location']}) - Score: {$score}, Location: {$locationScore}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Analysis completed\n";
