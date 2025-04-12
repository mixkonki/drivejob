<?php
// public/index.php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Router;
use Drivejob\Core\CSRFMiddleware;
use Drivejob\Controllers\HomeController;
use Drivejob\Controllers\JobListingController;

// Εκτέλεση των middleware
CSRFMiddleware::handle();

// Δημιουργία του router
$router = new Router();

// Ορισμός διαδρομών
$router->get('/', [HomeController::class, 'renderHomePage']);

// Διαδρομές για τις αγγελίες
$router->get('/job-listings', function() use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->index();
});

$router->get('/job-listings/create', function() use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->create();
});

$router->post('/job-listings/store', function() use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->store();
});

$router->get('/job-listings/show/{id}', function($id) use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->show($id);
});

$router->get('/job-listings/edit/{id}', function($id) use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->edit($id);
});

$router->post('/job-listings/update/{id}', function($id) use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->update($id);
});

$router->get('/job-listings/delete/{id}', function($id) use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->delete($id);
});

$router->get('/job-listings/company/{id}', function($id) use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->companyListings($id);
});

$router->get('/job-listings/driver/{id}', function($id) use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->driverListings($id);
});

$router->get('/my-listings', function() use ($pdo) {
    $controller = new JobListingController($pdo);
    $controller->myListings();
});

// Διαδρομές για επεξεργασία προφίλ εταιρείας
$router->get('/companies/edit-profile', function() use ($pdo) {
    $controller = new \Drivejob\Controllers\CompaniesController($pdo);
    $controller->edit();
});

$router->post('/companies/update-profile', function() use ($pdo) {
    $controller = new \Drivejob\Controllers\CompaniesController($pdo);
    $controller->update();
});

// Διαδρομές για επεξεργασία προφίλ οδηγού
$router->get('/drivers/edit-profile', function() use ($pdo) {
    $controller = new \Drivejob\Controllers\DriversController($pdo);
    $controller->edit();
});

$router->post('/drivers/update-profile', function() use ($pdo) {
    $controller = new \Drivejob\Controllers\DriversController($pdo);
    $controller->update();
});

// Διαδρομή για 404 Not Found
$router->notFound(function() {
    require_once ROOT_DIR . '/src/Views/404.php';
});

// Εκτέλεση του router
$router->resolve();