<?php

namespace Drivejob\Helpers;

/**
 * Βοηθητική κλάση για λειτουργίες JSON
 */
class JsonHelper
{
    /**
     * Κωδικοποιεί δεδομένα σε JSON και τα εμφανίζει
     * 
     * @param mixed $data Τα δεδομένα προς κωδικοποίηση
     * @param int $options Επιλογές κωδικοποίησης JSON
     * @return void
     */
    public static function response($data, $options = 0)
    {
        header('Content-Type: application/json');
        echo self::encode($data, $options);
    }

    /**
     * Κωδικοποιεί δεδομένα σε JSON
     * 
     * @param mixed $data Τα δεδομένα προς κωδικοποίηση
     * @param int $options Επιλογές κωδικοποίησης JSON
     * @return string Κωδικοποιημένα δεδομένα JSON
     */
    public static function encode($data, $options = 0)
    {
        return \json_encode($data, $options);
    }

    /**
     * Αποκωδικοποιεί δεδομένα JSON
     * 
     * @param string $json Δεδομένα JSON
     * @param bool $assoc Επιστροφή ως συσχετιστικός πίνακας
     * @return mixed Αποκωδικοποιημένα δεδομένα
     */
    public static function decode($json, $assoc = true)
    {
        return \json_decode($json, $assoc);
    }

    /**
     * Επιστρέφει μήνυμα επιτυχίας σε μορφή JSON
     * 
     * @param string $message Μήνυμα επιτυχίας
     * @param array $data Επιπλέον δεδομένα
     * @return void
     */
    public static function success($message = 'Η ενέργεια ολοκληρώθηκε με επιτυχία', $data = [])
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        self::response($response);
    }

    /**
     * Επιστρέφει μήνυμα σφάλματος σε μορφή JSON
     * 
     * @param string $message Μήνυμα σφάλματος
     * @param array $errors Λεπτομέρειες σφαλμάτων
     * @return void
     */
    public static function error($message = 'Παρουσιάστηκε ένα σφάλμα', $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        self::response($response);
    }
}
