# Migrations & Tests

## Run migrations
```powershell
powershell -ExecutionPolicy Bypass -File scripts/tools/run_migrations.ps1
```

## Run tests
```bash
php scripts/tests/run_rbac_tests.php
```

## Migration Files

### 001_bootstrap_schema_migrations.sql
Creates the `schema_migrations` table to track applied migrations.

### 002_permissions_any_and_candidates.sql
Adds new permissions:
- `applications.manage.any` - Manage all applications
- `matches.view.any` - View all matches  
- `drivers.view.candidates` - View candidates for own jobs

### 003_indexes_rbac_related.sql
Creates performance indexes:
- `idx_user_roles_user_primary` on user_roles(user_id, is_primary)
- `idx_job_listings_company` on job_listings(company_id)
- `idx_job_applications_listing` on job_applications(job_listing_id) (if table exists)
- `idx_driver_ratings_listing` on driver_ratings(job_listing_id) (if table exists)

### 004_triggers_user_roles_primary_guard.sql
Creates database triggers to enforce only one primary role per user:
- `trg_user_roles_primary_bi` - Before INSERT trigger
- `trg_user_roles_primary_bu` - Before UPDATE trigger

### 005_sp_set_primary_role.sql
Creates stored procedure `sp_set_primary_role(user_id, role_id)` for safe primary role switching.

## Test Suite

The test suite (`scripts/tests/run_rbac_tests.php`) validates:

1. **admin_has_admin.access** - Admin user has admin.access permission
2. **sp_set_primary_role_exists** - Stored procedure exists in database
3. **primary_switch_to_employer** - Primary role switching works correctly
4. **user_roles_pair_unique** - Duplicate user-role pairs are prevented
5. **admin_permissions_list_count** - Admin has expected number of permissions (47+)

### Test Output Format
```json
{
  "failed": 0,
  "passed": 5,
  "tests": [
    {"name": "test_name", "ok": true, "ctx": {...}}
  ]
}
```

## CI Integration

Both migrations and tests are designed for CI/CD pipelines:
- **Idempotent migrations** - Safe to run multiple times
- **JSON test output** - Easy to parse in CI systems
- **Exit codes** - Non-zero on failure
- **Detailed logging** - Clear success/failure messages

## Database Schema Tracking

The `schema_migrations` table tracks applied migrations:
```sql
CREATE TABLE schema_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

Each migration file is recorded when successfully applied, preventing duplicate execution.
