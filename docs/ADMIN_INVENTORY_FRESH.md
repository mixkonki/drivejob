## Admin Inventory (fresh)

### public/admin/
- `public/admin/.htaccess`
- `public/admin/_rbac_bootstrap.php`
- `public/admin/_require_login.php`
- `public/admin/ai-settings.php`
- `public/admin/debug-session.php`
- `public/admin/identity_linker.php`
- `public/admin/index.php`
- `public/admin/login_post.php`
- `public/admin/login.php`
- `public/admin/logout.php`
- `public/admin/matching-dashboard.php`
- `public/admin/openai-settings.php`
- `public/admin/settings.php`
- `public/admin/test-connection.php`

### src/Views/admin/
- `src/Views/admin/dashboard.php`
- `src/Views/admin/login.php`
- `src/Views/admin/settings.php`
- `src/Views/admin/users.php`
- `src/Views/admin/monitoring/`

### API Endpoints (working)
- `public/api/admin/matching_metrics.php` ✅ (200 OK, JSON response)
- `public/api/admin/matching_metrics_prom.php`
- `public/api/admin/matching_enqueue_demo.php`
- `public/api/admin/users_overview.php`
- `public/api/admin/users.php`
- `public/api/admin/link_identity.php`

### Archived Files
- `_archive/admin_cleanup_20250822-165537/menu.php`
- `_archive/admin_cleanup_20250822-165537/rbac_dashboard.php`
- `_archive/admin_cleanup_20250822-165537/legacy_dashboard.php`
- `_archive/admin_cleanup_20250822-165537/dashboard_legacy.php`
- `_archive/admin_cleanup_20250822-165537/widgets/`

### 301 Redirects Active
- `/admin/menu.php` → `/admin/index.php`
- `/admin/rbac_dashboard.php` → `/admin/index.php`
- `/admin/legacy_dashboard.php` → `/admin/index.php`
