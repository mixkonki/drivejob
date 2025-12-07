<?php

declare(strict_types=1);

namespace App\Services\Supervisor;

use App\Services\Interfaces\ServiceInterface;
use App\Services\Interfaces\SupervisorInterface;
use App\Services\SupervisorResult;
use App\Services\SupervisorStatus;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Main supervisor orchestrator for managing all services in the system.
 *
 * This is the root supervisor that coordinates all other supervisors and
 * provides system-wide service orchestration and monitoring.
 */
class MainSupervisor extends AbstractSupervisor
{
    private array $supervisors = [];
    private array $systemMetrics = [];
    private int $lastSystemHealthCheck = 0;

    /**
     * Create a new MainSupervisor instance.
     */
    public function __construct(
        array $configuration = [],
        ?LoggerInterface $logger = null
    ) {
        parent::__construct('MainSupervisor', $configuration, $logger);
        $this->initializeSystemMetrics();
    }

    /**
     * Initialize system-wide metrics.
     */
    protected function initializeSystemMetrics(): void
    {
        $this->systemMetrics = [
            'total_services' => 0,
            'active_supervisors' => 0,
            'system_health_checks' => 0,
            'system_failures' => 0,
            'last_system_check' => time(),
        ];
    }

    /**
     * Execute service with monitoring - MainSupervisor implementation.
     */
    protected function executeServiceWithMonitoring(ServiceInterface $service): SupervisorResult
    {
        $startTime = microtime(true);

        try {
            // Find the appropriate supervisor for this service
            $supervisor = $this->findSupervisorForService($service);

            if ($supervisor === null) {
                // Create a new ServiceSupervisor for this service
                $supervisor = $this->createServiceSupervisor($service);
                $this->addSupervisor($supervisor);
            }

            // Delegate to the specific supervisor
            $result = $supervisor->supervise($service);

            $executionTime = microtime(true) - $startTime;

            // Update system metrics
            $this->updateSystemMetrics($result, $executionTime);

            return $result;
        } catch (Throwable $e) {
            $executionTime = microtime(true) - $startTime;
            $this->systemMetrics['system_failures']++;
            $this->logError("System-level supervision failed", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);

            return SupervisorResult::failure($e, SupervisorStatus::CRITICAL, [], $executionTime);
        }
    }

    /**
     * Find the appropriate supervisor for a given service.
     */
    private function findSupervisorForService(ServiceInterface $service): ?SupervisorInterface
    {
        foreach ($this->supervisors as $supervisor) {
            $managedServices = $supervisor->getManagedServices();
            foreach ($managedServices as $managedService) {
                if ($managedService->getName() === $service->getName()) {
                    return $supervisor;
                }
            }
        }

        return null;
    }

    /**
     * Create a new ServiceSupervisor for a service.
     */
    private function createServiceSupervisor(ServiceInterface $service): ServiceSupervisor
    {
        $config = $this->configuration['service_supervisor_config'] ?? [];
        return new ServiceSupervisor(
            "ServiceSupervisor_{$service->getName()}",
            $config,
            $this->logger
        );
    }

    /**
     * Supervise all registered services.
     */
    public function superviseAll(): array
    {
        $results = [];
        $this->logInfo("Starting system-wide supervision");

        foreach ($this->managedServices as $service) {
            $result = $this->supervise($service);
            $results[$service->getName()] = $result;
        }

        $this->logInfo("Completed system-wide supervision", [
            'total_services' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r->isSuccessful())),
            'failed' => count(array_filter($results, fn($r) => $r->isFailure()))
        ]);

        return $results;
    }

    /**
     * Handle service failures at the system level.
     */
    public function handleFailure(ServiceInterface $service): bool
    {
        $this->logInfo("Handling system-level failure for service: {$service->getName()}");

        // Try recovery through the specific supervisor first
        $supervisor = $this->findSupervisorForService($service);
        if ($supervisor && $supervisor->recover($service)) {
            $this->logInfo("Service recovered at supervisor level: {$service->getName()}");
            return true;
        }

        // If supervisor recovery failed, try system-level recovery
        return $this->performSystemLevelRecovery($service);
    }

    /**
     * Perform system-level recovery.
     */
    private function performSystemLevelRecovery(ServiceInterface $service): bool
    {
        try {
            $this->logInfo("Attempting system-level recovery for: {$service->getName()}");

            // Restart the service
            if ($service->shutdown() && $service->initialize()) {
                $this->logInfo("Successfully restarted service: {$service->getName()}");
                return true;
            }

            $this->logError("Failed to restart service: {$service->getName()}");
            return false;
        } catch (Throwable $e) {
            $this->logError("System-level recovery failed", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get system status including all supervisors.
     */
    public function getSystemStatus(): array
    {
        $supervisorStatuses = [];
        foreach ($this->supervisors as $supervisor) {
            $supervisorStatuses[$supervisor->getName()] = [
                'status' => $supervisor->getStatus()->value,
                'managed_services' => count($supervisor->getManagedServices()),
                'metrics' => $supervisor->getMetrics()
            ];
        }

        return [
            'main_supervisor' => [
                'status' => $this->getStatus()->value,
                'total_services' => count($this->managedServices),
                'total_supervisors' => count($this->supervisors),
                'metrics' => $this->getMetrics()
            ],
            'supervisors' => $supervisorStatuses,
            'system_metrics' => $this->systemMetrics,
            'last_system_check' => $this->lastSystemHealthCheck
        ];
    }

    /**
     * Perform system health check.
     */
    public function performSystemHealthCheck(): SupervisorStatus
    {
        $this->lastSystemHealthCheck = time();
        $this->systemMetrics['system_health_checks']++;

        $this->logInfo("Performing system health check");

        $failedSupervisors = 0;
        $totalSupervisors = count($this->supervisors);

        foreach ($this->supervisors as $supervisor) {
            $status = $supervisor->getStatus();
            if ($status->isFailure()) {
                $failedSupervisors++;
                $this->logWarning("Supervisor in failed state", [
                    'supervisor' => $supervisor->getName(),
                    'status' => $status->value
                ]);
            }
        }

        if ($totalSupervisors === 0) {
            return SupervisorStatus::UNKNOWN;
        }

        $failureRate = $failedSupervisors / $totalSupervisors;

        if ($failureRate > 0.5) {
            return SupervisorStatus::CRITICAL;
        } elseif ($failureRate > 0.2) {
            return SupervisorStatus::DEGRADED;
        } else {
            return SupervisorStatus::HEALTHY;
        }
    }

    /**
     * Add a supervisor to the system.
     */
    public function addSupervisor(SupervisorInterface $supervisor): bool
    {
        $supervisorName = $supervisor->getName();

        if (isset($this->supervisors[$supervisorName])) {
            $this->logWarning("Supervisor already exists: {$supervisorName}");
            return false;
        }

        $this->supervisors[$supervisorName] = $supervisor;
        $this->systemMetrics['active_supervisors'] = count($this->supervisors);
        $this->logInfo("Added supervisor: {$supervisorName}");

        return true;
    }

    /**
     * Remove a supervisor from the system.
     */
    public function removeSupervisor(SupervisorInterface $supervisor): bool
    {
        $supervisorName = $supervisor->getName();

        if (!isset($this->supervisors[$supervisorName])) {
            $this->logWarning("Supervisor not found: {$supervisorName}");
            return false;
        }

        unset($this->supervisors[$supervisorName]);
        $this->systemMetrics['active_supervisors'] = count($this->supervisors);
        $this->logInfo("Removed supervisor: {$supervisorName}");

        return true;
    }

    /**
     * Get all supervisors.
     */
    public function getSupervisors(): array
    {
        return $this->supervisors;
    }

    /**
     * Update system metrics.
     */
    private function updateSystemMetrics(SupervisorResult $result, float $executionTime): void
    {
        $this->systemMetrics['total_services'] = count($this->managedServices);
        $this->systemMetrics['last_system_check'] = time();

        if ($result->isFailure()) {
            $this->systemMetrics['system_failures']++;
        }
    }

    /**
     * Perform the actual recovery logic for MainSupervisor.
     */
    protected function performRecovery(ServiceInterface $service): bool
    {
        return $this->handleFailure($service);
    }

    /**
     * Get default configuration with MainSupervisor-specific defaults.
     */
    protected function getDefaultConfiguration(): array
    {
        return array_merge(parent::getDefaultConfiguration(), [
            'auto_create_supervisors' => true,
            'max_supervisors' => 50,
            'system_health_check_interval' => 60,
            'enable_system_metrics' => true,
            'service_supervisor_config' => []
        ]);
    }
}
