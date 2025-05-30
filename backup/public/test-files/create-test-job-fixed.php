<?php
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Creating Test Job Listing (Fixed) ===\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Get the test company ID
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?");
    $stmt->execute(['test@thessdrive.gr']);
    $company = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$company) {
        echo "ERROR: Test company not found!\n";
        exit;
    }

    $companyId = $company['id'];
    echo "Found company with ID: $companyId\n\n";

    // Create a test job listing with correct columns
    $stmt = $pdo->prepare("
        INSERT INTO job_listings (
            company_id, title, description, listing_type, 
            location, salary_min, salary_max, job_type, 
            required_license, experience_years, is_active, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $companyId,
        'Οδηγός Φορτηγού C+E',
        'Ζητείται έμπειρος οδηγός φορτηγού με δίπλωμα C+E για διεθνείς μεταφορές. Προσφέρουμε ανταγωνιστικό μισθό και άριστες συνθήκες εργασίας.',
        'job_offer',
        'Θεσσαλονίκη',
        1500.00,
        2000.00,
        'full_time',
        'C+E',
        3,
        1
    ]);

    $jobId = $pdo->lastInsertId();
    echo "Created job listing with ID: $jobId\n";

    // Also create some matching scores for testing
    echo "\nCreating test matching scores...\n";

    // Get some drivers
    $stmt = $pdo->query("SELECT id FROM drivers WHERE available_for_work = 1 LIMIT 5");
    $drivers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (count($drivers) > 0) {
        foreach ($drivers as $driver) {
            $score = rand(60, 95) / 100.0;

            $stmt = $pdo->prepare("
                INSERT INTO matching_scores (job_id, driver_id, overall_score, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE overall_score = VALUES(overall_score)
            ");

            $stmt->execute([$jobId, $driver['id'], $score]);
            echo "- Added matching score for driver {$driver['id']}: " . ($score * 100) . "%\n";
        }
    } else {
        echo "No available drivers found for matching scores.\n";
    }

    echo "\nJob listing created successfully!\n";
    echo "You can now see the AI candidates widget in the company profile.\n";
    echo "\nLogin with:\n";
    echo "Email: test@thessdrive.gr\n";
    echo "Password: test123\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
