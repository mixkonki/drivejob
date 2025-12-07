<?php
$logPath = 'c:/wamp64/logs/php_error.log';
$lines = file($logPath);
$recent = array_slice($lines, -50);
echo "=== Last 50 Lines ===\n\n";
foreach ($recent as $line) {
    echo $line;
}
