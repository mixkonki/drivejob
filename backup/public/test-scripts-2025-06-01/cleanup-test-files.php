<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "<h1>Καθαρισμός Test/Debug Αρχείων</h1>";

// Define patterns for files to move
$patterns = [
    'fix-*.php',
    'test-*.php',
    'check-*.php',
    'debug-*.php',
    'populate-*.php',
    'run-*.php'
];

$publicDir = ROOT_DIR . '/public';
$backupDir = ROOT_DIR . '/backup/public/test-scripts-2025-06-01';

// Create backup directory if it doesn't exist
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
    echo "<p>✅ Δημιουργήθηκε φάκελος backup: $backupDir</p>";
}

$movedFiles = [];
$skippedFiles = [];

// Important files to keep
$keepFiles = [
    'index.php',
    'login.php',
    'logout.php',
    'contact.php',
    'about.php',
    '.htaccess'
];

// Process each pattern
foreach ($patterns as $pattern) {
    $files = glob($publicDir . '/' . $pattern);

    foreach ($files as $file) {
        $filename = basename($file);

        // Skip if it's an important file
        if (in_array($filename, $keepFiles)) {
            $skippedFiles[] = $filename;
            continue;
        }

        // Move to backup
        $destination = $backupDir . '/' . $filename;

        if (rename($file, $destination)) {
            $movedFiles[] = $filename;
        }
    }
}

echo "<h2>Αποτελέσματα Καθαρισμού</h2>";

if (!empty($movedFiles)) {
    echo "<h3>Μεταφέρθηκαν στο backup (" . count($movedFiles) . " αρχεία):</h3>";
    echo "<ul>";
    foreach ($movedFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

if (!empty($skippedFiles)) {
    echo "<h3>Παραλείφθηκαν (σημαντικά αρχεία):</h3>";
    echo "<ul>";
    foreach ($skippedFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

// Also check subdirectories
$subdirs = ['api', 'companies', 'drivers'];
foreach ($subdirs as $subdir) {
    $subdirPath = $publicDir . '/' . $subdir;
    if (is_dir($subdirPath)) {
        $testFiles = [];
        foreach ($patterns as $pattern) {
            $files = glob($subdirPath . '/' . $pattern);
            $testFiles = array_merge($testFiles, $files);
        }

        if (!empty($testFiles)) {
            $backupSubdir = $backupDir . '/' . $subdir;
            if (!file_exists($backupSubdir)) {
                mkdir($backupSubdir, 0777, true);
            }

            echo "<h3>Αρχεία στο $subdir:</h3>";
            echo "<ul>";
            foreach ($testFiles as $file) {
                $filename = basename($file);
                $destination = $backupSubdir . '/' . $filename;
                if (rename($file, $destination)) {
                    echo "<li>✅ $filename</li>";
                }
            }
            echo "</ul>";
        }
    }
}

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>Καθαρισμός Ολοκληρώθηκε!</h3>";
echo "<p>Όλα τα test/debug αρχεία μεταφέρθηκαν στο:<br>";
echo "<code>$backupDir</code></p>";
echo "<p>Το project είναι τώρα καθαρό από test scripts!</p>";
echo "</div>";

// Show current public directory status
echo "<h2>Τρέχουσα Κατάσταση Public Directory</h2>";
$remainingFiles = scandir($publicDir);
$remainingFiles = array_diff($remainingFiles, ['.', '..']);

echo "<p>Αρχεία που παρέμειναν στο public:</p>";
echo "<ul>";
foreach ($remainingFiles as $file) {
    if (is_file($publicDir . '/' . $file)) {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

echo "<p><a href='" . BASE_URL . "' class='btn btn-primary'>Επιστροφή στην Αρχική</a></p>";
