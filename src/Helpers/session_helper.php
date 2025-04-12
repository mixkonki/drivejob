<?php
// src/Helpers/session_helper.php

use Drivejob\Core\Session;

/**
 * Βοηθητική συνάρτηση για την καταγραφή πληροφοριών συνεδρίας για αποσφαλμάτωση
 */
function logSessionDebug($message = 'Session Debug')
{
    $sessionInfo = Session::getDebugInfo();
    
    $logMessage = date('[Y-m-d H:i:s] ') . $message . "\n";
    $logMessage .= "Session ID: " . $sessionInfo['id'] . "\n";
    $logMessage .= "Session Name: " . $sessionInfo['name'] . "\n";
    $logMessage .= "Session Status: " . $sessionInfo['status'] . "\n";
    $logMessage .= "Session Data: " . print_r($sessionInfo['data'], true) . "\n";
    $logMessage .= "Request URL: " . $_SERVER['REQUEST_URI'] . "\n\n";
    
    file_put_contents(
        ROOT_DIR . '/session_debug.log',
        $logMessage,
        FILE_APPEND
    );
}

/**
 * Συνάρτηση που ελέγχει αν τα cookies του browser είναι ενεργοποιημένα
 */
function checkCookiesEnabled()
{
    if (!isset($_COOKIE['cookie_test'])) {
        setcookie('cookie_test', '1', time() + 3600);
        if (isset($_SERVER['HTTP_REFERER'])) {
            // Αποθήκευση της αρχικής σελίδας στη συνεδρία
            Session::set('original_page', $_SERVER['HTTP_REFERER']);
            // Ανακατεύθυνση στη σελίδα ελέγχου cookies
            header('Location: ' . BASE_URL . 'check_cookies.php');
            exit();
        }
    }
    return true;
}