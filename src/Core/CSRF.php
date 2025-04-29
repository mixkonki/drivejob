<?php
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
        Session::start();
        
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        Session::set('csrf_token_time', time());
        
        return $token;
    }
    
    /**
     * Επαληθεύει το CSRF token
     *
     * @param string $token Το token προς επαλήθευση
     * @param int $maxTokenAge Μέγιστος χρόνος ζωής του token σε δευτερόλεπτα (προαιρετικό)
     * @return bool true εάν το token είναι έγκυρο, false διαφορετικά
     */
    public static function validateToken($token, $maxTokenAge = 7200) // 2 ώρες προεπιλογή
    {
        Session::start();
        
        if (!Session::has('csrf_token')) {
            return false;
        }
        
        // Έλεγχος χρόνου ζωής του token
        if (Session::has('csrf_token_time')) {
            $tokenTime = Session::get('csrf_token_time');
            if ((time() - $tokenTime) > $maxTokenAge) {
                // Το token έχει λήξει
                self::generateToken(); // Δημιουργία νέου token
                return false;
            }
        }
        
        return hash_equals(Session::get('csrf_token'), $token);
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
    
    /**
     * Ανανεώνει το υπάρχον CSRF token
     *
     * @return string Το νέο CSRF token
     */
    public static function refreshToken()
    {
        return self::generateToken();
    }
    
    /**
     * Επιστρέφει το τρέχον CSRF token χωρίς να δημιουργήσει νέο
     *
     * @return string|null Το τρέχον CSRF token ή null αν δεν υπάρχει
     */
    public static function getCurrentToken()
    {
        Session::start();
        return Session::get('csrf_token');
    }
}