<?php
// config/routes.php

use Drivejob\Core\Router;
use Drivejob\Controllers\HomeController;
use Drivejob\Controllers\JobListingController;
use Drivejob\Controllers\DriversController;
use Drivejob\Controllers\CompaniesController;
use Drivejob\Controllers\AuthController;

// Αρχική σελίδα
$router->get('/', [HomeController::class, 'renderHomePage']);

// Διαδρομές αυθεντικοποίησης
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/verify', [AuthController::class, 'verify']);
$router->get('/password-reset', [AuthController::class, 'showPasswordResetForm']);
$router->post('/password-reset', [AuthController::class, 'sendPasswordResetLink']);
$router->get('/password-reset/{token}', [AuthController::class, 'showResetPasswordForm']);
$router->post('/password-reset/{token}', [AuthController::class, 'resetPassword']);
$router->get('/access-denied', [AuthController::class, 'accessDenied']);
$router->get('/verification-required', [AuthController::class, 'verificationRequired']);

// Διαδρομές για τις αγγελίες
$router->get('/job-listings', [JobListingController::class, 'index']);
$router->get('/job-listings/create', [JobListingController::class, 'create']);
$router->post('/job-listings/store', [JobListingController::class, 'store']);
$router->get('/job-listings/show/{id}', [JobListingController::class, 'show']);
$router->get('/job-listings/edit/{id}', [JobListingController::class, 'edit']);
$router->post('/job-listings/update/{id}', [JobListingController::class, 'update']);
$router->get('/job-listings/delete/{id}', [JobListingController::class, 'delete']);
$router->get('/job-listings/company/{id}', [JobListingController::class, 'companyListings']);
$router->get('/job-listings/driver/{id}', [JobListingController::class, 'driverListings']);
$router->get('/my-listings', [JobListingController::class, 'myListings']);

// Διαδρομές για οδηγούς
$router->get('/drivers/register', [DriversController::class, 'showRegistrationForm']);
$router->post('/drivers/register', [DriversController::class, 'register']);
$router->get('/drivers/profile', [DriversController::class, 'profile']);
$router->get('/drivers/profile/{id}', [DriversController::class, 'publicProfile']);
$router->get('/drivers/edit-profile', [DriversController::class, 'edit']);
$router->post('/drivers/update-profile', [DriversController::class, 'update']);
$router->post('/drivers/change-password', [DriversController::class, 'changePassword']);
$router->get('/drivers/search', [DriversController::class, 'search']);
$router->get('/drivers/top-rated', [DriversController::class, 'topRated']);
$router->get('/drivers/recently-available', [DriversController::class, 'recentlyAvailable']);
$router->post('/drivers/add-rating/{id}', [DriversController::class, 'addRating']);

// Διαδρομές για εταιρείες
$router->get('/companies/register', [CompaniesController::class, 'showRegistrationForm']);
$router->post('/companies/register', [CompaniesController::class, 'register']);
$router->get('/companies/profile', [CompaniesController::class, 'profile']);
$router->get('/companies/profile/{id}', [CompaniesController::class, 'publicProfile']);
$router->get('/companies/edit-profile', [CompaniesController::class, 'edit']);
$router->post('/companies/update-profile', [CompaniesController::class, 'update']);
$router->post('/companies/change-password', [CompaniesController::class, 'changePassword']);
$router->get('/companies/search', [CompaniesController::class, 'search']);

// Διαδρομές για άλλες σελίδες
$router->get('/about', [HomeController::class, 'about']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'submitContactForm']);
$router->get('/terms', [HomeController::class, 'terms']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/faq', [HomeController::class, 'faq']);

// Διαδρομή για 404 Not Found
$router->notFound(function() {
    require_once ROOT_DIR . '/src/Views/404.php';
});