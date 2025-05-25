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
use Drivejob\Controllers\UnifiedJobListingController;

// Αρχική σελίδα
$router->get('/', [HomeController::class, 'renderHomePage'])->name('home');

// Διαδρομή για τις αγγελίες (εκτός ομάδας)
// Αφαιρέθηκε η διπλή διαδρομή για τις αγγελίες

// Ομαδοποίηση διαδρομών αυθεντικοποίησης
$router->group(['prefix' => 'auth'], function ($router) {
    $router->get('/login', [AuthController::class, 'showLoginForm'])->name('auth.login');
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    $router->get('/verify/{token}', [AuthController::class, 'verify'])->name('auth.verify');
    $router->get('/password-reset', [AuthController::class, 'showPasswordResetForm'])->name('auth.password-reset');
    $router->post('/password-reset', [AuthController::class, 'sendPasswordResetLink']);
    $router->get('/password-reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('auth.password-reset.token');
    $router->post('/password-reset/{token}', [AuthController::class, 'resetPassword']);
    $router->get('/access-denied', [AuthController::class, 'accessDenied'])->name('auth.access-denied');
    $router->get('/verification-required', [AuthController::class, 'verificationRequired'])->name('auth.verification-required');
});

// Ομαδοποίηση διαδρομών για τις αγγελίες
$router->group(['prefix' => 'job-listings'], function ($router) {
    // Βασικές διαδρομές αγγελιών
    $router->get('/', [UnifiedJobListingController::class, 'index'])->name('job-listings.index');
    $router->get('/show/{id}', [UnifiedJobListingController::class, 'show'])->name('job-listings.show');
    $router->get('/company/{id}', [UnifiedJobListingController::class, 'companyListings'])->name('job-listings.company');
    $router->get('/driver/{id}', [UnifiedJobListingController::class, 'driverListings'])->name('job-listings.driver');
    $router->get('/my-listings', [UnifiedJobListingController::class, 'myListings'])->name('job-listings.my-listings');

    // Διαδρομές για τη δημιουργία αγγελιών
    $router->get('/create', [UnifiedJobListingController::class, 'create'])->name('job-listings.create');
    $router->post('/store', [UnifiedJobListingController::class, 'store'])->name('job-listings.store');

    // Διαδρομές για την επεξεργασία αγγελιών
    $router->get('/edit/{id}', [UnifiedJobListingController::class, 'edit'])->name('job-listings.edit');
    $router->post('/update/{id}', [UnifiedJobListingController::class, 'update'])->name('job-listings.update');

    // Διαδρομές για τη διαγραφή αγγελιών
    $router->get('/delete/{id}', [UnifiedJobListingController::class, 'delete'])->name('job-listings.delete');
    $router->post('/destroy/{id}', [UnifiedJobListingController::class, 'destroy'])->name('job-listings.destroy');

    // Ανακατευθύνσεις για τις παλιές διαδρομές
    $router->get('/Driver/create', function () {
        header('Location: ' . BASE_URL . 'job-listings/create');
        exit();
    });
    $router->post('/Driver/store', function () {
        header('Location: ' . BASE_URL . 'job-listings/store');
        exit();
    });
    $router->get('/edit-driver/{id}', function ($id) {
        header('Location: ' . BASE_URL . 'job-listings/edit/' . $id);
        exit();
    });
    $router->post('/update-driver/{id}', function ($id) {
        header('Location: ' . BASE_URL . 'job-listings/update/' . $id);
        exit();
    });
    $router->get('/delete-driver/{id}', function ($id) {
        header('Location: ' . BASE_URL . 'job-listings/delete/' . $id);
        exit();
    });
    $router->post('/destroy-driver/{id}', function ($id) {
        header('Location: ' . BASE_URL . 'job-listings/destroy/' . $id);
        exit();
    });
});

// Ομαδοποίηση διαδρομών για τους οδηγούς
$router->group(['prefix' => 'drivers'], function ($router) {
    // Διαδρομές εγγραφής και προφίλ
    $router->get('/register', [DriversController::class, 'showRegistrationForm'])->name('drivers.register');
    $router->post('/register', [DriversController::class, 'register']);
    $router->get('/profile', [DriversController::class, 'profile'])->name('drivers.profile');
    $router->get('/profile/{id}', [DriversController::class, 'publicProfile'])->name('drivers.profile.public');
    $router->get('/edit-profile', [DriversController::class, 'edit'])->name('drivers.edit-profile');
    $router->post('/update-profile', [DriversController::class, 'update'])->name('drivers.update-profile');
    $router->post('/change-password', [DriversController::class, 'changePassword'])->name('drivers.change-password');

    // Διαδρομές αναζήτησης και αξιολόγησης
    $router->get('/search', [DriversController::class, 'search'])->name('drivers.search');
    $router->get('/top-rated', [DriversController::class, 'topRated'])->name('drivers.top-rated');
    $router->get('/recently-available', [DriversController::class, 'recentlyAvailable'])->name('drivers.recently-available');
    $router->post('/add-rating/{id}', [DriversController::class, 'addRating'])->name('drivers.add-rating');

    // Διαδρομές για νέους οδηγούς
    $router->get('/welcome', [DriversController::class, 'welcome'])->name('drivers.welcome');
    $router->post('/complete-profile', [DriversController::class, 'completeProfile'])->name('drivers.complete-profile');

    // Διαδρομές για την αυτοαξιολόγηση
    $router->get('/update-assessment', [DriversController::class, 'updateAssessment'])->name('drivers.update-assessment');
    $router->post('/update-assessment', [DriversController::class, 'updateAssessment']);
    $router->post('/save-assessment', [DriversController::class, 'saveAssessment'])->name('drivers.save-assessment');

    // Διαδρομές για τη διαθεσιμότητα
    $router->post('/toggle-availability', [DriversController::class, 'toggleAvailability'], [
        function () {
            \Drivejob\Core\AuthMiddleware::hasRole('driver');
            return null;
        }
    ])->name('drivers.toggle-availability');

    // Διαδρομές για το σύστημα αξιολόγησης οδηγών
    $router->get('/driver-rating', [DriversController::class, 'driverRating'])->name('drivers.driver-rating');
    $router->get('/refresh-rating', [DriversController::class, 'refreshRating'])->name('drivers.refresh-rating');

    // Διαδρομές για τα περιστατικά
    $router->get('/incident-history', [DriversController::class, 'incidentHistory'])->name('drivers.incident-history');
    $router->get('/report-incident', [DriversController::class, 'reportIncident'])->name('drivers.report-incident');
    $router->post('/save-incident', [DriversController::class, 'saveIncident'])->name('drivers.save-incident');

    // Διαδρομές για το βιογραφικό
    $router->get('/edit-resume', [DriverResumeController::class, 'editResume'])->name('drivers.edit-resume');
    $router->post('/update-resume', [DriverResumeController::class, 'updateResume'])->name('drivers.update-resume');

    // Διαδρομές για debugging
    $router->get('/debug-request', [DriversController::class, 'debugRequest'])->name('drivers.debug-request');
});

// Ομαδοποίηση διαδρομών για τις εταιρείες
$router->group(['prefix' => 'companies'], function ($router) {
    $router->get('/register', [CompaniesController::class, 'showRegistrationForm'])->name('companies.register');
    $router->post('/register', [CompaniesController::class, 'register']);
    $router->get('/profile', [CompaniesController::class, 'profile'])->name('companies.profile');
    $router->get('/profile/{id}', [CompaniesController::class, 'publicProfile'])->name('companies.profile.public');
    $router->get('/edit-profile', [CompaniesController::class, 'edit'])->name('companies.edit-profile');
    $router->post('/update-profile', [CompaniesController::class, 'update'])->name('companies.update-profile');
    $router->post('/change-password', [CompaniesController::class, 'changePassword'])->name('companies.change-password');
    $router->get('/search', [CompaniesController::class, 'search'])->name('companies.search');
    $router->post('/add-review/{id}', [CompaniesController::class, 'addReview'])->name('companies.add-review');
});

// Ομαδοποίηση διαδρομών για τις σελίδες πληροφοριών
$router->group(['prefix' => 'info'], function ($router) {
    $router->get('/about', [HomeController::class, 'about'])->name('info.about');
    $router->get('/contact', [HomeController::class, 'contact'])->name('info.contact');
    $router->post('/contact', [HomeController::class, 'submitContactForm'])->name('info.contact.submit');
    $router->get('/terms', [HomeController::class, 'terms'])->name('info.terms');
    $router->get('/privacy', [HomeController::class, 'privacy'])->name('info.privacy');
    $router->get('/faq', [HomeController::class, 'faq'])->name('info.faq');
});

// Ομαδοποίηση διαδρομών για τα ταιριάσματα
$router->group(['prefix' => 'matching'], function ($router) {
    $router->get('/driver-matches', [MatchingController::class, 'driverMatches'])->name('matching.driver-matches');
    $router->get('/company-matches', [MatchingController::class, 'companyMatches'])->name('matching.company-matches');
    $router->get('/job-listing-matches/{id}', [MatchingController::class, 'jobListingMatches'])->name('matching.job-listing-matches');
    $router->get('/preferences', [MatchingController::class, 'preferences'])->name('matching.preferences');
    $router->post('/save-preferences', [MatchingController::class, 'savePreferences'])->name('matching.save-preferences');
    $router->post('/log-action', [MatchingController::class, 'logAction'])->name('matching.log-action');
});

// Ομαδοποίηση διαδρομών για τις αιτήσεις εργασίας
$router->group(['prefix' => 'job-applications'], function ($router) {
    $router->post('/apply/{id}', [JobApplicationController::class, 'apply'])->name('job-applications.apply');
    $router->get('/my-applications', [JobApplicationController::class, 'myApplications'])->name('job-applications.my-applications');
    $router->get('/view/{id}', [JobApplicationController::class, 'view'])->name('job-applications.view');
    $router->post('/withdraw/{id}', [JobApplicationController::class, 'withdraw'])->name('job-applications.withdraw');
});

// Ομαδοποίηση διαδρομών για τις προσφορές εργασίας
$router->group(['prefix' => 'job-offers'], function ($router) {
    $router->post('/send/{id}', [JobOfferController::class, 'send'])->name('job-offers.send');
    $router->get('/my-offers', [JobOfferController::class, 'myOffers'])->name('job-offers.my-offers');
    $router->get('/view/{id}', [JobOfferController::class, 'view'])->name('job-offers.view');
    $router->post('/accept/{id}', [JobOfferController::class, 'accept'])->name('job-offers.accept');
    $router->post('/reject/{id}', [JobOfferController::class, 'reject'])->name('job-offers.reject');
});

// Διαδρομές με ελληνικά ονόματα για SEO
$router->get('/αγγελιες/οδηγος/{id}', [UnifiedJobListingController::class, 'driverListings'])->name('job-listings.driver.greek');

// Διαδρομή για 404 Not Found
$router->notFound(function () {
    require_once ROOT_DIR . '/src/Views/errors/404.php';
});


// Ομαδοποίηση διαδρομών αυθεντικοποίησης
$router->group(['prefix' => 'auth'], function ($router) {
    $router->get('/login', [AuthController::class, 'showLoginForm'])->name('auth.login');
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    $router->get('/verify/{token}', [AuthController::class, 'verify'])->name('auth.verify');
    $router->get('/password-reset', [AuthController::class, 'showPasswordResetForm'])->name('auth.password-reset');
    $router->post('/password-reset', [AuthController::class, 'sendPasswordResetLink']);
    $router->get('/password-reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('auth.password-reset.token');
    $router->post('/password-reset/{token}', [AuthController::class, 'resetPassword']);
    $router->get('/access-denied', [AuthController::class, 'accessDenied'])->name('auth.access-denied');
    $router->get('/verification-required', [AuthController::class, 'verificationRequired'])->name('auth.verification-required');
    $router->post('/resend-verification', [AuthController::class, 'resendVerification'])->name('auth.resend-verification');
});

// Ομαδοποίηση διαδρομών για το Admin Panel
$router->group(['prefix' => 'admin'], function ($router) {
    // Admin Authentication
    $router->get('/login', [\Drivejob\Controllers\AdminController::class, 'showLoginForm'])->name('admin.login');
    $router->post('/login', [\Drivejob\Controllers\AdminController::class, 'login']);
    $router->get('/logout', [\Drivejob\Controllers\AdminController::class, 'logout'])->name('admin.logout');

    // Admin Dashboard
    $router->get('/dashboard', [\Drivejob\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');

    // User Management
    $router->get('/users', [\Drivejob\Controllers\AdminController::class, 'users'])->name('admin.users');
    $router->get('/users/{type}', [\Drivejob\Controllers\AdminController::class, 'users'])->name('admin.users.type');
    $router->get('/user-details/{userId}/{userType}', [\Drivejob\Controllers\AdminController::class, 'userDetails'])->name('admin.user-details');
    $router->post('/toggle-user-status/{userId}/{userType}', [\Drivejob\Controllers\AdminController::class, 'toggleUserStatus'])->name('admin.toggle-user-status');

    // Job Listings Management
    $router->get('/job-listings', [\Drivejob\Controllers\AdminController::class, 'jobListings'])->name('admin.job-listings');

    // Analytics & Reports
    $router->get('/analytics', [\Drivejob\Controllers\AdminController::class, 'analytics'])->name('admin.analytics');

    // System Settings
    $router->get('/settings', [\Drivejob\Controllers\AdminController::class, 'settings'])->name('admin.settings');
    $router->post('/settings', [\Drivejob\Controllers\AdminController::class, 'settings']);

    // Activity Logs
    $router->get('/activity-logs', [\Drivejob\Controllers\AdminController::class, 'activityLogs'])->name('admin.activity-logs');
});
