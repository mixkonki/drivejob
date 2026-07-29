<?php
$root = realpath(__DIR__ . "/..");
$php = "php";

// Step 1: Enqueue some demo jobs
echo "=== Step 1: Enqueue demo jobs ===\n";
$enqueue = run($php . " " . escapeshellarg($root . "/matching/enqueue_demo.php"));
echo "Result: " . json_encode($enqueue, JSON_UNESCAPED_UNICODE) . "\n\n";

// Step 2: Run the supervisor with matching worker for a short time
echo "=== Step 2: Run Supervisor with Matching Worker (10 seconds) ===\n";
echo "Starting supervisor in background...\n";

// Run supervisor for ~10 seconds (it will auto-stop due to tick limit or sentinel file)
$runner = realpath($root . "/supervisor/run_supervisor.php");
if (file_exists($runner)) {
    $pid = popen($php . " " . escapeshellarg($runner), "r");
    sleep(12); // Wait 12 seconds to let it process

    // Create sentinel file to gracefully stop
    touch($root . "/../storage/supervisor.stop");

    if (is_resource($pid)) {
        pclose($pid);
    }
    echo "Supervisor run completed.\n\n";
} else {
    echo "Warning: Supervisor runner not found at: {$runner}\n\n";
}

// Step 3: Check results
echo "=== Step 3: Check Results ===\n";

// Check queue size
$queueSize = 0;
try {
    require_once $root . "/../src/Services/Matching/MatchingQueue.php";
    $queue = new \DriveJob\Services\Matching\MatchingQueue();
    $queueSize = $queue->size();
} catch (\Throwable $e) {
    echo "Queue check failed: " . $e->getMessage() . "\n";
}

echo "Remaining queue size: {$queueSize}\n";

// Check if metrics were recorded
try {
    require_once $root . "/../src/RBAC/DB.php";
    $pdo = \DriveJob\RBAC\DB::pdo();
    $metricsCount = (int)$pdo->query("SELECT COUNT(*) FROM matching_metrics")->fetchColumn();
    echo "Metrics recorded: {$metricsCount}\n";
} catch (\Throwable $e) {
    echo "Metrics check failed: " . $e->getMessage() . "\n";
}

echo "\n=== Integration Test Complete ===\n";
echo "If you see processed jobs and metrics, the integration is working!\n";

function run($cmd)
{
    $out = [];
    exec($cmd . " 2>&1", $out, $code);
    return ["code" => $code, "out" => implode("\n", $out)];
}
