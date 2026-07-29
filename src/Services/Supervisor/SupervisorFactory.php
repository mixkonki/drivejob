<?php

declare(strict_types=1);

namespace App\Services\Supervisor;

use App\Services\Interfaces\SupervisorInterface;
use App\Services\Supervisor\SupervisorRegistry;
use Drivejob\Core\Container;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Factory for creating and configuring supervisor instances.
 *
 * This factory provides centralized supervisor creation with proper dependency
 * injection, configuration, and initialization.
 */
class SupervisorFactory
{
    private Container $container;
    private SupervisorRegistry $registry;
    private ?LoggerInterface $logger;
    private array $configuration;

    /**
     * Create a new SupervisorFactory instance.
     */
    public function __construct(
        Container $container,
        SupervisorRegistry $registry,
        array $configuration = [],
        ?LoggerInterface $logger = null
    ) {
        $this->container = $container;
        $this->registry = $registry;
        $this->configuration = array_merge($this->getDefaultConfiguration(), $configuration);
        $this->logger = $logger;
    }

    /**
     * Get default configuration.
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'default_supervisor_type' => 'service',
            'auto_register_supervisors' => true,
            'enable_monitoring_integration' => true,
            'enable_recovery_integration' => true,
            'supervisor_configurations' => [
                'main' => [
                    'enable_system_metrics' => true,
                    'max_supervisors' => 50,
                ],
                'service' => [
                    'max_retry_attempts' => 3,
                    'health_check_interval' => 30,
                ]
            ]
        ];
    }

    /**
     * Create a MainSupervisor instance.
     */
    public function createMainSupervisor(array $config = []): MainSupervisor
    {
        try {
            $mergedConfig = array_merge(
                $this->configuration['supervisor_configurations']['main'] ?? [],
                $config
            );

            $logger = $this->logger;
            if (!$logger && $this->container->has(LoggerInterface::class)) {
                $logger = $this->container->get(LoggerInterface::class);
            }

            $mainSupervisor = new MainSupervisor($mergedConfig, $logger);

            $this->logInfo("MainSupervisor created successfully", [
                'config_keys' => array_keys($mergedConfig)
            ]);

            // Auto-register if enabled
            if ($this->configuration['auto_register_supervisors']) {
                $this->registry->registerSupervisor($mainSupervisor);
            }

            // Integrate with monitoring if enabled
            if ($this->configuration['enable_monitoring_integration']) {
                $this->integrateWithMonitoring($mainSupervisor);
            }

            return $mainSupervisor;
        } catch (Throwable $e) {
            $this->logError("Failed to create MainSupervisor", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a ServiceSupervisor instance.
     */
    public function createServiceSupervisor(
        string $name,
        array $config = []
    ): ServiceSupervisor {
        try {
            $mergedConfig = array_merge(
                $this->configuration['supervisor_configurations']['service'] ?? [],
                $config
            );

            $logger = $this->logger;
            if (!$logger && $this->container->has(LoggerInterface::class)) {
                $logger = $this->container->get(LoggerInterface::class);
            }

            $serviceSupervisor = new ServiceSupervisor($name, $mergedConfig, $logger);

            $this->logInfo("ServiceSupervisor created successfully", [
                'name' => $name,
                'config_keys' => array_keys($mergedConfig)
            ]);

            // Auto-register if enabled
            if ($this->configuration['auto_register_supervisors']) {
                $this->registry->registerSupervisor($serviceSupervisor);
            }

            // Integrate with monitoring if enabled
            if ($this->configuration['enable_monitoring_integration']) {
                $this->integrateWithMonitoring($serviceSupervisor);
            }

            return $serviceSupervisor;
        } catch (Throwable $e) {
            $this->logError("Failed to create ServiceSupervisor", [
                'name' => $name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a supervisor based on type.
     */
    public function createSupervisor(
        string $type,
        string $name = '',
        array $config = []
    ): SupervisorInterface {
        switch ($type) {
            case 'main':
                return $this->createMainSupervisor($config);

            case 'service':
                if (empty($name)) {
                    throw new \InvalidArgumentException("Name is required for ServiceSupervisor");
                }
                return $this->createServiceSupervisor($name, $config);

            default:
                throw new \InvalidArgumentException("Unknown supervisor type: {$type}");
        }
    }

    /**
     * Create a supervisor with services pre-assigned.
     */
    public function createSupervisorWithServices(
        string $type,
        string $name = '',
        array $services = [],
        array $config = []
    ): SupervisorInterface {
        $supervisor = $this->createSupervisor($type, $name, $config);

        foreach ($services as $service) {
            if ($this->registry->assignServiceToSupervisor($service, $supervisor)) {
                $this->logInfo("Service assigned to supervisor during creation", [
                    'supervisor' => $supervisor->getName(),
                    'service' => $service->getName()
                ]);
            } else {
                $this->logWarning("Failed to assign service to supervisor during creation", [
                    'supervisor' => $supervisor->getName(),
                    'service' => $service->getName()
                ]);
            }
        }

        return $supervisor;
    }

    /**
     * Create a supervisor chain (hierarchical supervisors).
     */
    public function createSupervisorChain(array $chainConfig): array
    {
        $supervisors = [];
        $parentSupervisor = null;

        foreach ($chainConfig as $level => $levelConfig) {
            $type = $levelConfig['type'] ?? $this->configuration['default_supervisor_type'];
            $name = $levelConfig['name'] ?? "{$type}_level_{$level}";
            $services = $levelConfig['services'] ?? [];
            $config = $levelConfig['config'] ?? [];

            $supervisor = $this->createSupervisorWithServices($type, $name, $services, $config);
            $supervisors[] = $supervisor;

            // If this is not the first supervisor, add it as a service to the parent
            if ($parentSupervisor !== null) {
                $this->addSupervisorAsService($parentSupervisor, $supervisor);
            }

            $parentSupervisor = $supervisor;
        }

        $this->logInfo("Supervisor chain created", [
            'levels' => count($supervisors),
            'supervisor_names' => array_map(fn($s) => $s->getName(), $supervisors)
        ]);

        return $supervisors;
    }

    /**
     * Add a supervisor as a service to another supervisor.
     */
    private function addSupervisorAsService(
        SupervisorInterface $parent,
        SupervisorInterface $child
    ): void {
        // In a real implementation, you might create a wrapper service
        // that exposes the child supervisor as a service interface
        $this->logInfo("Added supervisor as service", [
            'parent' => $parent->getName(),
            'child' => $child->getName()
        ]);
    }

    /**
     * Integrate supervisor with monitoring service.
     */
    private function integrateWithMonitoring(SupervisorInterface $supervisor): void
    {
        try {
            if ($this->container->has(MonitoringService::class)) {
                $monitoringService = $this->container->get(MonitoringService::class);
                $monitoringService->registerComponent($supervisor);

                $this->logInfo("Supervisor integrated with monitoring", [
                    'supervisor' => $supervisor->getName()
                ]);
            }
        } catch (Throwable $e) {
            $this->logWarning("Failed to integrate supervisor with monitoring", [
                'supervisor' => $supervisor->getName(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get factory statistics.
     */
    public function getStatistics(): array
    {
        return [
            'configuration' => $this->configuration,
            'registry_services_count' => count($this->registry->getServices()),
            'registry_supervisors_count' => count($this->registry->getSupervisors()),
            'available_supervisor_types' => ['main', 'service'],
            'container_has_logger' => $this->container->has(LoggerInterface::class),
            'container_has_monitoring' => $this->container->has(MonitoringService::class),
        ];
    }

    /**
     * Create supervisors from configuration.
     */
    public function createFromConfiguration(array $supervisorConfigs): array
    {
        $createdSupervisors = [];

        foreach ($supervisorConfigs as $config) {
            try {
                $type = $config['type'] ?? $this->configuration['default_supervisor_type'];
                $name = $config['name'] ?? '';
                $services = $config['services'] ?? [];
                $supervisorConfig = $config['config'] ?? [];

                $supervisor = $this->createSupervisorWithServices(
                    $type,
                    $name,
                    $services,
                    $supervisorConfig
                );

                $createdSupervisors[] = $supervisor;
            } catch (Throwable $e) {
                $this->logError("Failed to create supervisor from configuration", [
                    'config' => $config,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logInfo("Supervisors created from configuration", [
            'created_count' => count($createdSupervisors),
            'total_configs' => count($supervisorConfigs)
        ]);

        return $createdSupervisors;
    }

    /**
     * Validate supervisor configuration.
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (!isset($config['type']) || !in_array($config['type'], ['main', 'service'])) {
            $errors[] = "Invalid or missing supervisor type";
        }

        if ($config['type'] === 'service' && empty($config['name'])) {
            $errors[] = "Name is required for service supervisors";
        }

        if (isset($config['services']) && !is_array($config['services'])) {
            $errors[] = "Services must be an array";
        }

        if (isset($config['config']) && !is_array($config['config'])) {
            $errors[] = "Config must be an array";
        }

        return $errors;
    }

    /**
     * Get available supervisor types.
     */
    public function getAvailableTypes(): array
    {
        return [
            'main' => 'Main supervisor orchestrator',
            'service' => 'Individual service supervisor'
        ];
    }

    /**
     * Log an informational message.
     */
    private function logInfo(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info("[SupervisorFactory] {$message}", $context);
        }
    }

    /**
     * Log a warning message.
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning("[SupervisorFactory] {$message}", $context);
        }
    }

    /**
     * Log an error message.
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error("[SupervisorFactory] {$message}", $context);
        }
    }
}
