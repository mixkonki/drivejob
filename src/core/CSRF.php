<?php
// src/Core/CSRF.php

namespace Drivejob\Core;

class CSRF
{
    /**
     * Δημιουργεί ένα νέο CSRF token και το αποθηκεύει στο session
     *
     * @return string Το CSRF token
     */
    public static function generateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }
    
    /**
     * Επαληθεύει το CSRF token
     *
     * @param string $token Το token προς επαλήθευση
     * @return bool true εάν το token είναι έγκυρο, false διαφορετικά
     */
    public static function validateToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Δημιουργεί ένα hidden input πεδίο με το CSRF token
     *
     * @return string HTML string με το hidden input
     */
    public static function tokenField()
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}