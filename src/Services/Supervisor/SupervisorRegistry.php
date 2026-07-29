<?php

declare(strict_types=1);

namespace App\Services\Supervisor;

use App\Services\Interfaces\ServiceInterface;
use App\Services\Interfaces\SupervisorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Registry for service registration and discovery in the supervisor system.
 *
 * This registry manages the registration, discovery, and lifecycle of services
 * and supervisors within the system, providing a centralized service directory.
 */
class SupervisorRegistry
{
    private array $services = [];
    private array $supervisors = [];
    private array $serviceMetadata = [];
    private array $tags = [];
    private ?LoggerInterface $logger;
    private array $configuration;

    /**
     * Create a new SupervisorRegistry instance.
     */
    public function __construct(
        array $configuration = [],
        ?LoggerInterface $logger = null
    ) {
        $this->configuration = array_merge($this->getDefaultConfiguration(), $configuration);
        $this->logger = $logger;
    }

    /**
     * Get default configuration.
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'enable_auto_registration' => true,
            'enable_health_checks' => true,
            'health_check_interval' => 30,
            'enable_tagging' => true,
            'max_services_per_supervisor' => 50,
        ];
    }

    /**
     * Register a service with the registry.
     */
    public function register(ServiceInterface $service, array $metadata = []): bool
    {
        $serviceName = $service->getName();

        if (isset($this->services[$serviceName])) {
            $this->logWarning("Service already registered: {$serviceName}");
            return false;
        }

        // Create service entry
        $this->services[$serviceName] = [
            'service' => $service,
            'registered_at' => time(),
            'last_health_check' => null,
            'health_status' => $service->getHealth(),
            'supervisor' => null,
            'dependencies' => $service->getDependencies(),
            'tags' => $metadata['tags'] ?? []
        ];

        // Store additional metadata
        $this->serviceMetadata[$serviceName] = array_merge([
            'version' => $service->getVersion(),
            'description' => $metadata['description'] ?? '',
            'author' => $metadata['author'] ?? '',
            'contact' => $metadata['contact'] ?? '',
            'documentation' => $metadata['documentation'] ?? '',
        ], $metadata);

        // Index by tags
        if ($this->configuration['enable_tagging']) {
            foreach ($this->services[$serviceName]['tags'] as $tag) {
                if (!isset($this->tags[$tag])) {
                    $this->tags[$tag] = [];
                }
                $this->tags[$tag][] = $serviceName;
            }
        }

        $this->logInfo("Service registered successfully: {$serviceName}", [
            'dependencies' => $service->getDependencies(),
            'tags' => $this->services[$serviceName]['tags']
        ]);

        return true;
    }

    /**
     * Unregister a service from the registry.
     */
    public function unregister(ServiceInterface $service): bool
    {
        $serviceName = $service->getName();

        if (!isset($this->services[$serviceName])) {
            $this->logWarning("Service not found: {$serviceName}");
            return false;
        }

        // Remove from tag index
        if ($this->configuration['enable_tagging']) {
            foreach ($this->services[$serviceName]['tags'] as $tag) {
                if (isset($this->tags[$tag])) {
                    $this->tags[$tag] = array_filter(
                        $this->tags[$tag],
                        fn($name) => $name !== $serviceName
                    );
                    if (empty($this->tags[$tag])) {
                        unset($this->tags[$tag]);
                    }
                }
            }
        }

        unset($this->services[$serviceName]);
        unset($this->serviceMetadata[$serviceName]);

        $this->logInfo("Service unregistered: {$serviceName}");
        return true;
    }

    /**
     * Register a supervisor with the registry.
     */
    public function registerSupervisor(SupervisorInterface $supervisor): bool
    {
        $supervisorName = $supervisor->getName();

        if (isset($this->supervisors[$supervisorName])) {
            $this->logWarning("Supervisor already registered: {$supervisorName}");
            return false;
        }

        $this->supervisors[$supervisorName] = [
            'supervisor' => $supervisor,
            'registered_at' => time(),
            'managed_services' => $supervisor->getManagedServices()
        ];

        $this->logInfo("Supervisor registered: {$supervisorName}");
        return true;
    }

    /**
     * Unregister a supervisor from the registry.
     */
    public function unregisterSupervisor(SupervisorInterface $supervisor): bool
    {
        $supervisorName = $supervisor->getName();

        if (!isset($this->supervisors[$supervisorName])) {
            $this->logWarning("Supervisor not found: {$supervisorName}");
            return false;
        }

        unset($this->supervisors[$supervisorName]);
        $this->logInfo("Supervisor unregistered: {$supervisorName}");
        return true;
    }

    /**
     * Get all registered services.
     */
    public function getServices(): array
    {
        return array_map(
            fn($entry) => $entry['service'],
            $this->services
        );
    }

    /**
     * Get a specific service by name.
     */
    public function getService(string $serviceName): ?ServiceInterface
    {
        return $this->services[$serviceName]['service'] ?? null;
    }

    /**
     * Get all registered supervisors.
     */
    public function getSupervisors(): array
    {
        return array_map(
            fn($entry) => $entry['supervisor'],
            $this->supervisors
        );
    }

    /**
     * Get a specific supervisor by name.
     */
    public function getSupervisor(string $supervisorName): ?SupervisorInterface
    {
        return $this->supervisors[$supervisorName]['supervisor'] ?? null;
    }

    /**
     * Find services by tags.
     */
    public function findServicesByTags(array $tags): array
    {
        if (!$this->configuration['enable_tagging']) {
            return [];
        }

        $matchingServices = [];

        foreach ($tags as $tag) {
            if (isset($this->tags[$tag])) {
                foreach ($this->tags[$tag] as $serviceName) {
                    if (isset($this->services[$serviceName])) {
                        $matchingServices[$serviceName] = $this->services[$serviceName]['service'];
                    }
                }
            }
        }

        return array_unique($matchingServices);
    }

    /**
     * Find services by dependencies.
     */
    public function findServicesByDependency(string $dependency): array
    {
        $matchingServices = [];

        foreach ($this->services as $serviceName => $serviceEntry) {
            if (in_array($dependency, $serviceEntry['dependencies'])) {
                $matchingServices[$serviceName] = $serviceEntry['service'];
            }
        }

        return $matchingServices;
    }

    /**
     * Assign a service to a supervisor.
     */
    public function assignServiceToSupervisor(
        ServiceInterface $service,
        SupervisorInterface $supervisor
    ): bool {
        $serviceName = $service->getName();
        $supervisorName = $supervisor->getName();

        if (!isset($this->services[$serviceName])) {
            $this->logWarning("Service not registered: {$serviceName}");
            return false;
        }

        if (!isset($this->supervisors[$supervisorName])) {
            $this->logWarning("Supervisor not registered: {$supervisorName}");
            return false;
        }

        // Check if supervisor has capacity
        $managedServices = $supervisor->getManagedServices();
        if (count($managedServices) >= $this->configuration['max_services_per_supervisor']) {
            $this->logWarning("Supervisor at capacity: {$supervisorName}");
            return false;
        }

        // Assign the service to the supervisor
        if ($supervisor->addService($service)) {
            $this->services[$serviceName]['supervisor'] = $supervisorName;
            $this->supervisors[$supervisorName]['managed_services'] = $supervisor->getManagedServices();

            $this->logInfo("Service assigned to supervisor", [
                'service' => $serviceName,
                'supervisor' => $supervisorName
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get services assigned to a supervisor.
     */
    public function getServicesForSupervisor(SupervisorInterface $supervisor): array
    {
        $supervisorName = $supervisor->getName();

        if (!isset($this->supervisors[$supervisorName])) {
            return [];
        }

        return array_filter(
            $this->services,
            fn($serviceEntry) => $serviceEntry['supervisor'] === $supervisorName
        );
    }

    /**
     * Get service metadata.
     */
    public function getServiceMetadata(string $serviceName): array
    {
        return $this->serviceMetadata[$serviceName] ?? [];
    }

    /**
     * Update service health status.
     */
    public function updateServiceHealth(string $serviceName): bool
    {
        if (!isset($this->services[$serviceName])) {
            return false;
        }

        try {
            $service = $this->services[$serviceName]['service'];
            $health = $service->getHealth();

            $this->services[$serviceName]['health_status'] = $health;
            $this->services[$serviceName]['last_health_check'] = time();

            return true;
        } catch (Throwable $e) {
            $this->logError("Failed to update service health", [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get system overview.
     */
    public function getSystemOverview(): array
    {
        $totalServices = count($this->services);
        $totalSupervisors = count($this->supervisors);
        $healthyServices = count(array_filter(
            $this->services,
            fn($entry) => $entry['health_status']->isHealthy()
        ));
        $unhealthyServices = count(array_filter(
            $this->services,
            fn($entry) => $entry['health_status']->isUnhealthy()
        ));

        return [
            'total_services' => $totalServices,
            'total_supervisors' => $totalSupervisors,
            'healthy_services' => $healthyServices,
            'unhealthy_services' => $unhealthyServices,
            'unknown_services' => $totalServices - $healthyServices - $unhealthyServices,
            'services_per_supervisor' => $totalSupervisors > 0 ? $totalServices / $totalSupervisors : 0,
            'available_tags' => array_keys($this->tags),
            'configuration' => $this->configuration
        ];
    }

    /**
     * Get registry statistics.
     */
    public function getStatistics(): array
    {
        return [
            'services_count' => count($this->services),
            'supervisors_count' => count($this->supervisors),
            'tags_count' => count($this->tags),
            'metadata_entries_count' => count($this->serviceMetadata),
            'oldest_service_registration' => !empty($this->services)
                ? min(array_column($this->services, 'registered_at'))
                : null,
            'newest_service_registration' => !empty($this->services)
                ? max(array_column($this->services, 'registered_at'))
                : null,
            'configuration' => $this->configuration
        ];
    }

    /**
     * Clear the registry (useful for testing or reset scenarios).
     */
    public function clear(): void
    {
        $this->services = [];
        $this->supervisors = [];
        $this->serviceMetadata = [];
        $this->tags = [];

        $this->logInfo("Registry cleared");
    }

    /**
     * Log an informational message.
     */
    private function logInfo(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info("[SupervisorRegistry] {$message}", $context);
        }
    }

    /**
     * Log a warning message.
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning("[SupervisorRegistry] {$message}", $context);
        }
    }

    /**
     * Log an error message.
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error("[SupervisorRegistry] {$message}", $context);
        }
    }
}
