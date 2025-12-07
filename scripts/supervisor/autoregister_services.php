<?php
// scripts/supervisor/autoregister_services.php
// This script is automatically included by the supervisor runner to register project-specific services

// Get access to the supervisor system components
// These variables should be available from the runner context:
// - $container (DI container)
// - $registry (SupervisorRegistry)
// - $main (MainSupervisor)

require_once __DIR__ . "/../../src/Services/Matching/MatchingWorkerService.php";

use DriveJob\Services\Matching\MatchingWorkerService;

try {
    // Create the matching worker service
    $matchingWorker = new MatchingWorkerService(300); // 5-minute cache TTL

    // Register with the supervisor registry
    if (isset($registry) && method_exists($registry, 'register')) {
        $registry->register($matchingWorker, [
            'description' => 'Background worker for processing matching jobs with caching',
            'tags' => ['matching', 'worker', 'background', 'cache'],
            'author' => 'Supervisor System',
            'contact' => 'admin@drivejob.com'
        ]);

        error_log("[AutoRegister] Successfully registered MatchingWorkerService with registry");
    } else {
        error_log("[AutoRegister] Warning: Registry not available for service registration");
    }

    // Add to MainSupervisor if available
    if (isset($main) && method_exists($main, 'addService')) {
        $main->addService($matchingWorker);
        error_log("[AutoRegister] Successfully added MatchingWorkerService to MainSupervisor");
    } else {
        error_log("[AutoRegister] Warning: MainSupervisor not available for service addition");
    }

    // Log successful registration
    error_log("[AutoRegister] MatchingWorkerService integration completed successfully");
} catch (\Throwable $e) {
    // Don't crash the supervisor if service registration fails
    error_log("[AutoRegister] Failed to register MatchingWorkerService: " . $e->getMessage());
}
