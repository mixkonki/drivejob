<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

Session::start();

$pdo = Database::getInstance()->getConnection();

echo "<h1>Διόρθωση Driver Profile Widgets</h1>";

// Get driver ID for testing
$driverId = null;
if (Session::get('user_role') === 'driver') {
    $driverId = Session::get('user_id');
} else {
    // Get first driver for testing
    $stmt = $pdo->query("SELECT id FROM drivers WHERE is_active = 1 LIMIT 1");
    $driverId = $stmt->fetchColumn();
}

echo "<h2>Έλεγχος για Driver ID: $driverId</h2>";

// 1. Check and create matches
echo "<h3>1. Δημιουργία Matches</h3>";

// Get active jobs
$stmt = $pdo->query("SELECT id, title FROM job_listings WHERE is_active = 1 LIMIT 10");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$createdMatches = 0;
foreach ($jobs as $job) {
    // Check if match exists
    $stmt = $pdo->prepare("SELECT id FROM matching_scores WHERE driver_id = ? AND job_id = ?");
    $stmt->execute([$driverId, $job['id']]);

    if (!$stmt->fetch()) {
        // Create match with random score
        $score = rand(60, 95) / 100;
        $stmt = $pdo->prepare("
            INSERT INTO matching_scores 
            (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
             experience_match_score, availability_match_score, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $driverId,
            $job['id'],
            $score,
            $score,
            $score,
            $score,
            $score
        ]);
        $createdMatches++;
        echo "<p>✓ Created match for job '{$job['title']}' with score " . ($score * 100) . "%</p>";
    }
}

if ($createdMatches === 0) {
    echo "<p>Driver already has matches</p>";
}

// 2. Check matching widget
echo "<h3>2. Έλεγχος Matching Widget</h3>";

$widgetPath = ROOT_DIR . '/src/Views/drivers/partials/matching-widget.php';
if (file_exists($widgetPath)) {
    echo "<p>✓ Matching widget exists</p>";

    // Check if it has the correct API endpoint
    $content = file_get_contents($widgetPath);
    if (strpos($content, 'api/matching/driver/matches') !== false) {
        echo "<p>✓ Widget uses correct API endpoint</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Widget may not use correct API endpoint</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Matching widget missing!</p>";
}

// 3. Check messages widget
echo "<h3>3. Έλεγχος Messages Widget</h3>";

// Create test conversation if none exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM conversations WHERE driver_id = ?");
$stmt->execute([$driverId]);
$convCount = $stmt->fetchColumn();

if ($convCount == 0) {
    // Get a company and job
    $stmt = $pdo->query("
        SELECT c.id as company_id, j.id as job_id, j.title 
        FROM companies c 
        JOIN job_listings j ON j.company_id = c.id 
        WHERE c.is_active = 1 AND j.is_active = 1 
        LIMIT 1
    ");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Create conversation
        $stmt = $pdo->prepare("
            INSERT INTO conversations 
            (driver_id, company_id, job_id, subject, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $driverId,
            $data['company_id'],
            $data['job_id'],
            "Ενδιαφέρον για τη θέση: " . $data['title']
        ]);
        $conversationId = $pdo->lastInsertId();

        // Add a test message
        $stmt = $pdo->prepare("
            INSERT INTO messages 
            (conversation_id, sender_type, sender_id, message, is_read, created_at)
            VALUES (?, 'company', ?, 'Γεια σας! Είδαμε το προφίλ σας και θα θέλαμε να συζητήσουμε για τη θέση.', 0, NOW())
        ");
        $stmt->execute([$conversationId, $data['company_id']]);

        echo "<p>✓ Created test conversation and message</p>";
    }
} else {
    echo "<p>✓ Driver has $convCount conversations</p>";
}

// 4. Check driver profile view
echo "<h3>4. Έλεγχος Driver Profile View</h3>";

$profilePath = ROOT_DIR . '/src/Views/drivers/driver-profile.php';
if (file_exists($profilePath)) {
    $content = file_get_contents($profilePath);

    // Check for widgets
    $checks = [
        'matching-widget.php' => 'Matching Widget',
        'messages-widget' => 'Messages Widget',
        'AI Προτάσεις' => 'AI Suggestions Section'
    ];

    foreach ($checks as $search => $name) {
        if (strpos($content, $search) !== false) {
            echo "<p>✓ $name is included</p>";
        } else {
            echo "<p style='color: orange;'>⚠ $name may be missing</p>";
        }
    }
}

// 5. Test API endpoints
echo "<h3>5. Test API Endpoints</h3>";

// Test matches API
$apiUrl = BASE_URL . "api/matching/driver/matches?driver_id=$driverId&limit=5";
echo "<p>Testing: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";

// Summary
echo "<h2>Σύνοψη</h2>";
echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
echo "<h3>Επόμενα Βήματα:</h3>";
echo "<ol>";
echo "<li>Login ως οδηγός (Driver ID: $driverId)</li>";
echo "<li>Πηγαίνετε στο <a href='" . BASE_URL . "drivers/profile'>Driver Profile</a></li>";
echo "<li>Ελέγξτε αν εμφανίζονται:</li>";
echo "<ul>";
echo "<li>AI Προτάσεις Εργασίας (matching widget)</li>";
echo "<li>Μηνύματα (messages widget)</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Show current matches
echo "<h3>Τρέχοντα Matches για Driver $driverId:</h3>";
$stmt = $pdo->prepare("
    SELECT j.title, ms.overall_score 
    FROM matching_scores ms
    JOIN job_listings j ON ms.job_id = j.id
    WHERE ms.driver_id = ?
    ORDER BY ms.overall_score DESC
    LIMIT 5
");
$stmt->execute([$driverId]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($matches) {
    echo "<ul>";
    foreach ($matches as $match) {
        $score = round($match['overall_score'] * 100);
        echo "<li>{$match['title']} - {$score}% match</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Δεν βρέθηκαν matches</p>";
}
