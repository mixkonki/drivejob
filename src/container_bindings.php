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

// Επιστροφή του container
return $container;
