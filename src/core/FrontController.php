<?php
namespace Drivejob\Core;

use Drivejob\Core\Router;

class FrontController
{
    private static $instance = null;
    private static $router = null;
    
    /**
     * Αρχικοποίηση του FrontController
     */
    public static function initialize()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$router = new Router();
            
            // Ορισμός των διαδρομών
            self::setupRoutes();
        }
        
        return self::$instance;
    }
    
    /**
     * Ρύθμιση των διαδρομών της εφαρμογής
     */
    private static function setupRoutes()
    {
        // Αρχική σελίδα
        self::$router->get('/', [new \Drivejob\Controllers\HomeController(), 'renderHomePage']);
        
        // Διαδρομές για τις αγγελίες εργασίας
        self::$router->get('/job-listings', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'index']);
        self::$router->get('/job-listings/create', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'create']);
        self::$router->post('/job-listings/store', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'store']);
        self::$router->get('/job-listings/show/{id}', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'show']);
        self::$router->get('/job-listings/edit/{id}', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'edit']);
        self::$router->post('/job-listings/update/{id}', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'update']);
        self::$router->get('/job-listings/delete/{id}', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'delete']);
        
        // Διαδρομές για το προφίλ οδηγών
        self::$router->get('/drivers/driver_profile', [new \Drivejob\Controllers\DriversController($GLOBALS['pdo']), 'profile']);
        self::$router->get('/drivers/edit-profile', [new \Drivejob\Controllers\DriversController($GLOBALS['pdo']), 'edit']);
        self::$router->post('/drivers/update-profile', [new \Drivejob\Controllers\DriversController($GLOBALS['pdo']), 'update']);
        
        // Διαδρομές για το προφίλ εταιρειών
        self::$router->get('/companies/company_profile', [new \Drivejob\Controllers\CompaniesController($GLOBALS['pdo']), 'profile']);
        self::$router->get('/companies/edit-profile', [new \Drivejob\Controllers\CompaniesController($GLOBALS['pdo']), 'edit']);
        self::$router->post('/companies/update-profile', [new \Drivejob\Controllers\CompaniesController($GLOBALS['pdo']), 'update']);
        
        // Διαδρομές σύνδεσης και αυθεντικοποίησης
        self::$router->get('/login', [new \Drivejob\Controllers\AuthController($GLOBALS['pdo']), 'login']);
        self::$router->post('/login_process', [new \Drivejob\Controllers\AuthController($GLOBALS['pdo']), 'processLogin']);
        self::$router->get('/logout', [new \Drivejob\Controllers\AuthController($GLOBALS['pdo']), 'logout']);
        
        // Διαδρομή για τις αγγελίες του χρήστη
        self::$router->get('/my-listings', [new \Drivejob\Controllers\JobListingController($GLOBALS['pdo']), 'myListings']);
        
        // Διαδρομή για 404 σελίδα
        self::$router->notFound(function() {
            include ROOT_DIR . '/src/Views/404.php';
        });
    }
    
    /**
     * Δρομολόγηση της αίτησης στον κατάλληλο controller
     */
    public static function dispatch()
    {
        if (self::$router === null) {
            self::initialize();
        }
        
        return self::$router->resolve();
    }
}