<?php

namespace Drivejob\Models\Auth;

use Drivejob\Core\Logger;
use PDO;

/**
 * Αυθεντικοποίηση χρηστών (Πακέτο 5.3 — από το AuthModel).
 *
 * Οδηγοί και εταιρείες αυθεντικοποιούνται από τους δικούς τους πίνακες
 * (drivers/companies), οι διαχειριστές από τον πίνακα users με ρόλο από
 * στήλη (role/user_type) ή από τα RBAC tables (user_roles + roles).
 */
class UserAuthenticator
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Αυθεντικοποίηση με προαιρετικό ρόλο. Επιστρέφει κανονικοποιημένο
     * array session-στοιχείων ή false.
     */
    public function authenticate(string $email, string $password, ?string $role = null)
    {
        try {
            Logger::debug('UserAuthenticator::authenticate called', [
                'email' => $email,
                'role' => $role,
                'session_id' => session_id(),
            ]);

            if ($role === 'driver' || $role === null) {
                $driver = $this->authenticateDriver($email, $password);
                if ($driver) {
                    return [
                        'user_id' => $driver['id'],
                        'role' => 'driver',
                        'email' => $driver['email'],
                        'name' => $driver['first_name'] . ' ' . $driver['last_name'],
                        'is_verified' => $driver['is_verified'],
                        'is_active' => $driver['is_verified'], // το σχήμα δεν έχει is_active
                    ];
                }
            }

            if ($role === 'company' || $role === null) {
                $company = $this->authenticateCompany($email, $password);
                if ($company) {
                    return [
                        'user_id' => $company['id'],
                        'role' => 'company',
                        'email' => $company['email'],
                        'name' => $company['company_name'],
                        'is_verified' => $company['is_verified'],
                        'is_active' => $company['is_verified'],
                    ];
                }
            }

            if ($role === 'admin' || $role === null) {
                $admin = $this->authenticateAdmin($email, $password);
                if ($admin) {
                    return [
                        'user_id' => $admin['id'],
                        'role' => $admin['role'] ?? $admin['user_type'] ?? 'admin',
                        'email' => $admin['email'],
                        'name' => 'Administrator',
                        'is_verified' => 1,
                        'is_active' => 1,
                    ];
                }
            }

            return false;
        } catch (\Exception $e) {
            Logger::error('Error in authenticate: ' . $e->getMessage());
            return false;
        }
    }

    private function authenticateDriver(string $email, string $password)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM drivers WHERE email = ?');
            $stmt->execute([$email]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            Logger::debug('Driver query result', ['driver_found' => !empty($driver)]);

            if ($driver && password_verify($password, $driver['password'])) {
                $this->updateLastLogin('drivers', (int) $driver['id']);
                return $driver;
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in authenticateDriver: ' . $e->getMessage());
            return false;
        }
    }

    private function authenticateCompany(string $email, string $password)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM companies WHERE email = ?');
            $stmt->execute([$email]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($company && password_verify($password, $company['password'])) {
                $this->updateLastLogin('companies', (int) $company['id']);
                return $company;
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in authenticateCompany: ' . $e->getMessage());
            return false;
        }
    }

    private function authenticateAdmin(string $email, string $password)
    {
        try {
            // Ο πίνακας users δεν έχει εγγυημένη στήλη ρόλου — ο ρόλος
            // ελέγχεται μετά (στήλη role/user_type ή RBAC lookup)
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE (email = ? OR username = ?)');
            $stmt->execute([$email, $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            /*
             * ══════════════════════════════════════════════════════════════
             *  ΤΟ RBAC ΕΙΝΑΙ Η ΠΗΓΗ ΑΛΗΘΕΙΑΣ — ΟΧΙ Η ΣΤΗΛΗ user_type
             * ══════════════════════════════════════════════════════════════
             *
             * Ο κώδικας ρωτούσε πρώτα τις στήλες του πίνακα:
             *
             *     $adminRole = $admin['role'] ?? $admin['user_type'] ?? null;
             *     if ($adminRole === null) { ...τότε μόνο RBAC lookup... }
             *
             * Ο πίνακας `users` ΔΕΝ έχει στήλη `role` (η αρχιτεκτονική το
             * λέει ρητά: οι ρόλοι ζουν στα `user_roles` + `roles`). Έχει
             * όμως μια παλιά στήλη `user_type` — και για τον λογαριασμό
             * admin@drivejob.gr αυτή περιείχε **'driver'**.
             *
             * Αποτέλεσμα: το `$adminRole` γινόταν 'driver', δεν ήταν null,
             * άρα το RBAC lookup ΔΕΝ ΕΚΤΕΛΟΥΝΤΑΝ ΠΟΤΕ. Ο επόμενος έλεγχος
             * («είναι admin ή super_admin;») απέτυχε, και ο διαχειριστής
             * δεν μπορούσε να συνδεθεί — με σωστό email, σωστό συνθηματικό
             * και σωστή εγγραφή `admin` στον πίνακα user_roles.
             *
             * Η σειρά αντιστράφηκε: ρωτάμε ΠΡΩΤΑ το RBAC, που είναι η
             * επίσημη πηγή, και πέφτουμε στις στήλες μόνο αν δεν υπάρχει
             * καμία εγγραφή ρόλου εκεί.
             */
            $adminRole = null;

            if ($admin) {
                try {
                    $roleStmt = $this->pdo->prepare(
                        'SELECT r.name FROM user_roles ur
                         JOIN roles r ON r.id = ur.role_id
                         WHERE ur.user_id = ?
                         ORDER BY FIELD(r.name, "super_admin", "admin") DESC
                         LIMIT 1'
                    );
                    $roleStmt->execute([$admin['id']]);
                    $adminRole = $roleStmt->fetchColumn() ?: null;
                } catch (\PDOException $rbacError) {
                    Logger::debug('RBAC role lookup failed: ' . $rbacError->getMessage());
                }

                // Εφεδρεία μόνο όταν το RBAC δεν έχει τίποτα να πει.
                if ($adminRole === null) {
                    $adminRole = $admin['role'] ?? $admin['user_type'] ?? null;
                }
            }
            if ($admin && !in_array($adminRole, ['admin', 'super_admin'], true)) {
                $admin = false;
            }
            if ($adminRole === 'super_admin') {
                $adminRole = 'admin'; // κανονικοποίηση για το session
            }

            Logger::debug('Admin query result', [
                'admin_found' => !empty($admin),
                'role' => $admin ? $adminRole : null,
            ]);

            if ($admin) {
                $storedHash = $admin['password'] ?? $admin['password_hash'] ?? '';
                if ($storedHash !== '' && password_verify($password, $storedHash)) {
                    $this->updateLastLogin('users', (int) $admin['id']);
                    $admin['role'] = $adminRole;
                    return $admin;
                }
            }
            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in authenticateAdmin: ' . $e->getMessage());
            return false;
        }
    }

    private function updateLastLogin(string $table, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE {$table} SET last_login = NOW() WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updateLastLogin: ' . $e->getMessage());
            return false;
        }
    }
}
