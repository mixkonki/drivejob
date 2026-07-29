<?php
require_once __DIR__ . "/../../src/Services/Matching/MatchingQueue.php";

use DriveJob\Services\Matching\MatchingQueue;

$q = new MatchingQueue();
$jobs = [];
for ($i = 1; $i <= 10; $i++) {
    $jobs[] = ["job_id" => $i];
}
foreach ($jobs as $j) $q->enqueue($j);
echo json_encode(["ok" => true, "enqueued" => count($jobs)], JSON_UNESCAPED_UNICODE);
