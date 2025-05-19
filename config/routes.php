<?php
// config/routes.php
// Δημιουργία του router (εάν δεν έχει περαστεί ως παράμετρος)
if (!isset($router)) {
    $router = new Drivejob\Core\Router();
}

use Drivejob\Core\Router;
use Drivejob\Controllers\HomeController;
use Drivejob\Controllers\AuthController;
use Drivejob\Controllers\MatchingController;

// Controllers με Repository pattern
use Drivejob\Controllers\Driver\DriversController;
use Drivejob\Controllers\Driver\JobApplicationController;
use Drivejob\Controllers\Driver\JobOfferController;
use Drivejob\Controllers\Driver\DriverResumeController;
use Drivejob\Controllers\Company\CompaniesController;
use Drivejob\Controllers\Company\JobListingController;
use Drivejob\Controllers\Company\JobListingController as CompanyJobListingController;
use Drivejob\Controllers\Driver\JobListingController as DriverJobListingController;

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
$router->get('/job-listings/show/{id}', [JobListingController::class, 'show']);
$router->get('/job-listings/company/{id}', [JobListingController::class, 'companyListings']);
$router->get('/job-listings/driver/{id}', [JobListingController::class, 'driverListings']);
$router->get('/job-listings/my-listings', [JobListingController::class, 'myListings']);

// Διαδρομές για τις αγγελίες εταιρειών
$router->get('/job-listings/Company/create', [CompanyJobListingController::class, 'create']);
$router->post('/job-listings/Company/store', [CompanyJobListingController::class, 'store']);
$router->get('/job-listings/edit/{id}', [CompanyJobListingController::class, 'edit']);
$router->post('/job-listings/update/{id}', [CompanyJobListingController::class, 'update']);
$router->get('/job-listings/delete/{id}', [CompanyJobListingController::class, 'delete']);
$router->post('/job-listings/destroy/{id}', [CompanyJobListingController::class, 'destroy']);

// Διαδρομές για τις αγγελίες οδηγών
$router->get('/job-listings/Driver/create', [DriverJobListingController::class, 'create']);
$router->post('/job-listings/Driver/store', [DriverJobListingController::class, 'store']);
$router->get('/job-listings/Driver/edit/{id}', [DriverJobListingController::class, 'edit']);
$router->post('/job-listings/Driver/update/{id}', [DriverJobListingController::class, 'update']);
$router->post('/job-listings/Driver/delete/{id}', [DriverJobListingController::class, 'delete']);

// Διαδρομές για οδηγούς (χρησιμοποιώντας τον νέο controller)
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
$router->get('/drivers/welcome', [DriversController::class, 'welcome']);
$router->post('/drivers/complete-profile', [DriversController::class, 'completeProfile']);

// Διαδρομές για την αυτοαξιολόγηση
$router->get('/drivers/update-assessment', [DriversController::class, 'updateAssessment']);
$router->post('/drivers/update-assessment', [DriversController::class, 'updateAssessment']);
$router->post('/drivers/save-assessment', [DriversController::class, 'saveAssessment']);

// Εναλλαγή διαθεσιμότητας οδηγού (χρησιμοποιώντας τον νέο controller)
$router->post(
    '/drivers/toggle-availability',
    [DriversController::class, 'toggleAvailability'],
    [
        // Middleware #1: Χρήση ανώνυμης συνάρτησης (closure)
        function () {
            // Καλεί τη στατική μέθοδο hasRole με την παράμετρο 'driver'
            \Drivejob\Core\AuthMiddleware::hasRole('driver');
            // Δεν επιστρέφει τίποτα (null) αν ο έλεγχος πετύχει
            return null;
        }
    ]
);

// Διαδρομές για εταιρείες (χρησιμοποιώντας τον νέο controller)
$router->get('/companies/register', [CompaniesController::class, 'showRegistrationForm']);
$router->post('/companies/register', [CompaniesController::class, 'register']);
$router->get('/companies/profile', [CompaniesController::class, 'profile']);
$router->get('/companies/profile/{id}', [CompaniesController::class, 'publicProfile']);
$router->get('/companies/edit-profile', [CompaniesController::class, 'edit']);
$router->post('/companies/update-profile', [CompaniesController::class, 'update']);
$router->post('/companies/change-password', [CompaniesController::class, 'changePassword']);
$router->get('/companies/search', [CompaniesController::class, 'search']);
$router->post('/companies/add-review/{id}', [CompaniesController::class, 'addReview']);

// Διαδρομές για άλλες σελίδες
$router->get('/about', [HomeController::class, 'about']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'submitContactForm']);
$router->get('/terms', [HomeController::class, 'terms']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/faq', [HomeController::class, 'faq']);

// Δρομολογήσεις για το σύστημα αξιολόγησης οδηγών
$router->get('/drivers/driver-rating', [DriversController::class, 'driverRating']);
$router->get('/drivers/refresh-rating', [DriversController::class, 'refreshRating']);
$router->get('/drivers/incident-history', [DriversController::class, 'incidentHistory']);
$router->get('/drivers/report-incident', [DriversController::class, 'reportIncident']);
$router->post('/drivers/save-incident', [DriversController::class, 'saveIncident']);

// Διαδρομές για τα ταιριάσματα
$router->get('/matching/driver-matches', [MatchingController::class, 'driverMatches']);
$router->get('/matching/company-matches', [MatchingController::class, 'companyMatches']);

// Διαδρομές για τις αιτήσεις εργασίας
$router->post('/job-applications/apply/{id}', [JobApplicationController::class, 'apply']);
$router->get('/job-applications/my-applications', [JobApplicationController::class, 'myApplications']);
$router->get('/job-applications/view/{id}', [JobApplicationController::class, 'view']);
$router->post('/job-applications/withdraw/{id}', [JobApplicationController::class, 'withdraw']);

// Διαδρομές για τις προσφορές εργασίας προς οδηγούς
$router->post('/job-offers/send/{id}', [JobOfferController::class, 'send']);
$router->get('/job-offers/my-offers', [JobOfferController::class, 'myOffers']);
$router->get('/job-offers/view/{id}', [JobOfferController::class, 'view']);
$router->post('/job-offers/accept/{id}', [JobOfferController::class, 'accept']);
$router->post('/job-offers/reject/{id}', [JobOfferController::class, 'reject']);

$router->get('/drivers/debug-request', [DriversController::class, 'debugRequest']);


// Διαδρομή για το δημόσιο προφίλ οδηγού
$router->get('/drivers/profile/{id}', [DriversController::class, 'publicProfile']);
// Διαδρομή για τις αγγελίες ενός συγκεκριμένου οδηγού
$router->get('/job-listings/driver/{id}', [CompanyJobListingController::class, 'driverListings']);
$router->get('/αγγελιες/οδηγος/{id}', [CompanyJobListingController::class, 'driverListings']);

$router->get('/job-listings/delete/{id}', [CompanyJobListingController::class, 'delete']);
$router->post('/job-listings/destroy/{id}', [CompanyJobListingController::class, 'destroy']);

$router->get('/drivers/edit-resume', [DriverResumeController::class, 'editResume']);
$router->post('/drivers/update-resume', [DriverResumeController::class, 'updateResume']);

// Διαδρομή για 404 Not Found
$router->notFound(function () {
    require_once ROOT_DIR . '/src/Views/errors/404.php';
});
