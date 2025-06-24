<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

// Driver ID 26
$driverId = 26;

echo "<h2>Έλεγχος Ταιριασμάτων για Driver ID: $driverId</h2>";

// Check if driver exists
$stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
$stmt->execute([$driverId]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$driver) {
    die("Driver not found!");
}

echo "<h3>Driver Info:</h3>";
echo "<pre>";
print_r([
    'id' => $driver['id'],
    'name' => $driver['first_name'] . ' ' . $driver['last_name'],
    'available_for_work' => $driver['available_for_work'],
    'city' => $driver['city']
]);
echo "</pre>";

// Check matching_scores table
echo "<h3>Matching Scores:</h3>";
$stmt = $pdo->prepare("
    SELECT 
        ms.*,
        j.title as job_title,
        j.is_active,
        c.company_name
    FROM matching_scores ms
    JOIN job_listings j ON ms.job_id = j.id
    LEFT JOIN companies c ON j.company_id = c.id
    WHERE ms.driver_id = ?
    ORDER BY ms.overall_score DESC
    LIMIT 10
");
$stmt->execute([$driverId]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($matches)) {
    echo "<p style='color: red;'>No matches found in matching_scores table!</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Job ID</th><th>Job Title</th><th>Company</th><th>Score</th><th>Active</th><th>Updated</th></tr>";
    foreach ($matches as $match) {
        echo "<tr>";
        echo "<td>{$match['job_id']}</td>";
        echo "<td>{$match['job_title']}</td>";
        echo "<td>{$match['company_name']}</td>";
        echo "<td>" . number_format($match['overall_score'], 2) . "</td>";
        echo "<td>" . ($match['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$match['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check active job listings
echo "<h3>Active Job Listings:</h3>";
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM job_listings
");
$stmt->execute();
$jobStats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Total Jobs: {$jobStats['total']}, Active: {$jobStats['active']}</p>";

// Check if MatchingService can find matches
echo "<h3>Testing MatchingService:</h3>";
try {
    $matchingService = new \Drivejob\Services\AI\MatchingService();
    $topMatches = $matchingService->getTopMatchesForDriver($driverId, 5);

    if (empty($topMatches)) {
        echo "<p style='color: red;'>MatchingService returned no matches!</p>";
    } else {
        echo "<p style='color: green;'>MatchingService found " . count($topMatches) . " matches</p>";
        foreach ($topMatches as $match) {
            echo "<p>- {$match['job']['title']} (Score: " . number_format($match['score'], 2) . ")</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check driver features
echo "<h3>Driver Features:</h3>";
try {
    $featureExtractor = new \Drivejob\Services\AI\FeatureExtractor();
    $features = $featureExtractor->extractDriverFeatures($driverId);
    echo "<pre>";
    print_r($features);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error extracting features: " . $e->getMessage() . "</p>";
}
