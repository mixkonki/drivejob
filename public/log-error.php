<?php
// Λήψη των δεδομένων σφάλματος
$input = file_get_contents('php://input');
$error = json_decode($input, true);

if ($error) {
    $logMessage = date('Y-m-d H:i:s') . " JavaScript Error: " . 
                  $error['message'] . " in " . $error['source'] . 
                  " on line " . $error['lineno'] . "\n";
    
    if (isset($error['stack'])) {
        $logMessage .= "Stack Trace: " . $error['stack'] . "\n";
    }
    
    // Καταγραφή σε αρχείο
    file_put_contents('../logs/js_errors.log', $logMessage, FILE_APPEND);
}



/** * Αρχείο καταγραφής σφαλμάτων JavaScript
 * 
 * Αυτό το αρχείο καταγράφει σφάλματα JavaScript που προκύπτουν στην εφαρμογή.
 * Τα σφάλματα καταγράφονται σε ένα αρχείο logs/js_errors.log.
 * 
 * @package Drivejob
 * @author Your Name
 * @version 1.0
 */