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
        
        if (!Session::has('user_id')) {
            // Καταγραφή αποσφαλμάτωσης
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Login check failed - redirecting to login\n\n", 
                FILE_APPEND
            );
            
            // Αποθήκευση της τρέχουσας URL για επιστροφή μετά τη σύνδεση
            if (isset($_SERVER['REQUEST_URI'])) {
                Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);
            }
            
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        return true;
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει τον απαιτούμενο ρόλο
     */
    public static function hasRole($role)
    {
        // Βεβαιωνόμαστε ότι η συνεδρία έχει ξεκινήσει
        Session::start();
        
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('role')) {
            // Καταγραφή αποσφαλμάτωσης
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Role check failed: user not logged in - redirecting to login\n\n", 
                FILE_APPEND
            );
            
            // Αποθήκευση της τρέχουσας URL για επιστροφή μετά τη σύνδεση
            if (isset($_SERVER['REQUEST_URI'])) {
                Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);
            }
            
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Έλεγχος αν ο χρήστης έχει τον απαιτούμενο ρόλο
        if (Session::get('role') !== $role) {
            // Καταγραφή αποσφαλμάτωσης
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Role check failed: user has role '" . Session::get('role') . 
                "', required '" . $role . "' - redirecting to login\n\n", 
                FILE_APPEND
            );
            
            // Ανακατεύθυνση στην αρχική σελίδα ή σε σελίδα πρόσβασης άρνησης
            header('Location: ' . BASE_URL . 'access-denied.php');
            exit();
        }
        
        return true;
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
     */
    public static function hasAnyRole($roles)
    {
        // Βεβαιωνόμαστε ότι η συνεδρία έχει ξεκινήσει
        Session::start();
        
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('role')) {
            // Καταγραφή αποσφαλμάτωσης
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "AnyRole check failed - redirecting to login\n\n", 
                FILE_APPEND
            );
            
            // Αποθήκευση της τρέχουσας URL για επιστροφή μετά τη σύνδεση
            if (isset($_SERVER['REQUEST_URI'])) {
                Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);
            }
            
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Έλεγχος αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
        if (!in_array(Session::get('role'), $roles)) {
            // Καταγραφή αποσφαλμάτωσης
            file_put_contents(
                ROOT_DIR . '/auth_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "AnyRole check failed - user has role '" . Session::get('role') . 
                "', required one of: " . implode(', ', $roles) . 
                " - redirecting to access denied\n\n", 
                FILE_APPEND
            );
            
            // Ανακατεύθυνση στην αρχική σελίδα ή σε σελίδα πρόσβασης άρνησης
            header('Location: ' . BASE_URL . 'access-denied.php');
            exit();
        }
        
        return true;
    }
}