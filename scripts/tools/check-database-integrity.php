<?php

/**
 * Database Integrity Checker
 * Ελέγχει την τρέχουσα κατάσταση των constraints, indexes και data integrity
 */

// Standalone script - no bootstrap needed

class DatabaseIntegrityChecker
{
    private PDO $pdo;
    private array $results = [];

    public function __construct()
    {
        // config/database.php returns PDO object directly
        $this->pdo = require __DIR__ . '/../../config/database.php';
    }

    public function runAllChecks(): void
    {
        echo "=== DriveJob Database Integrity Check ===\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

        $this->checkUniqueConstraints();
        $this->checkForeignKeyConstraints();
        $this->checkCheckConstraints();
        $this->checkIndexes();
        $this->checkDuplicateData();
        $this->checkOrphanedRecords();
        $this->checkInvalidData();

        $this->printSummary();
    }

    private function checkUniqueConstraints(): void
    {
        echo "📋 Checking UNIQUE Constraints...\n";
        echo str_repeat('-', 80) . "\n";

        $query = "
            SELECT 
                TABLE_NAME,
                CONSTRAINT_NAME,
                CONSTRAINT_TYPE
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND TABLE_NAME IN ('drivers', 'companies', 'users', 'roles', 'permissions', 'user_roles', 'role_permissions')
            ORDER BY TABLE_NAME, CONSTRAINT_NAME
        ";

        $constraints = $this->pdo->query($query)->fetchAll();

        $expectedConstraints = [
            'drivers' => ['uk_drivers_email', 'uk_drivers_afm', 'uk_drivers_amka', 'uk_drivers_id_number', 'uk_drivers_license_number'],
            'companies' => ['uk_companies_email', 'uk_companies_afm', 'uk_companies_registration'],
            'users' => ['uk_users_username'],
            'roles' => ['uk_roles_name'],
            'permissions' => ['uk_permissions_name'],
            'user_roles' => ['uk_user_roles_user_role'],
            'role_permissions' => ['uk_role_permissions_role_permission']
        ];

        $foundConstraints = [];
        foreach ($constraints as $constraint) {
            $foundConstraints[$constraint['TABLE_NAME']][] = $constraint['CONSTRAINT_NAME'];
        }

        $missingConstraints = [];
        foreach ($expectedConstraints as $table => $expected) {
            $found = $foundConstraints[$table] ?? [];
            $missing = array_diff($expected, $found);
            if (!empty($missing)) {
                $missingConstraints[$table] = $missing;
            }
        }

        if (empty($missingConstraints)) {
            echo "✅ All UNIQUE constraints are present (" . count($constraints) . " total)\n";
            $this->results['unique_constraints'] = 'PASS';
        } else {
            echo "❌ Missing UNIQUE constraints:\n";
            foreach ($missingConstraints as $table => $missing) {
                echo "   - $table: " . implode(', ', $missing) . "\n";
            }
            $this->results['unique_constraints'] = 'FAIL';
        }
        echo "\n";
    }

    private function checkForeignKeyConstraints(): void
    {
        echo "🔗 Checking FOREIGN KEY Constraints...\n";
        echo str_repeat('-', 80) . "\n";

        $query = "
            SELECT 
                TABLE_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_NAME IN ('user_roles', 'role_permissions', 'job_listings', 'job_listing_vehicle_types', 'job_listing_tags', 'matching_scores')
            ORDER BY TABLE_NAME, CONSTRAINT_NAME
        ";

        $fks = $this->pdo->query($query)->fetchAll();

        $expectedFKs = [
            'user_roles' => ['fk_user_roles_user', 'fk_user_roles_role'],
            'role_permissions' => ['fk_role_permissions_role', 'fk_role_permissions_permission'],
            'job_listings' => ['fk_job_listings_company', 'fk_job_listings_driver'],
            'job_listing_vehicle_types' => ['fk_job_vehicle_types_listing'],
            'job_listing_tags' => ['fk_job_tags_listing', 'fk_job_tags_tag'],
            'matching_scores' => ['fk_matching_scores_driver', 'fk_matching_scores_job']
        ];

        $foundFKs = [];
        foreach ($fks as $fk) {
            $foundFKs[$fk['TABLE_NAME']][] = $fk['CONSTRAINT_NAME'];
        }

        $missingFKs = [];
        foreach ($expectedFKs as $table => $expected) {
            $found = $foundFKs[$table] ?? [];
            $missing = array_diff($expected, $found);
            if (!empty($missing)) {
                $missingFKs[$table] = $missing;
            }
        }

        if (empty($missingFKs)) {
            echo "✅ All FOREIGN KEY constraints are present (" . count($fks) . " total)\n";
            $this->results['foreign_keys'] = 'PASS';
        } else {
            echo "❌ Missing FOREIGN KEY constraints:\n";
            foreach ($missingFKs as $table => $missing) {
                echo "   - $table: " . implode(', ', $missing) . "\n";
            }
            $this->results['foreign_keys'] = 'FAIL';
        }
        echo "\n";
    }

    private function checkCheckConstraints(): void
    {
        echo "✓ Checking CHECK Constraints...\n";
        echo str_repeat('-', 80) . "\n";

        $query = "
            SELECT 
                TABLE_NAME,
                CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND CONSTRAINT_TYPE = 'CHECK'
            AND TABLE_NAME IN ('drivers', 'companies', 'job_listings')
            ORDER BY TABLE_NAME, CONSTRAINT_NAME
        ";

        $checks = $this->pdo->query($query)->fetchAll();

        echo "Found " . count($checks) . " CHECK constraints\n";

        if (count($checks) > 0) {
            echo "✅ CHECK constraints are present\n";
            $this->results['check_constraints'] = 'PASS';
        } else {
            echo "⚠️  No CHECK constraints found (may not be critical)\n";
            $this->results['check_constraints'] = 'WARNING';
        }
        echo "\n";
    }

    private function checkIndexes(): void
    {
        echo "📊 Checking Performance Indexes...\n";
        echo str_repeat('-', 80) . "\n";

        $query = "
            SELECT 
                TABLE_NAME,
                COUNT(DISTINCT INDEX_NAME) as index_count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME IN ('drivers', 'companies', 'job_listings', 'matching_scores', 'user_roles', 'role_permissions')
            AND INDEX_NAME != 'PRIMARY'
            GROUP BY TABLE_NAME
            ORDER BY TABLE_NAME
        ";

        $indexes = $this->pdo->query($query)->fetchAll();

        $criticalIndexes = [
            'drivers' => ['idx_drivers_email', 'idx_drivers_location', 'idx_drivers_coordinates'],
            'companies' => ['idx_companies_email', 'idx_companies_location'],
            'job_listings' => ['idx_job_listings_company', 'idx_job_listings_location'],
            'matching_scores' => ['idx_matching_scores_driver', 'idx_matching_scores_job'],
            'user_roles' => ['idx_user_roles_user'],
            'role_permissions' => ['idx_role_permissions_role']
        ];

        foreach ($indexes as $index) {
            echo "   {$index['TABLE_NAME']}: {$index['index_count']} indexes\n";
        }

        // Check for critical indexes
        $missingCritical = [];
        foreach ($criticalIndexes as $table => $expectedIndexes) {
            $query = "
                SELECT DISTINCT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = :table
                AND INDEX_NAME != 'PRIMARY'
            ";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['table' => $table]);
            $foundIndexes = array_column($stmt->fetchAll(), 'INDEX_NAME');

            $missing = array_diff($expectedIndexes, $foundIndexes);
            if (!empty($missing)) {
                $missingCritical[$table] = $missing;
            }
        }

        if (empty($missingCritical)) {
            echo "✅ All critical indexes are present\n";
            $this->results['indexes'] = 'PASS';
        } else {
            echo "⚠️  Missing critical indexes:\n";
            foreach ($missingCritical as $table => $missing) {
                echo "   - $table: " . implode(', ', $missing) . "\n";
            }
            $this->results['indexes'] = 'WARNING';
        }
        echo "\n";
    }

    private function checkDuplicateData(): void
    {
        echo "🔍 Checking for Duplicate Data...\n";
        echo str_repeat('-', 80) . "\n";

        $checks = [
            'drivers.email' => "SELECT email, COUNT(*) as cnt FROM drivers WHERE email IS NOT NULL GROUP BY email HAVING cnt > 1",
            'drivers.afm' => "SELECT afm, COUNT(*) as cnt FROM drivers WHERE afm IS NOT NULL AND afm != '' GROUP BY afm HAVING cnt > 1",
            'drivers.amka' => "SELECT amka, COUNT(*) as cnt FROM drivers WHERE amka IS NOT NULL AND amka != '' GROUP BY amka HAVING cnt > 1",
            'drivers.license_number' => "SELECT license_number, COUNT(*) as cnt FROM drivers WHERE license_number IS NOT NULL AND license_number != '' GROUP BY license_number HAVING cnt > 1",
            'companies.email' => "SELECT email, COUNT(*) as cnt FROM companies WHERE email IS NOT NULL GROUP BY email HAVING cnt > 1",
            'companies.afm' => "SELECT afm, COUNT(*) as cnt FROM companies WHERE afm IS NOT NULL AND afm != '' GROUP BY afm HAVING cnt > 1",
            'users.username' => "SELECT username, COUNT(*) as cnt FROM users WHERE username IS NOT NULL GROUP BY username HAVING cnt > 1"
        ];

        $duplicatesFound = false;
        foreach ($checks as $field => $query) {
            $duplicates = $this->pdo->query($query)->fetchAll();
            if (!empty($duplicates)) {
                $duplicatesFound = true;
                echo "❌ Duplicates found in $field:\n";
                foreach ($duplicates as $dup) {
                    $value = array_values($dup)[0];
                    $count = $dup['cnt'];
                    echo "   - '$value': $count occurrences\n";
                }
            }
        }

        if (!$duplicatesFound) {
            echo "✅ No duplicate data found\n";
            $this->results['duplicates'] = 'PASS';
        } else {
            $this->results['duplicates'] = 'FAIL';
        }
        echo "\n";
    }

    private function checkOrphanedRecords(): void
    {
        echo "🔗 Checking for Orphaned Records...\n";
        echo str_repeat('-', 80) . "\n";

        $checks = [
            'user_roles without users' => "SELECT COUNT(*) as cnt FROM user_roles ur LEFT JOIN users u ON ur.user_id = u.id WHERE u.id IS NULL",
            'user_roles without roles' => "SELECT COUNT(*) as cnt FROM user_roles ur LEFT JOIN roles r ON ur.role_id = r.id WHERE r.id IS NULL",
            'role_permissions without roles' => "SELECT COUNT(*) as cnt FROM role_permissions rp LEFT JOIN roles r ON rp.role_id = r.id WHERE r.id IS NULL",
            'role_permissions without permissions' => "SELECT COUNT(*) as cnt FROM role_permissions rp LEFT JOIN permissions p ON rp.permission_id = p.id WHERE p.id IS NULL",
            'job_listings without companies' => "SELECT COUNT(*) as cnt FROM job_listings jl LEFT JOIN companies c ON jl.company_id = c.id WHERE jl.company_id IS NOT NULL AND c.id IS NULL",
            'matching_scores without drivers' => "SELECT COUNT(*) as cnt FROM matching_scores ms LEFT JOIN drivers d ON ms.driver_id = d.id WHERE d.id IS NULL",
            'matching_scores without jobs' => "SELECT COUNT(*) as cnt FROM matching_scores ms LEFT JOIN job_listings jl ON ms.job_id = jl.id WHERE jl.id IS NULL"
        ];

        $orphansFound = false;
        foreach ($checks as $description => $query) {
            $result = $this->pdo->query($query)->fetch();
            $count = $result['cnt'];
            if ($count > 0) {
                $orphansFound = true;
                echo "❌ $description: $count records\n";
            }
        }

        if (!$orphansFound) {
            echo "✅ No orphaned records found\n";
            $this->results['orphaned_records'] = 'PASS';
        } else {
            $this->results['orphaned_records'] = 'FAIL';
        }
        echo "\n";
    }

    private function checkInvalidData(): void
    {
        echo "⚠️  Checking for Invalid Data...\n";
        echo str_repeat('-', 80) . "\n";

        $checks = [
            'Invalid driver coordinates' => "SELECT COUNT(*) as cnt FROM drivers WHERE (latitude IS NOT NULL AND (latitude < -90 OR latitude > 90)) OR (longitude IS NOT NULL AND (longitude < -180 OR longitude > 180))",
            'Invalid company coordinates' => "SELECT COUNT(*) as cnt FROM companies WHERE (latitude IS NOT NULL AND (latitude < -90 OR latitude > 90)) OR (longitude IS NOT NULL AND (longitude < -180 OR longitude > 180))",
            'Invalid driver ratings' => "SELECT COUNT(*) as cnt FROM drivers WHERE rating IS NOT NULL AND (rating < 0 OR rating > 5)",
            'Invalid company ratings' => "SELECT COUNT(*) as cnt FROM companies WHERE rating IS NOT NULL AND (rating < 0 OR rating > 5)",
            'Negative experience years' => "SELECT COUNT(*) as cnt FROM drivers WHERE experience_years IS NOT NULL AND experience_years < 0",
            'Invalid email formats (drivers)' => "SELECT COUNT(*) as cnt FROM drivers WHERE email IS NOT NULL AND email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'",
            'Invalid email formats (companies)' => "SELECT COUNT(*) as cnt FROM companies WHERE email IS NOT NULL AND email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'"
        ];

        $invalidFound = false;
        foreach ($checks as $description => $query) {
            $result = $this->pdo->query($query)->fetch();
            $count = $result['cnt'];
            if ($count > 0) {
                $invalidFound = true;
                echo "❌ $description: $count records\n";
            }
        }

        if (!$invalidFound) {
            echo "✅ No invalid data found\n";
            $this->results['invalid_data'] = 'PASS';
        } else {
            $this->results['invalid_data'] = 'FAIL';
        }
        echo "\n";
    }

    private function printSummary(): void
    {
        echo str_repeat('=', 80) . "\n";
        echo "📊 SUMMARY\n";
        echo str_repeat('=', 80) . "\n";

        $passed = 0;
        $failed = 0;
        $warnings = 0;

        foreach ($this->results as $check => $status) {
            $icon = match ($status) {
                'PASS' => '✅',
                'FAIL' => '❌',
                'WARNING' => '⚠️',
                default => '❓'
            };

            echo sprintf("%-30s %s %s\n", ucwords(str_replace('_', ' ', $check)), $icon, $status);

            if ($status === 'PASS') $passed++;
            elseif ($status === 'FAIL') $failed++;
            elseif ($status === 'WARNING') $warnings++;
        }

        echo str_repeat('-', 80) . "\n";
        echo "Total Checks: " . count($this->results) . "\n";
        echo "Passed: $passed | Failed: $failed | Warnings: $warnings\n";
        echo str_repeat('=', 80) . "\n";

        if ($failed > 0) {
            echo "\n🔴 CRITICAL: Database integrity issues found!\n";
            echo "Action Required: Review failed checks and apply necessary fixes.\n";
        } elseif ($warnings > 0) {
            echo "\n⚠️  WARNING: Some non-critical issues found.\n";
            echo "Recommendation: Review warnings and consider improvements.\n";
        } else {
            echo "\n✅ SUCCESS: Database integrity is good!\n";
        }
    }
}

// Run the checker
try {
    $checker = new DatabaseIntegrityChecker();
    $checker->runAllChecks();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
