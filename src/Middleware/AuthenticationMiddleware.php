<?php
namespace Drivejob\Middleware;

use Drivejob\Core\Session;
use Drivejob\Core\Auth;

/**
 * Middleware για έλεγχο authentication
 */
class AuthenticationMiddleware
{
    /**
     * Απαιτεί ο χρήστης να είναι συνδεδεμένος
     */
    public static function requireLogin()
    {
        Session::start();
        
        if (!Auth::isLoggedIn()) {
            // Store current URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }
    }
    
    /**
     * Απαιτεί ο χρήστης να έχει συγκεκριμένο ρόλο
     */
    public static function requireRole($role)
    {
        self::requireLogin();
        
        if (!Auth::hasRole($role)) {
            header('Location: ' . BASE_URL . 'auth/access-denied');
            exit();
        }
    }
    
    /**
     * Απαιτεί ο χρήστης να έχει έναν από τους ρόλους
     */
    public static function requireAnyRole(array $roles)
    {
        self::requireLogin();
        
        if (!Auth::hasAnyRole($roles)) {
            header('Location: ' . BASE_URL . 'auth/access-denied');
            exit();
        }
    }
    
    /**
     * Απαιτεί ο χρήστης να είναι επαληθευμένος
     */
    public static function requireVerified($pdo)
    {
        self::requireLogin();
        
        if (!Auth::isVerified($pdo)) {
            header('Location: ' . BASE_URL . 'auth/verification-required');
            exit();
        }
    }
}
