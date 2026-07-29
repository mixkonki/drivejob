<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

Session::start();

$pdo = Database::getInstance()->getConnection();

echo "<h1>Τελικός Έλεγχος Όλων των Προβλημάτων</h1>";

// 1. Check companies/messages
echo "<h2>1. Companies Messages</h2>";
try {
    // Test query for messages
    $stmt = $pdo->query("SELECT message FROM messages LIMIT 1");
    echo "<p>✅ Messages table χρησιμοποιεί σωστά το πεδίο 'message'</p>";
    echo "<p><a href='" . BASE_URL . "companies/messages' target='_blank'>Test Companies Messages</a></p>";
} catch (Exception $e) {
    echo "<p>❌ Πρόβλημα με messages table: " . $e->getMessage() . "</p>";
}

// 2. Check drivers/search
echo "<h2>2. Drivers Search</h2>";
$controllerPath = ROOT_DIR . '/src/Controllers/Driver/DriversController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    if (strpos($content, 'public function search') !== false) {
        echo "<p>✅ DriversController έχει search method</p>";
        echo "<p><a href='" . BASE_URL . "drivers/search' target='_blank'>Test Drivers Search</a></p>";
    } else {
        echo "<p>❌ DriversController δεν έχει search method</p>";
    }
}

// 3. Check job-listings/create
echo "<h2>3. Job Listings Create</h2>";
if (file_exists(ROOT_DIR . '/public/job-listings/create.php')) {
    echo "<p>✅ Υπάρχει job-listings/create.php</p>";
    echo "<p><a href='" . BASE_URL . "job-listings/create' target='_blank'>Test Job Create</a></p>";
} else {
    echo "<p>❌ Λείπει το job-listings/create.php</p>";
}

// 4. Check company profile candidates tab
echo "<h2>4. Company Profile - Candidates Tab</h2>";
$widgetPath = ROOT_DIR . '/src/Views/companies/partials/candidates-widget-with-messaging.php';
if (file_exists($widgetPath)) {
    $content = file_get_contents($widgetPath);
    if (strpos($content, 'try {') !== false) {
        echo "<p>✅ Candidates widget έχει error handling</p>";
    } else {
        echo "<p>❌ Candidates widget χρειάζεται error handling</p>";
    }
}

// Test if company has jobs for candidates
if (Session::get('user_role') === 'company') {
    $companyId = Session::get('user_id');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_listings WHERE company_id = ? AND is_active = 1");
    $stmt->execute([$companyId]);
    $jobCount = $stmt->fetchColumn();
    echo "<p>Company has $jobCount active jobs</p>";
}

// 5. Check company profile layout
echo "<h2>5. Company Profile Layout</h2>";
$profilePath = ROOT_DIR . '/src/Views/companies/company-profile.php';
if (file_exists($profilePath)) {
    $content = file_get_contents($profilePath);
    if (strpos($content, 'profile-sidebar') !== false) {
        echo "<p>✅ Company profile έχει sidebar layout</p>";
        echo "<p><a href='" . BASE_URL . "companies/profile' target='_blank'>Test Company Profile</a></p>";
    } else {
        echo "<p>❌ Company profile δεν έχει sidebar layout</p>";
    }
}

// 6. Check driver profile widgets
echo "<h2>6. Driver Profile Widgets</h2>";
$driverProfilePath = ROOT_DIR . '/src/Views/drivers/driver-profile.php';
if (file_exists($driverProfilePath)) {
    $content = file_get_contents($driverProfilePath);

    $hasMatchingWidget = strpos($content, 'matching-widget.php') !== false;
    $hasMessagesWidget = strpos($content, 'messages-widget.php') !== false;

    echo "<p>" . ($hasMatchingWidget ? "✅" : "❌") . " Matching Widget</p>";
    echo "<p>" . ($hasMessagesWidget ? "✅" : "❌") . " Messages Widget</p>";

    if ($hasMatchingWidget && $hasMessagesWidget) {
        echo "<p><a href='" . BASE_URL . "drivers/profile' target='_blank'>Test Driver Profile</a></p>";
    }
}

// 7. Check conversation routing
echo "<h2>7. Conversation Routing</h2>";
if (file_exists(ROOT_DIR . '/public/companies/conversation.php')) {
    echo "<p>✅ Companies conversation.php exists</p>";
} else {
    echo "<p>❌ Companies conversation.php missing</p>";
}

// Summary
echo "<h2>Συνολική Κατάσταση</h2>";
echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
echo "<h3>Test Scenarios:</h3>";
echo "<ol>";
echo "<li><strong>Company Messages:</strong> Login ως εταιρεία → <a href='" . BASE_URL . "companies/messages'>Messages</a> → Κλικ σε μήνυμα</li>";
echo "<li><strong>Driver Search:</strong> <a href='" . BASE_URL . "drivers/search'>Αναζήτηση Οδηγών</a></li>";
echo "<li><strong>Create Job:</strong> Login ως εταιρεία → <a href='" . BASE_URL . "job-listings/create'>Δημιουργία Αγγελίας</a></li>";
echo "<li><strong>Company Profile:</strong> <a href='" . BASE_URL . "companies/profile'>Company Profile</a> → Tab 'Υποψήφιοι'</li>";
echo "<li><strong>Driver Profile:</strong> Login ως οδηγός → <a href='" . BASE_URL . "drivers/profile'>Driver Profile</a> → Δείτε widgets</li>";
echo "</ol>";
echo "</div>";

// Test data info
echo "<h2>Test Data</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";

// Get test accounts
$stmt = $pdo->query("SELECT id, email, company_name FROM companies WHERE email LIKE '%test%' LIMIT 1");
$testCompany = $stmt->fetch(PDO::FETCH_ASSOC);
if ($testCompany) {
    echo "<p><strong>Test Company:</strong> {$testCompany['email']} / 123456</p>";
}

$stmt = $pdo->query("SELECT d.id, d.email, d.first_name, d.last_name FROM drivers d WHERE d.is_active = 1 LIMIT 1");
$testDriver = $stmt->fetch(PDO::FETCH_ASSOC);
if ($testDriver) {
    echo "<p><strong>Test Driver:</strong> {$testDriver['email']}</p>";
}

// Check matches
$stmt = $pdo->query("SELECT COUNT(*) FROM matching_scores");
$matchCount = $stmt->fetchColumn();
echo "<p><strong>Total Matches:</strong> $matchCount</p>";

// Check conversations
$stmt = $pdo->query("SELECT COUNT(*) FROM conversations");
$convCount = $stmt->fetchColumn();
echo "<p><strong>Total Conversations:</strong> $convCount</p>";

echo "</div>";
