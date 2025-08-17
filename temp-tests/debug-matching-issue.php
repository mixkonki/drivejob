<?php

/**
 * Debug script για το matching issue
 * Ελέγχει γιατί όλα τα scores είναι 10%
 */

define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\EnhancedMatchingService;
use Drivejob\Services\AI\FeatureExtractor;
use Drivejob\Services\AI\ScoreCalculator;

echo "=== DEBUG MATCHING ISSUE ===\n\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Έλεγχος για τον οδηγό kostas.michailidis@hotmail.gr
    echo "1. Αναζήτηση οδηγού kostas.michailidis@hotmail.gr...\n";
    $stmt = $pdo->prepare("
        SELECT * FROM drivers WHERE email = ?
    ");
    $stmt->execute(['kostas.michailidis@hotmail.gr']);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        echo "✅ Βρέθηκε οδηγός: ID {$driver['id']}, {$driver['first_name']} {$driver['last_name']}\n";
        echo "   Πόλη: {$driver['city']}, Χώρα: {$driver['country']}\n";
        echo "   Διαθέσιμος: " . ($driver['available_for_work'] ? 'Ναι' : 'Όχι') . "\n";
        echo "   Εμπειρία: {$driver['experience_years']} έτη\n\n";

        $driverId = $driver['id'];
    } else {
        echo "❌ Δεν βρέθηκε οδηγός με email kostas.michailidis@hotmail.gr\n\n";

        // Δείξε όλους τους οδηγούς
        echo "Διαθέσιμοι οδηγοί:\n";
        $stmt = $pdo->query("
            SELECT d.id, d.first_name, d.last_name, u.email 
            FROM drivers d 
            JOIN users u ON d.user_id = u.id 
            LIMIT 5
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- ID {$row['id']}: {$row['first_name']} {$row['last_name']} ({$row['email']})\n";
        }

        // Χρησιμοποίησε τον πρώτο οδηγό για testing
        $stmt = $pdo->query("SELECT id FROM drivers LIMIT 1");
        $driverId = $stmt->fetchColumn();
        echo "\nΧρησιμοποιώ οδηγό ID {$driverId} για testing...\n\n";
    }

    // 2. Έλεγχος για ενεργές αγγελίες
    echo "2. Έλεγχος ενεργών αγγελιών...\n";
    $stmt = $pdo->query("
        SELECT j.id, j.title, j.location, c.company_name, j.vehicle_type
        FROM job_listings j
        JOIN companies c ON j.company_id = c.id
        WHERE j.is_active = 1
        LIMIT 5
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jobs)) {
        echo "❌ Δεν βρέθηκαν ενεργές αγγελίες!\n\n";

        // Δείξε όλες τις αγγελίες
        $stmt = $pdo->query("
            SELECT j.id, j.title, j.is_active, c.company_name
            FROM job_listings j
            JOIN companies c ON j.company_id = c.id
            LIMIT 5
        ");
        echo "Όλες οι αγγελίες:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['is_active'] ? 'Ενεργή' : 'Ανενεργή';
            echo "- ID {$row['id']}: {$row['title']} ({$row['company_name']}) - {$status}\n";
        }
        exit;
    } else {
        echo "✅ Βρέθηκαν " . count($jobs) . " ενεργές αγγελίες:\n";
        foreach ($jobs as $job) {
            echo "- ID {$job['id']}: {$job['title']} ({$job['company_name']}) - {$job['location']}\n";
        }
        echo "\n";
    }

    // 3. Test του FeatureExtractor
    echo "3. Testing FeatureExtractor...\n";
    $featureExtractor = new FeatureExtractor($pdo);

    $driverFeatures = $featureExtractor->extractDriverFeatures($driverId);
    echo "Driver Features για ID {$driverId}:\n";
    echo "- Licenses: " . implode(', ', $driverFeatures['licenses'] ?? []) . "\n";
    echo "- Vehicle Types: " . implode(', ', $driverFeatures['vehicle_types'] ?? []) . "\n";
    echo "- City: " . ($driverFeatures['location']['city'] ?? 'N/A') . "\n";
    echo "- Experience: " . ($driverFeatures['years_experience'] ?? 0) . " years\n";
    echo "- Available: " . ($driverFeatures['available_immediately'] ? 'Yes' : 'No') . "\n\n";

    $firstJob = $jobs[0];
    $jobFeatures = $featureExtractor->extractJobFeatures($firstJob['id']);
    echo "Job Features για '{$firstJob['title']}':\n";
    echo "- Required License: " . ($jobFeatures['required_license'] ?? 'N/A') . "\n";
    echo "- Vehicle Type: " . ($jobFeatures['vehicle_type'] ?? 'N/A') . "\n";
    echo "- Location: " . ($jobFeatures['location']['city'] ?? 'N/A') . "\n";
    echo "- Min Experience: " . ($jobFeatures['min_experience'] ?? 0) . " years\n\n";

    // 4. Test του matching algorithm
    echo "4. Testing Enhanced Matching Service...\n";
    $matchingService = new EnhancedMatchingService($pdo);

    foreach (array_slice($jobs, 0, 3) as $job) {
        echo "Testing match για '{$job['title']}':\n";

        // Direct calculation
        $score = $matchingService->calculateMatchScore($driverId, $job['id']);
        echo "- Final Score: {$score}%\n";

        // Manual traditional calculation για debugging
        $driverData = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
        $driverData->execute([$driverId]);
        $driverArray = $driverData->fetch(PDO::FETCH_ASSOC);

        // Test individual components - χρησιμοποιούμε reflection για private methods
        echo "- Driver Data Keys: " . implode(', ', array_keys($driverArray)) . "\n";
        echo "- Job Data Keys: " . implode(', ', array_keys($job)) . "\n";

        echo "\n";
    }

    // 5. Έλεγχος της βάσης δεδομένων matching_scores
    echo "5. Έλεγχος matching_scores table...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               AVG(overall_score) as avg_score,
               MIN(overall_score) as min_score,
               MAX(overall_score) as max_score
        FROM matching_scores
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "- Total scores: {$stats['total']}\n";
    echo "- Average score: " . number_format($stats['avg_score'], 2) . "%\n";
    echo "- Min score: " . number_format($stats['min_score'], 2) . "%\n";
    echo "- Max score: " . number_format($stats['max_score'], 2) . "%\n\n";

    // 6. Δείγμα από matching_scores
    echo "6. Δείγμα matching scores:\n";
    $stmt = $pdo->query("
        SELECT ms.*, d.first_name, d.last_name, j.title
        FROM matching_scores ms
        LEFT JOIN drivers d ON ms.driver_id = d.id
        LEFT JOIN job_listings j ON ms.job_id = j.id
        ORDER BY ms.updated_at DESC
        LIMIT 10
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- Driver: {$row['first_name']} {$row['last_name']}, Job: {$row['title']}, Score: {$row['overall_score']}%\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETED ===\n";
