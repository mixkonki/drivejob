<?php

// Ορισμός της χρονικής ζώνης
date_default_timezone_set('Europe/Athens');

// Ορισμός σταθερών
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__)); // Ριζικός φάκελος του project
}

// Βασικό URL για τα public assets - προσαρμοσμένο για CLI
if (php_sapi_name() === 'cli') {
    define('BASE_URL', 'http://localhost/drivejob/public/'); // Default για CLI
} else {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/drivejob/public/'); // URL για τα public assets
}

define('SESSION_NAMESPACE', 'drivejob'); // Namespace για τη συνεδρία

// Επιστρέφει τις ρυθμίσεις της βάσης δεδομένων
return [
    'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
    'db_name' => $_ENV['DB_DATABASE'] ?? 'drivejob',
    'db_user' => $_ENV['DB_USERNAME'] ?? 'root',
    'db_pass' => $_ENV['DB_PASSWORD'] ?? '',
];

// Ρυθμίσεις συνεδρίας
define('USE_DB_SESSIONS', true); // Χρήση βάσης δεδομένων για τις συνεδρίες
define('SESSION_LIFETIME', 86400); // 24 ώρες
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false); // Αλλάξτε σε true για παραγωγικό περιβάλλον με HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');