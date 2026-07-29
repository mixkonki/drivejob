<?php

namespace Drivejob\Core;

use Drivejob\Helpers\JsonHelper;

/**
 * Κλάση για την καταγραφή μηνυμάτων
 */
class Logger
{
    /**
     * Επίπεδα καταγραφής
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';

    /**
     * Το ελάχιστο επίπεδο καταγραφής
     *
     * @var string
     */
    private static $minLevel = self::DEBUG;

    /**
     * Το αρχείο καταγραφής
     *
     * @var string
     */
    private static $logFile = null;

    /**
     * Αρχικοποίηση του logger
     *
     * @param string $logFile Το αρχείο καταγραφής
     * @param string $minLevel Το ελάχιστο επίπεδο καταγραφής
     * @return void
     */
    public static function init($logFile = null, $minLevel = self::DEBUG)
    {
        self::$logFile = $logFile ?: ROOT_DIR . '/logs/app.log';
        self::$minLevel = $minLevel;

        // Δημιουργία του καταλόγου logs αν δεν υπάρχει
        $logsDir = dirname(self::$logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
    }

    /**
     * Καταγραφή μηνύματος debug
     *
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return void
     */
    public static function debug($message, array $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Καταγραφή μηνύματος info
     *
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return void
     */
    public static function info($message, array $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Καταγραφή μηνύματος warning
     *
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return void
     */
    public static function warning($message, array $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Καταγραφή μηνύματος error
     *
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return void
     */
    public static function error($message, array $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Καταγραφή μηνύματος critical
     *
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return void
     */
    public static function critical($message, array $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Καταγραφή μηνύματος
     *
     * @param string $level Το επίπεδο καταγραφής
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return void
     */
    private static function log($level, $message, array $context = [])
    {
        // Έλεγχος αν το επίπεδο είναι μεγαλύτερο ή ίσο με το ελάχιστο
        if (!self::shouldLog($level)) {
            return;
        }

        // Αρχικοποίηση του logger αν δεν έχει αρχικοποιηθεί
        if (self::$logFile === null) {
            self::init();
        }

        // Μορφοποίηση του μηνύματος
        $logMessage = self::formatMessage($level, $message, $context);

        // Καταγραφή του μηνύματος
        self::writeLog($logMessage);
    }

    /**
     * Έλεγχος αν το επίπεδο είναι μεγαλύτερο ή ίσο με το ελάχιστο
     *
     * @param string $level Το επίπεδο καταγραφής
     * @return bool Αν το επίπεδο είναι μεγαλύτερο ή ίσο με το ελάχιστο
     */
    private static function shouldLog($level)
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        ];

        return $levels[$level] >= $levels[self::$minLevel];
    }

    /**
     * Μορφοποίηση του μηνύματος
     *
     * @param string $level Το επίπεδο καταγραφής
     * @param string $message Το μήνυμα
     * @param array $context Επιπλέον context
     * @return string Το μορφοποιημένο μήνυμα
     */
    private static function formatMessage($level, $message, array $context = [])
    {
        // Μορφοποίηση του μηνύματος
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] [$level] $message";

        // Προσθήκη του context
        if (!empty($context)) {
            $logMessage .= ' ' . JsonHelper::encode($context);
        }

        return $logMessage;
    }

    /**
     * Καταγραφή του μηνύματος στο αρχείο
     *
     * @param string $message Το μήνυμα
     * @return void
     */
    private static function writeLog($message)
    {
        // Προσθήκη του μηνύματος στο αρχείο
        file_put_contents(self::$logFile, $message . PHP_EOL, FILE_APPEND);
    }
}
