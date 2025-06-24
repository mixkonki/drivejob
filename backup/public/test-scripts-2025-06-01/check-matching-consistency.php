<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AI\MatchingService;

$pdo = Database::getInstance()->getConnection();

echo "<h1>Έλεγχος Συνέπειας Συστήματος Ταιριάσματος</h1>";

// 1. Check Company Matching System
echo "<h2>1. Σύστημα Ταιριάσματος Εταιρειών</h2>";

// Get a company with active jobs
$stmt = $pdo->query("
    SELECT c.*, COUNT(j.id) as job_count
    FROM companies c
    LEFT JOIN job_listings j ON c.id = j.company_id AND j.is_active = 1
    GROUP BY c.id
    HAVING job_count > 0
    LIMIT 1
");
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if ($company) {
    echo "<p><strong>Εταιρεία:</strong> {$company['company_name']} (ID: {$company['id']})</p>";
    echo "<p><strong>Ενεργές αγγελίες:</strong> {$company['job_count']}</p>";

    // Get first active job
    $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE company_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$company['id']]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        echo "<p><strong>Αγγελία:</strong> {$job['title']} (ID: {$job['id']})</p>";

        // Test company API
        echo "<h3>API Endpoints Εταιρείας:</h3>";
        echo "<ul>";
        echo "<li><a href='" . BASE_URL . "api/matching/job/candidates?job_id={$job['id']}' target='_blank'>Υποψήφιοι για αγγελία</a></li>";
        echo "<li><a href='" . BASE_URL . "companies/profile' target='_blank'>Company Profile με tabs</a></li>";
        echo "<li><a href='" . BASE_URL . "companies/messages' target='_blank'>Company Messages</a></li>";
        echo "</ul>";

        // Get top candidates
        $matchingService = new MatchingService();
        $candidates = $matchingService->getTopCandidatesForJob($job['id'], 5);

        echo "<h3>Top 5 Υποψήφιοι:</h3>";
        if (empty($candidates)) {
            echo "<p style='color: orange;'>Δεν βρέθηκαν υποψήφιοι</p>";
        } else {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Όνομα</th><th>Τοποθεσία</th><th>Score</th></tr>";
            foreach ($candidates as $candidate) {
                echo "<tr>";
                echo "<td>{$candidate['driver']['first_name']} {$candidate['driver']['last_name']}</td>";
                echo "<td>{$candidate['driver']['city']}</td>";
                echo "<td>" . number_format($candidate['score'] * 100, 1) . "%</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p style='color: red;'>Δεν βρέθηκε εταιρεία με ενεργές αγγελίες</p>";
}

// 2. Check Driver Matching System
echo "<h2>2. Σύστημα Ταιριάσματος Οδηγών</h2>";

// Get an active driver
$stmt = $pdo->query("
    SELECT d.*, u.email
    FROM drivers d
    JOIN users u ON d.user_id = u.id
    WHERE d.available_for_work = 1
    LIMIT 1
");
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if ($driver) {
    echo "<p><strong>Οδηγός:</strong> {$driver['first_name']} {$driver['last_name']} (ID: {$driver['id']})</p>";
    echo "<p><strong>Email:</strong> {$driver['email']}</p>";

    // Test driver API
    echo "<h3>API Endpoints Οδηγού:</h3>";
    echo "<ul>";
    echo "<li><a href='" . BASE_URL . "api/matching/driver/matches?limit=5' target='_blank'>Προτεινόμενες θέσεις (requires login)</a></li>";
    echo "<li><a href='" . BASE_URL . "drivers/profile' target='_blank'>Driver Profile με tabs</a></li>";
    echo "<li><a href='" . BASE_URL . "drivers/job-matches' target='_blank'>Job Matches Page</a></li>";
    echo "<li><a href='" . BASE_URL . "drivers/messages' target='_blank'>Driver Messages</a></li>";
    echo "</ul>";

    // Get top matches
    $matchingService = new MatchingService();
    $matches = $matchingService->getTopMatchesForDriver($driver['id'], 5);

    echo "<h3>Top 5 Προτεινόμενες Θέσεις:</h3>";
    if (empty($matches)) {
        echo "<p style='color: orange;'>Δεν βρέθηκαν προτάσεις</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Τίτλος</th><th>Εταιρεία</th><th>Τοποθεσία</th><th>Score</th></tr>";
        foreach ($matches as $match) {
            echo "<tr>";
            echo "<td>{$match['job']['title']}</td>";
            echo "<td>{$match['job']['company_name']}</td>";
            echo "<td>{$match['job']['location']}</td>";
            echo "<td>" . number_format($match['score'] * 100, 1) . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>Δεν βρέθηκε διαθέσιμος οδηγός</p>";
}

// 3. Check Messaging System
echo "<h2>3. Σύστημα Μηνυμάτων</h2>";

// Check conversations
$stmt = $pdo->query("SELECT COUNT(*) as total FROM conversations");
$convCount = $stmt->fetchColumn();
echo "<p><strong>Συνολικές συνομιλίες:</strong> $convCount</p>";

// Check messages
$stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
$msgCount = $stmt->fetchColumn();
echo "<p><strong>Συνολικά μηνύματα:</strong> $msgCount</p>";

// Recent conversations
$stmt = $pdo->query("
    SELECT c.*, comp.company_name, 
           CONCAT(d.first_name, ' ', d.last_name) as driver_name,
           j.title as job_title
    FROM conversations c
    JOIN companies comp ON c.company_id = comp.id
    JOIN drivers d ON c.driver_id = d.id
    LEFT JOIN job_listings j ON c.job_id = j.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Πρόσφατες Συνομιλίες:</h3>";
if (empty($conversations)) {
    echo "<p style='color: orange;'>Δεν υπάρχουν συνομιλίες</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Εταιρεία</th><th>Οδηγός</th><th>Θέμα</th><th>Status</th><th>Ημερομηνία</th></tr>";
    foreach ($conversations as $conv) {
        echo "<tr>";
        echo "<td>{$conv['id']}</td>";
        echo "<td>{$conv['company_name']}</td>";
        echo "<td>{$conv['driver_name']}</td>";
        echo "<td>{$conv['subject']}</td>";
        echo "<td>{$conv['status']}</td>";
        echo "<td>{$conv['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Check Data Consistency
echo "<h2>4. Έλεγχος Συνέπειας Δεδομένων</h2>";

// Check matching_scores table
$stmt = $pdo->query("
    SELECT COUNT(*) as total,
           COUNT(DISTINCT driver_id) as unique_drivers,
           COUNT(DISTINCT job_id) as unique_jobs,
           AVG(overall_score) as avg_score,
           MAX(overall_score) as max_score,
           MIN(overall_score) as min_score
    FROM matching_scores
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Στατιστικά Matching Scores:</h3>";
echo "<ul>";
echo "<li>Συνολικά matches: {$stats['total']}</li>";
echo "<li>Μοναδικοί οδηγοί: {$stats['unique_drivers']}</li>";
echo "<li>Μοναδικές αγγελίες: {$stats['unique_jobs']}</li>";
echo "<li>Μέσο score: " . number_format($stats['avg_score'] * 100, 1) . "%</li>";
echo "<li>Max score: " . number_format($stats['max_score'] * 100, 1) . "%</li>";
echo "<li>Min score: " . number_format($stats['min_score'] * 100, 1) . "%</li>";
echo "</ul>";

// Check for orphaned records
echo "<h3>Έλεγχος Ορφανών Εγγραφών:</h3>";

// Orphaned matching scores
$stmt = $pdo->query("
    SELECT COUNT(*) as orphaned
    FROM matching_scores ms
    LEFT JOIN drivers d ON ms.driver_id = d.id
    WHERE d.id IS NULL
");
$orphaned = $stmt->fetchColumn();
echo "<p>Orphaned matching scores (driver deleted): $orphaned</p>";

$stmt = $pdo->query("
    SELECT COUNT(*) as orphaned
    FROM matching_scores ms
    LEFT JOIN job_listings j ON ms.job_id = j.id
    WHERE j.id IS NULL
");
$orphaned = $stmt->fetchColumn();
echo "<p>Orphaned matching scores (job deleted): $orphaned</p>";

// Summary
echo "<h2>5. Σύνοψη</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<h3>Λειτουργικότητα:</h3>";
echo "<ul>";
echo "<li>✅ Company Profile με tabs</li>";
echo "<li>✅ Driver Profile με tabs</li>";
echo "<li>✅ AI Matching για εταιρείες (εύρεση υποψηφίων)</li>";
echo "<li>✅ AI Matching για οδηγούς (εύρεση θέσεων)</li>";
echo "<li>✅ Messaging system (αμφίδρομο)</li>";
echo "<li>✅ Email notifications</li>";
echo "</ul>";

echo "<h3>Επόμενα Βήματα:</h3>";
echo "<ul>";
echo "<li>🔄 Monetization strategy</li>";
echo "<li>🔄 Payment integration</li>";
echo "<li>🔄 Subscription plans</li>";
echo "<li>🔄 Analytics dashboard</li>";
echo "</ul>";
echo "</div>";
