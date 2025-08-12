<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drivejob;charset=utf8', 'root', '');

    echo "=== ΕΛΕΓΧΟΣ MATCHES ΣΤΗ ΒΑΣΗ ===\n\n";

    // Check job_matches table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_matches");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Συνολικά matches στον πίνακα job_matches: {$count['count']}\n\n";

    if ($count['count'] > 0) {
        // Show matches for driver 26
        $stmt = $pdo->prepare("
            SELECT jm.*, jl.title, jl.location, c.company_name 
            FROM job_matches jm 
            LEFT JOIN job_listings jl ON jm.company_listing_id = jl.id 
            LEFT JOIN companies c ON jl.company_id = c.id
            WHERE jm.driver_id = 26
            ORDER BY jm.match_score DESC
        ");
        $stmt->execute();
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Matches για driver ID 26:\n";
        foreach ($matches as $match) {
            echo "- Match ID: {$match['id']}\n";
            echo "  Job ID: {$match['company_listing_id']}\n";
            echo "  Title: " . ($match['title'] ?? 'NULL') . "\n";
            echo "  Company: " . ($match['company_name'] ?? 'NULL') . "\n";
            echo "  Location: " . ($match['location'] ?? 'NULL') . "\n";
            echo "  Score: {$match['match_score']}\n";
            echo "  Created: {$match['created_at']}\n\n";
        }

        // Check if job_listings exist
        echo "Έλεγχος job_listings:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_listings WHERE status = 'active'");
        $jobCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Ενεργές αγγελίες: {$jobCount['count']}\n\n";

        // Check specific job listings that should match
        $stmt = $pdo->query("
            SELECT id, title, company_id, status, is_active, is_approved 
            FROM job_listings 
            WHERE id IN (2, 15, 16, 19, 20, 21)
        ");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Συγκεκριμένες αγγελίες:\n";
        foreach ($jobs as $job) {
            echo "- Job ID: {$job['id']}\n";
            echo "  Title: {$job['title']}\n";
            echo "  Company ID: {$job['company_id']}\n";
            echo "  Status: {$job['status']}\n";
            echo "  Is Active: " . ($job['is_active'] ?? 'NULL') . "\n";
            echo "  Is Approved: " . ($job['is_approved'] ?? 'NULL') . "\n\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
