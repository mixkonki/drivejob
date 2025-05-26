<?php
// routes/web.php

/**
 * Αρχείο καθορισμού διαδρομών (routes)
 * 
 * Εδώ ορίζουμε όλες τις διαδρομές της εφαρμογής και τους αντίστοιχους controllers/methods
 */

// Αρχική σελίδα
$router->get('/', 'HomeController@index');
$router->get('/about', 'HomeController@about');
$router->get('/contact', 'HomeController@contact');
$router->post('/contact', 'HomeController@sendContact');

// Αυθεντικοποίηση
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/register', 'AuthController@showRegisterForm');
$router->post('/register', 'AuthController@register');
$router->get('/verify', 'AuthController@verify');

// Διαδρομές για οδηγούς
$router->get('/drivers/register', 'DriversController@showRegisterForm');
$router->post('/drivers/register', 'DriversController@register');
$router->get('/drivers/profile', 'DriversController@profile', ['AuthMiddleware@isDriver']);
$router->get('/drivers/edit-profile', 'DriversController@edit', ['AuthMiddleware@isDriver']);
$router->post('/drivers/update-profile', 'DriversController@update', ['AuthMiddleware@isDriver']);
$router->post('/drivers/toggle-availability', 'DriversController@toggleAvailability', ['AuthMiddleware@isDriver']);
$router->post('/drivers/update-skills', 'DriversController@updateSkills', ['AuthMiddleware@isDriver']);

// Διαδρομές για εταιρείες
$router->get('/companies/register', 'Company\CompaniesController@showRegisterForm');
$router->post('/companies/register', 'Company\CompaniesController@register');
$router->get('/companies/profile', 'Company\CompaniesController@profile', ['AuthMiddleware@isCompany']);
$router->get('/companies/edit-profile', 'Company\CompaniesController@edit', ['AuthMiddleware@isCompany']);
$router->post('/companies/update-profile', 'Company\CompaniesController@update', ['AuthMiddleware@isCompany']);

// Διαδρομές για αγγελίες
$router->get('/jobs', 'JobListingsController@index');
$router->get('/jobs/view', 'JobListingsController@view');
$router->get('/jobs/create', 'JobListingsController@create', ['AuthMiddleware@isCompany']);
$router->post('/jobs/store', 'JobListingsController@store', ['AuthMiddleware@isCompany']);
$router->get('/jobs/edit', 'JobListingsController@edit', ['AuthMiddleware@isCompany']);
$router->post('/jobs/update', 'JobListingsController@update', ['AuthMiddleware@isCompany']);
$router->post('/jobs/delete', 'JobListingsController@delete', ['AuthMiddleware@isCompany']);
$router->post('/jobs/apply', 'JobListingsController@apply', ['AuthMiddleware@isDriver']);
$router->get('/jobs/search', 'JobListingsController@search');

// API Διαδρομές (για AJAX κλήσεις)
$router->get('/api/drivers', 'Api\DriversApiController@getDrivers');
$router->get('/api/companies', 'Api\CompaniesApiController@getCompanies');
$router->get('/api/matching', 'Api\MatchingApiController@getMatches');

// Δρομολογήσεις για το σύστημα αξιολόγησης οδηγών
$router->get('/drivers/driver-rating', 'DriversController@driverRating');
$router->get('/drivers/refresh-rating', 'DriversController@refreshRating');
$router->get('/drivers/incident-history', 'DriversController@incidentHistory');
$router->get('/drivers/report-incident', 'DriversController@reportIncident');
$router->post('/drivers/save-incident', 'DriversController@saveIncident');


// Admin routes
$router->group('/admin', function ($router) {
    // Dashboard
    $router->get('/dashboard', 'Admin\\AdminController@dashboard', ['AuthMiddleware@isAdmin']);
    
    // Users Management
    $router->get('/users', 'Admin\\AdminController@users', ['AuthMiddleware@isAdmin']);
    $router->get('/users/{type}', 'Admin\\AdminController@users', ['AuthMiddleware@isAdmin']);
    $router->get('/user-details/{userId}/{userType}', 'Admin\\AdminController@userDetails', ['AuthMiddleware@isAdmin']);
    $router->post('/toggle-user-status/{userId}/{userType}', 'Admin\\AdminController@toggleUserStatus', ['AuthMiddleware@isAdmin']);
    
    // Job Listings Management
    $router->get('/job-listings', 'Admin\\AdminController@jobListings', ['AuthMiddleware@isAdmin']);
    
    // Analytics
    $router->get('/analytics', 'Admin\\AdminController@analytics', ['AuthMiddleware@isAdmin']);
    
    // Settings
    $router->get('/settings', 'Admin\\AdminController@settings', ['AuthMiddleware@isAdmin']);
    $router->post('/settings', 'Admin\\AdminController@settings', ['AuthMiddleware@isAdmin']);
    
    // Activity Logs
    $router->get('/activity-logs', 'Admin\\AdminController@activityLogs', ['AuthMiddleware@isAdmin']);
    
    // System Monitoring
    $router->get('/monitoring/dashboard', 'Admin\\SystemMonitoringController@dashboard', ['AuthMiddleware@isAdmin']);
    $router->get('/monitoring/errors', 'Admin\\SystemMonitoringController@errors', ['AuthMiddleware@isAdmin']);
    $router->get('/monitoring/performance', 'Admin\\SystemMonitoringController@performance', ['AuthMiddleware@isAdmin']);
    $router->get('/monitoring/usage', 'Admin\\SystemMonitoringController@usage', ['AuthMiddleware@isAdmin']);
    $router->get('/monitoring/logs', 'Admin\\SystemMonitoringController@logs', ['AuthMiddleware@isAdmin']);
    $router->post('/monitoring/clear-cache', 'Admin\\SystemMonitoringController@clearCache', ['AuthMiddleware@isAdmin']);
});


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
