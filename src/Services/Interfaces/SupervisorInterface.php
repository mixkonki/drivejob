<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Services\Interfaces\ServiceInterface;
use App\Services\SupervisorResult;
use App\Services\SupervisorStatus;
use Throwable;

/**
 * Core supervisor interface for service orchestration and lifecycle management.
 *
 * This interface defines the contract for supervisor components that manage
 * service execution, monitoring, and recovery in a hierarchical manner.
 */
interface SupervisorInterface
{
    /**
     * Supervise a service execution with monitoring and error handling.
     *
     * @param ServiceInterface $service The service to supervise
     * @return SupervisorResult Result of the supervision process
     */
    public function supervise(ServiceInterface $service): SupervisorResult;

    /**
     * Get the current status of the supervisor.
     *
     * @return SupervisorStatus Current supervisor status
     */
    public function getStatus(): SupervisorStatus;

    /**
     * Attempt to recover a failed service.
     *
     * @param ServiceInterface $service The service to recover
     * @return bool True if recovery was successful, false otherwise
     */
    public function recover(ServiceInterface $service): bool;

    /**
     * Get the name/identifier of this supervisor.
     *
     * @return string Supervisor name
     */
    public function getName(): string;

    /**
     * Get the list of services managed by this supervisor.
     *
     * @return array<ServiceInterface> Array of managed services
     */
    public function getManagedServices(): array;

    /**
     * Add a service to be managed by this supervisor.
     *
     * @param ServiceInterface $service The service to add
     * @return bool True if service was added successfully
     */
    public function addService(ServiceInterface $service): bool;

    /**
     * Remove a service from management.
     *
     * @param ServiceInterface $service The service to remove
     * @return bool True if service was removed successfully
     */
    public function removeService(ServiceInterface $service): bool;
}
