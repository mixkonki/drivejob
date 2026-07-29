<?php

namespace Drivejob\Core;

/**
 * Βασική κλάση Controller
 * 
 * Παρέχει βασικές λειτουργίες για όλους τους controllers
 */
class Controller
{
    /**
     * Φορτώνει και εμφανίζει ένα view
     *
     * @param string $view Το όνομα του view
     * @param array $data Δεδομένα που θα περαστούν στο view
     * @return void
     */
    protected function view($view, $data = [])
    {
        // Εξαγωγή των δεδομένων σε μεταβλητές
        extract($data);

        // Ορισμός του μονοπατιού του view
        $viewPath = ROOT_DIR . '/src/Views/' . $view . '.php';

        // Έλεγχος αν υπάρχει το view
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            // Σφάλμα αν δεν βρεθεί το view
            throw new \Exception("View {$view} not found");
        }
    }

    /**
     * Ανακατεύθυνση σε άλλο URL
     *
     * @param string $url Το URL για ανακατεύθυνση
     * @return void
     */
    protected function redirect($url)
    {
        if (strpos($url, 'http') !== 0) {
            // Αν δεν είναι πλήρες URL, προσθέτουμε το BASE_URL
            $url = BASE_URL . ltrim($url, '/');
        }

        header("Location: {$url}");
        exit;
    }

    /**
     * Επιστρέφει δεδομένα σε μορφή JSON
     *
     * @param mixed $data Τα δεδομένα για μετατροπή σε JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo \json_encode($data);
        exit;
    }

    /**
     * Ελέγχει αν η αίτηση είναι AJAX
     *
     * @return boolean
     */
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Ελέγχει αν η αίτηση είναι POST
     *
     * @return boolean
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Ελέγχει αν η αίτηση είναι GET
     *
     * @return boolean
     */
    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Επιστρέφει τα δεδομένα της αίτησης (GET ή POST)
     *
     * @param string $method Η μέθοδος (get ή post)
     * @return array
     */
    protected function getRequestData($method = null)
    {
        if ($method === 'post' || ($method === null && $this->isPost())) {
            return $_POST;
        }

        if ($method === 'get' || ($method === null && $this->isGet())) {
            return $_GET;
        }

        return [];
    }
}
