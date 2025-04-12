<?php
namespace Drivejob\Core;

class Session
{
    private static $started = false;
    
    /**
     * Ξεκινά τη συνεδρία αν δεν έχει ήδη ξεκινήσει.
     * Επιστρέφει true αν η συνεδρία ξεκίνησε επιτυχώς.
     */
    public static function start()
    {
        if (self::$started) {
            return true;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            // Ρυθμίσεις ασφαλείας για τις συνεδρίες
            session_name('DRIVEJOBSESSION');
            
            // Ρύθμιση των cookies που χρησιμοποιούνται για τις συνεδρίες
            session_set_cookie_params([
                'lifetime' => 86400, // 24 ώρες
                'path' => '/',      
                'domain' => '',     // Άδειο σημαίνει το τρέχον domain
                'secure' => false,  // Θέστε το σε true σε παραγωγικό περιβάλλον με HTTPS
                'httponly' => true, // Προστασία από XSS
                'samesite' => 'Lax' // Προστασία από CSRF
            ]);
            
            // Έναρξη συνεδρίας
            $result = session_start();
            self::$started = $result;
            
            return $result;
        }
        
        self::$started = true;
        return true;
    }
    
    /**
     * Ελέγχει αν η συνεδρία έχει ξεκινήσει
     */
    public static function isStarted()
    {
        return self::$started || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Θέτει μια τιμή στη συνεδρία
     */
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Επιστρέφει μια τιμή από τη συνεδρία
     */
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Ελέγχει αν υπάρχει ένα κλειδί στη συνεδρία
     */
    public static function has($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Αφαιρεί ένα κλειδί από τη συνεδρία
     */
    public static function remove($key)
    {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }
    
    /**
     * Καταστρέφει τη συνεδρία
     */
    public static function destroy()
    {
        if (self::isStarted() || session_status() === PHP_SESSION_ACTIVE) {
            // Καθαρισμός όλων των μεταβλητών συνεδρίας
            $_SESSION = [];
            
            // Διαγραφή του cookie συνεδρίας
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            // Καταστροφή της συνεδρίας
            session_destroy();
            self::$started = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Ανανεώνει το ID της συνεδρίας
     */
    public static function regenerate($deleteOldSession = true)
    {
        self::start();
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Καθαρίζει όλα τα δεδομένα της συνεδρίας διατηρώντας την ίδια
     */
    public static function clear()
    {
        self::start();
        $_SESSION = [];
        return true;
    }
    
    /**
     * Επιστρέφει το τρέχον ID της συνεδρίας
     */
    public static function getId()
    {
        self::start();
        return session_id();
    }
    
    /**
     * Διαγνωστική μέθοδος που επιστρέφει πληροφορίες για τη συνεδρία
     */
    public static function getDebugInfo()
    {
        self::start();
        return [
            'id' => session_id(),
            'name' => session_name(),
            'started' => self::$started,
            'status' => session_status(),
            'save_path' => session_save_path(),
            'cookie_params' => session_get_cookie_params(),
            'data' => $_SESSION
        ];
    }
}