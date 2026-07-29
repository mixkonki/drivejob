<?php
$root = realpath(__DIR__ . "/..");
function run($cmd)
{
    $out = [];
    exec($cmd . " 2>&1", $out, $code);
    return ["code" => $code, "out" => implode("\n", $out)];
}

$php = "php";
$enqueue = run($php . " " . escapeshellarg($root . "/matching/enqueue_demo.php"));
$worker1  = run($php . " " . escapeshellarg($root . "/matching/worker.php") . " --max=5");
$worker2  = run($php . " " . escapeshellarg($root . "/matching/worker.php") . " --max=20");

$reportUrl = "http://localhost/drivejob/public/api/admin/matching_metrics.php";

echo json_encode([
    "enqueue" => $enqueue,
    "worker1" => $worker1,
    "worker2" => $worker2,
    "report_url" => $reportUrl
], JSON_UNESCAPED_UNICODE);
