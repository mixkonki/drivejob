<?php

namespace Drivejob\Core;

use Drivejob\Core\Session;
use Drivejob\Core\Logger;

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
        // Καταγραφή για αποσφαλμάτωση
        // ΠΡΟΣΟΧΗ: εδώ καταγραφόταν ολόκληρο το $_SESSION, που περιέχει το
        // old_input των φορμών — δηλαδή συνθηματικά σε καθαρό κείμενο, IP και
        // user agent. Αφαιρέθηκε (GDPR άρθρο 32).
        Logger::debug('CSRF validateToken called', [
            'session_id' => session_id(),
            'has_csrf_token' => Session::has('csrf_token'),
        ]);

        if (!Session::has('csrf_token')) {
            Logger::warning('CSRF token not found in session', [
                'session_id' => session_id(),
                'session_keys' => array_keys($_SESSION),
            ]);
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

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('CSRF validation', [
            'session_token' => Session::get('csrf_token'),
            'provided_token' => $token,
            'match' => hash_equals(Session::get('csrf_token'), $token)
        ]);

        return hash_equals(Session::get('csrf_token'), $token);
    }

    /**
     * Δημιουργεί ένα hidden input πεδίο με το CSRF token
     *
     * @return string HTML string με το hidden input
     */
    public static function tokenField()
    {
        Session::start();

        // Έλεγχος αν υπάρχει ήδη token
        if (Session::has('csrf_token')) {
            $token = Session::get('csrf_token');

            // Έλεγχος αν το token έχει λήξει
            if (Session::has('csrf_token_time')) {
                $tokenTime = Session::get('csrf_token_time');
                if ((time() - $tokenTime) > 7200) { // 2 ώρες
                    // Το token έχει λήξει, δημιουργία νέου
                    $token = self::generateToken();
                }
            }
        } else {
            // Δεν υπάρχει token, δημιουργία νέου
            $token = self::generateToken();
        }

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('CSRF token field generated', [
            'token' => $token,
            'session_id' => Session::getId()
        ]);

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
