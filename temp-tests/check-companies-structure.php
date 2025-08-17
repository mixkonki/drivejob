<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΈΛΕΓΧΟΣ ΔΟΜΗΣ ΠΊΝΑΚΑ COMPANIES ===\n\n";

    // Έλεγχος δομής πίνακα companies
    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Δομή πίνακα companies:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";

    // Έλεγχος δεδομένων εταιρίας
    $stmt = $pdo->prepare("
        SELECT * FROM companies 
        WHERE email = ? OR company_name LIKE '%thessdrive%'
        LIMIT 5
    ");
    $stmt->execute(['info@thessdrive.gr']);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Αναζήτηση εταιρίας info@thessdrive.gr:\n";
    if (empty($companies)) {
        echo "- Δεν βρέθηκε εταιρία με αυτό το email\n\n";

        // Έλεγχος όλων των εταιριών
        $stmt = $pdo->query("SELECT id, email, company_name FROM companies ORDER BY email LIMIT 10");
        $allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Όλες οι εταιρίες (πρώτες 10):\n";
        foreach ($allCompanies as $comp) {
            echo "- ID: {$comp['id']}, Email: {$comp['email']}, Name: {$comp['company_name']}\n";
        }
    } else {
        foreach ($companies as $company) {
            echo "- ID: {$company['id']}\n";
            echo "- Email: {$company['email']}\n";
            echo "- Company Name: {$company['company_name']}\n";
            echo "- User ID field: " . (isset($company['user_id']) ? $company['user_id'] : 'ΔΕΝ ΥΠΑΡΧΕΙ') . "\n\n";
        }
    }

    // Έλεγχος σχέσης με users
    echo "Έλεγχος σχέσης companies-users:\n";
    $stmt = $pdo->query("
        SELECT u.email, u.id as user_id, c.id as company_id, c.company_name
        FROM users u
        WHERE u.email LIKE '%thessdrive%' OR u.email LIKE '%company%'
        ORDER BY u.email
        LIMIT 10
    ");
    $userCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($userCompanies as $uc) {
        echo "- User: {$uc['email']} (ID: {$uc['user_id']})\n";

        // Αναζήτηση αντίστοιχης εταιρίας
        $stmt2 = $pdo->prepare("SELECT id, company_name FROM companies WHERE email = ?");
        $stmt2->execute([$uc['email']]);
        $companyMatch = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($companyMatch) {
            echo "  -> Company: {$companyMatch['company_name']} (ID: {$companyMatch['id']})\n";
        } else {
            echo "  -> Δεν βρέθηκε αντίστοιχη εταιρία\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
