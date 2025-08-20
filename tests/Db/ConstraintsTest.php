<?php

/**
 * Database Constraints Test
 * 
 * Αυτό το test class ελέγχει την ορθή λειτουργία των database constraints
 * που προστέθηκαν στο P0-01 Database Integrity task
 */

use PHPUnit\Framework\TestCase;

class ConstraintsTest extends TestCase
{
    private static $pdo;
    private static $testDbConfig;

    /**
     * Setup test database connection πριν από όλα τα tests
     */
    public static function setUpBeforeClass(): void
    {
        // Load test database configuration
        self::$testDbConfig = require ROOT_DIR . '/config/database-test.php';

        // Check if test database is available
        if (!isTestDatabaseAvailable()) {
            self::markTestSkipped('Test database is not available. Please configure .env.testing with valid credentials.');
            return;
        }

        try {
            // Create test database connection
            self::$pdo = createTestDatabaseConnection();

            // Setup test schema
            setupTestDatabaseSchema(self::$pdo);

            // Seed test data
            seedTestDatabase(self::$pdo);
        } catch (Exception $e) {
            self::markTestSkipped('Failed to setup test database: ' . $e->getMessage());
        }
    }

    /**
     * Clean up μετά από όλα τα tests
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            cleanTestDatabase(self::$pdo);
        }
    }

    /**
     * Setup πριν από κάθε test
     */
    protected function setUp(): void
    {
        if (!self::$pdo) {
            $this->markTestSkipped('Test database connection not available');
        }
    }

    /**
     * Test ότι duplicate emails αποτυγχάνουν στους drivers
     * 
     * WHY: Email uniqueness είναι κρίσιμο για authentication security
     * Duplicate emails επιτρέπουν account takeover attacks
     */
    public function testDuplicateEmailFails(): void
    {
        // Arrange: Insert first driver με email
        $email = 'duplicate.test@example.com';
        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, created_at) 
            VALUES (?, 'Test', 'Driver', NOW())
        ");
        $stmt->execute([$email]);

        // Act & Assert: Προσπάθεια insert δεύτερου driver με το ίδιο email
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, created_at) 
            VALUES (?, 'Another', 'Driver', NOW())
        ");
        $stmt->execute([$email]);
    }

    /**
     * Test ότι duplicate emails αποτυγχάνουν στις companies
     */
    public function testDuplicateCompanyEmailFails(): void
    {
        // Arrange: Insert first company με email
        $email = 'duplicate.company@example.com';
        $stmt = self::$pdo->prepare("
            INSERT INTO companies (email, company_name, created_at) 
            VALUES (?, 'Test Company', NOW())
        ");
        $stmt->execute([$email]);

        // Act & Assert: Προσπάθεια insert δεύτερης company με το ίδιο email
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');

        $stmt = self::$pdo->prepare("
            INSERT INTO companies (email, company_name, created_at) 
            VALUES (?, 'Another Company', NOW())
        ");
        $stmt->execute([$email]);
    }

    /**
     * Test ότι duplicate usernames αποτυγχάνουν στους users
     */
    public function testDuplicateUsernameFails(): void
    {
        // Arrange: Insert first user με username
        $username = 'duplicate_user';
        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, password_hash, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$username, password_hash('test123', PASSWORD_DEFAULT)]);

        // Act & Assert: Προσπάθεια insert δεύτερου user με το ίδιο username
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');

        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, password_hash, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$username, password_hash('test456', PASSWORD_DEFAULT)]);
    }

    /**
     * Test ότι duplicate ΑΦΜ αποτυγχάνουν στους drivers
     */
    public function testDuplicateDriverAfmFails(): void
    {
        // Arrange: Insert first driver με ΑΦΜ
        $afm = '123456789';
        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, afm, created_at) 
            VALUES ('driver1@test.com', 'Test', 'Driver1', ?, NOW())
        ");
        $stmt->execute([$afm]);

        // Act & Assert: Προσπάθεια insert δεύτερου driver με το ίδιο ΑΦΜ
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, afm, created_at) 
            VALUES ('driver2@test.com', 'Test', 'Driver2', ?, NOW())
        ");
        $stmt->execute([$afm]);
    }

    /**
     * Test ότι duplicate ΑΜΚΑ αποτυγχάνουν στους drivers
     */
    public function testDuplicateDriverAmkaFails(): void
    {
        // Arrange: Insert first driver με ΑΜΚΑ
        $amka = '12345678901';
        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, amka, created_at) 
            VALUES ('driver.amka1@test.com', 'Test', 'Driver1', ?, NOW())
        ");
        $stmt->execute([$amka]);

        // Act & Assert: Προσπάθεια insert δεύτερου driver με το ίδιο ΑΜΚΑ
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate entry');

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, amka, created_at) 
            VALUES ('driver.amka2@test.com', 'Test', 'Driver2', ?, NOW())
        ");
        $stmt->execute([$amka]);
    }

    /**
     * Test Foreign Key integrity για user_roles
     * 
     * WHY: FK constraints εξασφαλίζουν referential integrity
     * Αποτρέπουν orphaned records και data corruption
     */
    public function testForeignKeyIntegrityUserRoles(): void
    {
        // Act & Assert: Προσπάθεια insert user_role με non-existent user_id
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('foreign key constraint');

        $stmt = self::$pdo->prepare("
            INSERT INTO user_roles (user_id, role_id, created_at) 
            VALUES (99999, 1, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test Foreign Key integrity για role_permissions
     */
    public function testForeignKeyIntegrityRolePermissions(): void
    {
        // Act & Assert: Προσπάθεια insert role_permission με non-existent role_id
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('foreign key constraint');

        $stmt = self::$pdo->prepare("
            INSERT INTO role_permissions (role_id, permission_id, created_at) 
            VALUES (99999, 1, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι invalid coordinates αποτυγχάνουν (CHECK constraint)
     * 
     * WHY: Invalid coordinates θα σπάσουν το location-based matching
     * Coordinates εκτός valid ranges προκαλούν calculation errors
     */
    public function testInsertInvalidCoordinatesFails(): void
    {
        // Test invalid latitude (> 90)
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, latitude, longitude, created_at) 
            VALUES ('invalid.coords@test.com', 'Test', 'Driver', 91.0, 23.0, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι invalid longitude αποτυγχάνει
     */
    public function testInsertInvalidLongitudeFails(): void
    {
        // Test invalid longitude (> 180)
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, latitude, longitude, created_at) 
            VALUES ('invalid.longitude@test.com', 'Test', 'Driver', 37.9, 181.0, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι invalid email format αποτυγχάνει
     */
    public function testInsertInvalidEmailFormatFails(): void
    {
        // Test invalid email format
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, created_at) 
            VALUES ('invalid-email-format', 'Test', 'Driver', NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι invalid phone format αποτυγχάνει
     */
    public function testInsertInvalidPhoneFormatFails(): void
    {
        // Test invalid phone format (too short)
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, phone, created_at) 
            VALUES ('phone.test@test.com', 'Test', 'Driver', '123', NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι invalid rating αποτυγχάνει
     */
    public function testInsertInvalidRatingFails(): void
    {
        // Test rating > 5
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, rating, created_at) 
            VALUES ('rating.test@test.com', 'Test', 'Driver', 6.0, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι negative experience years αποτυγχάνει
     */
    public function testInsertNegativeExperienceFails(): void
    {
        // Test negative experience
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (email, first_name, last_name, experience_years, created_at) 
            VALUES ('experience.test@test.com', 'Test', 'Driver', -5, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι valid data εισάγεται επιτυχώς
     * 
     * WHY: Positive test για να επιβεβαιώσουμε ότι valid data δεν απορρίπτεται
     */
    public function testInsertValidDataSucceeds(): void
    {
        // Arrange: Valid driver data
        $email = 'valid.driver@test.com';
        $afm = '987654321';
        $amka = '12345678901';
        $license = 'LIC123456';

        // Act: Insert valid driver
        $stmt = self::$pdo->prepare("
            INSERT INTO drivers (
                email, first_name, last_name, phone, afm, amka, 
                license_number, latitude, longitude, rating, 
                experience_years, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $email,
            'Valid',
            'Driver',
            '+306912345678',
            $afm,
            $amka,
            $license,
            37.9755,
            23.7348,
            4.5,
            5
        ]);

        // Assert: Insert succeeded
        $this->assertTrue($result);

        // Verify data was inserted
        $stmt = self::$pdo->prepare("SELECT id FROM drivers WHERE email = ?");
        $stmt->execute([$email]);
        $this->assertGreaterThan(0, $stmt->rowCount());
    }

    /**
     * Test ότι valid company data εισάγεται επιτυχώς
     */
    public function testInsertValidCompanySucceeds(): void
    {
        // Arrange: Valid company data
        $email = 'valid.company@test.com';
        $afm = '888888888';
        $regNumber = 'REG888888';

        // Act: Insert valid company
        $stmt = self::$pdo->prepare("
            INSERT INTO companies (
                email, company_name, phone, afm, registration_number,
                latitude, longitude, rating, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $email,
            'Valid Company',
            '+302101234567',
            $afm,
            $regNumber,
            37.9755,
            23.7348,
            4.2
        ]);

        // Assert: Insert succeeded
        $this->assertTrue($result);

        // Verify data was inserted
        $stmt = self::$pdo->prepare("SELECT id FROM companies WHERE email = ?");
        $stmt->execute([$email]);
        $this->assertGreaterThan(0, $stmt->rowCount());
    }

    /**
     * Test cascade delete για user_roles όταν διαγράφεται user
     * 
     * WHY: FK constraints με CASCADE πρέπει να διαγράφουν related records
     */
    public function testCascadeDeleteUserRoles(): void
    {
        // Arrange: Create test user και role assignment
        $stmt = self::$pdo->prepare("
            INSERT INTO users (username, password_hash, created_at) 
            VALUES ('cascade_test_user', ?, NOW())
        ");
        $stmt->execute([password_hash('test123', PASSWORD_DEFAULT)]);
        $userId = self::$pdo->lastInsertId();

        // Assign role to user
        $stmt = self::$pdo->prepare("
            INSERT INTO user_roles (user_id, role_id, created_at) 
            VALUES (?, 1, NOW())
        ");
        $stmt->execute([$userId]);

        // Verify role assignment exists
        $stmt = self::$pdo->prepare("SELECT COUNT(*) as count FROM user_roles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetch()['count'];
        $this->assertEquals(1, $count);

        // Act: Delete user
        $stmt = self::$pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        // Assert: Role assignment should be deleted (CASCADE)
        $stmt = self::$pdo->prepare("SELECT COUNT(*) as count FROM user_roles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetch()['count'];
        $this->assertEquals(0, $count);
    }

    /**
     * Test ότι job listings με invalid company_id αποτυγχάνουν
     */
    public function testJobListingInvalidCompanyFails(): void
    {
        // Act & Assert: Προσπάθεια insert job listing με non-existent company
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('foreign key constraint');

        $stmt = self::$pdo->prepare("
            INSERT INTO job_listings (
                company_id, title, description, listing_type, 
                job_type, is_active, created_at
            ) VALUES (99999, 'Test Job', 'Test Description', 'offer', 'full_time', 1, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι matching_scores με invalid driver_id αποτυγχάνουν
     */
    public function testMatchingScoreInvalidDriverFails(): void
    {
        // Arrange: Create valid job listing
        $stmt = self::$pdo->prepare("
            INSERT INTO job_listings (
                company_id, title, description, listing_type, 
                job_type, is_active, created_at
            ) VALUES (1, 'Test Job for Matching', 'Test Description', 'offer', 'full_time', 1, NOW())
        ");
        $stmt->execute();
        $jobId = self::$pdo->lastInsertId();

        // Act & Assert: Προσπάθεια insert matching score με non-existent driver
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('foreign key constraint');

        $stmt = self::$pdo->prepare("
            INSERT INTO matching_scores (driver_id, job_id, score, created_at) 
            VALUES (99999, ?, 85.5, NOW())
        ");
        $stmt->execute([$jobId]);
    }

    /**
     * Test ότι invalid salary range αποτυγχάνει στα job listings
     */
    public function testJobListingInvalidSalaryRangeFails(): void
    {
        // Act & Assert: salary_min > salary_max should fail
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO job_listings (
                company_id, title, description, listing_type, job_type,
                salary_min, salary_max, is_active, created_at
            ) VALUES (1, 'Invalid Salary Job', 'Test Description', 'offer', 'full_time', 2000, 1000, 1, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test ότι negative salary αποτυγχάνει
     */
    public function testJobListingNegativeSalaryFails(): void
    {
        // Act & Assert: Negative salary should fail
        $this->expectException(PDOException::class);

        $stmt = self::$pdo->prepare("
            INSERT INTO job_listings (
                company_id, title, description, listing_type, job_type,
                salary_min, is_active, created_at
            ) VALUES (1, 'Negative Salary Job', 'Test Description', 'offer', 'full_time', -1000, 1, NOW())
        ");
        $stmt->execute();
    }

    /**
     * Test index effectiveness για email lookups
     * 
     * WHY: Email indexes είναι κρίσιμα για login performance
     */
    public function testEmailIndexEffectiveness(): void
    {
        // Act: Explain query για email lookup
        $stmt = self::$pdo->prepare("
            EXPLAIN SELECT id, email, is_verified 
            FROM drivers 
            WHERE email = 'driver1@test.com'
        ");
        $stmt->execute();
        $explain = $stmt->fetch();

        // Assert: Query should use index (not full table scan)
        $this->assertNotEquals('ALL', $explain['type']);
        $this->assertContains('idx_drivers_email', $explain['key'] ?? '');
    }

    /**
     * Test composite index effectiveness για advanced search
     */
    public function testCompositeIndexEffectiveness(): void
    {
        // Act: Explain complex search query
        $stmt = self::$pdo->prepare("
            EXPLAIN SELECT id, first_name, last_name 
            FROM drivers 
            WHERE is_verified = 1 
            AND available_for_work = 1 
            AND city = 'Athens' 
            AND experience_years >= 5
        ");
        $stmt->execute();
        $explain = $stmt->fetch();

        // Assert: Query should use composite index
        $this->assertNotEquals('ALL', $explain['type']);
        $this->assertContains('idx_drivers', $explain['key'] ?? '');
    }

    /**
     * Test ότι RBAC queries χρησιμοποιούν indexes
     */
    public function testRbacIndexEffectiveness(): void
    {
        // Act: Explain RBAC permission lookup
        $stmt = self::$pdo->prepare("
            EXPLAIN SELECT p.name 
            FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            JOIN user_roles ur ON rp.role_id = ur.role_id 
            WHERE ur.user_id = 1
        ");
        $stmt->execute();
        $explains = $stmt->fetchAll();

        // Assert: At least one table should use index
        $usesIndex = false;
        foreach ($explains as $explain) {
            if ($explain['type'] !== 'ALL' && !empty($explain['key'])) {
                $usesIndex = true;
                break;
            }
        }
        $this->assertTrue($usesIndex, 'RBAC query should use indexes for performance');
    }

    /**
     * Test constraint names για proper identification
     */
    public function testConstraintNamesExist(): void
    {
        // Act: Get all constraints για our tables
        $stmt = self::$pdo->prepare("
            SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND CONSTRAINT_TYPE IN ('UNIQUE', 'FOREIGN KEY', 'CHECK')
            AND TABLE_NAME IN ('drivers', 'companies', 'users', 'user_roles', 'role_permissions')
            ORDER BY TABLE_NAME, CONSTRAINT_TYPE
        ");
        $stmt->execute();
        $constraints = $stmt->fetchAll();

        // Assert: We should have the expected constraints
        $this->assertGreaterThan(10, count($constraints), 'Should have multiple constraints applied');

        // Check for specific critical constraints
        $constraintNames = array_column($constraints, 'CONSTRAINT_NAME');
        $this->assertContains('uk_drivers_email', $constraintNames);
        $this->assertContains('uk_companies_email', $constraintNames);
        $this->assertContains('fk_user_roles_user', $constraintNames);
    }

    /**
     * Test performance improvement με indexes
     * 
     * WHY: Verification ότι τα indexes βελτιώνουν την performance
     */
    public function testPerformanceImprovement(): void
    {
        // Arrange: Insert some test data για realistic performance test
        for ($i = 100; $i < 200; $i++) {
            $stmt = self::$pdo->prepare("
                INSERT IGNORE INTO drivers (email, first_name, last_name, city, experience_years, created_at) 
                VALUES (?, 'Driver', ?, 'Athens', ?, NOW())
            ");
            $stmt->execute(["perf$i@test.com", "Test$i", $i % 10]);
        }

        // Act: Measure query performance
        $start = microtime(true);

        $stmt = self::$pdo->prepare("
            SELECT COUNT(*) as count 
            FROM drivers 
            WHERE city = 'Athens' 
            AND experience_years >= 5 
            AND is_verified = 1
        ");
        $stmt->execute();

        $end = microtime(true);
        $executionTime = ($end - $start) * 1000; // Convert to milliseconds

        // Assert: Query should be fast (< 100ms για small dataset)
        $this->assertLessThan(100, $executionTime, 'Indexed query should be fast');
    }

    /**
     * Helper method για cleanup μετά από κάθε test
     */
    protected function tearDown(): void
    {
        if (self::$pdo) {
            // Clean up test data που δημιουργήθηκε στο specific test
            try {
                self::$pdo->exec("DELETE FROM drivers WHERE email LIKE '%test.com' AND id > 100");
                self::$pdo->exec("DELETE FROM companies WHERE email LIKE '%test.com' AND id > 100");
                self::$pdo->exec("DELETE FROM users WHERE username LIKE '%test%' AND id > 100");
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    /**
     * Test helper: Verify constraint exists
     */
    private function assertConstraintExists(string $tableName, string $constraintName, string $constraintType): void
    {
        $stmt = self::$pdo->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ? 
            AND CONSTRAINT_TYPE = ?
        ");
        $stmt->execute([$tableName, $constraintName, $constraintType]);
        $count = $stmt->fetch()['count'];

        $this->assertEquals(1, $count, "Constraint $constraintName should exist on table $tableName");
    }

    /**
     * Test helper: Verify index exists
     */
    private function assertIndexExists(string $tableName, string $indexName): void
    {
        $stmt = self::$pdo->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ");
        $stmt->execute([$tableName, $indexName]);
        $count = $stmt->fetch()['count'];

        $this->assertGreaterThan(0, $count, "Index $indexName should exist on table $tableName");
    }
}
