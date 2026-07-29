<?php

namespace Drivejob\Core;

/**
 * Front Controller για τη διαχείριση των αιτημάτων
 */
class FrontController
{
    /**
     * @var FrontController Η μοναδική περίσταση του FrontController (Singleton pattern)
     */
    private static $instance = null;

    /**
     * @var Router Ο δρομολογητής
     */
    private static $router = null;

    /**
     * @var Container Το container
     */
    private static $container = null;

    /**
     * Ιδιωτικός constructor για αποτροπή δημιουργίας πολλαπλών περιστάσεων
     */
    private function __construct()
    {
        // Λήψη του container
        self::$container = Container::getInstance();

        // Δημιουργία του router και πέρασμα του container
        self::$router = new Router('', self::$container);

        // Ορισμός των διαδρομών
        $this->setupRoutes();
    }

    /**
     * Αρχικοποίηση του FrontController
     *
     * @return FrontController
     */
    public static function initialize()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Ρύθμιση των διαδρομών της εφαρμογής
     *
     * @return void
     */
    private function setupRoutes()
    {
        // Περνάμε το router στο routes.php για να καταχωρήσει όλες τις διαδρομές
        $router = self::$router;
        require_once ROOT_DIR . '/config/routes.php';
    }

    /**
     * Δρομολόγηση της αίτησης στον κατάλληλο controller
     *
     * @return mixed
     */
    public static function dispatch()
    {
        if (self::$router === null) {
            self::initialize();
        }

        return self::$router->resolve();
    }

    /**
     * Επιστρέφει το container
     *
     * @return Container
     */
    public static function getContainer()
    {
        if (self::$container === null) {
            self::$container = Container::getInstance();
        }

        return self::$container;
    }

    /**
     * Επιστρέφει τον router
     *
     * @return Router
     */
    public static function getRouter()
    {
        if (self::$router === null) {
            self::initialize();
        }

        return self::$router;
    }
}
