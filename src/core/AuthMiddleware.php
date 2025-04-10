<?php
namespace Drivejob\Core;

class AuthMiddleware
{
    /**
     * Ελέγχει αν ο χρήστης είναι συνδεδεμένος
     */
    public static function isLoggedIn()
    {
        Session::start();
        
        if (!Session::has('user_id')) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει τον απαιτούμενο ρόλο
     */
    public static function hasRole($role)
    {
        Session::start();
        
        if (!Session::has('user_id') || !Session::has('role') || Session::get('role') !== $role) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
     */
    public static function hasAnyRole($roles)
    {
        Session::start();
        
        if (!Session::has('user_id') || !Session::has('role') || !in_array(Session::get('role'), $roles)) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
}