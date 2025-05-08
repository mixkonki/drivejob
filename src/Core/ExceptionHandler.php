<?php

namespace Drivejob\Core;

use Drivejob\Core\Exceptions\BaseException;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;

/**
 * Κεντρικός χειριστής εξαιρέσεων
 */
class ExceptionHandler
{
    /**
     * @var ExceptionHandler Η μοναδική περίσταση του ExceptionHandler (Singleton pattern)
     */
    private static $instance = null;

    /**
     * @var bool Αν έχει καταχωρηθεί ο χειριστής εξαιρέσεων
     */
    private static $registered = false;

    /**
     * @var callable[] Οι καταχωρημένοι χειριστές εξαιρέσεων
     */
    private $handlers = [];

    /**
     * @var bool Αν είμαστε σε περιβάλλον ανάπτυξης
     */
    private $debug = false;

    /**
     * Ιδιωτικός constructor για αποτροπή δημιουργίας πολλαπλών περιστάσεων
     */
    private function __construct()
    {
        // Ορισμός του debug mode με βάση το περιβάλλον
        $this->debug = defined('ENVIRONMENT') && ENVIRONMENT === 'development';

        // Καταχώρηση των προεπιλεγμένων χειριστών εξαιρέσεων
        $this->registerDefaultHandlers();
    }

    /**
     * Επιστρέφει τη μοναδική περίσταση του ExceptionHandler
     *
     * @return ExceptionHandler
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Καταχωρεί τον χειριστή εξαιρέσεων στο PHP
     *
     * @return void
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }

        $handler = self::getInstance();

        // Καταχώρηση του χειριστή εξαιρέσεων
        set_exception_handler([$handler, 'handleException']);

        // Καταχώρηση του χειριστή σφαλμάτων
        set_error_handler([$handler, 'handleError']);

        // Καταχώρηση του χειριστή για τα σφάλματα που δεν έχουν αντιμετωπιστεί
        register_shutdown_function([$handler, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Καταχωρεί τους προεπιλεγμένους χειριστές εξαιρέσεων
     *
     * @return void
     */
    private function registerDefaultHandlers()
    {
        // Χειριστής για ValidationException
        $this->handlers[ValidationException::class] = function (ValidationException $e) {
            // Αποθήκευση των σφαλμάτων επικύρωσης στο session
            $e->storeErrorsInSession();

            // Καταγραφή του σφάλματος
            $e->log();

            // Ανακατεύθυνση πίσω με τα σφάλματα
            $this->redirectBack();
        };

        // Χειριστής για DatabaseException
        $this->handlers[DatabaseException::class] = function (DatabaseException $e) {
            // Καταγραφή του σφάλματος
            $e->log();

            // Εμφάνιση σελίδας σφάλματος
            $this->renderErrorPage(500, 'Σφάλμα Βάσης Δεδομένων', $e);
        };

        // Χειριστής για AuthException
        $this->handlers[AuthException::class] = function (AuthException $e) {
            // Καταγραφή του σφάλματος
            $e->log();

            // Ανάλογα με τον κωδικό σφάλματος, διαφορετική αντιμετώπιση
            switch ($e->getCode()) {
                case AuthException::ERROR_SESSION_EXPIRED:
                    // Καταστροφή της συνεδρίας
                    if (class_exists('\\Drivejob\\Core\\Session')) {
                        Session::destroy();
                    }

                    // Ανακατεύθυνση στη σελίδα σύνδεσης με μήνυμα
                    $this->redirectTo('/login.php', ['expired' => 1]);
                    break;

                case AuthException::ERROR_INSUFFICIENT_PERMISSIONS:
                    // Ανακατεύθυνση στη σελίδα άρνησης πρόσβασης
                    $this->redirectTo('/access-denied.php');
                    break;

                case AuthException::ERROR_ACCOUNT_NOT_VERIFIED:
                    // Ανακατεύθυνση στη σελίδα επαλήθευσης
                    $this->redirectTo('/verification-required.php');
                    break;

                default:
                    // Ανακατεύθυνση στη σελίδα σύνδεσης με μήνυμα σφάλματος
                    if (class_exists('\\Drivejob\\Core\\Session')) {
                        Session::set('login_error', $e->getMessage());
                    }

                    $this->redirectTo('/login.php');
                    break;
            }
        };

        // Γενικός χειριστής για BaseException
        $this->handlers[BaseException::class] = function (BaseException $e) {
            // Καταγραφή του σφάλματος
            $e->log();

            // Εμφάνιση σελίδας σφάλματος
            $this->renderErrorPage(500, 'Σφάλμα Εφαρμογής', $e);
        };

        // Γενικός χειριστής για όλες τις εξαιρέσεις
        $this->handlers[\Throwable::class] = function (\Throwable $e) {
            // Καταγραφή του σφάλματος
            if (class_exists('\\Drivejob\\Core\\Logger')) {
                Logger::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            } else {
                error_log($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }

            // Εμφάνιση σελίδας σφάλματος
            $this->renderErrorPage(500, 'Σφάλμα Συστήματος', $e);
        };
    }

    /**
     * Καταχωρεί έναν χειριστή εξαιρέσεων
     *
     * @param string $exceptionClass Η κλάση της εξαίρεσης
     * @param callable $handler Ο χειριστής
     * @return $this
     */
    public function registerHandler($exceptionClass, callable $handler)
    {
        $this->handlers[$exceptionClass] = $handler;
        return $this;
    }

    /**
     * Χειρίζεται μια εξαίρεση
     *
     * @param \Throwable $e Η εξαίρεση
     * @return void
     */
    public function handleException(\Throwable $e)
    {
        // Εύρεση του κατάλληλου χειριστή
        $handler = $this->findHandler($e);

        // Εκτέλεση του χειριστή
        call_user_func($handler, $e);
    }

    /**
     * Χειρίζεται ένα σφάλμα PHP
     *
     * @param int $level Το επίπεδο του σφάλματος
     * @param string $message Το μήνυμα του σφάλματος
     * @param string $file Το αρχείο που προκάλεσε το σφάλμα
     * @param int $line Η γραμμή που προκάλεσε το σφάλμα
     * @return bool Αν το σφάλμα έχει αντιμετωπιστεί
     */
    public function handleError($level, $message, $file, $line)
    {
        // Έλεγχος αν το σφάλμα πρέπει να αντιμετωπιστεί
        if (!(error_reporting() & $level)) {
            return false;
        }

        // Μετατροπή του σφάλματος σε εξαίρεση
        $e = new \ErrorException($message, 0, $level, $file, $line);

        // Χειρισμός της εξαίρεσης
        $this->handleException($e);

        return true;
    }

    /**
     * Χειρίζεται τα σφάλματα που δεν έχουν αντιμετωπιστεί
     *
     * @return void
     */
    public function handleShutdown()
    {
        // Λήψη του τελευταίου σφάλματος
        $error = error_get_last();

        // Έλεγχος αν υπάρχει σφάλμα
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Μετατροπή του σφάλματος σε εξαίρεση
            $e = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            // Χειρισμός της εξαίρεσης
            $this->handleException($e);
        }
    }

    /**
     * Βρίσκει τον κατάλληλο χειριστή για μια εξαίρεση
     *
     * @param \Throwable $e Η εξαίρεση
     * @return callable Ο χειριστής
     */
    private function findHandler(\Throwable $e)
    {
        // Έλεγχος για ακριβή ταίριασμα
        $class = get_class($e);
        if (isset($this->handlers[$class])) {
            return $this->handlers[$class];
        }

        // Έλεγχος για ταίριασμα με βάση την ιεραρχία κλάσεων
        foreach ($this->handlers as $exceptionClass => $handler) {
            if ($e instanceof $exceptionClass) {
                return $handler;
            }
        }

        // Επιστροφή του προεπιλεγμένου χειριστή
        return $this->handlers[\Throwable::class];
    }

    /**
     * Ανακατευθύνει πίσω στην προηγούμενη σελίδα
     *
     * @return void
     */
    private function redirectBack()
    {
        // Έλεγχος αν υπάρχει HTTP_REFERER
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if ($referer !== null) {
            header('Location: ' . $referer);
        } else {
            header('Location: ' . BASE_URL);
        }

        exit();
    }

    /**
     * Ανακατευθύνει σε μια συγκεκριμένη διεύθυνση
     *
     * @param string $url Η διεύθυνση
     * @param array $params Οι παράμετροι
     * @return void
     */
    private function redirectTo($url, array $params = [])
    {
        // Προσθήκη των παραμέτρων στη διεύθυνση
        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        // Ανακατεύθυνση
        header('Location: ' . BASE_URL . ltrim($url, '/'));
        exit();
    }

    /**
     * Εμφανίζει μια σελίδα σφάλματος
     *
     * @param int $code Ο κωδικός HTTP
     * @param string $title Ο τίτλος της σελίδας
     * @param \Throwable $e Η εξαίρεση
     * @return void
     */
    private function renderErrorPage($code, $title, \Throwable $e)
    {
        // Ορισμός του κωδικού HTTP
        http_response_code($code);

        // Έλεγχος αν το αίτημα είναι AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // Επιστροφή JSON απάντησης
            header('Content-Type: application/json');
            echo \json_encode([
                'error' => true,
                'code' => $code,
                'message' => $e->getMessage(),
                'details' => $this->debug ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => \explode("\n", $e->getTraceAsString())
                ] : null
            ]);
            exit();
        }

        // Έλεγχος αν υπάρχει προσαρμοσμένη σελίδα σφάλματος
        $errorPage = ROOT_DIR . '/src/Views/errors/' . $code . '.php';
        if (file_exists($errorPage)) {
            // Ορισμός των μεταβλητών για τη σελίδα σφάλματος
            $pageTitle = $title;
            $errorMessage = $e->getMessage();
            $errorDetails = $this->debug ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : null;

            // Συμπερίληψη της σελίδας σφάλματος
            include $errorPage;
            exit();
        }

        // Εμφάνιση προεπιλεγμένης σελίδας σφάλματος
        echo '<!DOCTYPE html>';
        echo '<html lang="el">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title) . '</title>';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }';
        echo '.error-container { max-width: 800px; margin: 0 auto; background-color: #f8f8f8; border: 1px solid #ddd; border-radius: 5px; padding: 20px; }';
        echo '.error-title { color: #d9534f; }';
        echo '.error-message { margin-bottom: 20px; }';
        echo '.error-details { background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; padding: 10px; font-family: monospace; white-space: pre-wrap; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="error-container">';
        echo '<h1 class="error-title">' . htmlspecialchars($title) . '</h1>';
        echo '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';

        if ($this->debug) {
            echo '<h2>Λεπτομέρειες Σφάλματος</h2>';
            echo '<div class="error-details">';
            echo 'Αρχείο: ' . htmlspecialchars($e->getFile()) . "\n";
            echo 'Γραμμή: ' . htmlspecialchars($e->getLine()) . "\n\n";
            echo 'Ίχνος Στοίβας:' . "\n";
            echo htmlspecialchars($e->getTraceAsString());
            echo '</div>';
        }

        echo '</div>';
        echo '</body>';
        echo '</html>';
        exit();
    }
}
