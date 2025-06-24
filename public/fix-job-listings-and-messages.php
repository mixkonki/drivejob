<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Διόρθωση Job Listings</h2>";

try {
    // Get a company to create job offers
    $stmt = $pdo->query("SELECT id, company_name FROM companies WHERE id = 2 LIMIT 1");
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        echo "<p>Δημιουργία αγγελιών για την εταιρεία: " . $company['company_name'] . "</p>";

        // Create job offers
        $jobOffers = [
            [
                'title' => 'Οδηγός Φορτηγού C+E - Διεθνείς Μεταφορές',
                'listing_type' => 'job_offer',
                'transport_type' => 'freight',
                'job_type' => 'full_time',
                'required_license' => 'C+E',
                'description' => 'Ζητείται έμπειρος οδηγός για διεθνείς μεταφορές. Απαραίτητη εμπειρία τουλάχιστον 2 ετών σε διεθνείς μεταφορές. Προσφέρουμε ανταγωνιστικό μισθό και bonus.',
                'salary_min' => 1500,
                'salary_max' => 2000,
                'salary_type' => 'monthly',
                'location' => 'Αθήνα',
                'experience_years' => 2,
                'adr_certificate' => 1,
                'status' => 'active'
            ],
            [
                'title' => 'Οδηγός Διανομής - Τοπικές Μεταφορές',
                'listing_type' => 'job_offer',
                'transport_type' => 'freight',
                'job_type' => 'full_time',
                'required_license' => 'B',
                'description' => 'Ζητείται οδηγός για διανομές εντός Αττικής. Πρωινό ωράριο 06:00-14:00. Σταθερό πρόγραμμα, Σαββατοκύριακα ελεύθερα.',
                'salary_min' => 900,
                'salary_max' => 1200,
                'salary_type' => 'monthly',
                'location' => 'Πειραιάς',
                'experience_years' => 1,
                'status' => 'active'
            ],
            [
                'title' => 'Οδηγός Λεωφορείου ΚΤΕΛ',
                'listing_type' => 'job_offer',
                'transport_type' => 'passenger',
                'job_type' => 'full_time',
                'required_license' => 'D',
                'description' => 'Ζητείται οδηγός λεωφορείου για τακτικά δρομολόγια ΚΤΕΛ. Μόνιμη θέση εργασίας με πλήρη ασφάλιση.',
                'salary_min' => 1200,
                'salary_max' => 1500,
                'salary_type' => 'monthly',
                'location' => 'Θεσσαλονίκη',
                'experience_years' => 2,
                'status' => 'active'
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO job_listings (
                company_id, title, listing_type, transport_type, job_type,
                required_license, description, salary_min, salary_max, salary_type,
                location, experience_years, adr_certificate, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($jobOffers as $job) {
            $stmt->execute([
                $company['id'],
                $job['title'],
                $job['listing_type'],
                $job['transport_type'],
                $job['job_type'],
                $job['required_license'],
                $job['description'],
                $job['salary_min'],
                $job['salary_max'],
                $job['salary_type'],
                $job['location'],
                $job['experience_years'],
                $job['adr_certificate'] ?? 0,
                $job['status']
            ]);
        }

        echo "<p>✅ Δημιουργήθηκαν " . count($jobOffers) . " νέες αγγελίες εργασίας</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Σφάλμα: " . $e->getMessage() . "</p>";
}

// Check messages display issue
echo "<hr><h2>Έλεγχος Messages Display</h2>";

// Check the messages view files
$messageFiles = [
    'companies' => __DIR__ . '/companies/messages.php',
    'drivers' => __DIR__ . '/drivers/messages.php'
];

foreach ($messageFiles as $type => $file) {
    echo "<h3>Checking $type messages file:</h3>";
    if (file_exists($file)) {
        echo "<p>✅ File exists: $file</p>";

        // Check if it's using the correct query
        $content = file_get_contents($file);
        if (strpos($content, 'LIMIT 1') !== false) {
            echo "<p>⚠️ Found LIMIT 1 in query - this needs to be removed!</p>";
        } else {
            echo "<p>✅ No LIMIT 1 found in queries</p>";
        }
    } else {
        echo "<p>❌ File not found: $file</p>";
    }
}

// Show current job listings
echo "<hr><h3>Current Active Job Offers:</h3>";
$stmt = $pdo->query("
    SELECT j.*, c.company_name 
    FROM job_listings j
    LEFT JOIN companies c ON j.company_id = c.id
    WHERE j.listing_type = 'job_offer' 
    AND j.status = 'active'
    AND j.company_id IS NOT NULL
    ORDER BY j.created_at DESC
");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
foreach ($jobs as $job) {
    echo "ID: {$job['id']} - {$job['title']} ({$job['company_name']}) - {$job['location']}\n";
}
echo "</pre>";

echo "<p><a href='/drivejob/public/job-listings/' class='btn btn-primary'>Δείτε τις αγγελίες</a></p>";
