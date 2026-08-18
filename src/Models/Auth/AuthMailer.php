<?php

namespace Drivejob\Models\Auth;

use Drivejob\Core\Logger;
use Drivejob\Services\EmailService;

/**
 * Emails του κυκλώματος auth: επαλήθευση λογαριασμού & επαναφορά κωδικού
 * (Πακέτο 5.3 — από το AuthModel).
 *
 * Αν δεν έχει ρυθμιστεί EmailService (π.χ. τοπικό dev χωρίς SMTP), κάνει
 * μόνο logging και επιστρέφει true — ίδια συμπεριφορά με πριν.
 */
class AuthMailer
{
    private ?EmailService $emailService;

    public function __construct(?EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /** Email επαλήθευσης λογαριασμού (σύνδεσμος 24ωρης ισχύος). */
    public function sendVerificationEmail(string $email, string $code, string $role): bool
    {
        try {
            if (!$this->emailService) {
                Logger::info("Verification email sent to $email with code $code for role $role (EmailService not available)");
                return true;
            }

            $verifyLink = BASE_URL . 'auth/verify/' . $code;
            $roleText = $role === 'driver' ? 'Οδηγός' : 'Εταιρεία';
            $subject = 'Επαλήθευση Λογαριασμού - DriveJob';

            $body = "
                <h2>Καλώς ήρθατε στο DriveJob!</h2>
                <p>Ευχαριστούμε για την εγγραφή σας ως <strong>$roleText</strong>.</p>
                <p>Για να ολοκληρώσετε την εγγραφή σας, παρακαλούμε επαληθεύστε τη διεύθυνση email σας.</p>
                <p style='text-align: center;'>
                    <a href='$verifyLink' class='button'>Επαλήθευση Email</a>
                </p>
                <p>Ή αντιγράψτε και επικολλήστε αυτόν τον σύνδεσμο στον browser σας:</p>
                <p style='word-break: break-all;'><a href='$verifyLink'>$verifyLink</a></p>
                <p><strong>Σημαντικό:</strong> Ο σύνδεσμος θα λήξει σε 24 ώρες.</p>
                <p>Αν δεν δημιουργήσατε λογαριασμό στο DriveJob, αγνοήστε αυτό το email.</p>";

            $result = $this->emailService->send($email, $subject, $this->wrap($body));

            if ($result) {
                Logger::info("Verification email sent successfully to $email for role $role");
            } else {
                Logger::error("Failed to send verification email to $email");
            }
            return $result;
        } catch (\Exception $e) {
            Logger::error('Error sending verification email: ' . $e->getMessage());
            return false;
        }
    }

    /** Email επαναφοράς κωδικού (σύνδεσμος 1 ώρας). */
    public function sendResetEmail(string $email, string $resetCode): bool
    {
        try {
            if (!$this->emailService) {
                Logger::info("Password reset email sent to $email with code $resetCode (EmailService not available)");
                return true;
            }

            $resetLink = BASE_URL . 'auth/password-reset/' . $resetCode;
            $subject = 'Επαναφορά Κωδικού Πρόσβασης - DriveJob';

            $body = "
                <h2>Επαναφορά Κωδικού Πρόσβασης</h2>
                <p>Λάβαμε αίτημα για επαναφορά του κωδικού πρόσβασής σας.</p>
                <p>Κάντε κλικ στο παρακάτω κουμπί για να επαναφέρετε τον κωδικό σας:</p>
                <p style='text-align: center;'>
                    <a href='$resetLink' class='button'>Επαναφορά Κωδικού</a>
                </p>
                <p>Ή αντιγράψτε και επικολλήστε αυτόν τον σύνδεσμο στον browser σας:</p>
                <p style='word-break: break-all;'><a href='$resetLink'>$resetLink</a></p>
                <p><strong>Σημαντικό:</strong> Ο σύνδεσμος θα λήξει σε 1 ώρα.</p>
                <p>Αν δεν ζητήσατε επαναφορά κωδικού, αγνοήστε αυτό το email. Ο λογαριασμός σας παραμένει ασφαλής.</p>";

            $result = $this->emailService->send($email, $subject, $this->wrap($body));

            if ($result) {
                Logger::info("Password reset email sent successfully to $email");
            } else {
                Logger::error("Failed to send password reset email to $email");
            }
            return $result;
        } catch (\Exception $e) {
            Logger::error('Error sending reset email: ' . $e->getMessage());
            return false;
        }
    }

    /** Κοινό HTML περιτύλιγμα (header/footer/στυλ) των auth emails. */
    private function wrap(string $body): string
    {
        $year = date('Y');
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #c62828; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .button { display: inline-block; padding: 12px 30px; background-color: #c62828; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>DriveJob</h1>
                </div>
                <div class='content'>
                    $body
                </div>
                <div class='footer'>
                    <p>&copy; $year DriveJob. Όλα τα δικαιώματα κατοχυρωμένα.</p>
                    <p>Αυτό είναι ένα αυτοματοποιημένο email. Παρακαλούμε μην απαντήσετε.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
