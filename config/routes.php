<?php
// config/routes.php
// Δημιουργία του router (εάν δεν έχει περαστεί ως παράμετρος)
if (!isset($router)) {
    $router = new Drivejob\Core\Router();
}

use Drivejob\Core\Router;
use Drivejob\Controllers\HomeController;
use Drivejob\Controllers\NotificationController;
use Drivejob\Controllers\AuthController;

// Controllers με Repository pattern
use Drivejob\Controllers\Driver\DriversController;
use Drivejob\Controllers\Driver\JobApplicationController;
use Drivejob\Controllers\Driver\JobOfferController;
use Drivejob\Controllers\Driver\DriverResumeController;
use Drivejob\Controllers\Company\CompaniesController;
use Drivejob\Controllers\Company\JobApplicationController as CompanyJobApplicationController;
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
    // Η φόρμα «Επαναποστολή email» στο verification-required.php κάνει POST
    // εδώ — χωρίς αυτή τη διαδρομή το κουμπί έβγαζε 404.
    $router->post('/resend-verification', [AuthController::class, 'resendVerification'])->name('auth.resend-verification');
});

// ── Συντομεύσεις πρώτου επιπέδου ────────────────────────────────────────
// Ο κώδικας και τα views παραπέμπουν σε /login (33 σημεία), /logout,
// /access-denied, /forgot-password και /reset-password/{token}. Χωρίς αυτές
// τις διαδρομές κάθε τέτοια ανακατεύθυνση κατέληγε σε 404 — π.χ. αμέσως
// μετά από επιτυχημένη εγγραφή.
$router->get('/login', [AuthController::class, 'showLoginForm'])->name('login');
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout'])->name('logout');
$router->get('/access-denied', [AuthController::class, 'accessDenied'])->name('access-denied');
$router->get('/forgot-password', [AuthController::class, 'showPasswordResetForm'])->name('forgot-password');
$router->post('/forgot-password', [AuthController::class, 'sendPasswordResetLink']);
$router->get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('reset-password');
$router->post('/reset-password/{token}', [AuthController::class, 'resetPassword']);

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
    $router->post('/register', [DriversController::class, 'processRegistration']);
    $router->get('/profile', [DriversController::class, 'profile'])->name('drivers.profile');
    $router->get('/profile/{id}', [DriversController::class, 'publicProfile'])->name('drivers.profile.public');
    $router->get('/edit-profile', [DriversController::class, 'edit'])->name('drivers.edit-profile');
    $router->post('/update-profile', [DriversController::class, 'update'])->name('drivers.update-profile');
    // Το κουμπί «Διαχείριση Προϋπηρεσίας σε Οχήματα» έδειχνε εδώ, αλλά η
    // διαδρομή δεν υπήρχε ποτέ — ο οδηγός έπαιρνε 404.
    $router->get('/vehicle-experience', [DriversController::class, 'vehicleExperience'])->name('drivers.vehicle-experience');
    // Άμεση αποθήκευση/διαγραφή ανά εγγραφή — καμία «Αποθήκευση Αλλαγών»
    $router->post('/vehicle-experience', [DriversController::class, 'addVehicleExperience'])->name('drivers.vehicle-experience.add');
    $router->post('/vehicle-experience/delete/{id}', [DriversController::class, 'deleteVehicleExperience'])->name('drivers.vehicle-experience.delete');
    $router->post('/languages', [DriversController::class, 'addLanguage'])->name('drivers.languages.add');
    $router->post('/languages/delete/{id}', [DriversController::class, 'deleteLanguage'])->name('drivers.languages.delete');
    // Σεμινάρια & πιστοποιητικά εκπαίδευσης — το κουμπί «Διαχείριση
    // Πιστοποιητικών» έδειχνε εδώ από καιρό, αλλά η διαδρομή δεν υπήρχε (404).
    $router->get('/certifications', [DriversController::class, 'certifications'])->name('drivers.certifications');
    $router->post('/certifications', [DriversController::class, 'addCertification'])->name('drivers.certifications.add');
    $router->post('/certifications/delete/{id}', [DriversController::class, 'deleteCertification'])->name('drivers.certifications.delete');
    $router->post('/change-password', [DriversController::class, 'changePassword'])->name('drivers.change-password');
    $router->get('/security', [DriversController::class, 'security'])->name('drivers.security');

    // Διαδρομές αναζήτησης
    $router->get('/search', [DriversController::class, 'search'])->name('drivers.search');

    /*
     * ══════════════════════════════════════════════════════════════════
     *  ΕΝΝΕΑ ΔΙΑΔΡΟΜΕΣ ΑΦΑΙΡΕΘΗΚΑΝ (23/08/2026) — ΝΕΚΡΕΣ
     * ══════════════════════════════════════════════════════════════════
     *
     * top-rated, recently-available, add-rating, welcome, complete-profile,
     * save-assessment, driver-rating, refresh-rating, debug-request:
     * έδειχναν σε μεθόδους που ΔΕΝ ΥΠΑΡΧΟΥΝ στον DriversController.
     * Κάθε επίσκεψη έβγαζε 500 — χειρότερο από 404, γιατί μοιάζει με
     * βλάβη της πλατφόρμας αντί για ανύπαρκτη σελίδα.
     *
     * Αν κάποια από αυτές τις λειτουργίες χτιστεί στο μέλλον (π.χ. οι
     * κορυφαίοι οδηγοί), γράφεται πρώτα η μέθοδος και μετά η διαδρομή —
     * ποτέ ανάποδα.
     */

    // Διαδρομές για την αυτοαξιολόγηση
    $router->get('/update-assessment', [DriversController::class, 'updateAssessment'])->name('drivers.update-assessment');
    $router->post('/update-assessment', [DriversController::class, 'updateAssessment']);

    // Διαδρομές για τη διαθεσιμότητα
    $router->post('/toggle-availability', [DriversController::class, 'toggleAvailability'], [
        function () {
            \Drivejob\Core\AuthMiddleware::hasRole('driver');
            return null;
        }
    ])->name('drivers.toggle-availability');

    // Μηνύματα
    $router->get('/messages', [\Drivejob\Controllers\MessagesController::class, 'driverMessages'])->name('drivers.messages');
    $router->get('/conversation', [\Drivejob\Controllers\MessagesController::class, 'driverConversation'])->name('drivers.conversation');
    $router->post('/conversation', [\Drivejob\Controllers\MessagesController::class, 'driverConversation']);

    // Διαδρομές για τα περιστατικά
    $router->get('/incident-history', [DriversController::class, 'incidentHistory'])->name('drivers.incident-history');
    $router->get('/report-incident', [DriversController::class, 'reportIncident'])->name('drivers.report-incident');
    $router->post('/save-incident', [DriversController::class, 'saveIncident'])->name('drivers.save-incident');

    // Διαδρομές για το βιογραφικό
    $router->get('/edit-resume', [DriverResumeController::class, 'editResume'])->name('drivers.edit-resume');
    $router->post('/update-resume', [DriverResumeController::class, 'updateResume'])->name('drivers.update-resume');

    // AI Matching routes
    $router->get('/job-matches', [DriversController::class, 'jobMatches'])->name('drivers.job-matches');
});

// Ομαδοποίηση διαδρομών για τις εταιρείες
$router->group(['prefix' => 'companies'], function ($router) {
    $router->get('/register', [CompaniesController::class, 'showRegistrationForm'])->name('companies.register');
    $router->post('/register', [CompaniesController::class, 'processRegistration']);
    $router->get('/profile', [CompaniesController::class, 'profile'])->name('companies.profile');
    $router->get('/profile/{id}', [CompaniesController::class, 'publicProfile'])->name('companies.profile.public');
    $router->get('/edit-profile', [CompaniesController::class, 'edit'])->name('companies.edit-profile');
    $router->post('/update-profile', [CompaniesController::class, 'update'])->name('companies.update-profile');
    $router->post('/change-password', [CompaniesController::class, 'changePassword'])->name('companies.change-password');
    $router->get('/search', [CompaniesController::class, 'search'])->name('companies.search');
    $router->post('/add-review/{id}', [CompaniesController::class, 'addReview'])->name('companies.add-review');

    $router->get('/messages', [\Drivejob\Controllers\MessagesController::class, 'companyMessages'])->name('companies.messages');
    $router->get('/conversation', [\Drivejob\Controllers\MessagesController::class, 'companyConversation'])->name('companies.conversation');
    $router->post('/conversation', [\Drivejob\Controllers\MessagesController::class, 'companyConversation']);
});

$router->get('/robots.txt', [\Drivejob\Controllers\RobotsController::class, 'index'])->name('robots');

// Health check για monitors/πλατφόρμες (Πακέτο 9)
$router->get('/health', [\Drivejob\Controllers\HealthController::class, 'index'])->name('health');

// Εκτέλεση προγραμματισμένων εργασιών μέσω HTTP (εφεδρεία όταν το cron του
// παρόχου δεν λειτουργεί). Προστατεύεται από το CRON_TOKEN του .env — χωρίς
// αυτό η διαδρομή απαντά 404.
$router->get('/cron/{task}', [\Drivejob\Controllers\CronController::class, 'run'])->name('cron.run');

// GDPR — δικαιώματα υποκειμένων (Πακέτο 7)
$router->get('/gdpr/export', [\Drivejob\Controllers\GdprController::class, 'export'])->name('gdpr.export');
$router->get('/gdpr/delete', [\Drivejob\Controllers\GdprController::class, 'deleteConfirm'])->name('gdpr.delete');
$router->post('/gdpr/delete', [\Drivejob\Controllers\GdprController::class, 'delete']);

// Σελίδες πληροφοριών (top-level: /about, /contact, /terms, /privacy, /faq)
$router->get('/about', [HomeController::class, 'about'])->name('info.about');
$router->get('/contact', [HomeController::class, 'contact'])->name('info.contact');
$router->post('/contact', [HomeController::class, 'submitContactForm'])->name('info.contact.submit');
$router->get('/terms', [HomeController::class, 'terms'])->name('info.terms');
$router->get('/privacy', [HomeController::class, 'privacy'])->name('info.privacy');
$router->get('/faq', [HomeController::class, 'faq'])->name('info.faq');

// Ομαδοποίηση διαδρομών για τα ταιριάσματα
// Το group /matching/* αποσύρθηκε (Πακέτο 4): καλούσε ανύπαρκτες μεθόδους από την εποχή WAMP.
// Οι λειτουργίες ζουν στα widgets προφίλ και στη σελίδα job-matches.

// Ομαδοποίηση διαδρομών για τις αιτήσεις εργασίας
$router->group(['prefix' => 'job-applications'], function ($router) {
    $router->post('/apply/{id}', [JobApplicationController::class, 'apply'])->name('job-applications.apply');
    $router->get('/my-applications', [JobApplicationController::class, 'myApplications'])->name('job-applications.my-applications');
    // Η μέθοδος λέγεται viewApplication — το 'view' δεν υπήρχε ποτέ.
    $router->get('/view/{id}', [JobApplicationController::class, 'viewApplication'])->name('job-applications.view');
    $router->post('/withdraw/{id}', [JobApplicationController::class, 'withdraw'])->name('job-applications.withdraw');

    // Πλευρά εταιρείας — οι μέθοδοι υπήρχαν αλλά καμία διαδρομή δεν έδειχνε σε αυτές.
    $router->get('/company-applications', [CompanyJobApplicationController::class, 'myApplications'])->name('job-applications.company');
    $router->get('/listing/{id}', [CompanyJobApplicationController::class, 'listingApplications'])->name('job-applications.listing');
    // Η προεπιλογή ήταν το βήμα που έλειπε: η κατάσταση 'shortlisted' υπήρχε
    // στο enum της βάσης και στο Visibility::ENGAGED_STATUSES, αλλά καμία
    // ενέργεια δεν την όριζε — άρα τα στοιχεία επικοινωνίας δεν μπορούσαν να
    // ξεκλειδώσουν παρά μόνο με απευθείας πρόσληψη.
    $router->post('/shortlist/{id}', [CompanyJobApplicationController::class, 'shortlist'])->name('job-applications.shortlist');
    $router->post('/accept/{id}', [CompanyJobApplicationController::class, 'accept'])->name('job-applications.accept');
    $router->post('/reject/{id}', [CompanyJobApplicationController::class, 'reject'])->name('job-applications.reject');
});

// Οι ειδοποιήσεις — το καμπανάκι. Ο πίνακας και το repository υπήρχαν·
// αυτές οι τρεις διαδρομές είναι ό,τι έλειπε για να τα δει ο χρήστης.
$router->group(['prefix' => 'notifications'], function ($router) {
    $router->get('/', [NotificationController::class, 'index'])->name('notifications.index');
    $router->get('/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    $router->post('/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

// Ομαδοποίηση διαδρομών για τις προσφορές εργασίας
$router->group(['prefix' => 'job-offers'], function ($router) {
    // Το {id} εδώ είναι η ΑΓΓΕΛΙΑ του οδηγού· η create() το μεταφράζει σε οδηγό.
    $router->get('/create/{id}', [JobOfferController::class, 'create'])->name('job-offers.create');
    $router->post('/send/{id}', [JobOfferController::class, 'send'])->name('job-offers.send');
    $router->get('/my-offers', [JobOfferController::class, 'myOffers'])->name('job-offers.my-offers');
    // Η μέθοδος λέγεται viewOffer — το «view» είναι δεσμευμένο σε πολλά MVC.
    // Η διαδρομή έδειχνε σε μέθοδο που δεν υπάρχει και έβγαζε 500.
    $router->get('/view/{id}', [JobOfferController::class, 'viewOffer'])->name('job-offers.view');
    $router->post('/accept/{id}', [JobOfferController::class, 'accept'])->name('job-offers.accept');
    $router->post('/reject/{id}', [JobOfferController::class, 'reject'])->name('job-offers.reject');
});

// Διαδρομές με ελληνικά ονόματα για SEO
$router->get('/αγγελιες/οδηγος/{id}', [UnifiedJobListingController::class, 'driverListings'])->name('job-listings.driver.greek');

// Ομαδοποίηση διαδρομών για το Admin Panel
$router->group(['prefix' => 'admin'], function ($router) {
    // Admin Dashboard
    $router->get('/dashboard', [\Drivejob\Controllers\Admin\AdminController::class, 'dashboard'])->name('admin.dashboard');

    // User Management
    $router->get('/users', [\Drivejob\Controllers\Admin\AdminController::class, 'users'])->name('admin.users');
    $router->get('/users/{type}', [\Drivejob\Controllers\Admin\AdminController::class, 'users'])->name('admin.users.type');
    $router->get('/user-details/{userId}/{userType}', [\Drivejob\Controllers\Admin\AdminController::class, 'userDetails'])->name('admin.user-details');
    $router->post('/toggle-user-status/{userId}/{userType}', [\Drivejob\Controllers\Admin\AdminController::class, 'toggleUserStatus'])->name('admin.toggle-user-status');

    // Job Listings Management
    $router->get('/job-listings', [\Drivejob\Controllers\Admin\AdminController::class, 'jobListings'])->name('admin.job-listings');
    $router->post('/toggle-listing/{id}', [\Drivejob\Controllers\Admin\AdminController::class, 'toggleListing'])->name('admin.toggle-listing');

    // Analytics & Reports
    $router->get('/analytics', [\Drivejob\Controllers\Admin\AdminController::class, 'analytics'])->name('admin.analytics');

    // System Settings
    $router->get('/settings', [\Drivejob\Controllers\Admin\AdminController::class, 'settings'])->name('admin.settings');
    $router->post('/settings', [\Drivejob\Controllers\Admin\AdminController::class, 'settings']);

    // Activity Logs
    $router->get('/activity-logs', [\Drivejob\Controllers\Admin\AdminController::class, 'activityLogs'])->name('admin.activity-logs');

    // System Monitoring
    $router->group(['prefix' => 'monitoring'], function ($router) {
        // Dashboard
        $router->get('/dashboard', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'dashboard'])->name('admin.monitoring.dashboard');

        // Errors
        $router->get('/errors', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'errors'])->name('admin.monitoring.errors');
        $router->get('/errors/{period}', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'errors'])->name('admin.monitoring.errors.period');

        // Performance
        $router->get('/performance', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'performance'])->name('admin.monitoring.performance');
        $router->get('/performance/{period}', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'performance'])->name('admin.monitoring.performance.period');

        // Usage
        $router->get('/usage', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'usage'])->name('admin.monitoring.usage');
        $router->get('/usage/{period}', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'usage'])->name('admin.monitoring.usage.period');

        // Logs
        $router->get('/logs', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'logs'])->name('admin.monitoring.logs');
        $router->get('/logs/{type}', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'logs'])->name('admin.monitoring.logs.type');
        // Το clearLogs αφαιρέθηκε: η μέθοδος δεν υπάρχει στον controller,
        // και η διαγραφή logs δεν είναι κουμπί — τα logs κόβονται με rotation.

        // Database Backup
        $router->post('/backup-database', [\Drivejob\Controllers\Admin\SystemMonitoringController::class, 'backupDatabase'])->name('admin.monitoring.backup-database');
    });
});

// Include API routes
require_once ROOT_DIR . '/routes/api.php';

// Legacy admin panel & εργαλεία (Πακέτο 4 — αρχεία στο src/Legacy/admin)
$router->get('/admin/panel', [\Drivejob\Controllers\LegacyPagesController::class, 'adminPanel'])->name('admin.panel');
$router->get('/admin/tools/{tool}', [\Drivejob\Controllers\LegacyPagesController::class, 'adminTool'])->name('admin.tools');

// Legacy API endpoints (αρχεία στο src/Legacy/api)
$router->get('/api/legacy/{endpoint}', [\Drivejob\Controllers\LegacyPagesController::class, 'api'])->name('api.legacy');
$router->post('/api/legacy/{endpoint}', [\Drivejob\Controllers\LegacyPagesController::class, 'api']);
$router->get('/api/admin/{endpoint}', [\Drivejob\Controllers\LegacyPagesController::class, 'adminApi'])->name('api.admin');
$router->post('/api/admin/{endpoint}', [\Drivejob\Controllers\LegacyPagesController::class, 'adminApi']);

// Σερβίρισμα αρχείων uploads με έλεγχο πρόσβασης (τα αρχεία ζουν στο storage/uploads)
$router->get("/uploads/{folder}/{filename}", [\Drivejob\Controllers\FileController::class, "serve"])->name("files.serve");

// Διαδρομή για 404 Not Found
$router->notFound(function () {
    http_response_code(404); // σωστό status για SEO/monitoring (πριν έστελνε 200)
    require_once ROOT_DIR . '/src/Views/errors/404.php';
});
