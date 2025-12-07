<?php

declare(strict_types=1);

namespace App\Services\Supervisor;

use App\Services\Interfaces\ServiceInterface;
use App\Services\ServiceResult;
use App\Services\SupervisorResult;
use App\Services\SupervisorStatus;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service supervisor for individual service management and monitoring.
 *
 * This supervisor handles the lifecycle management of individual services,
 * including health monitoring, retry logic, and service-specific recovery.
 */
class ServiceSupervisor extends AbstractSupervisor
{
    private ?ServiceInterface $service = null;
    private array $serviceMetrics = [];
    private int $retryCount = 0;

    /**
     * Create a new ServiceSupervisor instance.
     */
    public function __construct(
        string $name,
        array $configuration = [],
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($name, $configuration, $logger);
        $this->initializeServiceMetrics();
    }

    /**
     * Initialize service-specific metrics.
     */
    protected function initializeServiceMetrics(): void
    {
        $this->serviceMetrics = [
            'service_executions' => 0,
            'service_failures' => 0,
            'health_check_failures' => 0,
            'recovery_attempts' => 0,
            'successful_recoveries' => 0,
            'average_response_time' => 0.0,
            'last_successful_execution' => null,
            'last_failure' => null,
        ];
    }

    /**
     * Execute service with monitoring - ServiceSupervisor implementation.
     */
    protected function executeServiceWithMonitoring(ServiceInterface $service): SupervisorResult
    {
        $startTime = microtime(true);

        try {
            $this->logInfo("Executing service: {$service->getName()}");

            // Perform pre-execution health check
            if (!$this->performServiceHealthCheck($service)) {
                return SupervisorResult::degraded(
                    ['health_check_failed' => true],
                    null,
                    microtime(true) - $startTime
                );
            }

            // Execute the service
            $result = $this->executeServiceWithRetry($service);

            $executionTime = microtime(true) - $startTime;
            $this->updateServiceMetrics($result, $executionTime);

            return $result;
        } catch (Throwable $e) {
            $executionTime = microtime(true) - $startTime;
            $this->logError("Service execution failed", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);

            return SupervisorResult::failure($e, SupervisorStatus::CRITICAL, [], $executionTime);
        }
    }

    /**
     * Execute service with retry logic.
     */
    private function executeServiceWithRetry(ServiceInterface $service): SupervisorResult
    {
        $maxRetries = $this->configuration['max_retry_attempts'] ?? 3;
        $this->retryCount = 0;

        while ($this->retryCount <= $maxRetries) {
            try {
                $result = $service->execute([
                    'retry_attempt' => $this->retryCount,
                    'supervisor_context' => $this->getName()
                ]);

                // Convert ServiceResult to SupervisorResult
                if ($result->isSuccessful()) {
                    return SupervisorResult::success(
                        $result->data,
                        $result->executionTime,
                        $result->metadata
                    );
                } else {
                    // Handle service failure
                    if ($this->retryCount < $maxRetries) {
                        $this->logWarning("Service execution failed, retrying", [
                            'service' => $service->getName(),
                            'attempt' => $this->retryCount + 1,
                            'max_retries' => $maxRetries,
                            'error' => $result->getErrorMessage()
                        ]);

                        $this->retryCount++;
                        $this->performRetryDelay();
                        continue;
                    }

                    // Max retries reached
                    return SupervisorResult::failure(
                        $result->error ?? new \RuntimeException("Service execution failed after {$maxRetries} retries"),
                        SupervisorStatus::CRITICAL,
                        $result->metadata,
                        $result->executionTime
                    );
                }
            } catch (Throwable $e) {
                if ($this->retryCount < $maxRetries) {
                    $this->logWarning("Service execution threw exception, retrying", [
                        'service' => $service->getName(),
                        'attempt' => $this->retryCount + 1,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage()
                    ]);

                    $this->retryCount++;
                    $this->performRetryDelay();
                    continue;
                }

                // Max retries reached
                return SupervisorResult::failure($e, SupervisorStatus::CRITICAL, [], microtime(true));
            }
        }

        // This should never be reached, but just in case
        return SupervisorResult::failure(
            new \RuntimeException("Unexpected end of retry loop"),
            SupervisorStatus::CRITICAL
        );
    }

    /**
     * Perform retry delay with exponential backoff.
     */
    private function performRetryDelay(): void
    {
        $baseDelay = $this->configuration['retry_delay_ms'] ?? 1000;
        $delay = $baseDelay * pow(2, $this->retryCount - 1);

        // Add some jitter to prevent thundering herd
        $jitter = random_int(0, (int)($delay * 0.1));
        $totalDelay = (int)($delay + $jitter);

        $this->logInfo("Applying retry delay", [
            'delay_ms' => $totalDelay,
            'retry_attempt' => $this->retryCount
        ]);

        usleep($totalDelay * 1000);
    }

    /**
     * Perform service health check.
     */
    private function performServiceHealthCheck(ServiceInterface $service): bool
    {
        if (!$this->configuration['enable_health_checks'] ?? true) {
            return true;
        }

        $now = time();
        $healthCheckInterval = $this->configuration['health_check_interval'] ?? 30;

        // Check if we need to perform a health check
        if (
            $this->lastHealthCheck !== null &&
            ($now - $this->lastHealthCheck) < $healthCheckInterval
        ) {
            return true;
        }

        try {
            $this->lastHealthCheck = $now;
            $health = $service->getHealth();

            if ($health->isUnhealthy()) {
                $this->serviceMetrics['health_check_failures']++;
                $this->logWarning("Service health check failed", [
                    'service' => $service->getName(),
                    'health_status' => $health->value
                ]);
                return false;
            }

            return true;
        } catch (Throwable $e) {
            $this->serviceMetrics['health_check_failures']++;
            $this->logError("Health check threw exception", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update service-specific metrics.
     */
    private function updateServiceMetrics(SupervisorResult $result, float $executionTime): void
    {
        $this->serviceMetrics['service_executions']++;

        if ($result->isSuccessful()) {
            $this->serviceMetrics['last_successful_execution'] = time();

            // Update average response time
            $currentAvg = $this->serviceMetrics['average_response_time'];
            $this->serviceMetrics['average_response_time'] =
                ($currentAvg + $executionTime) / 2;
        } else {
            $this->serviceMetrics['service_failures']++;
            $this->serviceMetrics['last_failure'] = time();
        }
    }

    /**
     * Perform the actual recovery logic for ServiceSupervisor.
     */
    protected function performRecovery(ServiceInterface $service): bool
    {
        $this->serviceMetrics['recovery_attempts']++;

        try {
            $this->logInfo("Attempting service recovery", [
                'service' => $service->getName()
            ]);

            // Try simple restart first
            if ($this->performSimpleRecovery($service)) {
                $this->serviceMetrics['successful_recoveries']++;
                return true;
            }

            // If simple recovery failed, try advanced recovery
            if ($this->performAdvancedRecovery($service)) {
                $this->serviceMetrics['successful_recoveries']++;
                return true;
            }

            return false;
        } catch (Throwable $e) {
            $this->logError("Recovery failed with exception", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Perform simple recovery (restart service).
     */
    private function performSimpleRecovery(ServiceInterface $service): bool
    {
        try {
            if ($service->shutdown() && $service->initialize()) {
                $this->logInfo("Simple recovery successful", [
                    'service' => $service->getName()
                ]);
                return true;
            }

            return false;
        } catch (Throwable $e) {
            $this->logError("Simple recovery failed", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Perform advanced recovery with additional steps.
     */
    private function performAdvancedRecovery(ServiceInterface $service): bool
    {
        try {
            $this->logInfo("Attempting advanced recovery", [
                'service' => $service->getName()
            ]);

            // Reset service metrics
            $this->serviceMetrics['service_failures'] = 0;
            $this->serviceMetrics['health_check_failures'] = 0;

            // Force re-initialization with fresh config
            $config = $this->configuration['recovery_config'] ?? [];
            if ($service->initialize($config)) {
                $this->logInfo("Advanced recovery successful", [
                    'service' => $service->getName()
                ]);
                return true;
            }

            return false;
        } catch (Throwable $e) {
            $this->logError("Advanced recovery failed", [
                'service' => $service->getName(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get service-specific metrics.
     */
    public function getServiceMetrics(): array
    {
        return array_merge($this->serviceMetrics, $this->getMetrics());
    }

    /**
     * Get the service managed by this supervisor.
     */
    public function getService(): ?ServiceInterface
    {
        return $this->service;
    }

    /**
     * Set the service to be managed by this supervisor.
     */
    public function setService(ServiceInterface $service): void
    {
        $this->service = $service;
        $this->addService($service);
    }

    /**
     * Check if the service is healthy.
     */
    public function isServiceHealthy(): bool
    {
        if ($this->service === null) {
            return false;
        }

        return $this->service->getHealth()->isHealthy();
    }

    /**
     * Get default configuration with ServiceSupervisor-specific defaults.
     */
    protected function getDefaultConfiguration(): array
    {
        return array_merge(parent::getDefaultConfiguration(), [
            'max_retry_attempts' => 3,
            'retry_delay_ms' => 1000,
            'enable_health_checks' => true,
            'health_check_interval' => 30,
            'enable_retry_jitter' => true,
            'recovery_config' => []
        ]);
    }
}
