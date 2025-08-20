<?php

/**
 * Test Database Configuration
 * 
 * Αυτό το αρχείο παρέχει database connection για testing environment
 * Χρησιμοποιεί .env.testing για configuration
 */

// Load environment variables από .env.testing
function loadTestEnv()
{
    $envFile = ROOT_DIR . '/.env.testing';

    if (!file_exists($envFile)) {
        throw new Exception("Test environment file not found: .env.testing");
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue; // Skip comments and invalid lines
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }

        // Set environment variable
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Database configuration για testing
class TestDatabaseConfig
{
    private static $config = null;

    public static function getConfig(): array
    {
        if (self::$config === null) {
            // Load test environment
            loadTestEnv();

            self::$config = [
                'host' => $_ENV['DB_TEST_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_TEST_NAME'] ?? 'drivejob_test',
                'username' => $_ENV['DB_TEST_USER'] ?? 'test_user',
                'password' => $_ENV['DB_TEST_PASS'] ?? 'test_password',
                'port' => $_ENV['DB_TEST_PORT'] ?? '3306',
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            ];
        }

        return self::$config;
    }

    public static function getDSN(): string
    {
        $config = self::getConfig();
        return "mysql:host={$config['host']};dbname={$config['database']};port={$config['port']};charset={$config['charset']}";
    }
}

/**
 * Create test database connection
 * 
 * @return PDO
 * @throws Exception
 */
function createTestDatabaseConnection(): PDO
{
    try {
        $config = TestDatabaseConfig::getConfig();
        $dsn = TestDatabaseConfig::getDSN();

        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );

        // Set additional MySQL settings για testing
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        $pdo->exec("SET SESSION time_zone = '+00:00'");

        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Test database connection failed: " . $e->getMessage());
    }
}

/**
 * Check if test database exists and is accessible
 * 
 * @return bool
 */
function isTestDatabaseAvailable(): bool
{
    try {
        $config = TestDatabaseConfig::getConfig();

        // Try to connect without specifying database
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);

        // Check if test database exists
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$config['database']]);

        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Create test database if it doesn't exist
 * 
 * @return bool
 */
function createTestDatabase(): bool
{
    try {
        $config = TestDatabaseConfig::getConfig();

        // Connect without database
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);

        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        return true;
    } catch (Exception $e) {
        error_log("Failed to create test database: " . $e->getMessage());
        return false;
    }
}

/**
 * Setup test database schema
 * 
 * @param PDO $pdo
 * @return bool
 */
function setupTestDatabaseSchema(PDO $pdo): bool
{
    try {
        // Read and execute main database schema
        $schemaFiles = [
            ROOT_DIR . '/database/migrations/create_roles_table.php',
            ROOT_DIR . '/database/migrations/create_permissions_table.php',
            ROOT_DIR . '/database/migrations/create_user_roles_table.php',
            ROOT_DIR . '/database/migrations/create_role_permissions_table.php',
            // Add other essential migration files
        ];

        foreach ($schemaFiles as $file) {
            if (file_exists($file)) {
                // Execute migration file
                include $file;
            }
        }

        return true;
    } catch (Exception $e) {
        error_log("Failed to setup test database schema: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean test database
 * 
 * @param PDO $pdo
 * @return bool
 */
function cleanTestDatabase(PDO $pdo): bool
{
    try {
        // Get cleanup tables από environment
        $cleanupTables = explode(',', $_ENV['TEST_CLEANUP_TABLES'] ?? 'drivers,companies,users,job_listings,matching_scores');

        // Disable foreign key checks για cleanup
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($cleanupTables as $table) {
            $table = trim($table);
            $pdo->exec("TRUNCATE TABLE `$table`");
        }

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        return true;
    } catch (Exception $e) {
        error_log("Failed to clean test database: " . $e->getMessage());
        return false;
    }
}

/**
 * Seed test database με sample data
 * 
 * @param PDO $pdo
 * @return bool
 */
function seedTestDatabase(PDO $pdo): bool
{
    try {
        if (!($_ENV['TEST_SEED_ENABLED'] ?? true)) {
            return true;
        }

        // Insert test roles
        $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES 
            (1, 'admin', 'Administrator role'),
            (2, 'driver', 'Driver role'),
            (3, 'company', 'Company role')");

        // Insert test permissions
        $pdo->exec("INSERT IGNORE INTO permissions (id, name, description) VALUES 
            (1, 'manage_users', 'Manage users'),
            (2, 'view_dashboard', 'View dashboard'),
            (3, 'create_jobs', 'Create job listings')");

        // Insert test users
        $pdo->exec("INSERT IGNORE INTO users (id, username, password_hash, created_at) VALUES 
            (1, 'test_admin', '" . password_hash('test123', PASSWORD_DEFAULT) . "', NOW()),
            (2, 'test_driver', '" . password_hash('test123', PASSWORD_DEFAULT) . "', NOW()),
            (3, 'test_company', '" . password_hash('test123', PASSWORD_DEFAULT) . "', NOW())");

        // Insert test drivers
        $driversCount = (int)($_ENV['TEST_SEED_DRIVERS_COUNT'] ?? 10);
        for ($i = 1; $i <= $driversCount; $i++) {
            $pdo->exec("INSERT IGNORE INTO drivers (id, email, first_name, last_name, phone, afm, created_at) VALUES 
                ($i, 'driver$i@test.com', 'Driver', '$i', '+306900000$i', '12345678$i', NOW())");
        }

        // Insert test companies
        $companiesCount = (int)($_ENV['TEST_SEED_COMPANIES_COUNT'] ?? 5);
        for ($i = 1; $i <= $companiesCount; $i++) {
            $pdo->exec("INSERT IGNORE INTO companies (id, email, company_name, phone, afm, registration_number, created_at) VALUES 
                ($i, 'company$i@test.com', 'Test Company $i', '+302100000$i', '99999999$i', 'REG00000$i', NOW())");
        }

        return true;
    } catch (Exception $e) {
        error_log("Failed to seed test database: " . $e->getMessage());
        return false;
    }
}

// Export configuration για use σε tests
return [
    'config' => TestDatabaseConfig::getConfig(),
    'dsn' => TestDatabaseConfig::getDSN(),
    'connection' => 'createTestDatabaseConnection',
    'available' => 'isTestDatabaseAvailable',
    'create' => 'createTestDatabase',
    'setup' => 'setupTestDatabaseSchema',
    'clean' => 'cleanTestDatabase',
    'seed' => 'seedTestDatabase'
];
