<?php

namespace Drivejob\Models\Auth;

use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use PDO;

/**
 * Εγγραφή & επαλήθευση λογαριασμών (Πακέτο 5.3 — από το AuthModel).
 */
class UserRegistration
{
    private PDO $pdo;
    private AuthMailer $mailer;

    public function __construct(PDO $pdo, AuthMailer $mailer)
    {
        $this->pdo = $pdo;
        $this->mailer = $mailer;
    }

    /** Εγγραφή νέου οδηγού. Επιστρέφει το ID ή false. */
    public function registerDriver(array $data)
    {
        return $this->register('drivers', 'driver', $data);
    }

    /** Εγγραφή νέας εταιρείας. Επιστρέφει το ID ή false. */
    public function registerCompany(array $data)
    {
        return $this->register('companies', 'company', $data);
    }

    /** Κοινή ροή εγγραφής οδηγού/εταιρείας. */
    private function register(string $table, string $role, array $data)
    {
        try {
            if (AuthSupport::emailExists($this->pdo, $data['email'])) {
                return false;
            }

            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['verification_code'] = AuthSupport::generateCode();
            $data['verification_expires'] = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $data['is_verified'] = 0;
            $data['is_active'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');

            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $query = "INSERT INTO {$table} (" . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $this->pdo->prepare($query);

            if ($stmt->execute(array_values($data))) {
                $userId = $this->pdo->lastInsertId();
                $this->mailer->sendVerificationEmail($data['email'], $data['verification_code'], $role);
                return $userId;
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error("Error in register ({$role}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Επαλήθευση λογαριασμού από κωδικό. Επιστρέφει κανονικοποιημένα
     * στοιχεία χρήστη ή false.
     */
    public function verifyAccount(string $code)
    {
        try {
            $driver = $this->verifyIn('drivers', $code);
            if ($driver) {
                return [
                    'user_id' => $driver['id'],
                    'role' => 'driver',
                    'email' => $driver['email'],
                    'name' => $driver['first_name'] . ' ' . $driver['last_name'],
                ];
            }

            $company = $this->verifyIn('companies', $code);
            if ($company) {
                return [
                    'user_id' => $company['id'],
                    'role' => 'company',
                    'email' => $company['email'],
                    'name' => $company['company_name'],
                ];
            }

            return false;
        } catch (\Exception $e) {
            Logger::error('Error in verifyAccount: ' . $e->getMessage());
            return false;
        }
    }

    /** Επαλήθευση σε συγκεκριμένο πίνακα και ενεργοποίηση λογαριασμού. */
    private function verifyIn(string $table, string $code)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$table} WHERE verification_code = ? AND verification_expires > NOW()"
            );
            $stmt->execute([$code]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $update = $this->pdo->prepare(
                    "UPDATE {$table} SET is_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = ?"
                );
                $update->execute([$user['id']]);
                return $user;
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error("Error in verifyIn ({$table}): " . $e->getMessage());
            return false;
        }
    }

    /** Επαναποστολή email επαλήθευσης στον συνδεδεμένο χρήστη. */
    public function resendVerificationEmail(int $userId): bool
    {
        try {
            $role = Session::get('role');
            $table = AuthSupport::tableByRole($role);

            $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Logger::error("User not found for resendVerificationEmail: $userId");
                return false;
            }

            $verificationCode = AuthSupport::generateCode();
            $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $update = $this->pdo->prepare(
                "UPDATE {$table} SET verification_code = ?, verification_expires = ? WHERE id = ?"
            );
            $update->execute([$verificationCode, $verificationExpires, $userId]);

            return $this->mailer->sendVerificationEmail($user['email'], $verificationCode, $role);
        } catch (\Exception $e) {
            Logger::error('Error in resendVerificationEmail: ' . $e->getMessage());
            return false;
        }
    }
}
