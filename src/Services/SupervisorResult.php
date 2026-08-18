<?php

declare(strict_types=1);

namespace Drivejob\Services;

use Throwable;

/**
 * Result object returned by supervisor operations.
 *
 * Contains the outcome of a supervision process including status,
 * metrics, errors, and timing information.
 */
class SupervisorResult
{
    public SupervisorStatus $status;
    public array $metrics;
    public ?Throwable $error;
    public float $executionTime;
    public array $metadata;

    /**
     * Create a new SupervisorResult instance.
     */
    public function __construct(
        SupervisorStatus $status = SupervisorStatus::UNKNOWN,
        array $metrics = [],
        ?Throwable $error = null,
        float $executionTime = 0.0,
        array $metadata = []
    ) {
        $this->status = $status;
        $this->metrics = $metrics;
        $this->error = $error;
        $this->executionTime = $executionTime;
        $this->metadata = $metadata;
    }

    /**
     * Create a successful result.
     */
    public static function success(
        array $metrics = [],
        float $executionTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            SupervisorStatus::HEALTHY,
            $metrics,
            null,
            $executionTime,
            $metadata
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(
        Throwable $error,
        SupervisorStatus $status = SupervisorStatus::CRITICAL,
        array $metrics = [],
        float $executionTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            $status,
            $metrics,
            $error,
            $executionTime,
            $metadata
        );
    }

    /**
     * Create a degraded result.
     */
    public static function degraded(
        array $metrics = [],
        ?Throwable $error = null,
        float $executionTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            SupervisorStatus::DEGRADED,
            $metrics,
            $error,
            $executionTime,
            $metadata
        );
    }

    /**
     * Check if the result indicates success.
     */
    public function isSuccessful(): bool
    {
        return $this->status->isHealthy() && $this->error === null;
    }

    /**
     * Check if the result indicates failure.
     */
    public function isFailure(): bool
    {
        return $this->status->isFailure() || $this->error !== null;
    }

    /**
     * Get a summary of the result.
     */
    public function getSummary(): array
    {
        return [
            'status' => $this->status->value,
            'status_description' => $this->status->getDescription(),
            'has_error' => $this->error !== null,
            'error_message' => $this->error?->getMessage(),
            'execution_time' => $this->executionTime,
            'metrics_count' => count($this->metrics),
            'metadata_count' => count($this->metadata)
        ];
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'metrics' => $this->metrics,
            'error' => $this->error?->getMessage(),
            'execution_time' => $this->executionTime,
            'metadata' => $this->metadata,
            'summary' => $this->getSummary()
        ];
    }
}
