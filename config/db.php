<?php

/**
 * Ρυθμίσεις βάσης δεδομένων (Πακέτο 9).
 * Οι τιμές έρχονται από το .env — καμία διαπιστευτήριο στον κώδικα.
 * Τα fallbacks εξυπηρετούν μόνο το τοπικό περιβάλλον ανάπτυξης.
 */

$env = static function (string $key, $default = null) {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
};

return [
    'host'     => $env('DB_HOST', '127.0.0.1'),
    'port'     => $env('DB_PORT', '3306'),
    'database' => $env('DB_DATABASE', $env('DB_NAME', 'drivejob')),
    'username' => $env('DB_USERNAME', $env('DB_USER', 'root')),
    'password' => $env('DB_PASSWORD', $env('DB_PASS', '')),
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
