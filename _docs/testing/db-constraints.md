# Database Constraints Testing Guide

**Ημερομηνία**: 18 Αυγούστου 2025  
**Στόχος**: Testing για P0-01 Database Integrity constraints και indexes

## Overview

Αυτό το guide παρέχει οδηγίες για την εκτέλεση των PHPUnit tests που επιβεβαιώνουν την ορθή λειτουργία των database constraints και indexes που προστέθηκαν στο P0-01 Database Integrity task.

## Prerequisites

### 1. Test Database Setup

Πριν την εκτέλεση των tests, χρειάζεται να ρυθμίσετε ένα ξεχωριστό test database:

```bash
# Δημιουργία test database
mysql -u root -p
CREATE DATABASE drivejob_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Δημιουργία test user (προαιρετικό για security)
CREATE USER 'test_user'@'localhost' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON drivejob_test.* TO 'test_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Environment Configuration

Ενημερώστε το `.env.testing` αρχείο με τα πραγματικά credentials:

```bash
# Copy template και edit
cp .env.testing .env.testing.local

# Edit .env.testing.local με τα πραγματικά credentials
DB_TEST_HOST=localhost
DB_TEST_NAME=drivejob_test
DB_TEST_USER=test_user
DB_TEST_PASS=test_password
DB_TEST_PORT=3306
```

**ΣΗΜΑΝΤΙΚΟ**: ΜΗΝ commit το `.env.testing.local` με πραγματικά credentials!

### 3. Database Schema Setup

Εκτελέστε τα migration scripts στο test database:

```bash
# Apply main schema migrations
mysql -u test_user -p drivejob_test < database/migrations/sql/2025-08-18-dedupe-pre-constraints.sql
mysql -u test_user -p drivejob_test < database/migrations/sql/2025-08-18-constraints-and-indexes.sql
```

## Test Execution

### Quick Start (Αν έχετε configured credentials)

```bash
# Εκτέλεση όλων των constraint tests
./vendor/bin/phpunit tests/Db/ConstraintsTest.php

# Εκτέλεση specific test
./vendor/bin/phpunit tests/Db/ConstraintsTest.php::testDuplicateEmailFails

# Verbose output
./vendor/bin/phpunit tests/Db/ConstraintsTest.php --verbose
```

### Step-by-Step Execution (Χωρίς credentials)

Αν δεν έχετε configured test database, μπορείτε να δείτε τα steps που θα εκτελούνταν:

```bash
# 1. Check if test database is available
echo "Checking test database availability..."
echo "Would connect to: mysql://test_user@localhost:3306/drivejob_test"

# 2. Setup test schema
echo "Setting up test database schema..."
echo "Would execute: database/migrations/sql/2025-08-18-dedupe-pre-constraints.sql"
echo "Would execute: database/migrations/sql/2025-08-18-constraints-and-indexes.sql"

# 3. Seed test data
echo "Seeding test database..."
echo "Would insert: 10 test drivers, 5 test companies, 3 test users"

# 4. Run constraint tests
echo "Running constraint tests..."
echo "Would test: UNIQUE constraints, Foreign Keys, CHECK constraints"

# 5. Verify index effectiveness
echo "Testing index performance..."
echo "Would verify: Email indexes, Composite indexes, RBAC indexes"

# 6. Cleanup test data
echo "Cleaning up test data..."
echo "Would truncate: test tables and reset auto-increment"
```

## Test Categories

### 1. UNIQUE Constraint Tests

**Purpose**: Επιβεβαίωση ότι duplicate values αποτυγχάνουν

```php
// Tests που εκτελούνται:
testDuplicateEmailFails()           // Drivers email uniqueness
testDuplicateCompanyEmailFails()    // Companies email uniqueness  
testDuplicateUsernameFails()        // Users username uniqueness
testDuplicateDriverAfmFails()       // Drivers ΑΦΜ uniqueness
testDuplicateDriverAmkaFails()      // Drivers ΑΜΚΑ uniqueness
```

**Expected Results**:
- Duplicate email insertion → `PDOException: Duplicate entry`
- Duplicate ΑΦΜ insertion → `PDOException: Duplicate entry`
- Duplicate ΑΜΚΑ insertion → `PDOException: Duplicate entry`

### 2. Foreign Key Constraint Tests

**Purpose**: Επιβεβαίωση referential integrity

```php
// Tests που εκτελούνται:
testForeignKeyIntegrityUserRoles()      // user_roles → users/roles
testForeignKeyIntegrityRolePermissions() // role_permissions → roles/permissions
testJobListingInvalidCompanyFails()     // job_listings → companies
testMatchingScoreInvalidDriverFails()   // matching_scores → drivers
testCascadeDeleteUserRoles()            // CASCADE delete behavior
```

**Expected Results**:
- Invalid foreign key → `PDOException: foreign key constraint fails`
- Cascade delete → Related records automatically deleted

### 3. CHECK Constraint Tests

**Purpose**: Επιβεβαίωση data validation rules

```php
// Tests που εκτελούνται:
testInsertInvalidCoordinatesFails()     // Latitude/longitude validation
testInsertInvalidEmailFormatFails()     // Email format validation
testInsertInvalidPhoneFormatFails()     // Phone format validation
testInsertInvalidRatingFails()          // Rating range validation
testInsertNegativeExperienceFails()     // Experience years validation
testJobListingInvalidSalaryRangeFails() // Salary range validation
```

**Expected Results**:
- Invalid coordinates → `PDOException: Check constraint violation`
- Invalid email format → `PDOException: Check constraint violation`
- Invalid rating (>5) → `PDOException: Check constraint violation`

### 4. Performance Tests

**Purpose**: Επιβεβαίωση index effectiveness

```php
// Tests που εκτελούνται:
testEmailIndexEffectiveness()       // Email lookup performance
testCompositeIndexEffectiveness()   // Complex search performance
testRbacIndexEffectiveness()        // RBAC query performance
testPerformanceImprovement()        // Overall performance measurement
```

**Expected Results**:
- Email queries use `idx_drivers_email` index
- Complex searches use composite indexes
- Query execution time < 100ms για small datasets

### 5. Positive Tests

**Purpose**: Επιβεβαίωση ότι valid data εισάγεται επιτυχώς

```php
// Tests που εκτελούνται:
testInsertValidDataSucceeds()        // Valid driver insertion
testInsertValidCompanySucceeds()     // Valid company insertion
```

**Expected Results**:
- Valid data insertion succeeds
- Data is properly stored και retrievable

## Test Configuration

### Environment Variables

Τα tests χρησιμοποιούν τις εξής environment variables από `.env.testing`:

```bash
# Database Connection
DB_TEST_HOST=localhost          # Test database host
DB_TEST_NAME=drivejob_test     # Test database name
DB_TEST_USER=test_user         # Test database user
DB_TEST_PASS=test_password     # Test database password
DB_TEST_PORT=3306              # Test database port

# Test Data Seeding
TEST_SEED_ENABLED=true         # Enable test data seeding
TEST_SEED_DRIVERS_COUNT=10     # Number of test drivers
TEST_SEED_COMPANIES_COUNT=5    # Number of test companies

# Test Cleanup
TEST_CLEANUP_ENABLED=true      # Enable automatic cleanup
TEST_CLEANUP_TABLES=drivers,companies,users,job_listings,matching_scores
```

### Database Schema Requirements

Τα tests απαιτούν τα εξής tables με constraints:

```sql
-- Required tables:
drivers, companies, users, roles, permissions, user_roles, role_permissions,
job_listings, job_listing_vehicle_types, job_listing_tags, matching_scores

-- Required constraints:
uk_drivers_email, uk_companies_email, uk_users_username,
uk_drivers_afm, uk_drivers_amka, uk_drivers_license_number,
fk_user_roles_user, fk_user_roles_role, fk_role_permissions_role

-- Required indexes:
idx_drivers_email, idx_companies_email, idx_users_username,
idx_drivers_search, idx_companies_search, idx_matching_scores_performance
```

## Troubleshooting

### Common Issues

#### 1. "Test database is not available"

**Cause**: Δεν μπορεί να συνδεθεί στο test database

**Solution**:
```bash
# Check database exists
mysql -u test_user -p -e "SHOW DATABASES LIKE 'drivejob_test';"

# Check user permissions
mysql -u test_user -p -e "SHOW GRANTS;"

# Verify .env.testing configuration
cat .env.testing | grep DB_TEST_
```

#### 2. "Failed to setup test database schema"

**Cause**: Migration files δεν βρέθηκαν ή failed

**Solution**:
```bash
# Check migration files exist
ls -la database/migrations/sql/

# Manually execute migrations
mysql -u test_user -p drivejob_test < database/migrations/sql/2025-08-18-dedupe-pre-constraints.sql
mysql -u test_user -p drivejob_test < database/migrations/sql/2025-08-18-constraints-and-indexes.sql
```

#### 3. "Constraint does not exist"

**Cause**: Constraints δεν εφαρμόστηκαν στο test database

**Solution**:
```bash
# Check constraints exist
mysql -u test_user -p drivejob_test -e "
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = 'drivejob_test' 
AND CONSTRAINT_TYPE IN ('UNIQUE', 'FOREIGN KEY');"
```

#### 4. "Index does not exist"

**Cause**: Indexes δεν δημιουργήθηκαν

**Solution**:
```bash
# Check indexes exist
mysql -u test_user -p drivejob_test -e "
SELECT TABLE_NAME, INDEX_NAME 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'drivejob_test' 
AND INDEX_NAME != 'PRIMARY';"
```

### Performance Issues

#### Slow Test Execution

**Causes**:
- Large test dataset
- Missing indexes στο test database
- Slow database connection

**Solutions**:
```bash
# Reduce test data size
# Edit .env.testing:
TEST_SEED_DRIVERS_COUNT=5
TEST_SEED_COMPANIES_COUNT=3

# Optimize test database
mysql -u test_user -p drivejob_test -e "ANALYZE TABLE drivers, companies, users;"

# Check query performance
mysql -u test_user -p drivejob_test -e "
EXPLAIN SELECT * FROM drivers WHERE email = 'test@example.com';"
```

## Manual Testing (Without PHPUnit)

Αν δεν μπορείτε να εκτελέσετε PHPUnit, μπορείτε να κάνετε manual testing:

### 1. Test UNIQUE Constraints

```sql
-- Test duplicate email (should fail)
INSERT INTO drivers (email, first_name, last_name) 
VALUES ('existing@email.com', 'Test', 'User');

INSERT INTO drivers (email, first_name, last_name) 
VALUES ('existing@email.com', 'Another', 'User');
-- Expected: ERROR 1062 (23000): Duplicate entry
```

### 2. Test Foreign Key Constraints

```sql
-- Test invalid foreign key (should fail)
INSERT INTO user_roles (user_id, role_id) VALUES (99999, 1);
-- Expected: ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails
```

### 3. Test CHECK Constraints

```sql
-- Test invalid coordinates (should fail)
INSERT INTO drivers (email, first_name, last_name, latitude, longitude) 
VALUES ('test@example.com', 'Test', 'User', 91.0, 23.0);
-- Expected: ERROR 3819 (HY000): Check constraint violation
```

### 4. Test Index Usage

```sql
-- Check if email queries use indexes
EXPLAIN SELECT * FROM drivers WHERE email = 'test@example.com';
-- Expected: type = 'const' or 'ref', key = 'idx_drivers_email'
```

## CI/CD Integration

### GitHub Actions Example

```yaml
# .github/workflows/database-tests.yml
name: Database Constraints Tests

on: [push, pull_request]

jobs:
  database-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: drivejob_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, pdo_mysql
    
    - name: Install dependencies
      run: composer install
    
    - name: Setup test database
      run: |
        mysql -h 127.0.0.1 -u root -proot drivejob_test < database/migrations/sql/2025-08-18-dedupe-pre-constraints.sql
        mysql -h 127.0.0.1 -u root -proot drivejob_test < database/migrations/sql/2025-08-18-constraints-and-indexes.sql
    
    - name: Run database constraint tests
      run: ./vendor/bin/phpunit tests/Db/ConstraintsTest.php
```

## Test Results Interpretation

### Success Indicators

✅ **All tests pass**: Constraints και indexes λειτουργούν σωστά  
✅ **Performance tests < 100ms**: Indexes είναι effective  
✅ **Constraint violations caught**: Data validation works  
✅ **Foreign key integrity**: Referential integrity enforced  

### Failure Indicators

❌ **Duplicate insertion succeeds**: UNIQUE constraints missing  
❌ **Invalid data insertion succeeds**: CHECK constraints missing  
❌ **Orphaned records allowed**: Foreign Key constraints missing  
❌ **Slow query performance**: Indexes not effective  

### Example Success Output

```
PHPUnit 9.5.10 by Sebastian Bergmann and contributors.

Testing Database Constraints
...............                                                   15 / 15 (100%)

Time: 00:02.543, Memory: 12.00 MB

OK (15 tests, 45 assertions)
```

### Example Failure Output

```
PHPUnit 9.5.10 by Sebastian Bergmann and contributors.

Testing Database Constraints
.F.............                                                   15 / 15 ( 93%)

Time: 00:01.234, Memory: 10.00 MB

FAILURES!
Tests: 15, Assertions: 44, Failures: 1.

1) ConstraintsTest::testDuplicateEmailFails
Failed asserting that exception of type "PDOException" is thrown.
```

## Development Workflow

### 1. Before Applying Constraints

```bash
# Run detection queries πρώτα
mysql -u username -p drivejob < database/migrations/sql/2025-08-18-dedupe-pre-constraints.sql

# Review duplicate results
# Fix any duplicates manually
# Then apply constraints
```

### 2. After Applying Constraints

```bash
# Run tests για verification
./vendor/bin/phpunit tests/Db/ConstraintsTest.php

# Check constraint status
mysql -u username -p drivejob -e "
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = 'drivejob' 
AND CONSTRAINT_TYPE IN ('UNIQUE', 'FOREIGN KEY');"
```

### 3. Performance Monitoring

```bash
# Monitor query performance
mysql -u username -p drivejob -e "
EXPLAIN SELECT * FROM drivers WHERE email = 'user@example.com';"

# Check index usage
mysql -u username -p drivejob -e "
SELECT TABLE_NAME, INDEX_NAME, CARDINALITY 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = 'drivejob' 
AND INDEX_NAME != 'PRIMARY';"
```

## Test Data Management

### Seeded Test Data

Τα tests χρησιμοποιούν τα εξής test data:

```php
// Test Users
users: test_admin, test_driver, test_company

// Test Drivers  
drivers: driver1@test.com, driver2@test.com, ... driver10@test.com
AFM: 123456781, 123456782, ... 123456790
Phone: +3069000001, +3069000002, ... +30690000010

// Test Companies
companies: company1@test.com, company2@test.com, ... company5@test.com
AFM: 999999991, 999999992, ... 999999995
Registration: REG000001, REG000002, ... REG000005
```

### Test Data Cleanup

```php
// Automatic cleanup μετά από κάθε test:
DELETE FROM drivers WHERE email LIKE '%test.com' AND id > 100;
DELETE FROM companies WHERE email LIKE '%test.com' AND id > 100;
DELETE FROM users WHERE username LIKE '%test%' AND id > 100;

// Full cleanup μετά από όλα τα tests:
TRUNCATE TABLE drivers, companies, users, job_listings, matching_scores;
```

## Security Considerations

### Test Database Isolation

- **Ξεχωριστή Database**: `drivejob_test` ≠ `drivejob`
- **Ξεχωριστός User**: `test_user` με limited permissions
- **No Production Data**: Μόνο synthetic test data

### Credential Management

- **Environment Variables**: Credentials στο `.env.testing`
- **No Hardcoding**: Δεν υπάρχουν credentials στον κώδικα
- **Gitignore**: `.env.testing.local` excluded από version control

### Test Data Security

- **Synthetic Data**: Όλα τα test data είναι fake
- **No PII**: Δεν χρησιμοποιούνται πραγματικά personal data
- **Automatic Cleanup**: Test data διαγράφεται αυτόματα

## Maintenance

### Weekly Tasks

```bash
# Update test database schema
mysql -u test_user -p drivejob_test < database/migrations/sql/latest-migration.sql

# Run full test suite
./vendor/bin/phpunit tests/Db/

# Check for unused indexes
mysql -u test_user -p drivejob_test -e "
SELECT * FROM sys.schema_unused_indexes WHERE object_schema = 'drivejob_test';"
```

### Monthly Tasks

```bash
# Analyze test database performance
mysql -u test_user -p drivejob_test -e "ANALYZE TABLE drivers, companies, users;"

# Review test coverage
./vendor/bin/phpunit tests/Db/ --coverage-html coverage/

# Update test data if schema changes
# Review και update seeding logic
```

## Integration με Development Workflow

### Pre-commit Hooks

```bash
# Add to .git/hooks/pre-commit
#!/bin/bash
echo "Running database constraint tests..."
./vendor/bin/phpunit tests/Db/ConstraintsTest.php --stop-on-failure

if [ $? -ne 0 ]; then
    echo "Database constraint tests failed. Commit aborted."
    exit 1
fi
```

### Pull Request Checks

```bash
# Required checks πριν merge:
1. All constraint tests pass
2. No performance regression (< 100ms για core queries)
3. No constraint violations σε existing data
4. Proper rollback plan documented
```

## Files Structure

```
tests/
├── Db/
│   └── ConstraintsTest.php          # Main constraint tests
├── bootstrap.php                    # Test bootstrap
└── ...

config/
├── database.php                     # Production DB config
└── database-test.php               # Test DB config με .env.testing

database/migrations/sql/
├── 2025-08-18-dedupe-pre-constraints.sql    # Deduplication script
└── 2025-08-18-constraints-and-indexes.sql   # Constraints script

_docs/testing/
└── db-constraints.md               # This documentation

.env.testing                        # Test environment template
.env.testing.local                  # Local test credentials (gitignored)
```

## Next Steps

1. **Configure Test Database**: Setup `drivejob_test` database
2. **Update Credentials**: Edit `.env.testing` με valid credentials
3. **Run Schema Migrations**: Apply constraint scripts στο test DB
4. **Execute Tests**: Run PHPUnit constraint tests
5. **Review Results**: Verify όλα τα constraints λειτουργούν
6. **Integrate με CI/CD**: Add tests στο deployment pipeline

**ΣΗΜΑΝΤΙΚΟ**: Αυτά τα tests πρέπει να pass πριν την εφαρμογή των constraints στο production database!
