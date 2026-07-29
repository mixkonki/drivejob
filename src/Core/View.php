<?php

namespace Drivejob\Core;

/**
 * Βασική κλάση View
 * 
 * Παρέχει βασικές λειτουργίες για όλα τα views
 */
class View
{
    /**
     * Φορτώνει και εμφανίζει ένα view
     *
     * @param string $view Το όνομα του view
     * @param array $data Δεδομένα που θα περαστούν στο view
     * @return void
     */
    public static function render($view, $data = [])
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
     * Φορτώνει ένα view και επιστρέφει το περιεχόμενό του ως string
     *
     * @param string $view Το όνομα του view
     * @param array $data Δεδομένα που θα περαστούν στο view
     * @return string
     */
    public static function renderToString($view, $data = [])
    {
        ob_start();
        self::render($view, $data);
        return ob_get_clean();
    }

    /**
     * Φορτώνει ένα partial view
     *
     * @param string $partial Το όνομα του partial view
     * @param array $data Δεδομένα που θα περαστούν στο partial
     * @return void
     */
    public static function partial($partial, $data = [])
    {
        // Ορισμός του μονοπατιού του partial
        $partialPath = ROOT_DIR . '/src/Views/partials/' . $partial . '.php';

        // Εξαγωγή των δεδομένων σε μεταβλητές
        extract($data);

        // Έλεγχος αν υπάρχει το partial
        if (file_exists($partialPath)) {
            require $partialPath;
        } else {
            // Σφάλμα αν δεν βρεθεί το partial
            throw new \Exception("Partial view {$partial} not found");
        }
    }

    /**
     * Δημιουργεί ένα URL για ένα asset
     *
     * @param string $path Το μονοπάτι του asset
     * @return string
     */
    public static function asset($path)
    {
        return BASE_URL . ltrim($path, '/');
    }

    /**
     * Δημιουργεί ένα URL για μια διαδρομή
     *
     * @param string $path Το μονοπάτι της διαδρομής
     * @return string
     */
    public static function url($path)
    {
        return BASE_URL . ltrim($path, '/');
    }

    /**
     * Διαφυγή HTML για ασφαλή εμφάνιση
     *
     * @param string $string Το string προς διαφυγή
     * @return string
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Μορφοποίηση ημερομηνίας
     *
     * @param string $date Η ημερομηνία προς μορφοποίηση
     * @param string $format Η μορφή της ημερομηνίας
     * @return string
     */
    public static function formatDate($date, $format = 'd/m/Y')
    {
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }

    /**
     * Περικοπή κειμένου
     *
     * @param string $text Το κείμενο προς περικοπή
     * @param int $length Το μέγιστο μήκος
     * @param string $append Το string που θα προστεθεί στο τέλος
     * @return string
     */
    public static function truncate($text, $length = 100, $append = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));

        return $text . $append;
    }

    /**
     * Μετατροπή του πρώτου γράμματος σε κεφαλαίο
     *
     * @param string $string Το string προς μετατροπή
     * @return string
     */
    public static function capitalize($string)
    {
        return ucfirst($string);
    }

    /**
     * Μετατροπή του πρώτου γράμματος κάθε λέξης σε κεφαλαίο
     *
     * @param string $string Το string προς μετατροπή
     * @return string
     */
    public static function titleCase($string)
    {
        return ucwords($string);
    }
}
