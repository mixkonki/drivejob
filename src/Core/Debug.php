<?php
namespace Drivejob\Core;

class Debug {
    private static $logFile = null;
    
    public static function init($file = null) {
        if ($file === null) {
            self::$logFile = dirname(dirname(__DIR__)) . '/logs/debug.log';
        } else {
            self::$logFile = $file;
        }
        
        // Διαγραφή του παλιού αρχείου αν υπάρχει για να ξεκινήσουμε καθαρό
        if (file_exists(self::$logFile)) {
            unlink(self::$logFile);
        }
        
        // Δημιουργία του φακέλου αν δεν υπάρχει
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    public static function log($message, $data = null) {
        if (self::$logFile === null) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message";
        
        if ($data !== null) {
            $logEntry .= "\nDATA: " . print_r($data, true);
        }
        
        $logEntry .= "\n" . str_repeat('-', 80) . "\n";
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }
}