<?php
namespace Drivejob\Core;

/**
 * Κλάση App
 * 
 * Διαχειρίζεται την εφαρμογή και βασικές λειτουργίες αυτής
 */
class App
{
    /** @var PDO Σύνδεση με τη βάση δεδομένων */
    private static $db;
    
    /** @var array Ρυθμίσεις εφαρμογής */
    private static $config;
    
    /**
     * Αρχικοποίηση της εφαρμογής
     */
    public static function init()
    {
        // Εκκίνηση του session αν δεν είναι ήδη ενεργό
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Φόρτωση των ρυθμίσεων
        self::loadConfig();
        
        // Σύνδεση με τη βάση δεδομένων
        self::connectDB();
        
        // Ορισμός του error handler
        self::setErrorHandler();
    }
    
    /**
     * Φόρτωση ρυθμίσεων από το αρχείο config
     */
    private static function loadConfig()
    {
        // Το config.php θα οριστεί από το index.php
    }
    
    /**
     * Σύνδεση με τη βάση δεδομένων
     */
    private static function connectDB()
    {
        if (isset(self::$config['db'])) {
            try {
                $dsn = 'mysql:host=' . self::$config['db']['host'] . ';dbname=' . self::$config['db']['name'] . ';charset=utf8mb4';
                $options = [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$db = new \PDO($dsn, self::$config['db']['user'], self::$config['db']['pass'], $options);
            } catch (\PDOException $e) {
                // Καταγραφή του σφάλματος και εμφάνιση μηνύματος φιλικού προς τον χρήστη
                error_log('Σφάλμα σύνδεσης με τη βάση δεδομένων: ' . $e->getMessage());
                die('Προέκυψε ένα σφάλμα κατά την επικοινωνία με τη βάση δεδομένων. Παρακαλούμε δοκιμάστε αργότερα.');
            }
        }
    }
    
    /**
     * Ορισμός του error handler
     */
    private static function setErrorHandler()
    {
        // Εδώ μπορεί να οριστεί ένας custom error handler
    }
    
    /**
     * Ορισμός των ρυθμίσεων
     * 
     * @param array $config Οι ρυθμίσεις
     */
    public static function setConfig($config)
    {
        self::$config = $config;
    }
    
    /**
     * Λήψη των ρυθμίσεων
     * 
     * @return array
     */
    public static function getConfig()
    {
        return self::$config;
    }
    
    /**
     * Λήψη της σύνδεσης με τη βάση δεδομένων
     * 
     * @return PDO
     */
    public static function getDB()
    {
        return self::$db;
    }
}