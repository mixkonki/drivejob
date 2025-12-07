# DevTools - Development & Debug Scripts

This directory contains temporary development, debugging, and testing scripts that were moved from the root directory for better organization.

## ⚠️ Important Notes

- **These scripts are for DEVELOPMENT ONLY**
- **DO NOT use in production**
- **DO NOT commit sensitive data**
- Scripts may contain hardcoded credentials or test data
- Scripts may modify database directly

## 📁 Script Categories

### Check Scripts (check-*.php)
Diagnostic scripts for checking system state:
- `check-admin.php` - Check admin user status
- `check-admin-permissions.php` - Verify admin permissions
- `check-database-structure.php` - Database structure verification
- `check-failing-users.php` - Find users with login issues
- `check-jscpd.php` - Code duplication checker
- `check-session-issue.php` - Session debugging
- `check-users-table.php` - Users table inspection
- `check_admin_user.php` - Admin user verification

### Clear Scripts (clear-*.php)
Scripts for clearing/resetting data:
- `clear-all-sessions.php` - Clear all user sessions
- `clear-sessions-and-test.php` - Clear sessions and test

### Create Scripts (create-*.php)
Scripts for creating test data:
- `create-app-user.php` - Create application user
- `create-backup.php` - Create database backup
- `create-test-driver.php` - Create test driver account

### Fix Scripts (fix-*.php)
Scripts for fixing issues:
- `fix-login-session.php` - Fix login session issues
- `fix-user-passwords.php` - Reset/fix user passwords

### Test Scripts (test-*.php)
Testing and verification scripts:
- `test-actual-login.php` - Test actual login flow
- `test-admin-login.php` - Test admin login
- `test-admin-users.php` - Test admin user functionality
- `test-auth-direct.php` - Direct authentication test
- `test-csrf-fix.php` - CSRF protection test
- `test-email-system.php` - Email system test
- `test-logger.php` - Logger functionality test
- `test-login-clean.php` - Clean login test
- `test-login-debug.php` - Login debugging
- `test-login-final.php` - Final login test
- `test-password-verify.php` - Password verification test
- `test-permanent-fix.php` - Permanent fix verification
- `test-specific-users.php` - Test specific users
- `test-user-check.php` - User check test
- `test-users-overview.php` - Users overview test

### Utility Scripts
- `filter-auth-logs.php` - Filter authentication logs
- `list-users.php` - List all users
- `sanity-check.php` - General sanity check
- `sanity-check-phase8-5.php` - Phase 8.5 sanity check
- `show-recent-logs.php` - Display recent logs
- `simple-admin-test.php` - Simple admin test
- `simple-test-users.php` - Simple user test
- `view-error-log.php` - View error logs

## 🔒 Security Considerations

1. **Never expose these scripts publicly**
2. **Remove or disable in production**
3. **Use proper authentication before running**
4. **Review scripts before execution**
5. **Backup database before running fix scripts**

## 📝 Usage Guidelines

### Before Running Any Script:

1. **Backup your database**
   ```bash
   php create-backup.php
   ```

2. **Review the script code**
   - Check for hardcoded credentials
   - Understand what it does
   - Verify it's safe to run

3. **Run in development environment only**
   - Never run on production
   - Use test database if possible

4. **Check logs after execution**
   ```bash
   php view-error-log.php
   ```

## 🗑️ Cleanup

These scripts should be:
- Reviewed periodically
- Removed if no longer needed
- Never committed with sensitive data
- Excluded from production deployments

## 📊 Organization Date

**Moved to devtools/:** December 7, 2025  
**Reason:** Better project organization and security  
**Total Scripts:** 37

## 🔗 Related Directories

- `/scripts/tools/` - Production-ready utility scripts
- `/scripts/tests/` - Automated test scripts
- `/scripts/fixes/` - Production fix scripts
- `/temp-tests/` - Temporary test files

## ⚡ Quick Commands

```bash
# List all scripts
ls -la devtools/

# Find specific script
ls devtools/ | grep "test-login"

# Run a script (example)
php devtools/check-admin.php

# View recent modifications
ls -lt devtools/ | head -10
```

## 📞 Support

If you need to use any of these scripts:
1. Review the code first
2. Backup your data
3. Test in development
4. Document any issues

---

**Last Updated:** December 7, 2025  
**Maintained by:** Development Team
