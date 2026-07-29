<?php

/**
 * Test if Logger is working
 */

require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Logger;

echo "=== Testing Logger ===\n\n";

Logger::debug('TEST DEBUG MESSAGE');
Logger::info('TEST INFO MESSAGE');
Logger::warning('TEST WARNING MESSAGE');
Logger::error('TEST ERROR MESSAGE');

echo "Logs written. Check the error log file.\n";
echo "Run: php view-error-log.php\n";
