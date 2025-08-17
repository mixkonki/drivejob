<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΔΙΌΡΘΩΣΗ ΤΑΙΡΙΑΣΜΑΤΩΝ ΕΤΑΙΡΙΑΣ ===\n\n";

    // Βρίσκουμε την εταιρία info@thessdrive.gr
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE email = ?");
    $stmt->execute(['info@thessdrive.gr']);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        echo "Εταιρία βρέθηκε: {$company['company_name']} (ID: {$company['id']})\n\n";

        // Έλεγχος job listings
        $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE company_id = ?");
        $stmt->execute([$company['id']]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Υπάρχοντα job listings: " . count($jobs) . "\n\n";

        if (empty($jobs)) {
            echo "Δημιουργία test job listing για την εταιρία...\n";

            // Δημιουργία ενός test job listing
            $stmt = $pdo->prepare("
                INSERT INTO job_listings 
                (company_id, title, description, location, vehicle_type, license_required, 
                 experience_years, salary_min, salary_max, job_type, is_active, status, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $company['id'],
                'Οδηγός Φορτηγού - Thessdrive',
                'Αναζητούμε έμπειρο οδηγό φορτηγού για μεταφορές στη Θεσσαλονίκη και περιφέρεια. Απαιτείται άδεια Γ κατηγορίας και τουλάχιστον 2 χρόνια εμπειρίας.',
                'Θεσσαλονίκη',
                'truck',
                'C',
                2,
                1200.00,
                1800.00,
                'full_time',
                1,
                'active',
                date('Y-m-d H:i:s', strtotime('+30 days'))
            ]);

            if ($result) {
                $jobId = $pdo->lastInsertId();
                echo "✓ Δημιουργήθηκε job listing με ID: {$jobId}\n\n";

                // Τώρα δημιουργούμε matching scores για αυτό το job
                echo "Δημιουργία matching scores για το νέο job...\n";

                // Λήψη διαθέσιμων οδηγών
                $stmt = $pdo->query("
                    SELECT id FROM drivers 
                    WHERE available_for_work = 1 
                    LIMIT 10
                ");
                $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                echo "Βρέθηκαν " . count($drivers) . " διαθέσιμοι οδηγοί\n";

                // Δημιουργία matching scores
                $matchingService = new \Drivejob\Services\AI\MatchingService();
                $created = 0;

                foreach ($drivers as $driverId) {
                    try {
                        $result = $matchingService->calculateMatch($driverId, $jobId);
                        if ($result['success']) {
                            $created++;
                        }
                    } catch (Exception $e) {
                        echo "Σφάλμα για driver {$driverId}: " . $e->getMessage() . "\n";
                    }
                }

                echo "Δημιουργήθηκαν {$created} matching scores\n\n";

                // Έλεγχος των νέων scores
                $stmt = $pdo->prepare("
                    SELECT driver_id, overall_score, skill_match_score, location_match_score
                    FROM matching_scores 
                    WHERE job_id = ?
                    ORDER BY overall_score DESC
                    LIMIT 5
                ");
                $stmt->execute([$jobId]);
                $newScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo "Καλύτερα matching scores για το νέο job:\n";
                foreach ($newScores as $score) {
                    echo "- Driver {$score['driver_id']}: {$score['overall_score']}% ";
                    echo "(Skills: {$score['skill_match_score']}, Location: {$score['location_match_score']})\n";
                }
            } else {
                echo "✗ Αποτυχία δημιουργίας job listing\n";
            }
        } else {
            echo "Η εταιρία έχει ήδη job listings:\n";
            foreach ($jobs as $job) {
                echo "- Job {$job['id']}: {$job['title']} (Active: " . ($job['is_active'] ? 'Yes' : 'No') . ")\n";
            }
        }
    } else {
        echo "Δεν βρέθηκε εταιρία με email info@thessdrive.gr\n";

        // Έλεγχος όλων των εταιριών
        $stmt = $pdo->query("SELECT id, email, company_name FROM companies LIMIT 5");
        $allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Πρώτες 5 εταιρίες:\n";
        foreach ($allCompanies as $comp) {
            echo "- {$comp['email']} -> {$comp['company_name']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
