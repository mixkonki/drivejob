<?php

declare(strict_types=1);

require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/Services/Matching/MatchingCacheService.php";
require_once __DIR__ . "/../../src/Services/Matching/MatchingQueue.php";
require_once __DIR__ . "/../../src/Services/Matching/MatchingMetrics.php";

use DriveJob\Services\Matching\MatchingCacheService;
use DriveJob\Services\Matching\MatchingQueue;
use DriveJob\Services\Matching\MatchingMetrics;

$root = realpath(__DIR__ . "/..");
$max  = 50;  // default process up to 50 jobs
$ttl  = 300; // cache TTL seconds
foreach ($argv as $a) {
    if (str_starts_with($a, "--max="))  $max = max(1, (int)substr($a, 6));
    if (str_starts_with($a, "--ttl="))  $ttl = max(30, (int)substr($a, 6));
}

$cache = new MatchingCacheService(null, $ttl);
$q     = new MatchingQueue();

$processed = 0;
while ($processed < $max) {
    $job = $q->dequeue();
    if (!$job) break;

    $jobId = (int)($job["job_id"] ?? 0);
    if ($jobId <= 0) continue;

    $cacheKey = "match:v1:job:" . $jobId;
    $t0 = microtime(true);
    $cacheHit = 0;

    $matches = $cache->get($cacheKey);
    if ($matches !== null) {
        $cacheHit = 1;
    } else {
        // --- Simulated compute: κάνε κάτι «ρεαλιστικό» αλλά ακίνδυνο
        // Δουλεύουμε με ελαφριά SELECT ώστε να υπάρχει latency.
        try {
            require_once __DIR__ . "/../../src/RBAC/DB.php";
            $pdo = \DriveJob\RBAC\DB::pdo();

            // Προσπάθησε να μετρήσεις «υποψήφιους» από drivers (αν δεν υπάρχει, πέφτει στο 0)
            $count = 0;
            try {
                $count = (int)$pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
            } catch (\Throwable $e) {
                $count = 0;
            }

            // Συνθετικό αποτέλεσμα (π.χ. ids 1..min(10,count))
            $top = min(10, $count);
            $matches = $top > 0 ? range(1, $top) : [];
            $cache->set($cacheKey, $matches, $ttl);
        } catch (\Throwable $e) {
            $matches = [];
        }
    }

    $dt = (int)round((microtime(true) - $t0) * 1000);
    MatchingMetrics::insert($jobId, $dt, $cacheHit);
    $processed++;
}

echo json_encode(["ok" => true, "processed" => $processed, "queue_left" => $q->size()], JSON_UNESCAPED_UNICODE);
