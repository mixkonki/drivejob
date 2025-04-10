<?php
// src/Core/RateLimiter.php

namespace Drivejob\Core;

class RateLimiter
{
    private const MAX_ATTEMPTS = 5; // Μέγιστος αριθμός προσπαθειών
    private const LOCKOUT_TIME = 300; // Χρόνος κλειδώματος σε δευτερόλεπτα (5 λεπτά)
    
    /**
     * Ελέγχει αν η IP έχει φτάσει το όριο προσπαθειών
     */
    public static function checkLimit($ip, $action)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($ip . '_' . $action);
        
        // Αρχικοποίηση των μεταβλητών
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'last_attempt' => time(),
                'locked_until' => 0
            ];
        }
        
        // Έλεγχος αν είναι σε περίοδο κλειδώματος
        if ($_SESSION[$key]['locked_until'] > time()) {
            return [
                'limited' => true,
                'wait_time' => $_SESSION[$key]['locked_until'] - time()
            ];
        }
        
        // Έλεγχος αν έχει περάσει αρκετός χρόνος για reset
        if (time() - $_SESSION[$key]['last_attempt'] > self::LOCKOUT_TIME) {
            $_SESSION[$key]['attempts'] = 0;
        }
        
        // Έλεγχος αν έχει φτάσει το όριο προσπαθειών
        if ($_SESSION[$key]['attempts'] >= self::MAX_ATTEMPTS) {
            $_SESSION[$key]['locked_until'] = time() + self::LOCKOUT_TIME;
            return [
                'limited' => true,
                'wait_time' => self::LOCKOUT_TIME
            ];
        }
        
        return [
            'limited' => false,
            'attempts' => $_SESSION[$key]['attempts']
        ];
    }
    
    /**
     * Αυξάνει τον μετρητή προσπαθειών
     */
    public static function increment($ip, $action)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($ip . '_' . $action);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'last_attempt' => time(),
                'locked_until' => 0
            ];
        }
        
        $_SESSION[$key]['attempts']++;
        $_SESSION[$key]['last_attempt'] = time();
        
        return $_SESSION[$key]['attempts'];
    }
    
    /**
     * Επαναφέρει τον μετρητή προσπαθειών
     */
    public static function reset($ip, $action)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($ip . '_' . $action);
        
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
}