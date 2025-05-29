<?php

// API Routes
// The $router variable is already available from config/routes.php

// Check if router is available
if (!isset($router)) {
    throw new Exception('Router not initialized. This file should be included from config/routes.php');
}

// Company Features API Routes
$router->group(['prefix' => 'api/company'], function ($router) {
    // Fleet Management
    $router->get('/fleet/vehicles', 'Drivejob\Controllers\Api\CompanyFeaturesController@getFleetVehicles');
    $router->post('/fleet/vehicles', 'Drivejob\Controllers\Api\CompanyFeaturesController@addVehicle');
    $router->get('/fleet/analytics', 'Drivejob\Controllers\Api\CompanyFeaturesController@getFleetAnalytics');

    // Driver Management
    $router->get('/drivers/stats', 'Drivejob\Controllers\Api\CompanyFeaturesController@getDriverStats');

    // Subscription Management
    $router->post('/subscription/upgrade', 'Drivejob\Controllers\Api\CompanyFeaturesController@upgradeSubscription');

    // Compliance Management
    $router->get('/compliance/documents', 'Drivejob\Controllers\Api\CompanyFeaturesController@getComplianceDocuments');
});

// AI Matching API Routes
$router->group(['prefix' => 'api/matching'], function ($router) {
    // Driver matching endpoints
    $router->get('/driver/matches', 'Drivejob\Controllers\Api\MatchingController@getDriverMatches');

    // Company matching endpoints
    $router->get('/job/candidates', 'Drivejob\Controllers\Api\MatchingController@getJobCandidates');

    // General matching endpoints
    $router->get('/calculate', 'Drivejob\Controllers\Api\MatchingController@calculateMatch');
    $router->get('/insights', 'Drivejob\Controllers\Api\MatchingController@getMatchInsights');
});

// Include this file in your main routes file
