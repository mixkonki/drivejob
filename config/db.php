<?php
return [
    'host' => '127.0.0.1',
    'database' => 'drivejob',
    'username' => 'root',
    'password' => '',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
