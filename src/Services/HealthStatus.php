<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Enumeration of possible service health status values.
 *
 * Represents the health and operational state of individual services.
 */
enum HealthStatus: string
{
    case HEALTHY = 'healthy';
    case UNHEALTHY = 'unhealthy';
    case UNKNOWN = 'unknown';

    /**
     * Check if the status indicates a healthy state.
     */
    public function isHealthy(): bool
    {
        return $this === self::HEALTHY;
    }

    /**
     * Check if the status indicates an unhealthy state.
     */
    public function isUnhealthy(): bool
    {
        return $this === self::UNHEALTHY;
    }

    /**
     * Get a human-readable description of the health status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HEALTHY => 'Service is operating normally',
            self::UNHEALTHY => 'Service is experiencing issues',
            self::UNKNOWN => 'Health status cannot be determined'
        };
    }

    /**
     * Get the severity level (higher number = more severe).
     */
    public function getSeverity(): int
    {
        return match ($this) {
            self::HEALTHY => 0,
            self::UNHEALTHY => 1,
            self::UNKNOWN => 2
        };
    }
}
