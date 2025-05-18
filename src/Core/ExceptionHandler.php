<?php

namespace Drivejob\Core;

use Drivejob\Core\Exceptions\BaseException;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Core\Exceptions\NotFoundException;
use Drivejob\Core\Exceptions\ForbiddenException;
use Drivejob\Core\Exceptions\BadRequestException;
use Drivejob\Core\Exceptions\ServerErrorException;
use Drivejob\Helpers\JsonHelper;

/**
 * Κεντρικός χειριστής εξαιρέσεων της εφαρμογής
 * 
 * Διαχειρίζεται όλες τις εξαιρέσεις της εφαρμογής και τις μετατρέπει
 * σε κατάλληλες απαντήσεις HTTP.
 */
class ExceptionHandler
{
    /**
     * @var bool Αν είμαστε σε περιβάλλον ανάπτυξης
     */
    protected $debug;

    /**
     * @var array Οι προεπιλεγμένες ρυθμίσεις
     */
    protected $defaultSettings = [
        'debug' => false,
        'log_exceptions' => true,
        'display_errors' => false,
        'error_view' => 'error',
        'error_layout' => 'layout',
        'error_views_path' => '/src/Views/errors/',
        'default_error_message' => 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά αργότερα.'
    ];

    /**
     * @var array Οι ρυθμίσεις του χειριστή
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param array $settings Οι ρυθμίσεις του χειριστή
     */
    public function __construct(array $settings = [])
    {
        $this->settings = array_merge($this->defaultSettings, $settings);
        $this->debug = $this->settings['debug'];
    }

    /**
     * Καταγράφει μια εξαίρεση
     *
     * @param \Throwable $exception Η εξαίρεση
     * @return void
     */
    public function logException(\Throwable $exception)
    {
        if (!$this->settings['log_exceptions']) {
            return;
        }

        if ($exception instanceof BaseException) {
            $exception->log();
        } else {
            Logger::error($exception->getMessage(), [
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση
     *
     * @param \Throwable $exception Η εξαίρεση
     * @return void
     */
    public function handle(\Throwable $exception)
    {
        // Καταγραφή της εξαίρεσης
        $this->logException($exception);

        // Έλεγχος αν είναι AJAX αίτημα
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Διαχείριση ανάλογα με τον τύπο της εξαίρεσης
        if ($exception instanceof ValidationException) {
            $this->handleValidationException($exception, $isAjax);
        } elseif ($exception instanceof AuthException) {
            $this->handleAuthException($exception, $isAjax);
        } elseif ($exception instanceof DatabaseException) {
            $this->handleDatabaseException($exception, $isAjax);
        } elseif ($exception instanceof NotFoundException) {
            $this->handleNotFoundException($exception, $isAjax);
        } elseif ($exception instanceof ForbiddenException) {
            $this->handleForbiddenException($exception, $isAjax);
        } elseif ($exception instanceof BadRequestException) {
            $this->handleBadRequestException($exception, $isAjax);
        } elseif ($exception instanceof ServerErrorException) {
            $this->handleServerErrorException($exception, $isAjax);
        } else {
            $this->handleGenericException($exception, $isAjax);
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση επικύρωσης
     *
     * @param ValidationException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleValidationException(ValidationException $exception, $isAjax)
    {
        // Αποθήκευση των σφαλμάτων στο session
        $exception->storeErrorsInSession();

        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($exception->getMessage(), [
                'errors' => $exception->getErrors()
            ], 422);
        } else {
            // Ανακατεύθυνση πίσω με τα σφάλματα
            $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
            header('Location: ' . $referer);
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση αυθεντικοποίησης
     *
     * @param AuthException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleAuthException(AuthException $exception, $isAjax)
    {
        // Αποθήκευση του μηνύματος σφάλματος στο session
        Session::set('error_message', $exception->getMessage());

        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($exception->getMessage(), [
                'code' => $exception->getCode()
            ], 401);
        } else {
            // Ανακατεύθυνση στη σελίδα σύνδεσης
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση βάσης δεδομένων
     *
     * @param DatabaseException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleDatabaseException(DatabaseException $exception, $isAjax)
    {
        // Δημιουργία μηνύματος σφάλματος
        $message = $this->debug ? $exception->getMessage() : $this->settings['default_error_message'];

        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($message, [], 500);
        } else {
            // Αποθήκευση του μηνύματος σφάλματος στο session
            Session::set('error_message', $message);

            // Ανακατεύθυνση στην αρχική σελίδα
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση μη εύρεσης πόρου
     *
     * @param NotFoundException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleNotFoundException(NotFoundException $exception, $isAjax)
    {
        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($exception->getMessage(), [], 404);
        } else {
            // Εμφάνιση της σελίδας 404
            http_response_code(404);
            $this->renderErrorView('404', [
                'message' => $exception->getMessage(),
                'exception' => $exception
            ]);
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση απαγορευμένης πρόσβασης
     *
     * @param ForbiddenException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleForbiddenException(ForbiddenException $exception, $isAjax)
    {
        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($exception->getMessage(), [], 403);
        } else {
            // Εμφάνιση της σελίδας 403
            http_response_code(403);
            $this->renderErrorView('403', [
                'message' => $exception->getMessage(),
                'exception' => $exception
            ]);
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση μη έγκυρου αιτήματος
     *
     * @param BadRequestException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleBadRequestException(BadRequestException $exception, $isAjax)
    {
        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($exception->getMessage(), [], 400);
        } else {
            // Αποθήκευση του μηνύματος σφάλματος στο session
            Session::set('error_message', $exception->getMessage());

            // Ανακατεύθυνση στην αρχική σελίδα
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια εξαίρεση σφάλματος διακομιστή
     *
     * @param ServerErrorException $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleServerErrorException(ServerErrorException $exception, $isAjax)
    {
        // Δημιουργία μηνύματος σφάλματος
        $message = $this->debug ? $exception->getMessage() : $this->settings['default_error_message'];

        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($message, [], 500);
        } else {
            // Εμφάνιση της σελίδας 500
            http_response_code(500);
            $this->renderErrorView('500', [
                'message' => $message,
                'exception' => $exception
            ]);
            exit;
        }
    }

    /**
     * Διαχειρίζεται μια γενική εξαίρεση
     *
     * @param \Throwable $exception Η εξαίρεση
     * @param bool $isAjax Αν είναι AJAX αίτημα
     * @return void
     */
    protected function handleGenericException(\Throwable $exception, $isAjax)
    {
        // Δημιουργία μηνύματος σφάλματος
        $message = $this->debug ? $exception->getMessage() : $this->settings['default_error_message'];

        if ($isAjax) {
            // Επιστροφή JSON απάντησης
            JsonHelper::error($message, [], 500);
        } else {
            // Εμφάνιση της σελίδας 500
            http_response_code(500);
            $this->renderErrorView('500', [
                'message' => $message,
                'exception' => $exception
            ]);
            exit;
        }
    }

    /**
     * Εμφανίζει ένα view σφάλματος
     *
     * @param string $view Το όνομα του view
     * @param array $data Τα δεδομένα για το view
     * @return void
     */
    protected function renderErrorView($view, array $data = [])
    {
        // Έλεγχος αν υπάρχει το view
        $viewPath = ROOT_DIR . $this->settings['error_views_path'] . $view . '.php';
        if (!file_exists($viewPath)) {
            $viewPath = ROOT_DIR . $this->settings['error_views_path'] . 'error.php';
        }

        // Εξαγωγή των δεδομένων
        extract($data);

        // Έναρξη του output buffering
        ob_start();

        // Συμπερίληψη του view
        include $viewPath;

        // Λήψη του περιεχομένου του view
        $content = ob_get_clean();

        // Έλεγχος αν υπάρχει layout
        $layoutPath = ROOT_DIR . '/src/Views/' . $this->settings['error_layout'] . '.php';
        if (file_exists($layoutPath)) {
            // Συμπερίληψη του layout
            include $layoutPath;
        } else {
            // Εμφάνιση του περιεχομένου του view
            echo $content;
        }
    }

    /**
     * Καταχωρεί τον χειριστή εξαιρέσεων
     *
     * @return void
     */
    public function register()
    {
        // Καταχώρηση του χειριστή εξαιρέσεων
        set_exception_handler([$this, 'handle']);

        // Καταχώρηση του χειριστή σφαλμάτων
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                // Αυτό το σφάλμα δεν καταγράφεται στο error_reporting
                return;
            }

            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        // Καταχώρηση του χειριστή για τα σφάλματα που δεν έχουν αντιμετωπιστεί
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $this->handle(new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                ));
            }
        });
    }
}
