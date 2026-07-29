<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Core\Logger;
use Drivejob\Core\CSRF;
use Drivejob\Core\Container;
use Drivejob\Helpers\JsonHelper;

/**
 * Βασική κλάση για όλους τους controllers
 * 
 * Παρέχει κοινές λειτουργίες και βοηθητικές μεθόδους
 * για όλους τους επιμέρους controllers της εφαρμογής
 */
class BaseController
{
    /**
     * Το container για τις εξαρτήσεις
     *
     * @var Container
     */
    protected $container;

    /**
     * Η σύνδεση PDO με τη βάση δεδομένων
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Λήψη του container
        $this->container = Container::getInstance();
        
        // Λήψη της σύνδεσης PDO από το container
        if ($this->container->has('pdo')) {
            $this->pdo = $this->container->get('pdo');
        }
    }

    /**
     * Φορτώνει ένα view με τα δεδομένα που παρέχονται
     *
     * @param string $view Το όνομα του view
     * @param array $data Τα δεδομένα που θα περάσουν στο view
     * @return void
     */
    protected function view(string $view, array $data = [])
    {
        // Εξαγωγή των δεδομένων σε μεταβλητές
        extract($data);

        // Έλεγχος αν το view αρχείο υπάρχει
        $viewFile = ROOT_DIR . '/src/Views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            Logger::error('View not found: ' . $viewFile);
            echo "Το view δεν βρέθηκε. Επικοινωνήστε με τον διαχειριστή.";
        }
    }

    /**
     * Ανακατευθύνει σε μια διαδρομή URL
     *
     * @param string $url Η διαδρομή URL
     * @return void
     */
    protected function redirect(string $url)
    {
        header('Location: ' . $url);
        exit();
    }

    /**
     * Ανακατευθύνει στη βασική διαδρομή URL με ένα μήνυμα
     *
     * @param string $url Η διαδρομή URL
     * @param string $message Το μήνυμα
     * @param string $type Ο τύπος του μηνύματος (success, error, info, warning)
     * @return void
     */
    protected function redirectWithMessage(string $url, string $message, string $type = 'success')
    {
        // Αποθήκευση του μηνύματος στο session
        switch ($type) {
            case 'error':
                Session::set('error_message', $message);
                break;
            case 'info':
                Session::set('info_message', $message);
                break;
            case 'warning':
                Session::set('warning_message', $message);
                break;
            default:
                Session::set('success_message', $message);
                break;
        }

        // Ανακατεύθυνση
        $this->redirect($url);
    }

    /**
     * Ελέγχει αν η αίτηση είναι AJAX
     *
     * @return bool
     */
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Επιστρέφει μια απάντηση JSON
     *
     * @param mixed $data Τα δεδομένα προς επιστροφή
     * @param int $statusCode Ο κωδικός κατάστασης HTTP
     * @return void
     */
    protected function jsonResponse($data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo JsonHelper::encode($data);
        exit();
    }

    /**
     * Επιστρέφει μια απάντηση επιτυχίας JSON
     *
     * @param mixed $data Τα δεδομένα προς επιστροφή
     * @param string $message Το μήνυμα επιτυχίας
     * @param int $statusCode Ο κωδικός κατάστασης HTTP
     * @return void
     */
    protected function jsonSuccess($data = null, string $message = 'Επιτυχία', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Επιστρέφει μια απάντηση σφάλματος JSON
     *
     * @param string $message Το μήνυμα σφάλματος
     * @param mixed $errors Τα σφάλματα
     * @param int $statusCode Ο κωδικός κατάστασης HTTP
     * @return void
     */
    protected function jsonError(string $message = 'Σφάλμα', $errors = null, int $statusCode = 400)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Επικυρώνει ένα CSRF token
     *
     * @param string $token Το CSRF token
     * @param string $redirectUrl Η διαδρομή URL για ανακατεύθυνση σε περίπτωση αποτυχίας
     * @return bool
     */
    protected function validateCsrfToken(string $token, ?string $redirectUrl = null)
    {
        if (!CSRF::validateToken($token)) {
            Logger::error('CSRF token validation failed');
            
            if ($this->isAjax()) {
                $this->jsonError('Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.', null, 403);
            }
            
            if ($redirectUrl) {
                $this->redirectWithMessage($redirectUrl, 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.', 'error');
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Καθαρίζει μια τιμή εισόδου
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return string|null Η καθαρισμένη τιμή
     */
    protected function sanitize($input)
    {
        if ($input === null) {
            return null;
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Καθαρίζει HTML
     * 
     * @param string|null $input Η τιμή εισόδου
     * @param string $allowedTags Τα επιτρεπόμενα HTML tags
     * @return string|null Η καθαρισμένη τιμή
     */
    protected function sanitizeHtml($input, $allowedTags = '<p><br><strong><em><ul><ol><li><h2><h3><h4>')
    {
        if ($input === null) {
            return null;
        }
        return strip_tags(trim($input), $allowedTags);
    }

    /**
     * Καθαρίζει έναν ακέραιο
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return int|null Η καθαρισμένη τιμή
     */
    protected function sanitizeInt($input)
    {
        if ($input === null || $input === '') {
            return null;
        }
        return (int)$input;
    }

    /**
     * Καθαρίζει έναν αριθμό κινητής υποδιαστολής
     * 
     * @param string|null $input Η τιμή εισόδου
     * @return float|null Η καθαρισμένη τιμή
     */
    protected function sanitizeFloat($input)
    {
        if ($input === null || $input === '') {
            return null;
        }
        return (float)$input;
    }

    /**
     * Καθαρίζει ένα email
     * 
     * @param string|null $email Το email
     * @return string|null Το καθαρισμένο email
     */
    protected function sanitizeEmail($email)
    {
        if (empty($email)) {
            return null;
        }
        $sanitizedEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL)) {
            return $sanitizedEmail;
        }
        return null;
    }

    /**
     * Καθαρίζει μια ημερομηνία
     * 
     * @param string|null $date Η ημερομηνία
     * @param string $format Η μορφή της ημερομηνίας
     * @return string|null Η καθαρισμένη ημερομηνία
     */
    protected function sanitizeDate($date, $format = 'Y-m-d')
    {
        if ($date === null || empty($date)) {
            return null;
        }
        $dateObj = \DateTime::createFromFormat($format, $date);
        if ($dateObj && $dateObj->format($format) === $date) {
            return $date;
        }
        return null;
    }
}
