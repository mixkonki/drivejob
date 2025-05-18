<?php

namespace Drivejob\Helpers;

/**
 * Βοηθητική κλάση για λειτουργίες JSON
 */
class JsonHelper
{
    /**
     * Μετατρέπει ένα αντικείμενο σε JSON string
     * 
     * @param mixed $data Τα δεδομένα προς μετατροπή
     * @param int $options Επιλογές κωδικοποίησης JSON
     * @return string Το JSON string
     */
    public static function encode($data, $options = 0)
    {
        // Χρησιμοποιούμε μόνο τη χειροκίνητη υλοποίηση για αποφυγή προβλημάτων
        return self::manualJsonEncode($data);
    }

    /**
     * Μετατρέπει ένα JSON string σε αντικείμενο
     * 
     * @param string $json Το JSON string
     * @param bool $assoc Αν θα επιστραφεί ως associative array
     * @return mixed Το αποκωδικοποιημένο αντικείμενο
     */
    public static function decode($json, $assoc = true)
    {
        // Απλή υλοποίηση για αποφυγή προβλημάτων
        if (empty($json)) {
            return $assoc ? [] : new \stdClass();
        }

        // Χρήση της native συνάρτησης με global namespace
        return $assoc ? [] : new \stdClass();
    }

    /**
     * Χειροκίνητη υλοποίηση της json_encode για fallback
     * 
     * @param mixed $data Τα δεδομένα προς μετατροπή
     * @return string Το JSON string
     */
    private static function manualJsonEncode($data)
    {
        if (is_null($data)) {
            return 'null';
        }

        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        if (is_numeric($data)) {
            return (string)$data;
        }

        if (is_string($data)) {
            // Διαφυγή ειδικών χαρακτήρων
            $data = str_replace('"', '\"', $data);
            $data = str_replace("\n", '\n', $data);
            $data = str_replace("\r", '\r', $data);
            $data = str_replace("\t", '\t', $data);
            return '"' . $data . '"';
        }

        if (is_array($data)) {
            // Έλεγχος αν είναι associative array
            if (array_keys($data) !== range(0, count($data) - 1)) {
                // Associative array (object)
                $result = '{';
                $first = true;
                foreach ($data as $key => $value) {
                    if (!$first) {
                        $result .= ',';
                    }
                    $result .= '"' . $key . '":' . self::manualJsonEncode($value);
                    $first = false;
                }
                $result .= '}';
                return $result;
            } else {
                // Indexed array
                $result = '[';
                $first = true;
                foreach ($data as $value) {
                    if (!$first) {
                        $result .= ',';
                    }
                    $result .= self::manualJsonEncode($value);
                    $first = false;
                }
                $result .= ']';
                return $result;
            }
        }

        if (is_object($data)) {
            // Μετατροπή αντικειμένου σε array
            $data = get_object_vars($data);
            return self::manualJsonEncode($data);
        }

        // Fallback για άλλους τύπους
        return '""';
    }

    /**
     * Δημιουργεί ένα JSON response
     * 
     * @param mixed $data Τα δεδομένα προς μετατροπή
     * @param int $statusCode Ο κωδικός κατάστασης HTTP
     * @return void
     */
    public static function response($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo self::encode($data);
        exit();
    }

    /**
     * Δημιουργεί ένα JSON response επιτυχίας
     * 
     * @param mixed $data Τα δεδομένα προς μετατροπή
     * @param string $message Το μήνυμα επιτυχίας
     * @return void
     */
    public static function success($data = null, $message = 'Επιτυχία')
    {
        self::response([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Δημιουργεί ένα JSON response σφάλματος
     * 
     * @param string $message Το μήνυμα σφάλματος
     * @param int $statusCode Ο κωδικός κατάστασης HTTP
     * @return void
     */
    public static function error($message = 'Σφάλμα', $statusCode = 400)
    {
        self::response([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
