<?php

namespace Drivejob\Models\Auth;

use Drivejob\Core\Logger;
use PDO;

/**
 * Κοινά βοηθητικά του κυκλώματος auth (Πακέτο 5.3).
 */
class AuthSupport
{
    /** Τυχαίος κωδικός επαλήθευσης/επαναφοράς (32 hex). */
    public static function generateCode(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Όνομα πίνακα ανά ρόλο.
     *
     * @throws \InvalidArgumentException για άγνωστο ρόλο
     */
    public static function tableByRole(string $role): string
    {
        switch ($role) {
            case 'driver':
                return 'drivers';
            case 'company':
                return 'companies';
            case 'admin':
                return 'admins';
            default:
                throw new \InvalidArgumentException("Invalid role: $role");
        }
    }

    /**
     * Υπάρχει λογαριασμός (οδηγός ή εταιρεία) με αυτό το email;
     * Σε σφάλμα επιστρέφει true για ασφάλεια (μπλοκάρει διπλοεγγραφή).
     */
    public static function emailExists(PDO $pdo, string $email): bool
    {
        try {
            foreach (['drivers', 'companies'] as $table) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    return true;
                }
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in AuthSupport::emailExists: ' . $e->getMessage());
            return true;
        }
    }
}
