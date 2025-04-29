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
$router->get('/companies/register', 'CompaniesController@showRegisterForm');
$router->post('/companies/register', 'CompaniesController@register');
$router->get('/companies/profile', 'CompaniesController@profile', ['AuthMiddleware@isCompany']);
$router->get('/companies/edit-profile', 'CompaniesController@edit', ['AuthMiddleware@isCompany']);
$router->post('/companies/update-profile', 'CompaniesController@update', ['AuthMiddleware@isCompany']);

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