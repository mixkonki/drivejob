<?php
// Test script για το admin/users endpoint
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;

echo "=== Testing Admin Users Endpoint ===\n\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // 1. Έλεγχος αν προστέθηκαν τα user_id πεδία
    echo "1. Checking if user_id columns were added...\n";

    $companiesCheck = $pdo->query("SHOW COLUMNS FROM companies LIKE 'user_id'")->fetch();
    $driversCheck = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'user_id'")->fetch();

    echo "   - companies.user_id: " . ($companiesCheck ? "✓ EXISTS" : "✗ MISSING") . "\n";
    echo "   - drivers.user_id: " . ($driversCheck ? "✓ EXISTS" : "✗ MISSING") . "\n\n";

    // 2. Έλεγχος backfill - πόσες εγγραφές συνδέθηκαν
    echo "2. Checking backfill results...\n";

    $companiesLinked = $pdo->query("SELECT COUNT(*) as count FROM companies WHERE user_id IS NOT NULL")->fetch()['count'];
    $driversLinked = $pdo->query("SELECT COUNT(*) as count FROM drivers WHERE user_id IS NOT NULL")->fetch()['count'];
    $totalCompanies = $pdo->query("SELECT COUNT(*) as count FROM companies")->fetch()['count'];
    $totalDrivers = $pdo->query("SELECT COUNT(*) as count FROM drivers")->fetch()['count'];

    echo "   - Companies linked: $companiesLinked / $totalCompanies\n";
    echo "   - Drivers linked: $driversLinked / $totalDrivers\n\n";

    // 3. Test του actual endpoint με cURL
    echo "3. Testing admin/users endpoint...\n";

    // Πρώτα χρειαζόμαστε να κάνουμε login ως admin
    $loginUrl = 'http://localhost/drivejob/public/login-process.php';
    $usersUrl = 'http://localhost/drivejob/public/api/admin/users.php';

    // Δημιουργία session για admin login
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $loginUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'email' => 'admin@drivejob.com',
            'password' => 'admin123'
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => 'cookies.txt',
        CURLOPT_COOKIEFILE => 'cookies.txt',
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $loginResponse = curl_exec($ch);
    $loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "   - Admin login: HTTP $loginHttpCode\n";

    // Τώρα test το users endpoint
    curl_setopt_array($ch, [
        CURLOPT_URL => $usersUrl,
        CURLOPT_POST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => 'cookies.txt'
    ]);

    $usersResponse = curl_exec($ch);
    $usersHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "   - Users endpoint: HTTP $usersHttpCode\n";

    if ($usersHttpCode === 200) {
        $data = json_decode($usersResponse, true);
        if ($data && isset($data['users'])) {
            echo "   - Response: ✓ SUCCESS\n";
            echo "   - Users returned: " . count($data['users']) . "\n";

            // Δείγμα του πρώτου χρήστη
            if (!empty($data['users'])) {
                $firstUser = $data['users'][0];
                echo "   - Sample user fields: " . implode(', ', array_keys($firstUser)) . "\n";

                // Έλεγχος αν υπάρχουν company/driver data
                $hasCompanyData = isset($firstUser['company_id']) && !is_null($firstUser['company_id']);
                $hasDriverData = isset($firstUser['driver_id']) && !is_null($firstUser['driver_id']);
                echo "   - Has company data: " . ($hasCompanyData ? "✓" : "✗") . "\n";
                echo "   - Has driver data: " . ($hasDriverData ? "✓" : "✗") . "\n";
            }
        } else {
            echo "   - Response: ✗ INVALID JSON\n";
            echo "   - Raw response: " . substr($usersResponse, 0, 200) . "...\n";
        }
    } else {
        echo "   - Response: ✗ FAILED\n";
        echo "   - Raw response: " . substr($usersResponse, 0, 200) . "...\n";
    }

    curl_close($ch);

    // Cleanup
    if (file_exists('cookies.txt')) {
        unlink('cookies.txt');
    }

    echo "\n=== Test Complete ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
