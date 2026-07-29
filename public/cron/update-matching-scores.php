<?php

/**
 * Cron Job για ενημέρωση matching scores
 * Τρέχει κάθε ώρα για να ενημερώνει τα matching scores
 * 
 * Usage: php public/cron/update-matching-scores.php
 * Cron: 0 * * * * /usr/bin/php /path/to/drivejob/public/cron/update-matching-scores.php
 */

// Ορισμός του ROOT_DIR
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Φόρτωση του bootstrap
require_once ROOT_DIR . '/src/bootstrap.php';

use Drivejob\Core\Logger;
use Drivejob\Core\Database;
use Drivejob\Services\RealTimeMatchingService;
use Drivejob\Services\EventHookService;

// Έλεγχος αν τρέχει από command line
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

try {
    Logger::info("=== Starting Matching Scores Update Cron Job ===");

    // Αρχικοποίηση υπηρεσιών
    $pdo = Database::getInstance()->getConnection();
    $realTimeService = new RealTimeMatchingService($pdo);
    $eventHookService = new EventHookService($pdo, $realTimeService);

    // Έλεγχος για lock file (αποφυγή παράλληλων εκτελέσεων)
    $lockFile = ROOT_DIR . '/temp/matching-cron.lock';
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        if (time() - $lockTime < 3600) { // 1 hour timeout
            Logger::warning("Matching cron job already running, skipping");
            exit(0);
        } else {
            // Remove stale lock file
            unlink($lockFile);
        }
    }

    // Δημιουργία lock file
    if (!is_dir(dirname($lockFile))) {
        mkdir(dirname($lockFile), 0755, true);
    }
    file_put_contents($lockFile, time());

    // Στατιστικά πριν την ενημέρωση
    $statsBefore = $realTimeService->getMatchingStats();
    Logger::info("Stats before update", $statsBefore);

    // Batch update όλων των matching scores
    $result = $realTimeService->batchUpdateAllMatches();

    if ($result['success']) {
        Logger::info("Batch update completed successfully", [
            'drivers_processed' => $result['drivers_processed'],
            'jobs_processed' => $result['jobs_processed'],
            'total_updates' => $result['total_updates']
        ]);
    } else {
        Logger::error("Batch update failed: " . $result['error']);
    }

    // Καθαρισμός παλιών scores (παλαιότερα από 30 ημέρες)
    $cleanedRows = $realTimeService->cleanupOldScores(30);
    Logger::info("Cleaned up {$cleanedRows} old matching scores");

    // Στατιστικά μετά την ενημέρωση
    $statsAfter = $realTimeService->getMatchingStats();
    Logger::info("Stats after update", $statsAfter);

    // Αποστολή summary email στους admins (μόνο αν υπάρχουν σημαντικές αλλαγές)
    if ($result['total_updates'] > 100) {
        sendAdminSummary($result, $statsBefore, $statsAfter);
    }

    // Αφαίρεση lock file
    unlink($lockFile);

    Logger::info("=== Matching Scores Update Cron Job Completed ===");
} catch (\Exception $e) {
    Logger::error("Fatal error in matching cron job: " . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);

    // Αφαίρεση lock file σε περίπτωση σφάλματος
    if (isset($lockFile) && file_exists($lockFile)) {
        unlink($lockFile);
    }

    exit(1);
}

/**
 * Αποστολή summary email στους διαχειριστές
 */
function sendAdminSummary($result, $statsBefore, $statsAfter)
{
    try {
        $config = require ROOT_DIR . '/config/notifications.php';

        if (empty($config['admin_emails'])) {
            return;
        }

        $emailService = new \Drivejob\Services\EmailService(
            $config['smtp_host'],
            $config['smtp_port'],
            $config['smtp_username'],
            $config['smtp_password'],
            $config['sender_email'],
            $config['sender_name']
        );

        $subject = "DriveJob - Matching System Update Summary";
        $message = generateSummaryEmail($result, $statsBefore, $statsAfter);

        foreach ($config['admin_emails'] as $adminEmail) {
            $emailService->send($adminEmail, $subject, $message);
        }

        Logger::info("Admin summary email sent");
    } catch (\Exception $e) {
        Logger::error("Failed to send admin summary: " . $e->getMessage());
    }
}

/**
 * Δημιουργία HTML email για το summary
 */
function generateSummaryEmail($result, $statsBefore, $statsAfter)
{
    $date = date('d/m/Y H:i');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>DriveJob Matching System Update</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; }
            .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .stats-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .stats-table th, .stats-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            .stats-table th { background-color: #f2f2f2; }
            .success { color: #27ae60; font-weight: bold; }
            .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>DriveJob Matching System Update</h1>
            <p>Automated Update Report - {$date}</p>
        </div>
        <div class='content'>
            <h2>Update Summary</h2>
            <table class='stats-table'>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Drivers Processed</td>
                    <td class='success'>{$result['drivers_processed']}</td>
                </tr>
                <tr>
                    <td>Jobs Processed</td>
                    <td class='success'>{$result['jobs_processed']}</td>
                </tr>
                <tr>
                    <td>Total Score Updates</td>
                    <td class='success'>{$result['total_updates']}</td>
                </tr>
            </table>
            
            <h2>Statistics Comparison</h2>
            <table class='stats-table'>
                <tr>
                    <th>Metric</th>
                    <th>Before</th>
                    <th>After</th>
                    <th>Change</th>
                </tr>
                <tr>
                    <td>Total Matches</td>
                    <td>" . ($statsBefore['total_matches'] ?? 0) . "</td>
                    <td>" . ($statsAfter['total_matches'] ?? 0) . "</td>
                    <td>" . (($statsAfter['total_matches'] ?? 0) - ($statsBefore['total_matches'] ?? 0)) . "</td>
                </tr>
                <tr>
                    <td>High Quality Matches (≥70%)</td>
                    <td>" . ($statsBefore['high_quality_matches'] ?? 0) . "</td>
                    <td>" . ($statsAfter['high_quality_matches'] ?? 0) . "</td>
                    <td>" . (($statsAfter['high_quality_matches'] ?? 0) - ($statsBefore['high_quality_matches'] ?? 0)) . "</td>
                </tr>
                <tr>
                    <td>Average Score</td>
                    <td>" . ($statsBefore['average_score'] ?? 0) . "%</td>
                    <td>" . ($statsAfter['average_score'] ?? 0) . "%</td>
                    <td>" . round(($statsAfter['average_score'] ?? 0) - ($statsBefore['average_score'] ?? 0), 2) . "%</td>
                </tr>
            </table>
            
            <p>The matching system has been successfully updated. All scores are now current and ready for real-time matching.</p>
        </div>
        <div class='footer'>
            <p>This is an automated report from the DriveJob matching system.</p>
            <p>&copy; " . date('Y') . " DriveJob. All rights reserved.</p>
        </div>
    </body>
    </html>
    ";
}

echo "Matching scores update completed successfully\n";
