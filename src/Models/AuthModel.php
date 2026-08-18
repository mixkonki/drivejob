<?php

namespace Drivejob\Models;

use Drivejob\Core\Session;
use Drivejob\Models\Auth\AuthMailer;
use Drivejob\Models\Auth\PasswordManager;
use Drivejob\Models\Auth\UserAuthenticator;
use Drivejob\Models\Auth\UserRegistration;
use Drivejob\Services\EmailService;

/**
 * Facade αυθεντικοποίησης (Πακέτο 5.3).
 *
 * Σπάστηκε από 1.090 γραμμές σε 4 εστιασμένες κλάσεις στο Models/Auth:
 *   - UserAuthenticator → σύνδεση (drivers/companies/users+RBAC)
 *   - UserRegistration  → εγγραφή & επαλήθευση λογαριασμών
 *   - PasswordManager   → επαναφορά/αλλαγή κωδικών
 *   - AuthMailer        → emails επαλήθευσης & επαναφοράς
 *
 * Το δημόσιο API παραμένει ΙΔΙΟ — οι controllers δεν άλλαξαν.
 */
class AuthModel
{
    private $pdo;
    private ?EmailService $emailService = null;

    private ?UserAuthenticator $authenticator = null;
    private ?UserRegistration $registration = null;
    private ?PasswordManager $passwords = null;
    private ?AuthMailer $mailer = null;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;

        if (defined('SMTP_HOST') && defined('SMTP_PORT')) {
            $this->emailService = new EmailService(
                SMTP_HOST,
                SMTP_PORT,
                SMTP_USERNAME,
                SMTP_PASSWORD,
                SMTP_FROM_EMAIL,
                SMTP_FROM_NAME,
                EMAIL_DEBUG ?? false
            );
        }
    }

    // ---- Σύνδεση / αποσύνδεση -------------------------------------------

    public function authenticate($email, $password, $role = null)
    {
        return $this->authenticator()->authenticate($email, $password, $role);
    }

    public function isLoggedIn()
    {
        return Session::has('user_id') && Session::has('role');
    }

    public function hasRole($role)
    {
        return $this->isLoggedIn() && Session::get('role') === $role;
    }

    public function logout()
    {
        Session::destroy();
    }

    // ---- Εγγραφή & επαλήθευση -------------------------------------------

    public function registerDriver($data)
    {
        return $this->registration()->registerDriver($data);
    }

    public function registerCompany($data)
    {
        return $this->registration()->registerCompany($data);
    }

    public function emailExists($email)
    {
        return Auth\AuthSupport::emailExists($this->pdo, $email);
    }

    public function verifyAccount($code)
    {
        return $this->registration()->verifyAccount($code);
    }

    public function resendVerificationEmail($userId)
    {
        return $this->registration()->resendVerificationEmail((int) $userId);
    }

    // ---- Κωδικοί πρόσβασης ----------------------------------------------

    public function sendPasswordResetEmail($email)
    {
        return $this->passwords()->sendPasswordResetEmail($email);
    }

    /** Ιστορικό alias του sendPasswordResetEmail — ίδια λειτουργία. */
    public function sendPasswordResetLink($email)
    {
        return $this->passwords()->sendPasswordResetEmail($email);
    }

    public function isValidResetToken($token)
    {
        return $this->passwords()->isValidResetToken($token);
    }

    public function resetPassword($resetCode, $newPassword)
    {
        return $this->passwords()->resetPassword($resetCode, $newPassword);
    }

    public function changePassword($role, $userId, $currentPassword, $newPassword)
    {
        return $this->passwords()->changePassword($role, (int) $userId, $currentPassword, $newPassword);
    }

    // ---- lazy internals --------------------------------------------------

    private function authenticator(): UserAuthenticator
    {
        return $this->authenticator ??= new UserAuthenticator($this->pdo);
    }

    private function registration(): UserRegistration
    {
        return $this->registration ??= new UserRegistration($this->pdo, $this->mailer());
    }

    private function passwords(): PasswordManager
    {
        return $this->passwords ??= new PasswordManager($this->pdo, $this->mailer());
    }

    private function mailer(): AuthMailer
    {
        return $this->mailer ??= new AuthMailer($this->emailService);
    }
}
