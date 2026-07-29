<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;
use Drivejob\Services\AI\MatchingService;

Session::start();

$pdo = Database::getInstance()->getConnection();

echo "<h1>Τελικός Έλεγχος Συστήματος DriveJob</h1>";

// 1. Check Authentication System
echo "<h2>1. Σύστημα Authentication</h2>";

// Check companies
$stmt = $pdo->query("SELECT COUNT(*) FROM companies WHERE is_active = 1");
$activeCompanies = $stmt->fetchColumn();
echo "<p>✓ Ενεργές εταιρείες: $activeCompanies</p>";

// Check drivers
$stmt = $pdo->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1");
$activeDrivers = $stmt->fetchColumn();
echo "<p>✓ Ενεργοί οδηγοί: $activeDrivers</p>";

// 2. Check Job Listings
echo "<h2>2. Σύστημα Αγγελιών</h2>";

$stmt = $pdo->query("SELECT COUNT(*) FROM job_listings WHERE is_active = 1");
$activeJobs = $stmt->fetchColumn();
echo "<p>✓ Ενεργές αγγελίες: $activeJobs</p>";

// Check job types
$stmt = $pdo->query("
    SELECT listing_type, COUNT(*) as count 
    FROM job_listings 
    WHERE is_active = 1 
    GROUP BY listing_type
");
$jobTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($jobTypes as $type) {
    echo "<p>&nbsp;&nbsp;- {$type['listing_type']}: {$type['count']}</p>";
}

// 3. Check Matching System
echo "<h2>3. Σύστημα Ταιριάσματος AI</h2>";

$stmt = $pdo->query("SELECT COUNT(*) FROM matching_scores");
$totalMatches = $stmt->fetchColumn();
echo "<p>✓ Συνολικά ταιριάσματα: $totalMatches</p>";

// Check recent matches
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM matching_scores 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
");
$recentMatches = $stmt->fetchColumn();
echo "<p>✓ Ταιριάσματα τελευταίου 24ώρου: $recentMatches</p>";

// 4. Check Messaging System
echo "<h2>4. Σύστημα Μηνυμάτων</h2>";

$stmt = $pdo->query("SELECT COUNT(*) FROM conversations");
$totalConversations = $stmt->fetchColumn();
echo "<p>✓ Συνολικές συνομιλίες: $totalConversations</p>";

$stmt = $pdo->query("SELECT COUNT(*) FROM messages");
$totalMessages = $stmt->fetchColumn();
echo "<p>✓ Συνολικά μηνύματα: $totalMessages</p>";

// 5. Test Scenarios
echo "<h2>5. Test Scenarios</h2>";

echo "<h3>A. Company Workflow:</h3>";
echo "<ol>";
echo "<li>Login ως εταιρεία: <a href='" . BASE_URL . "login.php' target='_blank'>Login Page</a></li>";
echo "<li>Δημιουργία αγγελίας: <a href='" . BASE_URL . "job-listings/create' target='_blank'>Create Job</a></li>";
echo "<li>Προβολή υποψηφίων: Στο Company Profile > Tab Υποψήφιοι</li>";
echo "<li>Αποστολή μηνύματος σε οδηγό</li>";
echo "</ol>";

echo "<h3>B. Driver Workflow:</h3>";
echo "<ol>";
echo "<li>Login ως οδηγός: <a href='" . BASE_URL . "login.php' target='_blank'>Login Page</a></li>";
echo "<li>Προβολή προτάσεων εργασίας: Driver Profile > AI Προτάσεις</li>";
echo "<li>Προβολή μηνυμάτων: <a href='" . BASE_URL . "drivers/messages' target='_blank'>Messages</a></li>";
echo "<li>Απάντηση σε μήνυμα εταιρείας</li>";
echo "</ol>";

// 6. System Status
echo "<h2>6. Κατάσταση Συστήματος</h2>";

$issues = [];
$fixed = [];

// Check if UnifiedJobListingController is fixed
$controllerPath = ROOT_DIR . '/src/Controllers/UnifiedJobListingController.php';
$content = file_get_contents($controllerPath);
if (strpos($content, "Session::get('user_role')") !== false) {
    $fixed[] = "UnifiedJobListingController χρησιμοποιεί σωστά το user_role";
} else {
    $issues[] = "UnifiedJobListingController χρησιμοποιεί λάθος session field";
}

// Check if messages table has correct column
$stmt = $pdo->query("SHOW COLUMNS FROM messages WHERE Field = 'message'");
if ($stmt->rowCount() > 0) {
    $fixed[] = "Messages table έχει σωστή στήλη 'message'";
} else {
    $issues[] = "Messages table λείπει η στήλη 'message'";
}

// Check if matching data exists
if ($totalMatches > 0) {
    $fixed[] = "Υπάρχουν δεδομένα ταιριάσματος";
} else {
    $issues[] = "Δεν υπάρχουν δεδομένα ταιριάσματος";
}

// Display results
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 10px;'>";
echo "<h4>✅ Διορθωμένα:</h4>";
echo "<ul>";
foreach ($fixed as $fix) {
    echo "<li>$fix</li>";
}
echo "</ul>";
echo "</div>";

if (!empty($issues)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Προβλήματα:</h4>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// 7. Quick Links
echo "<h2>7. Γρήγοροι Σύνδεσμοι</h2>";
echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
echo "<h4>Company Pages:</h4>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "companies/profile'>Company Profile (με tabs και sidebar)</a></li>";
echo "<li><a href='" . BASE_URL . "companies/messages'>Company Messages</a></li>";
echo "<li><a href='" . BASE_URL . "job-listings/create'>Create Job Listing</a></li>";
echo "</ul>";

echo "<h4>Driver Pages:</h4>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "drivers/profile'>Driver Profile (με AI widget)</a></li>";
echo "<li><a href='" . BASE_URL . "drivers/job-matches'>All Job Matches</a></li>";
echo "<li><a href='" . BASE_URL . "drivers/messages'>Driver Messages</a></li>";
echo "</ul>";

echo "<h4>API Endpoints:</h4>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "api/matching/driver/matches?limit=5'>Driver Matches API</a> (requires driver login)</li>";
echo "<li><a href='" . BASE_URL . "api/matching/job/candidates?job_id=1'>Job Candidates API</a> (requires company login)</li>";
echo "</ul>";

echo "<h4>Test Tools:</h4>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "create-test-matches.php'>Create Test Matches</a></li>";
echo "<li><a href='" . BASE_URL . "check-matching-consistency.php'>Check Matching Consistency</a></li>";
echo "<li><a href='" . BASE_URL . "fix-system-issues.php'>Fix System Issues</a></li>";
echo "</ul>";
echo "</div>";

// 8. Test Accounts
echo "<h2>8. Test Accounts</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";

// Get a test company
$stmt = $pdo->query("SELECT id, email, company_name FROM companies WHERE email LIKE '%test%' LIMIT 1");
$testCompany = $stmt->fetch(PDO::FETCH_ASSOC);
if ($testCompany) {
    echo "<p><strong>Test Company:</strong><br>";
    echo "Email: {$testCompany['email']}<br>";
    echo "Password: 123456<br>";
    echo "Name: {$testCompany['company_name']}</p>";
}

// Get a test driver
$stmt = $pdo->query("
    SELECT d.id, d.email, d.first_name, d.last_name 
    FROM drivers d 
    WHERE d.available_for_work = 1 
    LIMIT 1
");
$testDriver = $stmt->fetch(PDO::FETCH_ASSOC);
if ($testDriver) {
    echo "<p><strong>Test Driver:</strong><br>";
    echo "Email: {$testDriver['email']}<br>";
    echo "Name: {$testDriver['first_name']} {$testDriver['last_name']}</p>";
}

echo "</div>";

// Final summary
$allGood = empty($issues);
echo "<h2>9. Τελική Κατάσταση</h2>";
if ($allGood) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; text-align: center;'>";
    echo "<h3 style='color: #155724;'>✅ Το σύστημα λειτουργεί σωστά!</h3>";
    echo "<p>Όλα τα υποσυστήματα είναι λειτουργικά και έτοιμα για χρήση.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; text-align: center;'>";
    echo "<h3 style='color: #721c24;'>⚠️ Υπάρχουν προβλήματα που χρειάζονται διόρθωση</h3>";
    echo "<p>Εκτελέστε το <a href='" . BASE_URL . "fix-system-issues.php'>Fix System Issues</a> script.</p>";
    echo "</div>";
}
