<?php
namespace Drivejob\Core;

/**
 * Κλάση για καταγραφή μηνυμάτων κατά την ανάπτυξη
 */
class Logger {
    /**
     * @var string $logDirectory Ο φάκελος για τα αρχεία καταγραφής
     */
    private static $logDirectory = 'logs';
    
    /**
     * @var bool $debugging Αν είναι ενεργοποιημένη η λειτουργία debugging
     */
    private static $debugging = true;
    
    /**
     * Καταγράφει ένα μήνυμα στο αρχείο καταγραφής
     * 
     * @param string $message Το μήνυμα προς καταγραφή
     * @param string $level Το επίπεδο του μηνύματος (info, warning, error, debug)
     * @param string $file Προαιρετικά ένα συγκεκριμένο αρχείο καταγραφής
     */
    public static function log($message, $level = 'info', $file = null) {
        if (!self::$debugging && $level == 'debug') {
            return; // Παραλείπουμε τα debug μηνύματα όταν δεν είναι ενεργή η λειτουργία debugging
        }
        
        // Δημιουργία του φακέλου καταγραφής αν δεν υπάρχει
        $logDir = ROOT_DIR . '/' . self::$logDirectory;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Καθορισμός του αρχείου καταγραφής
        $logFile = $file ? $file : 'application_' . date('Y-m-d') . '.log';
        $logPath = $logDir . '/' . $logFile;
        
        // Μορφοποίηση του μηνύματος
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Καταγραφή στο αρχείο
        file_put_contents($logPath, $formattedMessage, FILE_APPEND);
        
        // Επίσης καταγραφή στο error_log για εύκολη προβολή στο wamp
        error_log("[$level] $message");
    }
    
    /**
     * Καταγράφει ένα μήνυμα πληροφοριών
     * 
     * @param string $message Το μήνυμα
     * @param string $file Προαιρετικά ένα συγκεκριμένο αρχείο καταγραφής
     */
    public static function info($message, $file = null) {
        self::log($message, 'info', $file);
    }
    
    /**
     * Καταγράφει ένα μήνυμα προειδοποίησης
     * 
     * @param string $message Το μήνυμα
     * @param string $file Προαιρετικά ένα συγκεκριμένο αρχείο καταγραφής
     */
    public static function warning($message, $file = null) {
        self::log($message, 'warning', $file);
    }
    
    /**
     * Καταγράφει ένα μήνυμα σφάλματος
     * 
     * @param string $message Το μήνυμα
     * @param string $file Προαιρετικά ένα συγκεκριμένο αρχείο καταγραφής
     */
    public static function error($message, $file = null) {
        self::log($message, 'error', $file);
    }
    
    /**
     * Καταγράφει ένα μήνυμα debugging
     * 
     * @param string $message Το μήνυμα
     * @param string $file Προαιρετικά ένα συγκεκριμένο αρχείο καταγραφής
     */
    public static function debug($message, $file = null) {
        self::log($message, 'debug', $file);
    }
    
    /**
     * Ενεργοποιεί ή απενεργοποιεί τη λειτουργία debugging
     * 
     * @param bool $enabled Αν είναι ενεργοποιημένη η λειτουργία debugging
     */
    public static function setDebugging($enabled) {
        self::$debugging = $enabled;
    }
    
    /**
     * Καθορίζει τον φάκελο για τα αρχεία καταγραφής
     * 
     * @param string $directory Ο φάκελος για τα αρχεία καταγραφής
     */
    public static function setLogDirectory($directory) {
        self::$logDirectory = $directory;
    }
}