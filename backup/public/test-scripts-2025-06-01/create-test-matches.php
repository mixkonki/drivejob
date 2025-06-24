<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AI\MatchingService;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Creating Test Matches for Driver 26</h2>";

try {
    // Get driver 26
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = 26");
    $stmt->execute();
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        die("Driver 26 not found!");
    }

    echo "<p>Driver: {$driver['first_name']} {$driver['last_name']}</p>";

    // Get active job listings
    $stmt = $pdo->query("
        SELECT j.*, c.company_name, c.city as company_city 
        FROM job_listings j
        LEFT JOIN companies c ON j.company_id = c.id
        WHERE j.is_active = 1
        LIMIT 10
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Found " . count($jobs) . " active jobs</p>";

    if (count($jobs) == 0) {
        // Create some test jobs
        echo "<h3>Creating test jobs...</h3>";

        $testJobs = [
            [
                'title' => 'Οδηγός Φορτηγού - Αθήνα',
                'description' => 'Ζητείται έμπειρος οδηγός φορτηγού για διανομές εντός Αττικής',
                'location' => 'Αθήνα',
                'required_license' => 'C',
                'employment_type' => 'full_time',
                'salary_min' => 1200,
                'salary_max' => 1500,
                'is_urgent' => 1
            ],
            [
                'title' => 'Οδηγός Λεωφορείου - Θεσσαλονίκη',
                'description' => 'Ζητείται οδηγός λεωφορείου για αστικές συγκοινωνίες',
                'location' => 'Θεσσαλονίκη',
                'required_license' => 'D',
                'employment_type' => 'full_time',
                'salary_min' => 1300,
                'salary_max' => 1600,
                'is_urgent' => 0
            ],
            [
                'title' => 'Οδηγός Ταξί - Πάτρα',
                'description' => 'Ζητείται οδηγός ταξί με εμπειρία',
                'location' => 'Πάτρα',
                'required_license' => 'B',
                'employment_type' => 'flexible',
                'salary_min' => 1000,
                'salary_max' => 1400,
                'is_urgent' => 1
            ]
        ];

        // Get first company or create one
        $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            // Create a test company
            $stmt = $pdo->prepare("
                INSERT INTO companies (company_name, email, phone, city, country, vat_number)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'Test Transport Company',
                'test@company.com',
                '2101234567',
                'Αθήνα',
                'Ελλάδα',
                '123456789'
            ]);
            $companyId = $pdo->lastInsertId();
        } else {
            $companyId = $company['id'];
        }

        foreach ($testJobs as $job) {
            $stmt = $pdo->prepare("
                INSERT INTO job_listings 
                (company_id, title, description, location, required_license, 
                 employment_type, salary_min, salary_max, is_urgent, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
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

        // Re-fetch jobs
        $stmt = $pdo->query("
            SELECT j.*, c.company_name, c.city as company_city 
            FROM job_listings j
            LEFT JOIN companies c ON j.company_id = c.id
            WHERE j.is_active = 1
            LIMIT 10
        ");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate matches using MatchingService
    echo "<h3>Calculating matches...</h3>";
    $matchingService = new MatchingService();

    foreach ($jobs as $job) {
        try {
            $result = $matchingService->calculateMatch(26, $job['id']);
            if ($result['success']) {
                echo "<p>✓ Match calculated for job '{$job['title']}' - Score: " .
                    number_format($result['overall_score'] * 100, 1) . "%</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error for job '{$job['title']}': " . $e->getMessage() . "</p>";
        }
    }

    // Test the API
    echo "<h3>Testing API endpoint...</h3>";
    echo "<p><a href='" . BASE_URL . "api/matching/driver/matches?limit=5' target='_blank'>Test API Endpoint</a></p>";

    // Show matches
    $topMatches = $matchingService->getTopMatchesForDriver(26, 5);
    echo "<h3>Top 5 Matches:</h3>";
    if (empty($topMatches)) {
        echo "<p style='color: red;'>No matches found!</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Job Title</th><th>Company</th><th>Location</th><th>Score</th></tr>";
        foreach ($topMatches as $match) {
            echo "<tr>";
            echo "<td>{$match['job']['title']}</td>";
            echo "<td>{$match['job']['company_name']}</td>";
            echo "<td>{$match['job']['location']}</td>";
            echo "<td>" . number_format($match['score'] * 100, 1) . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
