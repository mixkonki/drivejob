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
        parent::__construct();
    }

    /**
     * Εμφανίζει τη φόρμα σύνδεσης
     *
     * @return void
     */
    public function showLoginForm()
    {
        // Ο έλεγχος για ήδη συνδεδεμένο χρήστη γίνεται στο login.php
        // Εδώ απλά φορτώνουμε το view
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
            $this->redirect(BASE_URL . 'login');
        }

        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('Processing login request', [
            'email' => $_POST['email'] ?? 'not set',
            'has_password' => isset($_POST['password']) && !empty($_POST['password']),
            'has_csrf' => isset($_POST['csrf_token']),
            'session_id' => session_id(),
            'session_keys' => array_keys($_SESSION),
        ]);

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Logger::warning('CSRF validation failed during login', [
                'has_token' => isset($_POST['csrf_token']),
                'session_id' => session_id()
            ]);
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'auth/login');
        }

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

        /*
         * Απενεργοποιημένος λογαριασμός (κουμπί admin panel): σωστός
         * κωδικός, αλλά ΔΕΝ δημιουργείται συνεδρία. Μέχρι σήμερα το
         * is_active αγνοούνταν εντελώς — η «απενεργοποίηση» δεν κρατούσε
         * κανέναν έξω.
         */
        if ($user && empty($user['is_active'])) {
            Logger::warning('Login refused for deactivated account', [
                'user_id' => $user['user_id'],
                'role' => $user['role'],
            ]);
            Session::set('error_message',
                'Ο λογαριασμός σας έχει απενεργοποιηθεί. Επικοινωνήστε μαζί μας '
                . 'στο info@drivejob.gr αν πιστεύετε ότι πρόκειται για λάθος.');
            $this->redirect(BASE_URL . 'login');
        }

        if ($user) {
            // Επιτυχής σύνδεση - Αναγέννηση του session ID για ασφάλεια
            Session::regenerate(true);

            // Αποθήκευση των στοιχείων χρήστη στο session
            Session::set('user_id', $user['user_id']);
            Session::set('user_role', $user['role']);
            Session::set('role', $user['role']); // Για συμβατότητα με υπάρχον κώδικα
            Session::set('user_name', $user['name']);
            Session::set('is_verified', !empty($user['is_verified']));

            // Δημιουργία νέου CSRF token μετά το login
            CSRF::generateToken();

            // Καταγραφή για αποσφαλμάτωση
            Logger::info('User logged in successfully', [
                'user_id' => $user['user_id'],
                'role' => $user['role'],
                'session_id' => session_id()
            ]);

            /*
             * Ανεπαλήθευτος λογαριασμός: η συνεδρία δημιουργείται (ώστε να
             * δουλεύει η επαναποστολή email), αλλά ο προορισμός είναι η
             * σελίδα «απαιτείται επαλήθευση» — όχι το προφίλ. Την υπόλοιπη
             * πλατφόρμα τη φράζει ο ίδιος έλεγχος στο AuthMiddleware.
             */
            if (in_array($user['role'], ['driver', 'company'], true) && empty($user['is_verified'])) {
                $this->redirect(BASE_URL . 'auth/verification-required');
            }

            // Έλεγχος για ανακατεύθυνση μετά τη σύνδεση
            $redirectUrl = Session::has('redirect_after_login')
                ? Session::get('redirect_after_login')
                : $this->getDefaultRedirectUrl($user['role']);

            Session::remove('redirect_after_login');

            // Ανακατεύθυνση με πλήρη διαδρομή
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
        // Καταγραφή για αποσφαλμάτωση
        Logger::debug('Logout initiated', [
            'user_id' => Session::get('user_id'),
            'session_id' => session_id()
        ]);

        // Καθαρισμός όλων των δεδομένων session
        Session::clear();

        // Καταστροφή του session
        Session::destroy();

        // Δημιουργία νέου session για το CSRF token
        Session::start();

        // Αποστολή headers για αποτροπή caching
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

        // Ανακατεύθυνση στη σελίδα login
        $this->redirect(BASE_URL . 'auth/login');
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
            $this->redirect(BASE_URL . 'auth/login');
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
            /*
             * Αν ο χρήστης που επαληθεύτηκε είναι ήδη συνδεδεμένος σε αυτόν
             * τον browser (συνηθισμένο: εγγραφή → σύνδεση → κλικ στο email),
             * ενημερώνεται και η συνεδρία — αλλιώς θα έβλεπε «απαιτείται
             * επαλήθευση» μέχρι να αποσυνδεθεί.
             */
            if (Session::get('user_id') == ($result['user_id'] ?? null)
                && Session::get('role') === ($result['role'] ?? null)) {
                Session::set('is_verified', true);
                Session::set('success_message', 'Το email σας επαληθεύτηκε. Καλώς ήρθατε!');
                $this->redirect($this->getDefaultRedirectUrl($result['role']));
            }

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
        // Χρήση του BASE_URL που έχει ήδη οριστεί στο config
        switch ($role) {
            case 'driver':
                return BASE_URL . 'drivers/profile';
            case 'company':
                return BASE_URL . 'companies/profile';
            case 'admin':
                return BASE_URL . 'admin/dashboard';
            default:
                return BASE_URL;
        }
    }
}
