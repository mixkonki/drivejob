<?php

declare(strict_types=1);

namespace Drivejob\Services\Interfaces;

use Drivejob\Services\HealthStatus;
use Drivejob\Services\ServiceResult;

/**
 * Core service interface for supervised service operations.
 *
 * This interface defines the contract for services that can be managed
 * by supervisor components, providing lifecycle management and health monitoring.
 */
interface ServiceInterface
{
    /**
     * Execute the service with the given context.
     *
     * @param array $context Execution context parameters
     * @return ServiceResult Result of the service execution
     */
    public function execute(array $context = []): ServiceResult;

    /**
     * Get the current health status of the service.
     *
     * @return HealthStatus Current health status
     */
    public function getHealth(): HealthStatus;

    /**
     * Get the name/identifier of this service.
     *
     * @return string Service name
     */
    public function getName(): string;

    /**
     * Get the version of this service.
     *
     * @return string Service version
     */
    public function getVersion(): string;

    /**
     * Check if the service is currently operational.
     *
     * @return bool True if the service can be executed
     */
    public function isOperational(): bool;

    /**
     * Get service dependencies.
     *
     * @return array<string> Array of service names this service depends on
     */
    public function getDependencies(): array;

    /**
     * Get service metadata.
     *
     * @return array Service metadata
     */
    public function getMetadata(): array;

    /**
     * Initialize the service with configuration.
     *
     * @param array $config Service configuration
     * @return bool True if initialization was successful
     */
    public function initialize(array $config = []): bool;

    /**
     * Shutdown the service cleanly.
     *
     * @return bool True if shutdown was successful
     */
    public function shutdown(): bool;
}
