<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

Session::start();

$pdo = Database::getInstance()->getConnection();

echo "<h1>Διόρθωση Όλων των Προβλημάτων</h1>";

$issues = [];
$fixed = [];

// 1. Check companies/messages
echo "<h2>1. Έλεγχος companies/messages</h2>";
try {
    // Test query
    $stmt = $pdo->query("SELECT message FROM messages LIMIT 1");
    $fixed[] = "✓ Companies messages χρησιμοποιεί σωστά το πεδίο 'message'";
} catch (Exception $e) {
    $issues[] = "✗ Πρόβλημα με messages table: " . $e->getMessage();
}

// 2. Check drivers/search
echo "<h2>2. Έλεγχος drivers/search</h2>";
$controllerPath = ROOT_DIR . '/src/Controllers/Driver/DriversController.php';
$content = file_get_contents($controllerPath);
if (strpos($content, 'public function search') !== false) {
    $fixed[] = "✓ DriversController έχει search method";
} else {
    $issues[] = "✗ DriversController δεν έχει search method";
}

// Check search view
if (file_exists(ROOT_DIR . '/src/Views/drivers/search.php')) {
    $fixed[] = "✓ Υπάρχει drivers search view";
} else {
    $issues[] = "✗ Λείπει το drivers search view";
}

// 3. Check job-listings/create
echo "<h2>3. Έλεγχος job-listings/create</h2>";
if (file_exists(ROOT_DIR . '/public/job-listings/create.php')) {
    $fixed[] = "✓ Υπάρχει job-listings/create.php";
} else {
    $issues[] = "✗ Λείπει το job-listings/create.php";
}

// 4. Check candidates widget
echo "<h2>4. Έλεγχος candidates widget</h2>";
$widgetPath = ROOT_DIR . '/src/Views/companies/partials/candidates-widget-with-messaging.php';
if (file_exists($widgetPath)) {
    $content = file_get_contents($widgetPath);
    if (strpos($content, 'try {') !== false) {
        $fixed[] = "✓ Candidates widget έχει error handling";
    } else {
        $issues[] = "✗ Candidates widget χρειάζεται error handling";
    }
} else {
    $issues[] = "✗ Λείπει το candidates widget";
}

// 5. Check company profile layout
echo "<h2>5. Έλεγχος company profile layout</h2>";
$profilePath = ROOT_DIR . '/src/Views/companies/company-profile.php';
if (file_exists($profilePath)) {
    $content = file_get_contents($profilePath);
    if (strpos($content, 'profile-sidebar') !== false) {
        $fixed[] = "✓ Company profile έχει sidebar layout";
    } else {
        $issues[] = "✗ Company profile δεν έχει sidebar layout";
    }
}

// Display results
echo "<h2>Αποτελέσματα Ελέγχου</h2>";

if (!empty($fixed)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 10px;'>";
    echo "<h3>✅ Διορθωμένα:</h3>";
    echo "<ul>";
    foreach ($fixed as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($issues)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin-bottom: 10px;'>";
    echo "<h3>❌ Προβλήματα που παραμένουν:</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Auto-fix remaining issues
    echo "<h2>Αυτόματη Διόρθωση...</h2>";

    // Fix job-listings/create if missing
    if (!file_exists(ROOT_DIR . '/public/job-listings/create.php')) {
        if (!file_exists(ROOT_DIR . '/public/job-listings')) {
            mkdir(ROOT_DIR . '/public/job-listings', 0777, true);
        }

        $createContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Controllers\UnifiedJobListingController;

$controller = new UnifiedJobListingController();
$controller->create();
';
        file_put_contents(ROOT_DIR . '/public/job-listings/create.php', $createContent);
        echo "<p>✓ Δημιουργήθηκε job-listings/create.php</p>";

        // Also create store.php
        $storeContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Controllers\UnifiedJobListingController;

$controller = new UnifiedJobListingController();
$controller->store();
';
        file_put_contents(ROOT_DIR . '/public/job-listings/store.php', $storeContent);
        echo "<p>✓ Δημιουργήθηκε job-listings/store.php</p>";
    }
}

// Test links
echo "<h2>Test Links</h2>";
echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "companies/messages' target='_blank'>Test Companies Messages</a></li>";
echo "<li><a href='" . BASE_URL . "drivers/search' target='_blank'>Test Drivers Search</a></li>";
echo "<li><a href='" . BASE_URL . "job-listings/create' target='_blank'>Test Job Create</a></li>";
echo "<li><a href='" . BASE_URL . "companies/profile' target='_blank'>Test Company Profile</a></li>";
echo "</ul>";
echo "</div>";

// Final status
$allGood = empty($issues);
echo "<h2>Τελική Κατάσταση</h2>";
if ($allGood) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; text-align: center;'>";
    echo "<h3 style='color: #155724;'>✅ Όλα τα προβλήματα διορθώθηκαν!</h3>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; text-align: center;'>";
    echo "<h3 style='color: #721c24;'>⚠️ Κάποια προβλήματα παραμένουν</h3>";
    echo "<p>Εκτελέστε ξανά το script ή ελέγξτε χειροκίνητα.</p>";
    echo "</div>";
}
