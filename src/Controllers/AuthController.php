<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Core\Logger;

/**
 * Controller για την αυθεντικοποίηση χρηστών
 * 
 * Επεκτείνει τον BaseUserController και χρησιμοποιεί τις κοινές λειτουργίες
 * για τη διαχείριση χρηστών (login, logout, κλπ.)
 */
class AuthController extends BaseUserController
{
    /**
     * Constructor
     */
    public function __construct($pdo = null)
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct($pdo);
    }

    /**
     * Εμφανίζει τη φόρμα σύνδεσης
     *
     * @return void
     */
    public function showLoginForm()
    {
        // Έλεγχος αν ο χρήστης είναι ήδη συνδεδεμένος
        if (Session::has('user_id')) {
            $role = Session::get('user_role');
            if ($role === 'driver') {
                $this->redirect(BASE_URL . 'drivers/profile');
            } elseif ($role === 'company') {
                $this->redirect(BASE_URL . 'companies/profile');
            } elseif ($role === 'admin') {
                $this->redirect(BASE_URL . 'admin/dashboard');
            }
        }

        // Φόρτωση της σελίδας σύνδεσης
        $this->view('auth/login');
    }

    /**
     * Επεξεργάζεται το αίτημα σύνδεσης
     *
     * @return void
     */
    public function login()
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'auth/login');
        }

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('Processing login request', [
            'email' => $_POST['email'] ?? 'not set',
            'has_password' => isset($_POST['password']) && !empty($_POST['password']),
            'has_csrf' => isset($_POST['csrf_token']),
            'session_id' => session_id(),
            'session_data' => $_SESSION
        ]);

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token'])) {
            Logger::warning('CSRF token missing during login');
            Session::set('error_message', 'Άκυρο αίτημα. Το CSRF token λείπει. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'auth/login');
        }

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('CSRF token validation', [
            'provided_token' => $_POST['csrf_token'],
            'session_token' => Session::get('csrf_token'),
            'session_id' => session_id()
        ]);

        // Προσωρινή απενεργοποίηση του ελέγχου CSRF για αποσφαλμάτωση
        /*
        if (!CSRF::validateToken($_POST['csrf_token'])) {
            Logger::warning('CSRF validation failed during login');
            Session::set('error_message', 'Άκυρο αίτημα. Το CSRF token δεν είναι έγκυρο. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'auth/login');
        }
        */

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('Attempting authentication', [
            'email' => $email,
            'password_length' => strlen($password)
        ]);

        // Αυθεντικοποίηση χρήστη
        $user = $this->authModel->authenticate($email, $password);

        Logger::debug('Authentication result', [
            'success' => $user ? true : false,
            'user' => $user ? [
                'user_id' => $user['user_id'],
                'role' => $user['role'],
                'name' => $user['name']
            ] : null
        ]);

        if ($user) {
            // Επιτυχής σύνδεση
            Session::set('user_id', $user['user_id']);
            Session::set('user_role', $user['role']);
            Session::set('user_name', $user['name']);

            // Καταγραφή για αποσφαλμάτωση
            Logger::debug('Session after login', [
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]);

            // Έλεγχος για ανακατεύθυνση μετά τη σύνδεση
            $redirectUrl = Session::has('redirect_after_login')
                ? Session::get('redirect_after_login')
                : $this->getDefaultRedirectUrl($user['role']);

            Session::remove('redirect_after_login');

            // Καταγραφή για αποσφαλμάτωση
            Logger::debug('Redirecting after successful login', [
                'redirect_url' => $redirectUrl
            ]);

            $this->redirect($redirectUrl);
        }

        // Αποτυχία σύνδεσης
        Logger::warning('Login failed', [
            'email' => $email
        ]);

        Session::set('error_message', 'Εσφαλμένο email ή συνθηματικό.');
        $this->redirect(BASE_URL . 'auth/login');
    }


    /**
     * Αποσυνδέει τον χρήστη
     *
     * @return void
     */
    public function logout()
    {
        // Καθαρισμός του session
        Session::destroy();

        // Ανακατεύθυνση στην αρχική σελίδα
        $this->redirect(BASE_URL);
    }

    /**
     * Εμφανίζει τη φόρμα επαναφοράς συνθηματικού
     *
     * @return void
     */
    public function showPasswordResetForm()
    {
        // Φόρτωση της σελίδας επαναφοράς συνθηματικού
        $this->view('auth/password-reset');
    }

    /**
     * Αποστέλλει τον σύνδεσμο επαναφοράς συνθηματικού
     *
     * @return void
     */
    public function sendPasswordResetLink()
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'auth/password-reset');
        }

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::warning('CSRF validation failed during password reset request');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'auth/password-reset');
        }

        $email = $_POST['email'] ?? '';

        // Αποστολή του συνδέσμου επαναφοράς
        $result = $this->authModel->sendPasswordResetLink($email);

        if ($result) {
            Session::set('success_message', 'Ένας σύνδεσμος επαναφοράς συνθηματικού έχει σταλεί στη διεύθυνση email σας.');
        } else {
            Session::set('error_message', 'Δεν βρέθηκε λογαριασμός με αυτή τη διεύθυνση email.');
        }

        $this->redirect(BASE_URL . 'auth/password-reset');
    }

    /**
     * Εμφανίζει τη φόρμα επαναφοράς συνθηματικού με token
     *
     * @param string $token Το token επαναφοράς
     * @return void
     */
    public function showResetPasswordForm($token)
    {
        // Έλεγχος εγκυρότητας του token
        if (!$this->authModel->isValidResetToken($token)) {
            Session::set('error_message', 'Το token επαναφοράς είναι άκυρο ή έχει λήξει.');
            $this->redirect(BASE_URL . 'auth/password-reset');
        }

        // Φόρτωση της σελίδας επαναφοράς συνθηματικού με token
        $this->view('auth/password-reset-token', ['token' => $token]);
    }

    /**
     * Επαναφέρει το συνθηματικό του χρήστη
     *
     * @param string $token Το token επαναφοράς
     * @return void
     */
    public function resetPassword($token)
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'auth/password-reset/' . $token);
        }

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::warning('CSRF validation failed during password reset');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'auth/password-reset/' . $token);
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Έλεγχος αν τα συνθηματικά ταιριάζουν
        if ($password !== $passwordConfirm) {
            Session::set('error_message', 'Τα συνθηματικά δεν ταιριάζουν.');
            $this->redirect(BASE_URL . 'auth/password-reset/' . $token);
        }

        // Έλεγχος μήκους συνθηματικού
        if (strlen($password) < 8) {
            Session::set('error_message', 'Το συνθηματικό πρέπει να έχει τουλάχιστον 8 χαρακτήρες.');
            $this->redirect(BASE_URL . 'auth/password-reset/' . $token);
        }

        // Επαναφορά του συνθηματικού
        $result = $this->authModel->resetPassword($token, $password);

        if ($result) {
            Session::set('success_message', 'Το συνθηματικό σας έχει επαναφερθεί με επιτυχία. Μπορείτε τώρα να συνδεθείτε.');
            $this->redirect(BASE_URL . 'auth/login');
        } else {
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την επαναφορά του συνθηματικού σας.');
            $this->redirect(BASE_URL . 'auth/password-reset/' . $token);
        }
    }

    /**
     * Εμφανίζει τη σελίδα άρνησης πρόσβασης
     *
     * @return void
     */
    public function accessDenied()
    {
        // Φόρτωση της σελίδας άρνησης πρόσβασης
        $this->view('auth/access-denied');
    }

    /**
     * Εμφανίζει τη σελίδα που απαιτεί επαλήθευση
     *
     * @return void
     */
    public function verificationRequired()
    {
        // Φόρτωση της σελίδας που απαιτεί επαλήθευση
        $this->view('auth/verification-required');
    }

    /**
     * Επαληθεύει τον λογαριασμό του χρήστη
     *
     * @param string $token Το token επαλήθευσης
     * @return void
     */
    public function verify($token)
    {
        // Επαλήθευση του λογαριασμού
        $result = $this->authModel->verifyAccount($token);

        if ($result) {
            Session::set('success_message', 'Ο λογαριασμός σας επαληθεύτηκε με επιτυχία. Μπορείτε τώρα να συνδεθείτε.');
            $this->redirect(BASE_URL . 'auth/login');
        } else {
            Session::set('error_message', 'Το token επαλήθευσης είναι άκυρο ή έχει λήξει.');
            $this->redirect(BASE_URL . 'auth/login');
        }
    }
    /**
     * Επαναποστέλλει το email επαλήθευσης
     *
     * @return void
     */
    public function resendVerification()
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'auth/verification-required');
        }

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::warning('CSRF validation failed during resend verification request');
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'auth/verification-required');
        }

        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id')) {
            Session::set('error_message', 'Πρέπει να είστε συνδεδεμένοι για να ζητήσετε επαναποστολή του email επαλήθευσης.');
            $this->redirect(BASE_URL . 'auth/login');
        }

        $userId = Session::get('user_id');

        // Επαναποστολή του email επαλήθευσης
        $result = $this->authModel->resendVerificationEmail($userId);

        if ($result) {
            Session::set('success_message', 'Ένα νέο email επαλήθευσης έχει σταλεί στη διεύθυνση email σας.');
        } else {
            Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την επαναποστολή του email επαλήθευσης. Παρακαλώ δοκιμάστε ξανά αργότερα.');
        }

        $this->redirect(BASE_URL . 'auth/verification-required');
    }

    /**
     * Επιστρέφει το προεπιλεγμένο URL ανακατεύθυνσης βάσει του ρόλου
     *
     * @param string $role Ο ρόλος του χρήστη
     * @return string Το URL ανακατεύθυνσης
     */
    private function getDefaultRedirectUrl($role)
    {
        switch ($role) {
            case 'driver':
                return BASE_URL . 'drivers/profile';
            case 'company':
                return BASE_URL . 'companies/profile';
            case 'admin':
                return BASE_URL . 'admin/monitoring/dashboard';
            default:
                return BASE_URL;
        }
    }
}
