<?php
namespace Drivejob\Core;

class Session
{
    /**
     * Ξεκινά τη συνεδρία αν δεν έχει ήδη ξεκινήσει
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Χρησιμοποιούμε τον προεπιλεγμένο φάκελο συνεδριών της PHP αντί για προσαρμοσμένο
            // Αυτό θα παρακάμψει τα προβλήματα δικαιωμάτων με τον φάκελο tmp/sessions
            
            // Ρυθμίσεις για το όνομα και τα cookies της συνεδρίας
            session_name('DRIVEJOBSESSION');
            
            session_set_cookie_params([
                'lifetime' => 86400, // 24 ώρες
                'path' => '/',      
                'domain' => '',     
                'secure' => false,  
                'httponly' => true, 
                'samesite' => 'Lax'
            ]);
            
            // Καταγραφή πριν την έναρξη της συνεδρίας
            file_put_contents(
                ROOT_DIR . '/session_start_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "About to start session, status: " . session_status() . "\n", 
                FILE_APPEND
            );
            
            // Έναρξη συνεδρίας
            session_start();
            
            // Καταγραφή μετά την έναρξη της συνεδρίας
            file_put_contents(
                ROOT_DIR . '/session_start_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Session started, ID: " . session_id() . ", Data: " . print_r($_SESSION, true) . "\n", 
                FILE_APPEND
            );
        }
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
        }
    }
    
    /**
     * Καταστρέφει τη συνεδρία
     */
    public static function destroy()
    {
        self::start();
        session_unset();
        session_destroy();
    }
    
    /**
     * Ανανεώνει το ID της συνεδρίας
     */
    public static function regenerate()
    {
        self::start();
        session_regenerate_id(true);
    }
    
    /**
     * Εμφανίζει πληροφορίες αποσφαλμάτωσης για τη συνεδρία
     */
    public static function debug()
    {
        self::start();
        echo "<h3>Session Debug</h3>";
        echo "<pre>";
        echo "Session ID: " . session_id() . "\n";
        echo "Session Name: " . session_name() . "\n";
        echo "Session Cookie: " . (isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : 'not set') . "\n";
        echo "Session Data: \n";
        print_r($_SESSION);
        echo "</pre>";
    }
}