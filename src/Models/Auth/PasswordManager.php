<?php

namespace Drivejob\Models\Auth;

use Drivejob\Core\Logger;
use PDO;

/**
 * Επαναφορά & αλλαγή κωδικών πρόσβασης (Πακέτο 5.3 — από το AuthModel).
 */
class PasswordManager
{
    private PDO $pdo;
    private AuthMailer $mailer;

    public function __construct(PDO $pdo, AuthMailer $mailer)
    {
        $this->pdo = $pdo;
        $this->mailer = $mailer;
    }

    /**
     * Δημιουργεί κωδικό επαναφοράς (1 ώρα ισχύς) και στέλνει το email.
     */
    public function sendPasswordResetEmail(string $email): bool
    {
        try {
            if (!AuthSupport::emailExists($this->pdo, $email)) {
                return false;
            }

            $resetCode = AuthSupport::generateCode();
            $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->updateResetCode($email, $resetCode, $resetExpires);
            $this->mailer->sendResetEmail($email, $resetCode);

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in sendPasswordResetEmail: ' . $e->getMessage());
            return false;
        }
    }

    /** Είναι έγκυρο (και σε ισχύ) το token επαναφοράς; */
    public function isValidResetToken(string $token): bool
    {
        try {
            foreach (['drivers', 'companies'] as $table) {
                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE reset_code = ? AND reset_expires > NOW()"
                );
                $stmt->execute([$token]);
                if ($stmt->fetchColumn() > 0) {
                    return true;
                }
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in isValidResetToken: ' . $e->getMessage());
            return false;
        }
    }

    /** Επαναφορά κωδικού με token. */
    public function resetPassword(string $resetCode, string $newPassword): bool
    {
        try {
            $user = $this->getUserByResetCode($resetCode);
            if (!$user) {
                return false;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->updatePassword($user['table'], (int) $user['id'], $hashedPassword);

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in resetPassword: ' . $e->getMessage());
            return false;
        }
    }

    /** Αλλαγή κωδικού από συνδεδεμένο χρήστη (με έλεγχο τρέχοντος). */
    public function changePassword(string $role, int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            if (!$this->verifyPassword($role, $userId, $currentPassword)) {
                return false;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $table = AuthSupport::tableByRole($role);
            return $this->updatePassword($table, $userId, $hashedPassword);
        } catch (\Exception $e) {
            Logger::error('Error in changePassword: ' . $e->getMessage());
            return false;
        }
    }

    // ---- internals -------------------------------------------------------

    private function updateResetCode(string $email, string $resetCode, string $resetExpires): bool
    {
        try {
            foreach (['drivers', 'companies'] as $table) {
                $stmt = $this->pdo->prepare(
                    "UPDATE {$table} SET reset_code = ?, reset_expires = ? WHERE email = ?"
                );
                $stmt->execute([$resetCode, $resetExpires, $email]);
            }
            return true;
        } catch (\PDOException $e) {
            Logger::error('Error in updateResetCode: ' . $e->getMessage());
            return false;
        }
    }

    private function getUserByResetCode(string $resetCode)
    {
        try {
            foreach (['drivers', 'companies'] as $table) {
                $stmt = $this->pdo->prepare(
                    "SELECT id FROM {$table} WHERE reset_code = ? AND reset_expires > NOW()"
                );
                $stmt->execute([$resetCode]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    return ['id' => $user['id'], 'table' => $table];
                }
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in getUserByResetCode: ' . $e->getMessage());
            return false;
        }
    }

    private function updatePassword(string $table, int $userId, string $hashedPassword): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$table} SET password = ?, reset_code = NULL, reset_expires = NULL WHERE id = ?"
            );
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updatePassword: ' . $e->getMessage());
            return false;
        }
    }

    private function verifyPassword(string $role, int $userId, string $password): bool
    {
        try {
            $table = AuthSupport::tableByRole($role);
            $stmt = $this->pdo->prepare("SELECT password FROM {$table} WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user && password_verify($password, $user['password']);
        } catch (\PDOException $e) {
            Logger::error('Error in verifyPassword: ' . $e->getMessage());
            return false;
        }
    }
}
