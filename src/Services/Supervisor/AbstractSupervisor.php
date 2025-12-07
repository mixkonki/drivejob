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
 * Abstract base class for supervisor implementations.
 *
 * Provides common functionality for service supervision, monitoring,
 * and recovery mechanisms that all supervisor types can inherit.
 */
abstract class AbstractSupervisor implements SupervisorInterface
{
    protected string $name;
    protected array $managedServices = [];
    protected SupervisorStatus $status = SupervisorStatus::HEALTHY;
    protected array $metrics = [];
    protected ?LoggerInterface $logger;
    protected array $configuration = [];
    protected int $lastHealthCheck = 0;

    /**
     * Create a new supervisor instance.
     */
    public function __construct(
        string $name,
        array $configuration = [],
        ?LoggerInterface $logger = null
    ) {
        $this->name = $name;
        $this->configuration = array_merge($this->getDefaultConfiguration(), $configuration);
        $this->logger = $logger;
        $this->initializeMetrics();
    }

    /**
     * Get default configuration values.
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'max_retry_attempts' => 3,
            'health_check_interval' => 30,
            'timeout_seconds' => 30,
            'enable_logging' => true,
            'enable_metrics' => true,
        ];
    }

    /**
     * Initialize metrics collection.
     */
    protected function initializeMetrics(): void
    {
        $this->metrics = [
            'total_supervisions' => 0,
            'successful_supervisions' => 0,
            'failed_supervisions' => 0,
            'recovery_attempts' => 0,
            'successful_recoveries' => 0,
            'last_activity' => time(),
            'uptime_seconds' => 0,
        ];
    }

    /**
     * Supervise a service execution with monitoring and error handling.
     */
    public function supervise(ServiceInterface $service): SupervisorResult
    {
        $startTime = microtime(true);
        $this->metrics['total_supervisions']++;

        try {
            $this->logInfo("Starting supervision of service: {$service->getName()}");

            // Pre-execution health check
            if (!$this->performPreExecutionChecks($service)) {
                return SupervisorResult::failure(
                    new \RuntimeException("Pre-execution checks failed for service: {$service->getName()}")
                );
            }

            // Execute the service
            $result = $this->executeServiceWithMonitoring($service);

            // Update metrics based on result
            if ($result->isSuccessful()) {
                $this->metrics['successful_supervisions']++;
                $this->logInfo("Successfully supervised service: {$service->getName()}");
            } else {
                $this->metrics['failed_supervisions']++;
                $this->logError("Failed to supervise service: {$service->getName()}", [
                    'error' => $result->error?->getMessage()
                ]);
            }

            $executionTime = microtime(true) - $startTime;
            $this->updateExecutionMetrics($executionTime);

            return $result;
        } catch (Throwable $e) {
            $executionTime = microtime(true) - $startTime;
            $this->metrics['failed_supervisions']++;
            $this->logError("Exception during supervision", [
                'service' => $service->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return SupervisorResult::failure($e, SupervisorStatus::CRITICAL, [], $executionTime);
        }
    }

    /**
     * Perform pre-execution checks on a service.
     */
    protected function performPreExecutionChecks(ServiceInterface $service): bool
    {
        // Check if service is operational
        if (!$service->isOperational()) {
            $this->logWarning("Service is not operational: {$service->getName()}");
            return false;
        }

        // Check service health
        $health = $service->getHealth();
        if ($health->isUnhealthy()) {
            $this->logWarning("Service health is unhealthy: {$service->getName()}", [
                'health_status' => $health->value
            ]);
            return false;
        }

        // Check dependencies
        $dependencies = $service->getDependencies();
        foreach ($dependencies as $dependency) {
            if (!isset($this->managedServices[$dependency])) {
                $this->logWarning("Missing dependency for service: {$service->getName()}", [
                    'dependency' => $dependency
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Execute service with monitoring.
     */
    abstract protected function executeServiceWithMonitoring(ServiceInterface $service): SupervisorResult;

    /**
     * Get the current status of the supervisor.
     */
    public function getStatus(): SupervisorStatus
    {
        $this->updateStatus();
        return $this->status;
    }

    /**
     * Update supervisor status based on current conditions.
     */
    protected function updateStatus(): void
    {
        $totalSupervisions = $this->metrics['total_supervisions'];
        $failedSupervisions = $this->metrics['failed_supervisions'];

        if ($totalSupervisions === 0) {
            $this->status = SupervisorStatus::UNKNOWN;
            return;
        }

        $failureRate = $failedSupervisions / $totalSupervisions;

        if ($failureRate > 0.5) {
            $this->status = SupervisorStatus::CRITICAL;
        } elseif ($failureRate > 0.2) {
            $this->status = SupervisorStatus::DEGRADED;
        } else {
            $this->status = SupervisorStatus::HEALTHY;
        }
    }

    /**
     * Attempt to recover a failed service.
     */
    public function recover(ServiceInterface $service): bool
    {
        $this->metrics['recovery_attempts']++;

        try {
            $this->logInfo("Attempting recovery for service: {$service->getName()}");

            $success = $this->performRecovery($service);

            if ($success) {
                $this->metrics['successful_recoveries']++;
                $this->logInfo("Successfully recovered service: {$service->getName()}");
            } else {
                $this->logError("Failed to recover service: {$service->getName()}");
            }

            return $success;
        } catch (Throwable $e) {
            $this->logError("Exception during recovery", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Perform the actual recovery logic.
     */
    abstract protected function performRecovery(ServiceInterface $service): bool;

    /**
     * Get the name/identifier of this supervisor.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the list of services managed by this supervisor.
     */
    public function getManagedServices(): array
    {
        return array_values($this->managedServices);
    }

    /**
     * Add a service to be managed by this supervisor.
     */
    public function addService(ServiceInterface $service): bool
    {
        $serviceName = $service->getName();

        if (isset($this->managedServices[$serviceName])) {
            $this->logWarning("Service already managed: {$serviceName}");
            return false;
        }

        $this->managedServices[$serviceName] = $service;
        $this->logInfo("Added service to management: {$serviceName}");

        return true;
    }

    /**
     * Remove a service from management.
     */
    public function removeService(ServiceInterface $service): bool
    {
        $serviceName = $service->getName();

        if (!isset($this->managedServices[$serviceName])) {
            $this->logWarning("Service not found in management: {$serviceName}");
            return false;
        }

        unset($this->managedServices[$serviceName]);
        $this->logInfo("Removed service from management: {$serviceName}");

        return true;
    }

    /**
     * Get supervisor metrics.
     */
    public function getMetrics(): array
    {
        $this->metrics['uptime_seconds'] = time() - ($this->metrics['last_activity'] ?? time());
        return $this->metrics;
    }

    /**
     * Update execution metrics.
     */
    protected function updateExecutionMetrics(float $executionTime): void
    {
        $this->metrics['last_execution_time'] = $executionTime;
        $this->metrics['average_execution_time'] = isset($this->metrics['average_execution_time'])
            ? ($this->metrics['average_execution_time'] + $executionTime) / 2
            : $executionTime;
        $this->metrics['last_activity'] = time();
    }

    /**
     * Log an informational message.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        if ($this->logger && $this->configuration['enable_logging']) {
            $this->logger->info("[{$this->name}] {$message}", $context);
        }
    }

    /**
     * Log a warning message.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        if ($this->logger && $this->configuration['enable_logging']) {
            $this->logger->warning("[{$this->name}] {$message}", $context);
        }
    }

    /**
     * Log an error message.
     */
    protected function logError(string $message, array $context = []): void
    {
        if ($this->logger && $this->configuration['enable_logging']) {
            $this->logger->error("[{$this->name}] {$message}", $context);
        }
    }

    /**
     * Get supervisor configuration.
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Update supervisor configuration.
     */
    public function updateConfiguration(array $newConfig): void
    {
        $this->configuration = array_merge($this->configuration, $newConfig);
        $this->logInfo("Configuration updated", $newConfig);
    }
}
