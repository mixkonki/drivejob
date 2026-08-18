<?php

declare(strict_types=1);

$bootstrap = realpath(__DIR__ . "/../_rbac_bootstrap.php");
if ($bootstrap === false || !is_file($bootstrap)) {
    http_response_code(500);
    echo "bootstrap_not_found";
    exit;
}
require_once $bootstrap;

require_once dirname(__DIR__, 4) . "/src/RBAC/DB.php";
require_once dirname(__DIR__, 4) . "/src/RBAC/RBAC.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

RBAC::requirePermission((int)currentUserId(), "admin.access");

$pdo = DB::pdo();

$N = 1000;
$rows = $pdo->query("SELECT duration_ms, cache_hit FROM matching_metrics ORDER BY id DESC LIMIT {$N}")->fetchAll(PDO::FETCH_ASSOC);
$durations = array_map(fn($r) => (int)$r["duration_ms"], $rows);
sort($durations);

$percentile = function (array $arr, float $p) {
    $n = count($arr);
    if (!$n) return 0;
    $k = ($p / 100) * ($n - 1);
    $f = (int)floor($k);
    $c = (int)ceil($k);
    if ($f === $c) return $arr[$f];
    return (int)round($arr[$f] + ($k - $f) * ($arr[$c] - $arr[$f]));
};

$hitSum = array_sum(array_map(fn($r) => (int)$r["cache_hit"], $rows));
$hitRate = $rows ? $hitSum / count($rows) : 0;

header("Content-Type: text/plain; version=0.0.4");
echo "# HELP matching_p50_ms Matching p50 in ms\n";
echo "# TYPE matching_p50_ms gauge\n";
echo "matching_p50_ms {$percentile($durations, 50)}\n";
echo "# HELP matching_p95_ms Matching p95 in ms\n";
echo "# TYPE matching_p95_ms gauge\n";
echo "matching_p95_ms {$percentile($durations, 95)}\n";
echo "# HELP matching_p99_ms Matching p99 in ms\n";
echo "# TYPE matching_p99_ms gauge\n";
echo "matching_p99_ms {$percentile($durations, 99)}\n";
echo "# HELP matching_cache_hit_rate Cache hit ratio\n";
echo "# TYPE matching_cache_hit_rate gauge\n";
echo "matching_cache_hit_rate {$hitRate}\n";
