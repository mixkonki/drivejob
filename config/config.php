<?php

// Ορισμός σταθερών
define('ROOT_DIR', dirname(__DIR__)); // Ριζικός φάκελος του project
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/drivejob/public/'); // URL για τα public assets

// Επιστρέφει τις ρυθμίσεις της βάσης δεδομένων
return [
    'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
    'db_name' => $_ENV['DB_DATABASE'] ?? 'drivejob',
    'db_user' => $_ENV['DB_USERNAME'] ?? 'root',
    'db_pass' => $_ENV['DB_PASSWORD'] ?? '',
];