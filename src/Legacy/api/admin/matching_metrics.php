<?php

declare(strict_types=1);

// --- Robust bootstrap ---
$bootstrap = realpath(__DIR__ . "/../_rbac_bootstrap.php");
if ($bootstrap === false || !is_file($bootstrap)) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "bootstrap_not_found", "path" => __DIR__ . "/../_rbac_bootstrap.php"], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $bootstrap;

// Safety fallback (dev) – αν για οποιονδήποτε λόγο δεν ήρθε από το bootstrap
if (!function_exists("currentUserId")) {
    function currentUserId(): ?int
    {
        // default σε admin id=1 για dev
        return isset($_GET["uid"]) ? max(1, (int)$_GET["uid"]) : 1;
    }
}

require_once dirname(__DIR__, 4) . "/src/RBAC/DB.php";
require_once dirname(__DIR__, 4) . "/src/RBAC/RBAC.php";

// ⚠️ ΜΗΝ βάλεις "use PDO;" εδώ — προκαλεί warning και χαλάει το JSON.
use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

header("Content-Type: application/json; charset=utf-8");

// --- RBAC guard (admin only) ---
RBAC::requirePermission((int)currentUserId(), "admin.access");

$pdo = DB::pdo();

// Pull last N samples for percentiles (computed in PHP for compatibility)
$N = 1000;
$rows = [];
try {
    $st = $pdo->query("SELECT duration_ms, cache_hit, created_at FROM matching_metrics ORDER BY id DESC LIMIT {$N}");
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (\Throwable $e) {
    echo json_encode(["error" => "sql_error", "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$durations = array_map(fn($r) => (int)$r["duration_ms"], $rows);
sort($durations);

$percentile = function (array $arr, float $p) {
    $n = count($arr);
    if ($n === 0) return null;
    $k = ($p / 100) * ($n - 1);
    $f = (int)floor($k);
    $c = (int)ceil($k);
    if ($f === $c) return $arr[$f];
    return (int)round($arr[$f] + ($k - $f) * ($arr[$c] - $arr[$f]));
};

$hitSum = array_sum(array_map(fn($r) => (int)$r["cache_hit"], $rows));
$hitRate = (count($rows) > 0) ? ($hitSum / count($rows)) : 0.0;

// Queue depth (file-based)
$queueDir = realpath(dirname(__DIR__, 4) . "/storage/queue/matching");
$queueDepth = 0;
if ($queueDir && is_dir($queueDir)) {
    $files = glob($queueDir . DIRECTORY_SEPARATOR . "*.json");
    $queueDepth = $files ? count($files) : 0;
}

echo json_encode([
    "samples" => count($rows),
    "p50_ms" => $percentile($durations, 50),
    "p95_ms" => $percentile($durations, 95),
    "p99_ms" => $percentile($durations, 99),
    "hit_rate" => round($hitRate, 3),
    "queue_depth" => $queueDepth,
    "last_10" => array_slice($rows, 0, 10),
], JSON_UNESCAPED_UNICODE);
