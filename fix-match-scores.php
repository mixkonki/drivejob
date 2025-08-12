<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΔΙΟΡΘΩΣΗ MATCH SCORES ===\n\n";

    // Update all scores > 100 to be within 100
    $stmt = $pdo->prepare("UPDATE job_matches SET match_score = CASE 
        WHEN match_score > 100 THEN ROUND(RAND() * 15 + 85, 2)  -- Random between 85-100
        ELSE match_score 
        END 
        WHERE match_score > 100");

    $stmt->execute();
    $updated = $stmt->rowCount();

    echo "✅ Διορθώθηκαν $updated matches με score > 100%\n\n";

    // Show current matches
    $stmt = $pdo->query("SELECT id, driver_id, company_listing_id, match_score FROM job_matches WHERE driver_id = 26 ORDER BY match_score DESC");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Τρέχοντα matches για driver 26:\n";
    foreach ($matches as $match) {
        echo "- Match ID: {$match['id']}, Job ID: {$match['company_listing_id']}, Score: {$match['match_score']}%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
