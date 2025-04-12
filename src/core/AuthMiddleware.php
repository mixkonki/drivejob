<?php
namespace Drivejob\Core;

class AuthMiddleware
{
    /**
     * Ελέγχει αν ο χρήστης είναι συνδεδεμένος
     */
    public static function isLoggedIn()
    {
        // Βεβαιωνόμαστε ότι η συνεδρία έχει ξεκινήσει
        Session::start();
        
        // Αποσφαλμάτωση
        file_put_contents(
            ROOT_DIR . '/auth_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "IsLoggedIn check - Session: " . print_r($_SESSION, true) . 
            "\nPHP_SESSION_ID: " . session_id() . "\n\n", 
            FILE_APPEND
        );
        
        if (!Session::has('user_id')) {
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Login check failed - redirecting to login\n\n", 
                FILE_APPEND
            );
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει τον απαιτούμενο ρόλο
     */
    public static function hasRole($role)
    {
        // Βεβαιωνόμαστε ότι η συνεδρία έχει ξεκινήσει
        Session::start();
        
        // Αποσφαλμάτωση
        file_put_contents(
            ROOT_DIR . '/auth_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "HasRole check - Session: " . print_r($_SESSION, true) . 
            "\nPHP_SESSION_ID: " . session_id() . 
            "\nRequired role: " . $role . "\n\n", 
            FILE_APPEND
        );
        
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Role check failed: user not logged in - redirecting to login\n\n", 
                FILE_APPEND
            );
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Έλεγχος αν ο χρήστης έχει τον απαιτούμενο ρόλο
        if ($_SESSION['role'] !== $role) {
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Role check failed: user has role '" . $_SESSION['role'] . "', required '" . $role . "' - redirecting to login\n\n", 
                FILE_APPEND
            );
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
     */
    public static function hasAnyRole($roles)
    {
        // Βεβαιωνόμαστε ότι η συνεδρία έχει ξεκινήσει
        Session::start();
        
        // Αποσφαλμάτωση
        file_put_contents(
            ROOT_DIR . '/auth_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "HasAnyRole check - Session: " . print_r($_SESSION, true) . 
            "\nPHP_SESSION_ID: " . session_id() . 
            "\nRequired roles: " . implode(', ', $roles) . "\n\n", 
            FILE_APPEND
        );
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "AnyRole check failed - redirecting to login\n\n", 
                FILE_APPEND
            );
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
}