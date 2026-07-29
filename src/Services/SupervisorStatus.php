<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Enumeration of possible supervisor status values.
 *
 * Represents the health and operational state of a supervisor component.
 */
enum SupervisorStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case CRITICAL = 'critical';
    case UNKNOWN = 'unknown';

    /**
     * Check if the status indicates a healthy state.
     */
    public function isHealthy(): bool
    {
        return $this === self::HEALTHY;
    }

    /**
     * Check if the status indicates a failure condition.
     */
    public function isFailure(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Get a human-readable description of the status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HEALTHY => 'All services are operating normally',
            self::DEGRADED => 'Some services are experiencing issues but system is functional',
            self::CRITICAL => 'Critical failures detected, immediate attention required',
            self::UNKNOWN => 'Status cannot be determined'
        };
    }

    /**
     * Get the severity level (higher number = more severe).
     */
    public function getSeverity(): int
    {
        return match ($this) {
            self::HEALTHY => 0,
            self::DEGRADED => 1,
            self::CRITICAL => 2,
            self::UNKNOWN => 3
        };
    }
}
