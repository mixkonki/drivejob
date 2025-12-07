<?php

/**
 * Filter authentication-related logs
 */

$logPath = 'c:/wamp64/logs/php_error.log';

if (!file_exists($logPath)) {
    echo "Log file not found: $logPath\n";
    exit(1);
}

echo "=== Authentication Logs ===\n\n";

$lines = file($logPath);
$keywords = ['AuthModel', 'authenticate', 'Driver', 'Password', 'verification', 'CSRF'];

foreach ($lines as $line) {
    foreach ($keywords as $keyword) {
        if (stripos($line, $keyword) !== false) {
            echo $line;
            break;
        }
    }
}

echo "\n=== End ===\n";
