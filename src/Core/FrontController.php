<?php
namespace Drivejob\Core;

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
        // Περνάμε το router στο routes.php για να καταχωρήσει όλες τις διαδρομές
        $router = self::$router;
        require_once ROOT_DIR . '/config/routes.php';
        
        // ΔΕΝ ορίζουμε επιπλέον διαδρομές εδώ!
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