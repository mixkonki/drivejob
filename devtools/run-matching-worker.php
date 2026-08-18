<?php

/**
 * Worker ουράς matching — αδειάζει το storage/queue/matching (Πακέτο 4)
 *
 * Χρήση:  php devtools/run-matching-worker.php [max_jobs=100]
 *
 * Για κάθε job της ουράς:
 *   1. Υπολογίζει ξανά τα matches της αγγελίας (γρήγορο rule-based — ΟΧΙ AI,
 *      το AI τρέχει στοχευμένα από το cron update-matching-scores.php)
 *   2. Καταγράφει duration/cache στο matching_metrics (τροφοδοτεί τα admin KPIs)
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

// Μαζική λειτουργία: χωρίς κλήσεις AI (γρήγορο & δωρεάν)
define('DRIVEJOB_DISABLE_AI', true);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/Matching/MatchingQueue.php';
require_once __DIR__ . '/../src/Services/Matching/MatchingMetrics.php';
require_once __DIR__ . '/../src/RBAC/DB.php';

use DriveJob\Services\Matching\MatchingQueue;
use DriveJob\Services\Matching\MatchingMetrics;

$maxJobs = max(1, (int) ($argv[1] ?? 100));
$queue = new MatchingQueue();
$realTime = new \Drivejob\Services\RealTimeMatchingService(
    \Drivejob\Core\Database::getInstance()->getConnection()
);

echo "🔄 Matching worker — μέχρι {$maxJobs} jobs\n\n";

$processed = 0;
$errors = 0;
$seen = [];

while ($processed + $errors < $maxJobs) {
    $job = $queue->dequeue();
    if (!$job) {
        break; // άδεια ουρά
    }

    $jobId = (int) ($job['job_id'] ?? 0);
    if ($jobId <= 0) {
        $errors++;
        continue;
    }

    // Idempotency μέσα στο ίδιο run
    $cacheHit = isset($seen[$jobId]) ? 1 : 0;
    $t0 = microtime(true);

    try {
        if (!$cacheHit) {
            $result = $realTime->processNewJobListing($jobId);
            $seen[$jobId] = true;
            $matchCount = is_array($result) ? count($result) : 0;
        } else {
            $matchCount = 0; // ήδη υπολογισμένο σε αυτό το run
        }

        $dt = (int) round((microtime(true) - $t0) * 1000);
        MatchingMetrics::insert($jobId, $dt, $cacheHit);
        $processed++;
        echo "  ✅ job {$jobId}: " . ($cacheHit ? "cache hit" : "{$matchCount} matches") . " σε {$dt}ms\n";
    } catch (\Throwable $e) {
        $errors++;
        echo "  ❌ job {$jobId}: {$e->getMessage()}\n";
    }
}

echo "\n🏁 Ολοκληρώθηκε: {$processed} επεξεργασμένα, {$errors} σφάλματα.\n";
echo "Δες τα KPIs: ", (defined('BASE_URL') ? BASE_URL : 'http://drivejob.test/'), "admin/dashboard\n";
