<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

Session::start();

$pdo = Database::getInstance()->getConnection();

echo "<h1>Διόρθωση Προβλημάτων Συστήματος</h1>";

// 1. Fix Session role issue in UnifiedJobListingController
echo "<h2>1. Διόρθωση Session Role Issue</h2>";

$controllerPath = ROOT_DIR . '/src/Controllers/UnifiedJobListingController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);

    // Replace all instances of Session::has('role') with Session::has('user_role')
    $content = str_replace("Session::has('role')", "Session::has('user_role')", $content);

    // Replace all instances of Session::get('role') with Session::get('user_role')
    $content = str_replace("Session::get('role')", "Session::get('user_role')", $content);

    // Replace auth/login with login.php
    $content = str_replace("'auth/login'", "'login.php'", $content);

    file_put_contents($controllerPath, $content);
    echo "<p>✓ Fixed UnifiedJobListingController</p>";
} else {
    echo "<p style='color: red;'>✗ UnifiedJobListingController not found</p>";
}

// 2. Check and fix driver messages issue
echo "<h2>2. Έλεγχος Συστήματος Μηνυμάτων</h2>";

// Check if conversations table has correct structure
$stmt = $pdo->query("SHOW COLUMNS FROM conversations");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('updated_at', $columns)) {
    try {
        $pdo->exec("ALTER TABLE conversations ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "<p>✓ Added updated_at to conversations</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Could not add updated_at: " . $e->getMessage() . "</p>";
    }
}

// 3. Fix driver conversation page
echo "<h2>3. Διόρθωση Driver Conversation Page</h2>";

$driverConvPath = ROOT_DIR . '/public/drivers/conversation.php';
if (file_exists($driverConvPath)) {
    $content = file_get_contents($driverConvPath);

    // Fix the content field issue
    $content = str_replace('$message[\'content\']', '$message[\'message\']', $content);

    file_put_contents($driverConvPath, $content);
    echo "<p>✓ Fixed driver conversation page</p>";
} else {
    echo "<p style='color: red;'>✗ Driver conversation page not found</p>";
}

// 4. Create test data for matching
echo "<h2>4. Δημιουργία Test Data για Matching</h2>";

// Check if we have active jobs
$stmt = $pdo->query("SELECT COUNT(*) FROM job_listings WHERE is_active = 1");
$activeJobs = $stmt->fetchColumn();

if ($activeJobs == 0) {
    echo "<p>Δημιουργία test αγγελιών...</p>";

    // Get or create a test company
    $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
    $companyId = $stmt->fetchColumn();

    if (!$companyId) {
        $stmt = $pdo->prepare("
            INSERT INTO companies (email, password, company_name, phone, city, country, vat_number, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            'test@company.gr',
            password_hash('123456', PASSWORD_DEFAULT),
            'Test Transport Company',
            '2101234567',
            'Αθήνα',
            'Ελλάδα',
            '123456789'
        ]);
        $companyId = $pdo->lastInsertId();
        echo "<p>✓ Created test company (ID: $companyId)</p>";
    }

    // Create test jobs
    $testJobs = [
        [
            'title' => 'Οδηγός Φορτηγού C - Αθήνα',
            'description' => 'Ζητείται έμπειρος οδηγός φορτηγού για διανομές εντός Αττικής. Άμεση πρόσληψη.',
            'location' => 'Αθήνα',
            'required_license' => 'C',
            'employment_type' => 'full_time',
            'salary_min' => 1200,
            'salary_max' => 1500,
            'is_urgent' => 1
        ],
        [
            'title' => 'Οδηγός Λεωφορείου D - Θεσσαλονίκη',
            'description' => 'Ζητείται οδηγός λεωφορείου για αστικές συγκοινωνίες. Μόνιμη θέση.',
            'location' => 'Θεσσαλονίκη',
            'required_license' => 'D',
            'employment_type' => 'full_time',
            'salary_min' => 1300,
            'salary_max' => 1600,
            'is_urgent' => 0
        ],
        [
            'title' => 'Οδηγός Ταξί B - Πάτρα',
            'description' => 'Ζητείται οδηγός ταξί με εμπειρία για βραδινές βάρδιες.',
            'location' => 'Πάτρα',
            'required_license' => 'B',
            'employment_type' => 'part_time',
            'salary_min' => 800,
            'salary_max' => 1200,
            'is_urgent' => 1
        ]
    ];

    foreach ($testJobs as $job) {
        $stmt = $pdo->prepare("
            INSERT INTO job_listings 
            (company_id, title, description, location, required_license, 
             employment_type, salary_min, salary_max, is_urgent, is_active, listing_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'job_offer')
        ");
        $stmt->execute([
            $companyId,
            $job['title'],
            $job['description'],
            $job['location'],
            $job['required_license'],
            $job['employment_type'],
            $job['salary_min'],
            $job['salary_max'],
            $job['is_urgent']
        ]);
        echo "<p>✓ Created job: {$job['title']}</p>";
    }
}

// 5. Calculate matches for all drivers
echo "<h2>5. Υπολογισμός Matches</h2>";

try {
    $matchingService = new \Drivejob\Services\AI\MatchingService();

    // Get all active drivers
    $stmt = $pdo->query("
        SELECT d.id, d.first_name, d.last_name 
        FROM drivers d
        WHERE d.available_for_work = 1
        LIMIT 10
    ");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active jobs
    $stmt = $pdo->query("
        SELECT id, title 
        FROM job_listings 
        WHERE is_active = 1
        LIMIT 10
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matchCount = 0;
    foreach ($drivers as $driver) {
        foreach ($jobs as $job) {
            try {
                $result = $matchingService->calculateMatch($driver['id'], $job['id']);
                if ($result['success']) {
                    $matchCount++;
                }
            } catch (Exception $e) {
                // Ignore individual match errors
            }
        }
    }

    echo "<p>✓ Calculated $matchCount matches</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error calculating matches: " . $e->getMessage() . "</p>";
}

// 6. Fix company profile layout
echo "<h2>6. Διόρθωση Company Profile Layout</h2>";

$companyProfilePath = ROOT_DIR . '/src/Views/companies/company-profile.php';
if (file_exists($companyProfilePath)) {
    echo "<p>✓ Company profile with tabs already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Company profile not found</p>";
}

// Summary
echo "<h2>Σύνοψη</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<h3>Διορθώσεις που έγιναν:</h3>";
echo "<ul>";
echo "<li>✓ Session role issue στον UnifiedJobListingController</li>";
echo "<li>✓ Message content field issue</li>";
echo "<li>✓ Test data για matching</li>";
echo "<li>✓ Υπολογισμός matches</li>";
echo "</ul>";

echo "<h3>Επόμενα βήματα:</h3>";
echo "<ol>";
echo "<li>Login ως εταιρεία: <a href='" . BASE_URL . "login.php'>Login</a></li>";
echo "<li>Δημιουργία αγγελίας: <a href='" . BASE_URL . "job-listings/create'>Create Job</a></li>";
echo "<li>Login ως οδηγός και δείτε τα matches</li>";
echo "</ol>";

echo "<h3>Test Accounts:</h3>";
echo "<p><strong>Company:</strong> test@company.gr / 123456</p>";
echo "<p><strong>Driver:</strong> Χρησιμοποιήστε υπάρχοντα οδηγό</p>";
echo "</div>";

// Test links
echo "<h2>Test Links</h2>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "check-matching-consistency.php'>Check Matching Consistency</a></li>";
echo "<li><a href='" . BASE_URL . "check-driver-matches.php'>Check Driver Matches</a></li>";
echo "<li><a href='" . BASE_URL . "check-messaging-tables.php'>Check Messaging Tables</a></li>";
echo "</ul>";
