<?php
namespace Tests\Mocks;

/**
 * Έκδοση της κλάσης Session για δοκιμές
 */
class MockSession {
    private static $session = [];
    private static $started = false;
    
    /**
     * Ξεκινά μια εικονική συνεδρία για δοκιμές
     */
    public static function start() {
        self::$started = true;
        return true;
    }
    
    /**
     * Ελέγχει αν η εικονική συνεδρία έχει ξεκινήσει
     */
    public static function isStarted() {
        return self::$started;
    }

    /**
     * Θέτει μια τιμή στην εικονική συνεδρία
     */
    public static function set($key, $value) {
        self::$session[$key] = $value;
    }
    
    /**
     * Επιστρέφει μια τιμή από την εικονική συνεδρία
     */
    public static function get($key, $default = null) {
        return self::$session[$key] ?? $default;
    }
    
    /**
     * Ελέγχει αν υπάρχει ένα κλειδί στην εικονική συνεδρία
     */
    public static function has($key) {
        return isset(self::$session[$key]);
    }
    
    /**
     * Αφαιρεί ένα κλειδί από την εικονική συνεδρία
     */
    public static function remove($key) {
        if (isset(self::$session[$key])) {
            unset(self::$session[$key]);
            return true;
        }
        return false;
    }
    
    /**
     * Καταστρέφει την εικονική συνεδρία
     */
    public static function destroy() {
        self::$session = [];
        self::$started = false;
        return true;
    }
    
    /**
     * Ανανεώνει το ID της εικονικής συνεδρίας (dummy για δοκιμές)
     */
    public static function regenerate($deleteOldSession = true) {
        return true;
    }
    
    /**
     * Καθαρίζει όλα τα δεδομένα της εικονικής συνεδρίας
     */
    public static function clear() {
        self::$session = [];
        return true;
    }
    
    /**
     * Επιστρέφει το τρέχον ID της εικονικής συνεδρίας (dummy για δοκιμές)
     */
    public static function getId() {
        return 'test_session_id';
    }
}