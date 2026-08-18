<?php

// Ορισμός της χρονικής ζώνης
date_default_timezone_set('Europe/Athens');

// Ορισμός σταθερών
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__)); // Ριζικός φάκελος του project
}

/* ───────────────────────────────────────────────────────────────
   Περιβάλλον εκτέλεσης (Πακέτο 9)
   Ορίζεται από το .env:  APP_ENV=local|production  ·  APP_DEBUG=true|false
   Χωρίς .env (π.χ. πρώτη εγκατάσταση) πέφτει σε ασφαλείς προεπιλογές:
   production + debug off — ΠΟΤΕ δεν εκθέτουμε σφάλματα κατά λάθος.
   ─────────────────────────────────────────────────────────────── */
$appEnv = strtolower($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production');
defined('ENVIRONMENT') or define('ENVIRONMENT', $appEnv === 'production' ? 'production' : 'development');

$appDebug = strtolower((string) ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: ''));
define('DEBUG_MODE', in_array($appDebug, ['1', 'true', 'yes', 'on'], true) && ENVIRONMENT !== 'production');

/* ───────────────────────────────────────────────────────────────
   BASE_URL
   1. APP_URL από το .env (συνιστάται σε παραγωγή, απαραίτητο για CLI/cron)
   2. Αυτόματος εντοπισμός από το αίτημα (σωστό scheme πίσω από proxy)
   3. Τοπικό fallback drivejob.test
   ─────────────────────────────────────────────────────────────── */
$appUrl = trim((string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: ''));

if ($appUrl !== '') {
    define('BASE_URL', rtrim($appUrl, '/') . '/');
} elseif (php_sapi_name() === 'cli') {
    define('BASE_URL', 'http://drivejob.test/');
} else {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')   // Render/Heroku/nginx proxy
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $host = $_SERVER['HTTP_HOST'] ?? 'drivejob.test';
    define('BASE_URL', ($https ? 'https://' : 'http://') . $host . '/');
}

// Είναι το τρέχον αίτημα ασφαλές; (για cookies συνεδρίας)
define('IS_HTTPS', strpos(BASE_URL, 'https://') === 0);

define('SESSION_NAMESPACE', 'drivejob'); // Namespace για τη συνεδρία

// Ρυθμίσεις συνεδρίας
define('USE_DB_SESSIONS', false); // Απενεργοποίηση χρήσης βάσης δεδομένων για τις συνεδρίες
define('SESSION_LIFETIME', 86400); // 24 ώρες
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', IS_HTTPS); // αυτόματο: true όταν το site τρέχει σε HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax'); // Αλλαγή σε 'Lax' για να αποφύγουμε προβλήματα με τα cookies

// Επιστρέφει τις ρυθμίσεις της βάσης δεδομένων
return [
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_name' => $_ENV['DB_DATABASE'] ?? 'drivejob',
    'db_user' => $_ENV['DB_USERNAME'] ?? 'root',
    'db_pass' => $_ENV['DB_PASSWORD'] ?? '',
];
