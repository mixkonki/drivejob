<?php

namespace DriveJob\Services\Matching;

require_once __DIR__ . "/../../RBAC/DB.php";

use DriveJob\RBAC\DB;
use PDO;

final class MatchingMetrics
{
    public static function insert(int $jobId, int $durationMs, int $cacheHit): void
    {
        $pdo = DB::pdo();
        $st = $pdo->prepare("INSERT INTO matching_metrics (job_id, duration_ms, cache_hit, created_at) VALUES (:j,:d,:c, NOW())");
        $st->execute([":j" => $jobId, ":d" => $durationMs, ":c" => $cacheHit]);
    }

    public static function aggregates(): array
    {
        $pdo = DB::pdo();
        $agg = $pdo->query("
            SELECT
              COUNT(*) AS samples,
              AVG(duration_ms) AS avg_ms,
              SUM(CASE WHEN cache_hit=1 THEN 1 ELSE 0 END)/NULLIF(COUNT(*),0) AS hit_rate
            FROM matching_metrics
        ")->fetch(PDO::FETCH_ASSOC) ?: [];
        return $agg;
    }
}
