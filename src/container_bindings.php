<?php

// src/container_bindings.php

// Λήψη του container
$container = \Drivejob\Core\Container::getInstance();

// Καταχώρηση των repositories
$container->set('DriverLicenseRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriverLicenseRepository($container->get('pdo'));
});

// Καταχώρηση των services
$container->set('rating_service', function () use ($container) {
    return new \Drivejob\Services\Rating\RatingService($container->get('pdo'));
});

$container->set('DriverOperatorLicenseRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriverOperatorLicenseRepository($container->get('pdo'));
});

$container->set('DriverADRRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriverADRRepository($container->get('pdo'));
});

$container->set('DriverTachographRepository', function () use ($container) {
    return new \Drivejob\Repositories\DriverTachographRepository($container->get('pdo'));
});

$container->set('JobApplicationRepository', function () use ($container) {
    return new \Drivejob\Repositories\JobApplicationRepository($container->get('pdo'));
});

// Supervisor System Bindings
$container->set('SupervisorRegistry', function () use ($container) {
    return new \Drivejob\Services\Supervisor\SupervisorRegistry([], $container->get('logger') ?? null);
});

$container->set('MainSupervisor', function () use ($container) {
    return new \Drivejob\Services\Supervisor\MainSupervisor([], $container->get('logger') ?? null);
});

$container->set('MonitoringService', function () use ($container) {
    return new \Drivejob\Services\Supervisor\MonitoringService([], $container->get('logger') ?? null);
});

$container->set('RecoveryService', function () use ($container) {
    return new \Drivejob\Services\Supervisor\RecoveryService([], $container->get('logger') ?? null);
});

$container->set('SupervisorFactory', function () use ($container) {
    return new \Drivejob\Services\Supervisor\SupervisorFactory(
        $container,
        $container->get('SupervisorRegistry'),
        [],
        $container->get('logger') ?? null
    );
});

// Factory method for creating service supervisors
$container->set('ServiceSupervisorFactory', function () use ($container) {
    return function (string $name, array $config = []) use ($container) {
        return new \Drivejob\Services\Supervisor\ServiceSupervisor(
            $name,
            $config,
            $container->get('logger') ?? null
        );
    };
});

// Επιστροφή του container
return $container;
