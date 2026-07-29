<?php

// src/helpers.php

if (!function_exists('old')) {
    /**
     * Επιστρέφει την παλιά τιμή ενός πεδίου από τα δεδομένα της φόρμας
     *
     * @param string $key Το όνομα του πεδίου
     * @param mixed $default Η προεπιλεγμένη τιμή αν δεν υπάρχει
     * @return mixed Η παλιά τιμή του πεδίου
     */
    function old($key, $default = null)
    {
        // Έλεγχος για PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Αν υπάρχει $_SESSION['old_input']
        if (isset($_SESSION['old_input'][$key])) {
            return $_SESSION['old_input'][$key];
        }

        // Αν υπάρχει $_POST
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        // Αν τίποτα άλλο δεν λειτουργεί, επιστροφή της προεπιλεγμένης τιμής
        return $default;
    }
}

if (!function_exists('e')) {
    /**
     * Κωδικοποιεί χαρακτήρες HTML για ασφάλεια
     *
     * @param string $value Η τιμή προς κωδικοποίηση
     * @return string Η κωδικοποιημένη τιμή
     */
    function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Δημιουργεί ένα hidden input με CSRF token
     *
     * @return string Το HTML για το CSRF token field
     */
    function csrf_field()
    {
        if (class_exists('Drivejob\Core\CSRF')) {
            return Drivejob\Core\CSRF::tokenField();
        }
        return '';
    }
}

if (!function_exists('set_old_input')) {
    /**
     * Αποθηκεύει τα δεδομένα της φόρμας στο session για χρήση με την old()
     *
     * @param array $data Τα δεδομένα προς αποθήκευση
     */
    function set_old_input($data)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['old_input'] = $data;
    }
}

if (!function_exists('clear_old_input')) {
    /**
     * Καθαρίζει τα αποθηκευμένα δεδομένα της φόρμας
     */
    function clear_old_input()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['old_input']);
    }
}
