<?php

use Drivejob\Core\Router;

// API Routes for Company Features
$router = Router::getInstance();

// Company Features API Routes
$router->group('/api/company', function ($router) {
    // Fleet Management
    $router->get('/fleet/vehicles', 'Api\CompanyFeaturesController@getFleetVehicles');
    $router->post('/fleet/vehicles', 'Api\CompanyFeaturesController@addVehicle');
    $router->get('/fleet/analytics', 'Api\CompanyFeaturesController@getFleetAnalytics');

    // Driver Management
    $router->get('/drivers/stats', 'Api\CompanyFeaturesController@getDriverStats');

    // Subscription Management
    $router->post('/subscription/upgrade', 'Api\CompanyFeaturesController@upgradeSubscription');

    // Compliance Management
    $router->get('/compliance/documents', 'Api\CompanyFeaturesController@getComplianceDocuments');
});

// Include this file in your main routes file
