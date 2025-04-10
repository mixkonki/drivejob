<?php

// Προσθέστε αυτές τις γραμμές στην αρχή του αρχείου public/index.php
ini_set('session.cookie_path', '/drivejob/');
session_name('DRIVEJOBSESSION');
session_start();
// Ενεργοποίηση εμφάνισης σφαλμάτων
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debugging information
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";


// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές ROOT_DIR και BASE_URL
require_once __DIR__ . '/../config/config.php';

// Σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Φόρτωση των περιβαλλοντικών μεταβλητών
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

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

// Διαδρομή για 404 Not Found
$router->notFound(function() {
    require_once ROOT_DIR . '/src/Views/404.php';
});

// Εκτέλεση του router
$router->resolve();
