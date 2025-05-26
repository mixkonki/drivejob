<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Core\Logger;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Models\AuthModel;

/**
 * Βασική κλάση για controllers που διαχειρίζονται χρήστες
 * 
 * Επεκτείνει τον BaseController και παρέχει κοινές λειτουργίες
 * για τη διαχείριση χρηστών (οδηγών και επιχειρήσεων)
 */
class BaseUserController extends BaseController
{
    /**
     * Το μοντέλο για τη διαχείριση της αυθεντικοποίησης
     *
     * @var AuthModel
     */
    protected $authModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Κλήση του constructor της γονικής κλάσης
        parent::__construct();

        // Αρχικοποίηση του AuthModel
        $this->authModel = new AuthModel($this->pdo);
    }

    /**
     * Εμφανίζει τη σελίδα σύνδεσης
     *
     * @return void
     */
    public function login()
    {
        // Έλεγχος αν ο χρήστης είναι ήδη συνδεδεμένος
        if (Session::has('user_id')) {
            $role = Session::get('role');
            if ($role === 'driver') {
                $this->redirect(BASE_URL . 'drivers/profile');
            } else {
                $this->redirect(BASE_URL . 'companies/profile');
            }
        }

        // Φόρτωση της σελίδας σύνδεσης
        $this->view('login');
    }

    /**
     * Επεξεργάζεται το αίτημα σύνδεσης
     *
     * @return void
     */
    public function processLogin()
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'login');
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Αυθεντικοποίηση χρήστη
        $user = $this->authModel->authenticate($email, $password);

        if ($user) {
            // Επιτυχής σύνδεση
            Session::set('user_id', $user['user_id']);
            Session::set('user_role', $user['role']);
            Session::set('user_name', $user['name']);

            // Έλεγχος για ανακατεύθυνση μετά τη σύνδεση
            $redirectUrl = Session::has('redirect_after_login')
                ? Session::get('redirect_after_login')
                : BASE_URL . ($user['role'] === 'driver' ? 'drivers/profile' : 'companies/profile');

            Session::remove('redirect_after_login');
            $this->redirect($redirectUrl);
        }

        // Αποτυχία σύνδεσης
        Session::set('login_error', 'Εσφαλμένο email ή συνθηματικό.');
        $this->redirect(BASE_URL . 'login');
    }

    /**
     * Αποσυνδέει τον χρήστη
     *
     * @return void
     */
    public function logout()
    {
        Session::destroy();
        $this->redirect(BASE_URL);
    }

    /**
     * Εμφανίζει τη φόρμα εγγραφής
     *
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return void
     */
    public function register($userType = 'driver')
    {
        // Έλεγχος αν ο χρήστης είναι ήδη συνδεδεμένος
        if (Session::has('user_id')) {
            $role = Session::get('role');
            if ($role === 'driver') {
                $this->redirect(BASE_URL . 'drivers/profile');
            } else {
                $this->redirect(BASE_URL . 'companies/profile');
            }
        }

        // Φόρτωση της σελίδας εγγραφής ανάλογα με τον τύπο του χρήστη
        if ($userType === 'company') {
            $this->view('companies/register');
        } else {
            $this->view('drivers/register');
        }
    }

    /**
     * Επεξεργάζεται το αίτημα εγγραφής
     *
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return void
     */
    public function processRegistration($userType = 'driver')
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . ($userType === 'company' ? 'companies/register' : 'drivers/register'));
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . ($userType === 'company' ? 'companies/register' : 'drivers/register'));
        }

        // Συλλογή και επικύρωση των δεδομένων
        $data = $this->collectRegistrationData($userType);
        $errors = $this->validateRegistrationData($data, $userType);

        if (!empty($errors)) {
            Session::set('errors', $errors);
            Session::set('old_input', $_POST);
            $this->redirect(BASE_URL . ($userType === 'company' ? 'companies/register' : 'drivers/register'));
        }

        try {
            // Εγγραφή του χρήστη
            $userId = $userType === 'company'
                ? $this->authModel->registerCompany($data)
                : $this->authModel->registerDriver($data);

            if ($userId) {
                // Επιτυχής εγγραφή
                Session::set('success_message', 'Η εγγραφή σας ολοκληρώθηκε με επιτυχία. Παρακαλώ ελέγξτε το email σας για να επιβεβαιώσετε τον λογαριασμό σας.');
                $this->redirect(BASE_URL . 'login');
            } else {
                // Αποτυχία εγγραφής
                Session::set('error_message', 'Υπήρξε ένα σφάλμα κατά την εγγραφή σας. Παρακαλώ δοκιμάστε ξανά.');
                Session::set('old_input', $_POST);
                $this->redirect(BASE_URL . ($userType === 'company' ? 'companies/register' : 'drivers/register'));
            }
        } catch (\Exception $e) {
            Logger::error('Exception in registration', [
                'user_type' => $userType,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
            Session::set('old_input', $_POST);
            $this->redirect(BASE_URL . ($userType === 'company' ? 'companies/register' : 'drivers/register'));
        }
    }

    /**
     * Επαληθεύει τον λογαριασμό ενός χρήστη
     *
     * @param string $code Ο κωδικός επαλήθευσης
     * @return void
     */
    public function verifyAccount($code)
    {
        if (empty($code)) {
            Session::set('error_message', 'Μη έγκυρος κωδικός επαλήθευσης.');
            $this->redirect(BASE_URL . 'login');
        }

        try {
            // Επαλήθευση του λογαριασμού
            $user = $this->authModel->verifyAccount($code);

            if ($user) {
                // Επιτυχής επαλήθευση
                Session::set('success_message', 'Ο λογαριασμός σας επαληθεύτηκε με επιτυχία. Μπορείτε τώρα να συνδεθείτε.');
            } else {
                // Αποτυχία επαλήθευσης
                Session::set('error_message', 'Μη έγκυρος ή ληγμένος κωδικός επαλήθευσης.');
            }
        } catch (\Exception $e) {
            Logger::error('Exception in account verification', [
                'code' => $code,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        $this->redirect(BASE_URL . 'login');
    }

    /**
     * Εμφανίζει τη φόρμα επαναφοράς κωδικού πρόσβασης
     *
     * @return void
     */
    public function forgotPassword()
    {
        // Φόρτωση της σελίδας επαναφοράς κωδικού πρόσβασης
        $this->view('forgot-password');
    }

    /**
     * Επεξεργάζεται το αίτημα επαναφοράς κωδικού πρόσβασης
     *
     * @return void
     */
    public function processForgotPassword()
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'forgot-password');
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'forgot-password');
        }

        $email = $this->sanitizeEmail($_POST['email'] ?? '');

        if (empty($email)) {
            Session::set('error_message', 'Παρακαλώ εισάγετε ένα έγκυρο email.');
            $this->redirect(BASE_URL . 'forgot-password');
        }

        try {
            // Αποστολή email επαναφοράς κωδικού πρόσβασης
            $result = $this->authModel->sendPasswordResetEmail($email);

            if ($result) {
                // Επιτυχής αποστολή
                Session::set('success_message', 'Ένα email με οδηγίες επαναφοράς του κωδικού πρόσβασης έχει σταλεί στη διεύθυνση που δώσατε.');
            } else {
                // Αποτυχία αποστολής
                Session::set('error_message', 'Δεν βρέθηκε λογαριασμός με αυτό το email.');
            }
        } catch (\Exception $e) {
            Logger::error('Exception in forgot password', [
                'email' => $email,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        $this->redirect(BASE_URL . 'login');
    }

    /**
     * Εμφανίζει τη φόρμα επαναφοράς κωδικού πρόσβασης
     *
     * @param string $code Ο κωδικός επαναφοράς
     * @return void
     */
    public function resetPassword($code)
    {
        if (empty($code)) {
            Session::set('error_message', 'Μη έγκυρος κωδικός επαναφοράς.');
            $this->redirect(BASE_URL . 'login');
        }

        // Φόρτωση της σελίδας επαναφοράς κωδικού πρόσβασης
        $this->view('reset-password', ['code' => $code]);
    }

    /**
     * Επεξεργάζεται το αίτημα επαναφοράς κωδικού πρόσβασης
     *
     * @return void
     */
    public function processResetPassword()
    {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . 'login');
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'login');
        }

        $code = $this->sanitize($_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($code)) {
            Session::set('error_message', 'Μη έγκυρος κωδικός επαναφοράς.');
            $this->redirect(BASE_URL . 'login');
        }

        if (empty($password) || strlen($password) < 8) {
            Session::set('error_message', 'Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 8 χαρακτήρες.');
            $this->redirect(BASE_URL . 'reset-password/' . $code);
        }

        if ($password !== $confirmPassword) {
            Session::set('error_message', 'Οι κωδικοί πρόσβασης δεν ταιριάζουν.');
            $this->redirect(BASE_URL . 'reset-password/' . $code);
        }

        try {
            // Επαναφορά του κωδικού πρόσβασης
            $result = $this->authModel->resetPassword($code, $password);

            if ($result) {
                // Επιτυχής επαναφορά
                Session::set('success_message', 'Ο κωδικός πρόσβασής σας έχει επαναφερθεί με επιτυχία. Μπορείτε τώρα να συνδεθείτε με τον νέο κωδικό πρόσβασης.');
            } else {
                // Αποτυχία επαναφοράς
                Session::set('error_message', 'Μη έγκυρος ή ληγμένος κωδικός επαναφοράς.');
            }
        } catch (\Exception $e) {
            Logger::error('Exception in reset password', [
                'code' => $code,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        $this->redirect(BASE_URL . 'login');
    }

    /**
     * Αλλάζει τον κωδικό πρόσβασης του συνδεδεμένου χρήστη
     *
     * @return void
     */
    public function changePassword()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('role')) {
            $this->redirect(BASE_URL . 'login');
        }

        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . (Session::get('role') === 'driver' ? 'drivers/profile' : 'companies/profile'));
        }

        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . (Session::get('role') === 'driver' ? 'drivers/profile' : 'companies/profile'));
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::set('error_message', 'Όλα τα πεδία είναι υποχρεωτικά.');
            $this->redirect(BASE_URL . (Session::get('role') === 'driver' ? 'drivers/profile' : 'companies/profile'));
        }

        if (strlen($newPassword) < 8) {
            Session::set('error_message', 'Ο νέος κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 8 χαρακτήρες.');
            $this->redirect(BASE_URL . (Session::get('role') === 'driver' ? 'drivers/profile' : 'companies/profile'));
        }

        if ($newPassword !== $confirmPassword) {
            Session::set('error_message', 'Οι κωδικοί πρόσβασης δεν ταιριάζουν.');
            $this->redirect(BASE_URL . (Session::get('role') === 'driver' ? 'drivers/profile' : 'companies/profile'));
        }

        try {
            // Αλλαγή του κωδικού πρόσβασης
            $result = $this->authModel->changePassword(Session::get('role'), Session::get('user_id'), $currentPassword, $newPassword);

            if ($result) {
                // Επιτυχής αλλαγή
                Session::set('success_message', 'Ο κωδικός πρόσβασής σας άλλαξε με επιτυχία.');
            } else {
                // Αποτυχία αλλαγής
                Session::set('error_message', 'Ο τρέχων κωδικός πρόσβασης είναι λανθασμένος.');
            }
        } catch (\Exception $e) {
            Logger::error('Exception in change password', [
                'user_id' => Session::get('user_id'),
                'role' => Session::get('role'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Session::set('error_message', 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά.');
        }

        $this->redirect(BASE_URL . (Session::get('role') === 'driver' ? 'drivers/profile' : 'companies/profile'));
    }

    /**
     * Συλλέγει τα δεδομένα εγγραφής από τη φόρμα
     *
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return array Τα δεδομένα εγγραφής
     */
    protected function collectRegistrationData($userType)
    {
        $data = [
            'email' => $this->sanitizeEmail($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($userType === 'company') {
            $data['company_name'] = $this->sanitize($_POST['company_name'] ?? '');
            $data['contact_person'] = $this->sanitize($_POST['contact_person'] ?? '');
            $data['phone'] = $this->sanitize($_POST['phone'] ?? '');
            $data['address'] = $this->sanitize($_POST['address'] ?? '');
            $data['city'] = $this->sanitize($_POST['city'] ?? '');
            $data['country'] = $this->sanitize($_POST['country'] ?? '');
            $data['postal_code'] = $this->sanitize($_POST['postal_code'] ?? '');
            $data['website'] = $this->sanitizeUrl($_POST['website'] ?? '');
            $data['description'] = $this->sanitizeHtml($_POST['description'] ?? '');
            $data['industry'] = $this->sanitize($_POST['industry'] ?? '');
            $data['company_size'] = $this->sanitize($_POST['company_size'] ?? '');
            $data['founded_year'] = $this->sanitize($_POST['founded_year'] ?? '');
        } else {
            $data['first_name'] = $this->sanitize($_POST['first_name'] ?? '');
            $data['last_name'] = $this->sanitize($_POST['last_name'] ?? '');
            $data['phone'] = $this->sanitize($_POST['phone'] ?? '');
            $data['address'] = $this->sanitize($_POST['address'] ?? '');
            $data['city'] = $this->sanitize($_POST['city'] ?? '');
            $data['country'] = $this->sanitize($_POST['country'] ?? '');
            $data['postal_code'] = $this->sanitize($_POST['postal_code'] ?? '');
            $data['date_of_birth'] = $this->sanitizeDate($_POST['birth_date'] ?? '');
            $data['legal_status'] = $this->sanitize($_POST['legal_status'] ?? '');
        }

        return $data;
    }

    /**
     * Επικυρώνει τα δεδομένα εγγραφής χρησιμοποιώντας την κλάση Validator
     *
     * @param array $data Τα δεδομένα εγγραφής
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return array Τα σφάλματα επικύρωσης
     */
    protected function validateRegistrationData($data, $userType)
    {
        // Δημιουργία του validator με τα δεδομένα της φόρμας
        $validator = new \Drivejob\Core\Validator($_POST);

        // Κοινοί έλεγχοι για όλους τους χρήστες
        $validator->required('email', 'Το email είναι υποχρεωτικό.')
            ->email('email', 'Παρακαλώ εισάγετε ένα έγκυρο email.')
            ->required('password', 'Ο κωδικός πρόσβασης είναι υποχρεωτικός.')
            ->minLength('password', 8, 'Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 8 χαρακτήρες.')
            ->matches('password', 'confirm_password', 'Οι κωδικοί πρόσβασης δεν ταιριάζουν.');

        // Ειδικοί έλεγχοι ανάλογα με τον τύπο του χρήστη
        if ($userType === 'company') {
            // Έλεγχοι για εταιρείες
            $validator->required('company_name', 'Το όνομα της εταιρείας είναι υποχρεωτικό.')
                ->required('contact_person', 'Το όνομα του υπεύθυνου επικοινωνίας είναι υποχρεωτικό.')
                ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        } else {
            // Έλεγχοι για οδηγούς
            $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
                ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
                ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        }

        // Επιστροφή των σφαλμάτων επικύρωσης
        return $validator->getErrors();
    }

    /**
     * Καθαρίζει ένα URL
     * 
     * @param string|null $url Το URL
     * @return string|null Το καθαρισμένο URL
     */
    protected function sanitizeUrl($url)
    {
        if (empty($url)) {
            return null;
        }
        $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
        if (filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
            return $sanitizedUrl;
        }
        return null;
    }
}
