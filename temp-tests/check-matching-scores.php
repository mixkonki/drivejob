<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΈΛΕΓΧΟΣ MATCHING SCORES ===\n\n";

    // Έλεγχος συνολικών matching scores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM matching_scores");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Συνολικά matching scores: " . $total['total'] . "\n\n";

    // Έλεγχος κατανομής scores
    $stmt = $pdo->query("
        SELECT 
            ROUND(overall_score, 1) as score_range,
            COUNT(*) as count
        FROM matching_scores 
        GROUP BY ROUND(overall_score, 1)
        ORDER BY score_range DESC
        LIMIT 10
    ");
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Κατανομή scores:\n";
    foreach ($distribution as $row) {
        echo "Score {$row['score_range']}%: {$row['count']} ταιριάσματα\n";
    }
    echo "\n";

    // Δείγμα matching scores
    $stmt = $pdo->query("
        SELECT 
            driver_id, 
            job_id, 
            overall_score,
            skill_match_score,
            location_match_score,
            experience_match_score,
            availability_match_score
        FROM matching_scores 
        ORDER BY overall_score DESC
        LIMIT 5
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Δείγμα καλύτερων scores:\n";
    foreach ($samples as $row) {
        echo "Driver {$row['driver_id']} -> Job {$row['job_id']}: {$row['overall_score']}% ";
        echo "(Skills: {$row['skill_match_score']}, Location: {$row['location_match_score']}, ";
        echo "Experience: {$row['experience_match_score']}, Availability: {$row['availability_match_score']})\n";
    }
    echo "\n";

    // Έλεγχος για τον συγκεκριμένο οδηγό
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, d.id as driver_id 
        FROM users u 
        JOIN drivers d ON u.id = d.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute(['kostas.michailidis@hotmail.gr']);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        echo "Οδηγός kostas.michailidis@hotmail.gr:\n";
        echo "User ID: {$driver['id']}, Driver ID: {$driver['driver_id']}\n\n";

        // Έλεγχος των scores του
        $stmt = $pdo->prepare("
            SELECT 
                job_id,
                overall_score,
                skill_match_score,
                location_match_score,
                experience_match_score,
                availability_match_score
            FROM matching_scores 
            WHERE driver_id = ?
            ORDER BY overall_score DESC
            LIMIT 5
        ");
        $stmt->execute([$driver['driver_id']]);
        $driverScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Scores για τον οδηγό:\n";
        foreach ($driverScores as $row) {
            echo "Job {$row['job_id']}: {$row['overall_score']}% ";
            echo "(Skills: {$row['skill_match_score']}, Location: {$row['location_match_score']}, ";
            echo "Experience: {$row['experience_match_score']}, Availability: {$row['availability_match_score']})\n";
        }
    } else {
        echo "Δεν βρέθηκε οδηγός με email kostas.michailidis@hotmail.gr\n";
    }

    echo "\n";

    // Έλεγχος για την εταιρία
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, c.id as company_id 
        FROM users u 
        JOIN companies c ON u.id = c.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute(['info@thessdrive.gr']);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        echo "Εταιρία info@thessdrive.gr:\n";
        echo "User ID: {$company['id']}, Company ID: {$company['company_id']}\n\n";

        // Έλεγχος των job listings της εταιρίας
        $stmt = $pdo->prepare("
            SELECT id, title, is_active, created_at
            FROM job_listings 
            WHERE company_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$company['company_id']]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Job listings της εταιρίας:\n";
        foreach ($jobs as $job) {
            echo "Job {$job['id']}: {$job['title']} (Active: " . ($job['is_active'] ? 'Yes' : 'No') . ")\n";

            // Έλεγχος scores για αυτό το job
            $stmt2 = $pdo->prepare("
                SELECT COUNT(*) as match_count, AVG(overall_score) as avg_score
                FROM matching_scores 
                WHERE job_id = ?
            ");
            $stmt2->execute([$job['id']]);
            $jobStats = $stmt2->fetch(PDO::FETCH_ASSOC);

            echo "  Matches: {$jobStats['match_count']}, Avg Score: " . round($jobStats['avg_score'], 2) . "%\n";
        }
    } else {
        echo "Δεν βρέθηκε εταιρία με email info@thessdrive.gr\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
