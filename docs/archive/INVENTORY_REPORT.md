# Inventory Report (2025-08-22 15:21:08)

## Auth Probe URL
- http://localhost/drivejob/public/api/dev/auth_probe.php?e=admin@drivejob.gr&p=admin123

## Admin Menu
- http://localhost/drivejob/public/admin/menu.php?uid=1

## Grep Findings (login/auth/password)

---- Pattern: password_verify\( ----
src/Repositories/AuthRepository.php
src/Services/Driver/DriverProfileService.php
src/Models/AuthModel.php
src/Models/Admin/AdminModel.php
scripts/tools/fix_admin_login.php
scripts/tools/auth_selftest.php
public/admin/temp-login.php
public/api/dev/auth_probe.php
debug-login.php
backup/src/Services/DriverProfileService.php

---- Pattern: password_hash\( ----
tests/Db/ConstraintsTest.php
src/Repositories/AuthRepository.php
src/Services/Driver/DriverProfileService.php
src/Models/AuthModel.php
src/Core/AuthControllerTest.php
scripts/tools/fix_admin_login.php
database/migrations/add_admin_role_to_users.php
config/database-test.php
database/migrations/create_admins_table.php
database/migrations/create_admin_user.php
backup/public/test-scripts-2025-06-01/fix-system-issues.php
backup/public/test-files/create-test-company-user.php
backup/src/Services/DriverProfileService.php
database/migrations/reset_admin_password.php

---- Pattern: session_start\( ----
src/Views/job-listings/index.php
src/RBAC/Middleware/HttpGuard.php
src/Middleware/CsrfMiddleware.php
src/helpers.php
src/Core/App.php
src/Core/Session.php
src/Core/RateLimiter.php
public/api/_rbac_bootstrap.php
public/api/matching/job/candidates.php
public/admin/test-connection.php
public/admin/temp-login.php
public/admin/settings.php
public/admin/debug-session.php
public/admin/ai-settings.php
fix-login-session.php
backup/public/test-messaging/test-messaging-direct.php
backup/public/test-files/test-api-raw.php
