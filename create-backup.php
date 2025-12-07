<?php
// Create full database backup with triggers and procedures
$ts = date('Ymd_Hi');
$backupFile = __DIR__ . '/backups/drivejob_' . $ts . '.sql';
$command = 'C:\wamp64\bin\mysql\mysql8.3.0\bin\mysqldump.exe -u root --default-character-set=utf8mb4 --routines --triggers --events drivejob > "' . $backupFile . '"';

echo "Creating backup: " . $backupFile . PHP_EOL;
echo "Command: " . $command . PHP_EOL;

exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Backup created successfully!" . PHP_EOL;
    if (file_exists($backupFile)) {
        echo "File size: " . filesize($backupFile) . " bytes" . PHP_EOL;
    } else {
        echo "Warning: Backup file not found after creation" . PHP_EOL;
    }
} else {
    echo "❌ Backup failed with code: " . $returnCode . PHP_EOL;
    echo "Output: " . implode(PHP_EOL, $output) . PHP_EOL;
}
