<?php

namespace Drivejob\Core;

class Session
{
    private static $started = false;
    private static $handler = null;
    private static $useDatabase = false;
    /**
     * Ορίζει έναν προσαρμοσμένο session handler
     */
    public static function setHandler($handler)
    {
        if (self::$started) {
            throw new \RuntimeException('Δεν μπορείτε να αλλάξετε τον session handler αφού έχει ξεκινήσει η συνεδρία.');
        }

        self::$handler = $handler;
        session_set_save_handler($handler, true);
    }

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
            // Έλεγχος για headers στο περιβάλλον testing
            if (defined('TESTING') && constant('TESTING') === true && headers_sent()) {
                self::$started = true;
                return true;
            }

            // Έλεγχος κανονικά για headers
            if (headers_sent($file, $line)) {
                throw new \RuntimeException("Headers already sent in $file at line $line");
            }

            // Ρυθμίσεις ασφαλείας για τις συνεδρίες
            session_name('DRIVEJOBSESSION');

            // Ρύθμιση των cookies που χρησιμοποιούνται για τις συνεδρίες
            session_set_cookie_params([
                'lifetime' => 86400, // 24 ώρες
                'path' => '/',
                'domain' => '',     // Άδειο σημαίνει το τρέχον domain
                'secure' => false,  // Θέστε το σε true σε παραγωγικό περιβάλλον με HTTPS
                'httponly' => true, // Προστασία από XSS
                'samesite' => 'Lax' // Αλλαγή σε 'Lax' για να αποφύγουμε προβλήματα με τα cookies
            ]);

            // Έναρξη συνεδρίας
            $result = session_start();
            self::$started = $result;

            // Καταγραφή για αποσφαλμάτωση
            Logger::debug('Session started', [
                'session_id' => session_id(),
                'result' => $result,
                'cookie_params' => session_get_cookie_params()
            ]);

            // Έλεγχος ασφάλειας συνεδρίας
            if ($result) {
                self::checkSessionSecurity();
            }

            return $result;
        }

        self::$started = true;
        return true;
    }

    /**
     * Έλεγχος ασφάλειας συνεδρίας (IP, User Agent)
     */
    private static function checkSessionSecurity()
    {
        // Έλεγχος αλλαγής IP (προαιρετικό, μπορεί να προκαλέσει προβλήματα με δυναμικές IP)
        if (isset($_SESSION['_user_ip']) && $_SESSION['_user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            // Ύποπτη αλλαγή IP - Καταγραφή για αποσφαλμάτωση και πιθανή αναγέννηση συνεδρίας
            Logger::warning("Suspicious session activity: IP change", [
                'old_ip' => $_SESSION['_user_ip'],
                'new_ip' => $_SERVER['REMOTE_ADDR'],
                'session_id' => session_id()
            ]);
            // self::regenerate(true); // Αν θέλετε πιο αυστηρό έλεγχο, ανανεώστε τη συνεδρία
        }

        // Έλεγχος αλλαγής User Agent
        if (isset($_SESSION['_user_agent']) && $_SESSION['_user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            // Ύποπτη αλλαγή User Agent - Καταγραφή και αναγέννηση συνεδρίας
            Logger::warning("Suspicious session activity: User Agent change", [
                'old_user_agent' => $_SESSION['_user_agent'],
                'new_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'session_id' => session_id()
            ]);
            // Απενεργοποίηση της αναγέννησης συνεδρίας για να αποφύγουμε προβλήματα με το CSRF token
            // self::regenerate(true);
        }

        // Αποθήκευση των τρεχόντων στοιχείων για μελλοντικούς ελέγχους
        $_SESSION['_user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['_last_activity'] = time();
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

            // Έλεγχος για headers στο περιβάλλον testing
            if ((!defined('TESTING') || constant('TESTING') !== true) && !headers_sent()) {
                // Διαγραφή του cookie συνεδρίας
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
                }
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
        $result = session_regenerate_id($deleteOldSession);

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('Session regenerated', [
            'old_session_id' => session_id(),
            'result' => $result,
            'delete_old_session' => $deleteOldSession
        ]);

        return $result;
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
     * Ελέγχει αν η συνεδρία έχει λήξει λόγω αδράνειας
     *
     * @param int $maxIdleTime Μέγιστος χρόνος αδράνειας σε δευτερόλεπτα
     * @return bool True αν η συνεδρία έχει λήξει, false διαφορετικά
     */
    public static function isExpired($maxIdleTime = 1800) // 30 λεπτά προεπιλογή
    {
        self::start();
        if (!isset($_SESSION['_last_activity'])) {
            $_SESSION['_last_activity'] = time();
            return false;
        }

        if ((time() - $_SESSION['_last_activity']) > $maxIdleTime) {
            return true;
        }

        $_SESSION['_last_activity'] = time();
        return false;
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
            'data' => $_SESSION,
            'using_database' => self::$useDatabase
        ];
    }
}
