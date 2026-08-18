<?php

declare(strict_types=1);

namespace Drivejob\Services\Supervisor;

use Drivejob\Services\HealthStatus;
use Drivejob\Services\Interfaces\MonitorableInterface;
use Drivejob\Services\Interfaces\ServiceInterface;
use Drivejob\Services\Interfaces\SupervisorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Monitoring service for health checks and metrics collection.
 *
 * This service provides comprehensive monitoring capabilities for supervisors,
 * services, and system components, including health checks, metrics collection,
 * and alerting mechanisms.
 */
class MonitoringService implements MonitorableInterface
{
    private array $monitoredComponents = [];
    private array $metrics = [];
    private array $alerts = [];
    private ?LoggerInterface $logger;
    private array $configuration;
    private int $lastHealthCheck = 0;

    /**
     * Create a new MonitoringService instance.
     */
    public function __construct(
        array $configuration = [],
        ?LoggerInterface $logger = null
    ) {
        $this->configuration = array_merge($this->getDefaultConfiguration(), $configuration);
        $this->logger = $logger;
        $this->initializeMetrics();
    }

    /**
     * Get default configuration.
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'health_check_interval' => 30,
            'metrics_retention_period' => 3600, // 1 hour
            'enable_alerting' => true,
            'alert_thresholds' => [
                'error_rate' => 0.05, // 5%
                'response_time' => 5.0, // 5 seconds
                'memory_usage' => 0.8, // 80%
            ],
            'enable_prometheus' => false,
            'prometheus_gateway_url' => null,
        ];
    }

    /**
     * Initialize monitoring metrics.
     */
    protected function initializeMetrics(): void
    {
        $this->metrics = [
            'total_health_checks' => 0,
            'successful_health_checks' => 0,
            'failed_health_checks' => 0,
            'total_components' => 0,
            'active_components' => 0,
            'alerts_sent' => 0,
            'last_metrics_collection' => time(),
        ];
    }

    /**
     * Register a component for monitoring.
     */
    public function registerComponent(MonitorableInterface $component): bool
    {
        $componentName = $component->getName();

        if (isset($this->monitoredComponents[$componentName])) {
            $this->logWarning("Component already registered: {$componentName}");
            return false;
        }

        $this->monitoredComponents[$componentName] = [
            'component' => $component,
            'registered_at' => time(),
            'last_check' => null,
            'status' => HealthStatus::UNKNOWN,
            'metrics' => []
        ];

        $this->metrics['total_components'] = count($this->monitoredComponents);
        $this->logInfo("Component registered for monitoring: {$componentName}");

        return true;
    }

    /**
     * Unregister a component from monitoring.
     */
    public function unregisterComponent(string $componentName): bool
    {
        if (!isset($this->monitoredComponents[$componentName])) {
            $this->logWarning("Component not found: {$componentName}");
            return false;
        }

        unset($this->monitoredComponents[$componentName]);
        $this->metrics['total_components'] = count($this->monitoredComponents);
        $this->logInfo("Component unregistered from monitoring: {$componentName}");

        return true;
    }

    /**
     * Perform health check on a specific component.
     */
    public function checkComponentHealth(string $componentName): HealthStatus
    {
        if (!isset($this->monitoredComponents[$componentName])) {
            $this->logWarning("Component not registered: {$componentName}");
            return HealthStatus::UNKNOWN;
        }

        try {
            $component = $this->monitoredComponents[$componentName]['component'];
            $health = $component->performHealthCheck();

            $this->monitoredComponents[$componentName]['last_check'] = time();
            $this->monitoredComponents[$componentName]['status'] = $health;

            $this->logInfo("Health check completed", [
                'component' => $componentName,
                'status' => $health->value
            ]);

            return $health;
        } catch (Throwable $e) {
            $this->logError("Health check failed", [
                'component' => $componentName,
                'error' => $e->getMessage()
            ]);
            return HealthStatus::UNKNOWN;
        }
    }

    /**
     * Perform health checks on all registered components.
     */
    public function checkAllComponentsHealth(): array
    {
        $results = [];
        $this->metrics['total_health_checks']++;

        foreach ($this->monitoredComponents as $componentName => $componentData) {
            $health = $this->checkComponentHealth($componentName);
            $results[$componentName] = $health;

            if ($health->isHealthy()) {
                $this->metrics['successful_health_checks']++;
            } else {
                $this->metrics['failed_health_checks']++;
            }
        }

        $this->logInfo("Completed health checks for all components", [
            'total_components' => count($results),
            'healthy' => count(array_filter($results, fn($h) => $h->isHealthy())),
            'unhealthy' => count(array_filter($results, fn($h) => $h->isUnhealthy())),
            'unknown' => count(array_filter($results, fn($h) => $h === HealthStatus::UNKNOWN))
        ]);

        return $results;
    }

    /**
     * Collect metrics from all registered components.
     */
    public function collectAllMetrics(): array
    {
        $allMetrics = [];

        foreach ($this->monitoredComponents as $componentName => $componentData) {
            try {
                $component = $componentData['component'];
                $metrics = $component->getMetrics();

                $allMetrics[$componentName] = $metrics;
                $this->monitoredComponents[$componentName]['metrics'] = $metrics;
            } catch (Throwable $e) {
                $this->logError("Failed to collect metrics", [
                    'component' => $componentName,
                    'error' => $e->getMessage()
                ]);
                $allMetrics[$componentName] = ['error' => $e->getMessage()];
            }
        }

        $this->metrics['last_metrics_collection'] = time();
        $this->checkThresholds($allMetrics);

        return $allMetrics;
    }

    /**
     * Check if metrics exceed configured thresholds.
     */
    private function checkThresholds(array $allMetrics): void
    {
        if (!$this->configuration['enable_alerting']) {
            return;
        }

        $thresholds = $this->configuration['alert_thresholds'];

        foreach ($allMetrics as $componentName => $metrics) {
            $this->checkComponentThresholds($componentName, $metrics, $thresholds);
        }
    }

    /**
     * Check thresholds for a specific component.
     */
    private function checkComponentThresholds(string $componentName, array $metrics, array $thresholds): void
    {
        // Check error rate threshold
        if (
            isset($metrics['total_supervisions'], $metrics['failed_supervisions']) &&
            $metrics['total_supervisions'] > 0
        ) {
            $errorRate = $metrics['failed_supervisions'] / $metrics['total_supervisions'];
            if ($errorRate > $thresholds['error_rate']) {
                $this->createAlert($componentName, 'error_rate', $errorRate, $thresholds['error_rate']);
            }
        }

        // Check response time threshold
        if (
            isset($metrics['average_execution_time']) &&
            $metrics['average_execution_time'] > $thresholds['response_time']
        ) {
            $this->createAlert($componentName, 'response_time', $metrics['average_execution_time'], $thresholds['response_time']);
        }

        // Check memory usage if available
        if (
            isset($metrics['memory_usage']) &&
            $metrics['memory_usage'] > $thresholds['memory_usage']
        ) {
            $this->createAlert($componentName, 'memory_usage', $metrics['memory_usage'], $thresholds['memory_usage']);
        }
    }

    /**
     * Create an alert for threshold violation.
     */
    private function createAlert(string $componentName, string $metric, float $actualValue, float $threshold): void
    {
        $alert = [
            'id' => uniqid('alert_'),
            'component' => $componentName,
            'metric' => $metric,
            'actual_value' => $actualValue,
            'threshold' => $threshold,
            'created_at' => time(),
            'acknowledged' => false
        ];

        $this->alerts[] = $alert;
        $this->metrics['alerts_sent']++;

        $this->logWarning("Alert created for threshold violation", [
            'component' => $componentName,
            'metric' => $metric,
            'actual_value' => $actualValue,
            'threshold' => $threshold
        ]);
    }

    /**
     * Get all active alerts.
     */
    public function getAlerts(): array
    {
        return array_filter($this->alerts, fn($alert) => !$alert['acknowledged']);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledgeAlert(string $alertId): bool
    {
        foreach ($this->alerts as &$alert) {
            if ($alert['id'] === $alertId) {
                $alert['acknowledged'] = true;
                $this->logInfo("Alert acknowledged", ['alert_id' => $alertId]);
                return true;
            }
        }

        return false;
    }

    /**
     * Get system status report.
     */
    public function getSystemStatus(): array
    {
        $componentStatuses = [];

        foreach ($this->monitoredComponents as $componentName => $componentData) {
            $componentStatuses[$componentName] = [
                'status' => $componentData['status']->value,
                'last_check' => $componentData['last_check'],
                'registered_at' => $componentData['registered_at'],
                'metrics' => $componentData['metrics']
            ];
        }

        return [
            'monitoring_service' => [
                'status' => $this->getStatus()->value,
                'total_components' => count($this->monitoredComponents),
                'active_alerts' => count($this->getAlerts()),
                'last_health_check' => $this->lastHealthCheck,
                'metrics' => $this->getMetrics()
            ],
            'components' => $componentStatuses,
            'alerts' => $this->getAlerts()
        ];
    }

    /**
     * Export metrics for external monitoring systems (e.g., Prometheus).
     */
    public function exportMetrics(): string
    {
        if (!$this->configuration['enable_prometheus']) {
            return '';
        }

        $output = '';

        // Export monitoring service metrics
        foreach ($this->metrics as $metric => $value) {
            if (is_numeric($value)) {
                $output .= "supervisor_monitoring_{$metric} {$value}\n";
            }
        }

        // Export component metrics
        foreach ($this->monitoredComponents as $componentName => $componentData) {
            $metrics = $componentData['metrics'];
            foreach ($metrics as $metric => $value) {
                if (is_numeric($value)) {
                    $output .= "supervisor_component_{$metric}{component=\"{$componentName}\"} {$value}\n";
                }
            }
        }

        return $output;
    }

    // MonitorableInterface implementation

    public function getHealth(): HealthStatus
    {
        $this->updateStatus();
        return $this->monitoredComponents ? HealthStatus::HEALTHY : HealthStatus::UNKNOWN;
    }

    public function getName(): string
    {
        return 'MonitoringService';
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function isOperational(): bool
    {
        return count($this->monitoredComponents) > 0;
    }

    public function getLastHealthCheck(): int
    {
        return $this->lastHealthCheck;
    }

    public function performHealthCheck(): HealthStatus
    {
        $this->lastHealthCheck = time();

        $totalComponents = count($this->monitoredComponents);
        if ($totalComponents === 0) {
            return HealthStatus::UNKNOWN;
        }

        $healthyComponents = count(array_filter(
            $this->monitoredComponents,
            fn($data) => $data['status']->isHealthy()
        ));

        $healthRatio = $healthyComponents / $totalComponents;

        if ($healthRatio > 0.8) {
            return HealthStatus::HEALTHY;
        } elseif ($healthRatio > 0.5) {
            return HealthStatus::UNHEALTHY;
        } else {
            return HealthStatus::UNHEALTHY;
        }
    }

    public function getMetadata(): array
    {
        return [
            'version' => '1.0.0',
            'monitored_components' => array_keys($this->monitoredComponents),
            'configuration' => $this->configuration
        ];
    }

    /**
     * Get the current status of the monitoring service.
     */
    private function getStatus(): HealthStatus
    {
        $this->updateStatus();
        return $this->monitoredComponents ? HealthStatus::HEALTHY : HealthStatus::UNKNOWN;
    }

    /**
     * Update the status based on current conditions.
     */
    private function updateStatus(): void
    {
        // Status is updated in performHealthCheck
    }

    /**
     * Log an informational message.
     */
    private function logInfo(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info("[MonitoringService] {$message}", $context);
        }
    }

    /**
     * Log a warning message.
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning("[MonitoringService] {$message}", $context);
        }
    }

    /**
     * Log an error message.
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error("[MonitoringService] {$message}", $context);
        }
    }
}
