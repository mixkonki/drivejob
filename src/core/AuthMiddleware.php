<?php
namespace Drivejob\Core;

class AuthMiddleware
{
    /**
     * Ελέγχει αν ο χρήστης είναι συνδεδεμένος
     */
    public static function isLoggedIn()
    {
        // Βεβαιωνόμαστε ότι η συνεδρία είναι ενεργή
        Session::start();
        
        if (!Session::has('user_id')) {
            // Καταγραφή αποσφαλμάτωσης
            error_log(
                date('[Y-m-d H:i:s] ') . 
                "Login check failed - redirecting to login\n"
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
        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();
        
        // Έλεγχος αν ο χρήστης έχει τον απαιτούμενο ρόλο
        if (Session::get('role') !== $role) {
            // Καταγραφή αποσφαλμάτωσης
            error_log(
                date('[Y-m-d H:i:s] ') . 
                "Role check failed: user has role '" . Session::get('role') . 
                "', required '" . $role . "' - redirecting to access denied\n"
            );
            
            // Ανακατεύθυνση στην σελίδα άρνησης πρόσβασης
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
        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();
        
        // Έλεγχος αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
        if (!in_array(Session::get('role'), $roles)) {
            // Καταγραφή αποσφαλμάτωσης
            error_log(
                date('[Y-m-d H:i:s] ') . 
                "AnyRole check failed - user has role '" . Session::get('role') . 
                "', required one of: " . implode(', ', $roles) . 
                " - redirecting to access denied\n"
            );
            
            // Ανακατεύθυνση στην σελίδα άρνησης πρόσβασης
            header('Location: ' . BASE_URL . 'access-denied.php');
            exit();
        }
        
        return true;
    }
    
    /**
     * Ελέγχει αν ο χρήστης είναι ο ιδιοκτήτης του αντικειμένου
     * 
     * @param int $ownerId ID του ιδιοκτήτη του αντικειμένου
     * @return bool true αν ο χρήστης είναι ο ιδιοκτήτης, διαφορετικά ανακατεύθυνση
     */
    public static function isOwner($ownerId)
    {
        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();
        
        if (Session::get('user_id') != $ownerId) {
            // Καταγραφή αποσφαλμάτωσης
            error_log(
                date('[Y-m-d H:i:s] ') . 
                "Owner check failed - user id: " . Session::get('user_id') . 
                ", owner id: " . $ownerId . 
                " - redirecting to access denied\n"
            );
            
            // Ανακατεύθυνση στην σελίδα άρνησης πρόσβασης
            header('Location: ' . BASE_URL . 'access-denied.php');
            exit();
        }
        
        return true;
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει επαληθεύσει το email του
     */
    public static function isVerified()
    {
        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();
        
        // Λήψη του χρήστη από τη βάση δεδομένων
        global $pdo;
        
        $role = Session::get('role');
        $userId = Session::get('user_id');
        
        if ($role === 'driver') {
            $sql = "SELECT is_verified FROM drivers WHERE id = ?";
        } else {
            $sql = "SELECT is_verified FROM companies WHERE id = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$result || !$result['is_verified']) {
            // Καταγραφή αποσφαλμάτωσης
            error_log(
                date('[Y-m-d H:i:s] ') . 
                "Verification check failed - user id: " . $userId . 
                ", role: " . $role . 
                " - redirecting to verification required\n"
            );
            
            // Ανακατεύθυνση στην σελίδα απαίτησης επαλήθευσης
            header('Location: ' . BASE_URL . 'verification-required.php');
            exit();
        }
        
        return true;
    }
    
    /**
     * Προστασία από επιθέσεις CSRF
     * 
     * @param string $token Token CSRF από τη φόρμα
     * @return bool true αν το token είναι έγκυρο, διαφορετικά ανακατεύθυνση
     */
    public static function validateCSRF($token)
    {
        if (!isset($token) || !CSRF::validateToken($token)) {
            // Καταγραφή αποσφαλμάτωσης
            error_log(
                date('[Y-m-d H:i:s] ') . 
                "CSRF validation failed - redirecting to error\n"
            );
            
            // Ανακατεύθυνση στην σελίδα σφάλματος CSRF
            header('Location: ' . BASE_URL . 'csrf-error.php');
            exit();
        }
        
        return true;
    }
    
    /**
     * Έλεγχος αν η συνεδρία έχει λήξει λόγω αδράνειας
     * 
     * @param int $maxIdleTime Μέγιστος χρόνος αδράνειας σε δευτερόλεπτα
     * @return bool true αν η συνεδρία είναι ενεργή, διαφορετικά ανακατεύθυνση
     */
    public static function checkSessionTimeout($maxIdleTime = 1800) // 30 λεπτά προεπιλογή
    {
        if (Session::isExpired($maxIdleTime)) {
            // Καταστροφή της συνεδρίας
            Session::destroy();
            
            // Ανακατεύθυνση στη σελίδα σύνδεσης με μήνυμα λήξης
            header('Location: ' . BASE_URL . 'login.php?expired=1');
            exit();
        }
        
        return true;
    }
}