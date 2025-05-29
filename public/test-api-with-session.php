<?php
session_start();
$_SESSION['user_id'] = 26;
$_SESSION['user_role'] = 'driver';
$_SESSION['user_type'] = 'drivers';
$_SESSION['user_name'] = 'Test Driver';

// Call the API endpoint
$ch = curl_init('http://localhost/drivejob/public/api/matching/driver/matches?limit=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

?>
<!DOCTYPE html>
<html>

<head>
    <title>API Test with Session</title>
    <style>
        pre {
            background: #f4f4f4;
            padding: 10px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <h1>API Test with Session</h1>

    <h2>Session Data:</h2>
    <pre><?php print_r($_SESSION); ?></pre>

    <h2>API Response (HTTP <?php echo $httpCode; ?>):</h2>
    <pre class="<?php echo $httpCode == 200 ? 'success' : 'error'; ?>">
<?php
if ($response) {
    $json = json_decode($response, true);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo htmlspecialchars($response);
    }
} else {
    echo "No response received";
}
?>
    </pre>

    <h2>Direct Database Query:</h2>
    <?php
    require_once '../src/bootstrap.php';
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check matching_scores table
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total, 
               COUNT(CASE WHEN driver_id = 26 THEN 1 END) as driver_26_matches
        FROM matching_scores
    ");
    $stmt->execute();
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<p>Total matches in database: <strong>{$counts['total']}</strong></p>";
    echo "<p>Matches for Driver 26: <strong>{$counts['driver_26_matches']}</strong></p>";

    // Get sample matches for driver 26
    $stmt = $pdo->prepare("
        SELECT 
            ms.job_id,
            ms.overall_score,
            jl.title,
            jl.is_active
        FROM matching_scores ms
        LEFT JOIN job_listings jl ON ms.job_id = jl.id
        WHERE ms.driver_id = 26
        ORDER BY ms.overall_score DESC
        LIMIT 5
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($matches) {
        echo "<h3>Top 5 Matches for Driver 26:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Job ID</th><th>Title</th><th>Score</th><th>Active</th></tr>";
        foreach ($matches as $match) {
            echo "<tr>";
            echo "<td>{$match['job_id']}</td>";
            echo "<td>" . htmlspecialchars($match['title'] ?? 'N/A') . "</td>";
            echo "<td>" . round($match['overall_score'] * 100) . "%</td>";
            echo "<td>" . ($match['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>

    <p><a href="test-widget-clean.php">Back to Widget Test</a></p>
</body>

</html>