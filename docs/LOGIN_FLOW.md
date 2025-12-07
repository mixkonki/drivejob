# Admin Login Flow (2025-08-22)

- `public/admin/login.php` (GET): Φόρμα με CSRF token σε session.
- `public/admin/login_post.php` (POST): Rate limit (10/60s/IP), CSRF, password_verify, ΔΕΝ δέχεται πλέον `?uid=1`.
- `public/admin/logout.php`: Καθαρίζει session και επιστρέφει στο login.

## Πρακτικές
- Dev probes (π.χ. `public/api/dev/auth_probe.php`) προστατεύτηκαν με `DEV_MODE`.
- Σταδιακά συμπεριλάβετε `public/admin/_require_login.php` στην κορυφή κάθε admin σελίδας για session-based πρόσβαση.
- Σε production, ορίστε `DEV_MODE=false` στο `config/dev.php`.
