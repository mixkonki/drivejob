<?php

/**
 * Script για προβολή των PHP error logs
 */

echo "=== PHP Error Log Viewer ===\n\n";

// Βρες το error log file
$errorLogPath = ini_get('error_log');

if (empty($errorLogPath)) {
    // Προσπάθησε να βρεις το default log
    $possiblePaths = [
        'C:/wamp64/logs/php_error.log',
        'C:/wamp64/bin/apache/apache2.4.54/logs/error.log',
        'C:/xampp/apache/logs/error.log',
        '/var/log/php_error.log',
        '/var/log/apache2/error.log'
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $errorLogPath = $path;
            break;
        }
    }
}

if (empty($errorLogPath) || !file_exists($errorLogPath)) {
    echo "❌ Δεν βρέθηκε το error log file.\n";
    echo "Configured path: " . ($errorLogPath ?: 'not set') . "\n\n";
    echo "Δοκιμάστε να ελέγξετε χειροκίνητα στο:\n";
    echo "- C:/wamp64/logs/php_error.log\n";
    echo "- C:/wamp64/bin/apache/apache2.4.54/logs/error.log\n";
    exit(1);
}

echo "📁 Error Log Path: $errorLogPath\n";
echo "📊 File Size: " . filesize($errorLogPath) . " bytes\n\n";

// Διάβασε τις τελευταίες 100 γραμμές
$lines = file($errorLogPath);
$totalLines = count($lines);
$linesToShow = min(100, $totalLines);

echo "=== Τελευταίες $linesToShow γραμμές ===\n\n";

$startLine = max(0, $totalLines - $linesToShow);
for ($i = $startLine; $i < $totalLines; $i++) {
    echo $lines[$i];
}

echo "\n=== Τέλος Log ===\n";
echo "\nΓια να δείτε το πλήρες log, ανοίξτε το αρχείο:\n";
echo "$errorLogPath\n";
