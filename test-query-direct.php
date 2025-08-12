<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drivejob;charset=utf8', 'root', '');

    echo "=== ΔΟΚΙΜΗ QUERY ΑΠΕΥΘΕΙΑΣ ===\n\n";

    $driverId = 26;

    // Test the exact query from MatchingRepository
    $query = "SELECT jm.*, jl.title, jl.description, jl.location, jl.job_type, jl.vehicle_type,
              jl.salary_min, jl.salary_max, jl.created_at as listing_created_at,
              c.company_name, c.city, c.country
              FROM job_matches jm
              JOIN job_listings jl ON jm.company_listing_id = jl.id
              JOIN companies c ON jl.company_id = c.id
              WHERE jm.driver_id = :driver_id
              ORDER BY jm.match_score DESC, jm.created_at DESC
              LIMIT 3";

    echo "Query που εκτελείται:\n$query\n\n";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['driver_id' => $driverId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Αποτελέσματα: " . count($results) . "\n\n";

    if (!empty($results)) {
        foreach ($results as $result) {
            echo "- Match ID: {$result['id']}\n";
            echo "  Job ID: {$result['company_listing_id']}\n";
            echo "  Title: {$result['title']}\n";
            echo "  Company: {$result['company_name']}\n";
            echo "  Score: {$result['match_score']}\n\n";
        }
    } else {
        echo "❌ Δεν βρέθηκαν αποτελέσματα!\n\n";

        // Let's check what's wrong
        echo "Έλεγχος βήμα-βήμα:\n\n";

        // Step 1: Check job_matches
        $stmt = $pdo->prepare("SELECT * FROM job_matches WHERE driver_id = ?");
        $stmt->execute([$driverId]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "1. Matches στον πίνακα job_matches: " . count($matches) . "\n";

        if (!empty($matches)) {
            foreach ($matches as $match) {
                echo "   - Match ID: {$match['id']}, Job ID: {$match['company_listing_id']}, Score: {$match['match_score']}\n";
            }
        }
        echo "\n";

        // Step 2: Check job_listings for these IDs
        $jobIds = array_column($matches, 'company_listing_id');
        if (!empty($jobIds)) {
            $placeholders = str_repeat('?,', count($jobIds) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, title, company_id, status FROM job_listings WHERE id IN ($placeholders)");
            $stmt->execute($jobIds);
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "2. Job listings που αντιστοιχούν: " . count($jobs) . "\n";
            foreach ($jobs as $job) {
                echo "   - Job ID: {$job['id']}, Title: {$job['title']}, Company ID: {$job['company_id']}, Status: {$job['status']}\n";
            }
            echo "\n";

            // Step 3: Check companies
            $companyIds = array_unique(array_column($jobs, 'company_id'));
            if (!empty($companyIds)) {
                $placeholders = str_repeat('?,', count($companyIds) - 1) . '?';
                $stmt = $pdo->prepare("SELECT id, company_name FROM companies WHERE id IN ($placeholders)");
                $stmt->execute($companyIds);
                $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo "3. Companies που αντιστοιχούν: " . count($companies) . "\n";
                foreach ($companies as $company) {
                    echo "   - Company ID: {$company['id']}, Name: {$company['company_name']}\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
