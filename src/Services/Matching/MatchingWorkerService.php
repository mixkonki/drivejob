<?php

declare(strict_types=1);

namespace DriveJob\Services\Matching;

require_once __DIR__ . "/../../RBAC/DB.php";
require_once __DIR__ . "/MatchingCacheService.php";
require_once __DIR__ . "/MatchingQueue.php";
require_once __DIR__ . "/MatchingMetrics.php";

use DriveJob\RBAC\DB;
use PDO;

// Import supervisor interfaces
require_once __DIR__ . "/../../Services/Interfaces/ServiceInterface.php";
require_once __DIR__ . "/../../Services/Interfaces/MonitorableInterface.php";
require_once __DIR__ . "/../../Services/HealthStatus.php";

use Drivejob\Services\Interfaces\ServiceInterface;
use Drivejob\Services\Interfaces\MonitorableInterface;
use Drivejob\Services\HealthStatus;

/**
 * Hardening:
 * - Idempotency guard (skip επανάληψη του ίδιου job για κάποιο TTL)
 * - Pacing (max N jobs/sec)
 * - Εμπλουτισμένο health state
 */
final class MatchingWorkerService implements ServiceInterface, MonitorableInterface
{
    private MatchingQueue $queue;
    private MatchingCacheService $cache;

    private int $cacheTtlSec;
    private int $idemTtlSec;
    private int $maxPerSec;

    private int $processed = 0;
    private int $errors = 0;
    private int $dedupSkipped = 0;
    private ?string $lastError = null;
    private HealthStatus $healthStatus = HealthStatus::HEALTHY;
    private int $lastHealthCheck = 0;

    private int $secWindow = 0;
    private int $secCount = 0;

    public function __construct(?int $cacheTtlSec = 300, ?int $idemTtlSec = 60, ?int $maxPerSec = 5)
    {
        $this->queue = new MatchingQueue();
        $this->cache = new MatchingCacheService(null, $cacheTtlSec ?? 300);

        $this->cacheTtlSec = $cacheTtlSec ?? 300;
        $this->idemTtlSec = $idemTtlSec ?? 60;
        $this->maxPerSec = $maxPerSec ?? 5;
    }

    public function getName(): string
    {
        return "matching_worker";
    }

    public function getVersion(): string
    {
        return "2.0.0";
    }

    public function isOperational(): bool
    {
        return $this->healthStatus === HealthStatus::HEALTHY;
    }

    public function getHealth(): HealthStatus
    {
        return $this->healthStatus;
    }

    public function getDependencies(): array
    {
        return []; // No dependencies for this service
    }

    public function getMetadata(): array
    {
        return [
            'description' => 'Hardened matching worker service with idempotency and pacing',
            'tags' => ["matching", "worker", "cache", "queue"],
            'capabilities' => ['job_processing', 'caching', 'metrics_collection'],
            'queue_size' => $this->queue->size(),
            'processed_count' => $this->processed,
            'error_count' => $this->errors,
            'dedup_skipped' => $this->dedupSkipped,
            'max_per_sec' => $this->maxPerSec
        ];
    }

    public function initialize(array $config = []): bool
    {
        try {
            // Initialize cache and queue
            $this->cache = new MatchingCacheService(null, $config['ttl'] ?? $this->cacheTtlSec);
            $this->queue = new MatchingQueue();

            $this->logInfo("MatchingWorkerService initialized successfully");
            return true;
        } catch (\Throwable $e) {
            $this->logError("Failed to initialize MatchingWorkerService", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function shutdown(): bool
    {
        try {
            $this->logInfo("MatchingWorkerService shutdown gracefully", [
                'total_processed' => $this->processed,
                'total_errors' => $this->errors,
                'total_dedup_skipped' => $this->dedupSkipped
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logError("Error during MatchingWorkerService shutdown", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // Process one job per tick to avoid blocking main loop
    public function execute(array $context = []): \Drivejob\Services\ServiceResult
    {
        try {
            $this->tick();
            return \Drivejob\Services\ServiceResult::success([
                'processed' => $this->processed,
                'queue_size' => $this->queue->size(),
                'errors' => $this->errors,
                'dedup_skipped' => $this->dedupSkipped
            ]);
        } catch (\Throwable $e) {
            $this->errors++;
            $this->lastError = $e->getMessage();
            $this->healthStatus = HealthStatus::UNHEALTHY;

            return \Drivejob\Services\ServiceResult::failure($e, [
                'processed' => $this->processed,
                'errors' => $this->errors
            ]);
        }
    }

    // Main processing method with hardening features
    public function tick(): void
    {
        // Pacing (soft backpressure)
        $nowSec = time();
        if ($this->secWindow !== $nowSec) {
            $this->secWindow = $nowSec;
            $this->secCount = 0;
        }
        if ($this->secCount >= $this->maxPerSec) {
            return; // Don't process more than max per second
        }

        $job = $this->queue->dequeue();
        if (!$job) {
            return; // No jobs to process
        }

        $jobId = (int)($job["job_id"] ?? 0);
        if ($jobId <= 0) {
            return; // Invalid job
        }

        // Idempotency guard (skip duplicate jobs for TTL period)
        $idemKey = "match:idem:job:" . $jobId;
        $idemSeen = $this->cache->get($idemKey);
        if ($idemSeen !== null) {
            $this->dedupSkipped++;
            return;
        }
        // Mark as recently processed
        $this->cache->set($idemKey, 1, $this->idemTtlSec);

        $this->secCount++;

        $cacheKey = "match:v1:job:" . $jobId;
        $t0 = microtime(true);
        $cacheHit = 0;

        try {
            $matches = $this->cache->get($cacheKey);
            if ($matches !== null) {
                $cacheHit = 1;
            } else {
                $pdo = DB::pdo();
                // Safe query to simulate processing with some latency
                $count = 0;
                try {
                    $count = (int)$pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
                } catch (\Throwable $e) {
                    $count = 0;
                }

                $top = min(10, $count);
                $matches = $top > 0 ? range(1, $top) : [];
                $this->cache->set($cacheKey, $matches, $this->cacheTtlSec);
            }

            $dt = (int)round((microtime(true) - $t0) * 1000);
            MatchingMetrics::insert($jobId, $dt, $cacheHit);
            $this->processed++;

            // Update health status
            $this->healthStatus = HealthStatus::HEALTHY;
            $this->lastError = null;

            $this->logInfo("Job processed successfully", [
                'job_id' => $jobId,
                'cache_hit' => $cacheHit,
                'duration_ms' => $dt,
                'sec_count' => $this->secCount
            ]);
        } catch (\Throwable $e) {
            $this->errors++;
            $this->lastError = $e->getMessage();
            $this->healthStatus = HealthStatus::UNHEALTHY;

            $this->logError("Job processing failed", [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
        }
    }

    // MonitorableInterface implementation
    public function performHealthCheck(): HealthStatus
    {
        $this->lastHealthCheck = time();

        // Check if we can access queue and cache
        try {
            $queueSize = $this->queue->size();
            $cacheTest = $this->cache->get('health_check_test');

            // Service is healthy if no critical errors and can access storage
            if ($this->errors < 10 && $queueSize >= 0) {
                return HealthStatus::HEALTHY;
            } elseif ($this->errors < 50) {
                return HealthStatus::UNHEALTHY;
            } else {
                return HealthStatus::UNHEALTHY;
            }
        } catch (\Throwable $e) {
            return HealthStatus::UNHEALTHY;
        }
    }

    public function getMetrics(): array
    {
        return [
            'processed_jobs' => $this->processed,
            'error_count' => $this->errors,
            'dedup_skipped' => $this->dedupSkipped,
            'queue_size' => $this->queue->size(),
            'health_status' => $this->healthStatus->value,
            'last_health_check' => $this->lastHealthCheck,
            'last_error' => $this->lastError,
            'max_per_sec' => $this->maxPerSec,
            'current_sec_count' => $this->secCount
        ];
    }

    public function getLastHealthCheck(): int
    {
        return $this->lastHealthCheck;
    }

    public function getHealthStatus(): array
    {
        return [
            "ok" => $this->healthStatus === HealthStatus::HEALTHY,
            "processed" => $this->processed,
            "errors" => $this->errors,
            "dedup_skipped" => $this->dedupSkipped,
            "last_error" => $this->lastError,
            "max_per_sec" => $this->maxPerSec,
            "current_sec_count" => $this->secCount
        ];
    }

    private function logInfo(string $message, array $context = []): void
    {
        // Simple logging - could be enhanced with proper logger
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        error_log("[MatchingWorkerService] INFO: {$message}{$contextStr}");
    }

    private function logError(string $message, array $context = []): void
    {
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        error_log("[MatchingWorkerService] ERROR: {$message}{$contextStr}");
    }
}
