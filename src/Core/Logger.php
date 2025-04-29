<?php
namespace Drivejob\Core;


/**
 * Κλάση Logger για καταγραφή συμβάντων και αποσφαλμάτωση
 */
class Logger {
    /**
     * @var string $logFile Διαδρομή αρχείου καταγραφής
     */
    private static $logFile = null;
    
    /**
     * @var bool $initialized Αν έχει αρχικοποιηθεί ο logger
     */
    private static $initialized = false;
    
    /**
     * @var string $defaultLogLevel Προεπιλεγμένο επίπεδο καταγραφής
     */
    private static $defaultLogLevel = 'info';
    
    /**
     * @var array $logLevels Επίπεδα καταγραφής
     */
    private static $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];
    
    /**
     * Αρχικοποίηση του logger
     * 
     * @param string $logFile Διαδρομή αρχείου καταγραφής
     * @param string $defaultLevel Προεπιλεγμένο επίπεδο καταγραφής
     * @return void
     */
    public static function init($logFile = null, $defaultLevel = 'info') {
        if (self::$initialized) {
            return;
        }
        
        // Αν δεν καθοριστεί αρχείο καταγραφής, χρήση προεπιλεγμένου
        if ($logFile === null) {
            $baseDir = dirname(dirname(dirname(__FILE__))); // ROOT_DIR
            $logDir = $baseDir . '/logs';
            
            // Δημιουργία καταλόγου logs αν δεν υπάρχει
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            self::$logFile = $logDir . '/application_' . date('Y-m-d') . '.log';
        } else {
            self::$logFile = $logFile;
        }
        
        // Έλεγχος εγκυρότητας επιπέδου καταγραφής
        if (isset(self::$logLevels[$defaultLevel])) {
            self::$defaultLogLevel = $defaultLevel;
        }
        
        self::$initialized = true;
    }
    
    /**
     * Καταγραφή μηνύματος
     * 
     * @param string $message Μήνυμα για καταγραφή
     * @param string $level Επίπεδο καταγραφής
     * @param string $context Πλαίσιο καταγραφής
     * @return void
     */
    public static function log($message, $level = null, $context = '') {
        if (!self::$initialized) {
            self::init();
        }
        
        // Αν δεν καθοριστεί επίπεδο, χρήση προεπιλεγμένου
        if ($level === null || !isset(self::$logLevels[$level])) {
            $level = self::$defaultLogLevel;
        }
        
        // Μορφοποίηση του μηνύματος με τα πλήρη στοιχεία
        $timestamp = date('Y-m-d H:i:s');
        $formattedContext = $context ? "[$context]" : '';
        $formattedLevel = strtoupper($level);
        
        // Αν το μήνυμα είναι αντικείμενο ή πίνακας, μορφοποίηση σε JSON
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        $logLine = "[$timestamp] $formattedLevel $formattedContext: $message" . PHP_EOL;
        
        // Καταγραφή στο αρχείο
        file_put_contents(self::$logFile, $logLine, FILE_APPEND);
        
        // Αν είναι σε περιβάλλον ανάπτυξης, καταγραφή και στο error_log
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            error_log($logLine);
        }
    }
    
    /**
     * Καταγραφή μηνύματος επιπέδου Debug
     * 
     * @param string $message Μήνυμα
     * @param string $context Πλαίσιο
     */
    public static function debug($message, $context = '') {
        self::log($message, 'debug', $context);
    }
    
    /**
     * Καταγραφή μηνύματος επιπέδου Info
     * 
     * @param string $message Μήνυμα
     * @param string $context Πλαίσιο
     */
    public static function info($message, $context = '') {
        self::log($message, 'info', $context);
    }
    
    /**
     * Καταγραφή μηνύματος επιπέδου Warning
     * 
     * @param string $message Μήνυμα
     * @param string $context Πλαίσιο
     */
    public static function warning($message, $context = '') {
        self::log($message, 'warning', $context);
    }
    
    /**
     * Καταγραφή μηνύματος επιπέδου Error
     * 
     * @param string $message Μήνυμα
     * @param string $context Πλαίσιο
     */
    public static function error($message, $context = '') {
        self::log($message, 'error', $context);
    }
    
    /**
     * Καταγραφή μηνύματος επιπέδου Critical
     * 
     * @param string $message Μήνυμα
     * @param string $context Πλαίσιο
     */
    public static function critical($message, $context = '') {
        self::log($message, 'critical', $context);
    }
}