<?php

use Drivejob\Core\Container;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\JobApplicationRepository;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\CompaniesRepository;

/**
 * Καταχώρηση των υπηρεσιών στο Container
 * 
 * @param Container $container Το container
 * @return void
 */
return function (Container $container) {
    // Καταχώρηση του PDO
    $container->set('pdo', function () {
        return require_once ROOT_DIR . '/config/database.php';
    });

    // Καταχώρηση των repositories
    $container->set('job_listing_repository', function ($container) {
        return new JobListingRepository($container->get('pdo'));
    });

    $container->set('job_application_repository', function ($container) {
        return new JobApplicationRepository($container->get('pdo'));
    });

    $container->set('drivers_repository', function ($container) {
        return new DriversRepository($container->get('pdo'));
    });

    $container->set('companies_repository', function ($container) {
        return new CompaniesRepository($container->get('pdo'));
    });
};
