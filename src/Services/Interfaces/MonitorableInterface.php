<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Services\HealthStatus;

/**
 * Interface for components that can be monitored for health and metrics.
 *
 * This interface provides a standard way to monitor the health and
 * collect metrics from various system components.
 */
interface MonitorableInterface
{
    /**
     * Get the current health status.
     *
     * @return HealthStatus Current health status
     */
    public function getHealth(): HealthStatus;

    /**
     * Get component name/identifier.
     *
     * @return string Component name
     */
    public function getName(): string;

    /**
     * Get health metrics for monitoring.
     *
     * @return array Health and performance metrics
     */
    public function getMetrics(): array;

    /**
     * Check if the component is operational.
     *
     * @return bool True if the component is operational
     */
    public function isOperational(): bool;

    /**
     * Get the last health check timestamp.
     *
     * @return int Unix timestamp of last health check
     */
    public function getLastHealthCheck(): int;

    /**
     * Perform a health check and return the result.
     *
     * @return HealthStatus Result of the health check
     */
    public function performHealthCheck(): HealthStatus;

    /**
     * Get component metadata.
     *
     * @return array Component metadata
     */
    public function getMetadata(): array;
}
