<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AIMatchingService;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΈΛΕΓΧΟΣ ΤΑΙΡΙΑΣΜΑΤΩΝ ΕΤΑΙΡΙΑΣ ===\n\n";

    // Έλεγχος εταιρίας info@thessdrive.gr
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.email, c.id as company_id, c.company_name
        FROM users u 
        LEFT JOIN companies c ON u.id = c.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute(['info@thessdrive.gr']);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        echo "Εταιρία βρέθηκε:\n";
        echo "- User ID: {$company['user_id']}\n";
        echo "- Company ID: {$company['company_id']}\n";
        echo "- Company Name: {$company['company_name']}\n\n";

        // Έλεγχος job listings της εταιρίας
        $stmt = $pdo->prepare("
            SELECT id, title, status, is_active, expires_at, created_at
            FROM job_listings 
            WHERE company_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$company['user_id']]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Job listings της εταιρίας:\n";
        if (empty($jobs)) {
            echo "- Δεν βρέθηκαν job listings!\n\n";
            echo "ΠΡΟΒΛΗΜΑ: Η εταιρία δεν έχει job listings, γι' αυτό δεν εμφανίζονται ταιριάσματα.\n\n";
        } else {
            foreach ($jobs as $job) {
                echo "- Job {$job['id']}: {$job['title']}\n";
                echo "  Status: {$job['status']}, Active: " . ($job['is_active'] ? 'Yes' : 'No') . "\n";
                echo "  Expires: {$job['expires_at']}\n";
                echo "  Created: {$job['created_at']}\n\n";
            }
        }

        // Δοκιμή του AIMatchingService
        echo "Δοκιμή AIMatchingService...\n";
        $aiService = new AIMatchingService($pdo);
        $matches = $aiService->findCompanyDriverMatches($company['user_id'], 1, 5);

        echo "AI Matches αποτελέσματα:\n";
        echo "- Total matches: {$matches['total']}\n";
        echo "- Matches count: " . count($matches['matches']) . "\n\n";

        if (!empty($matches['matches'])) {
            echo "Δείγμα matches:\n";
            foreach (array_slice($matches['matches'], 0, 3) as $i => $match) {
                echo "Match " . ($i + 1) . ":\n";
                echo "- Driver: {$match['driver']['first_name']} {$match['driver']['last_name']}\n";
                echo "- Score: " . round($match['score'] * 100, 2) . "%\n";
                echo "- Job: {$match['job']['title']}\n\n";
            }
        }
    } else {
        echo "Δεν βρέθηκε εταιρία με email info@thessdrive.gr\n\n";

        // Έλεγχος όλων των εταιριών
        $stmt = $pdo->query("
            SELECT u.email, c.company_name, c.user_id
            FROM users u 
            JOIN companies c ON u.id = c.user_id 
            ORDER BY u.email
        ");
        $allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Όλες οι εταιρίες στη βάση:\n";
        foreach ($allCompanies as $comp) {
            echo "- {$comp['email']} -> {$comp['company_name']} (User ID: {$comp['user_id']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
