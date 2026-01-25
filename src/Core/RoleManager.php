<?php

namespace Drivejob\Core;

use PDO;
use Drivejob\Core\Exceptions\NotFoundException;
use Drivejob\Core\Exceptions\ForbiddenException;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Διαχειριστής ρόλων και δικαιωμάτων
 */
class RoleManager
{
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    protected $pdo;

    /**
     * @var array Τα δικαιώματα του τρέχοντος χρήστη
     */
    protected $userPermissions = [];

    /**
     * @var array Οι ρόλοι του τρέχοντος χρήστη
     */
    protected $userRoles = [];

    /**
     * @var array Cache για τα δικαιώματα των ρόλων
     */
    protected static $rolePermissionsCache = [];

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: Container::getInstance()->get('pdo');
    }

    /**
     * Ελέγχει αν ο χρήστης έχει τον συγκεκριμένο ρόλο
     *
     * @param int $userId Το ID του χρήστη
     * @param string|array $roleName Το όνομα του ρόλου ή ένας πίνακας με ονόματα ρόλων
     * @return bool Αν ο χρήστης έχει τον ρόλο
     */
    public function hasRole($userId, $roleName)
    {
        // Φόρτωση των ρόλων του χρήστη αν δεν έχουν φορτωθεί
        if (!isset($this->userRoles[$userId])) {
            $this->loadUserRoles($userId);
        }

        // Έλεγχος για πολλαπλούς ρόλους
        if (is_array($roleName)) {
            foreach ($roleName as $name) {
                if (in_array($name, $this->userRoles[$userId])) {
                    return true;
                }
            }
            return false;
        }

        // Έλεγχος για έναν ρόλο
        return in_array($roleName, $this->userRoles[$userId]);
    }

    /**
     * Ελέγχει αν ο χρήστης έχει το συγκεκριμένο δικαίωμα
     *
     * @param int $userId Το ID του χρήστη
     * @param string|array $permissionName Το όνομα του δικαιώματος ή ένας πίνακας με ονόματα δικαιωμάτων
     * @return bool Αν ο χρήστης έχει το δικαίωμα
     */
    public function hasPermission($userId, $permissionName)
    {
        // Φόρτωση των δικαιωμάτων του χρήστη αν δεν έχουν φορτωθεί
        if (!isset($this->userPermissions[$userId])) {
            $this->loadUserPermissions($userId);
        }

        // Έλεγχος για πολλαπλά δικαιώματα
        if (is_array($permissionName)) {
            foreach ($permissionName as $name) {
                if (in_array($name, $this->userPermissions[$userId])) {
                    return true;
                }
            }
            return false;
        }

        // Έλεγχος για ένα δικαίωμα
        return in_array($permissionName, $this->userPermissions[$userId]);
    }

    /**
     * Φορτώνει τους ρόλους του χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @return array Οι ρόλοι του χρήστη
     */
    public function loadUserRoles($userId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.name
                FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Αποθήκευση των ρόλων στο cache
            $this->userRoles[$userId] = $roles;

            return $roles;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη φόρτωση των ρόλων του χρήστη", 0, $e, [
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Φορτώνει τα δικαιώματα του χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @return array Τα δικαιώματα του χρήστη
     */
    public function loadUserPermissions($userId)
    {
        try {
            // Φόρτωση των ρόλων του χρήστη αν δεν έχουν φορτωθεί
            if (!isset($this->userRoles[$userId])) {
                $this->loadUserRoles($userId);
            }

            // Αν ο χρήστης δεν έχει ρόλους, επιστροφή κενού πίνακα
            if (empty($this->userRoles[$userId])) {
                $this->userPermissions[$userId] = [];
                return [];
            }

            // Φόρτωση των δικαιωμάτων για κάθε ρόλο
            $permissions = [];
            foreach ($this->userRoles[$userId] as $roleName) {
                $rolePermissions = $this->getRolePermissions($roleName);
                $permissions = array_merge($permissions, $rolePermissions);
            }

            // Αφαίρεση των διπλότυπων
            $permissions = array_unique($permissions);

            // Αποθήκευση των δικαιωμάτων στο cache
            $this->userPermissions[$userId] = $permissions;

            return $permissions;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη φόρτωση των δικαιωμάτων του χρήστη", 0, $e, [
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Επιστρέφει τα δικαιώματα ενός ρόλου
     *
     * @param string $roleName Το όνομα του ρόλου
     * @return array Τα δικαιώματα του ρόλου
     */
    public function getRolePermissions($roleName)
    {
        // Έλεγχος αν τα δικαιώματα του ρόλου είναι στο cache
        if (isset(self::$rolePermissionsCache[$roleName])) {
            return self::$rolePermissionsCache[$roleName];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT p.name
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN roles r ON r.id = rp.role_id
                WHERE r.name = :role_name
            ");
            $stmt->execute(['role_name' => $roleName]);
            $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Αποθήκευση των δικαιωμάτων στο cache
            self::$rolePermissionsCache[$roleName] = $permissions;

            return $permissions;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη φόρτωση των δικαιωμάτων του ρόλου", 0, $e, [
                'role_name' => $roleName
            ]);
        }
    }

    /**
     * Προσθέτει έναν ρόλο σε έναν χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @param string $roleName Το όνομα του ρόλου
     * @return bool Αν η προσθήκη ήταν επιτυχής
     */
    public function addRoleToUser($userId, $roleName)
    {
        try {
            // Εύρεση του ID του ρόλου
            $stmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = :name");
            $stmt->execute(['name' => $roleName]);
            $roleId = $stmt->fetchColumn();

            if (!$roleId) {
                throw new NotFoundException("Ο ρόλος $roleName δεν βρέθηκε");
            }

            // Προσθήκη του ρόλου στον χρήστη
            $stmt = $this->pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                VALUES (:user_id, :role_id)
                ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
            ");
            $result = $stmt->execute([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);

            // Ενημέρωση του cache
            if ($result && isset($this->userRoles[$userId])) {
                $this->userRoles[$userId][] = $roleName;
                $this->userRoles[$userId] = array_unique($this->userRoles[$userId]);

                // Επαναφόρτωση των δικαιωμάτων
                $this->loadUserPermissions($userId);
            }

            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την προσθήκη ρόλου στον χρήστη", 0, $e, [
                'user_id' => $userId,
                'role_name' => $roleName
            ]);
        }
    }

    /**
     * Αφαιρεί έναν ρόλο από έναν χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @param string $roleName Το όνομα του ρόλου
     * @return bool Αν η αφαίρεση ήταν επιτυχής
     */
    public function removeRoleFromUser($userId, $roleName)
    {
        try {
            // Εύρεση του ID του ρόλου
            $stmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = :name");
            $stmt->execute(['name' => $roleName]);
            $roleId = $stmt->fetchColumn();

            if (!$roleId) {
                throw new NotFoundException("Ο ρόλος $roleName δεν βρέθηκε");
            }

            // Αφαίρεση του ρόλου από τον χρήστη
            $stmt = $this->pdo->prepare("
                DELETE FROM user_roles
                WHERE user_id = :user_id AND role_id = :role_id
            ");
            $result = $stmt->execute([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);

            // Ενημέρωση του cache
            if ($result && isset($this->userRoles[$userId])) {
                $this->userRoles[$userId] = array_diff($this->userRoles[$userId], [$roleName]);

                // Επαναφόρτωση των δικαιωμάτων
                $this->loadUserPermissions($userId);
            }

            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την αφαίρεση ρόλου από τον χρήστη", 0, $e, [
                'user_id' => $userId,
                'role_name' => $roleName
            ]);
        }
    }

    /**
     * Επιστρέφει όλους τους ρόλους
     *
     * @return array Όλοι οι ρόλοι
     */
    public function getAllRoles()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT id, name, display_name, description, is_system
                FROM roles
                ORDER BY name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη φόρτωση των ρόλων", 0, $e);
        }
    }

    /**
     * Επιστρέφει όλα τα δικαιώματα
     *
     * @return array Όλα τα δικαιώματα
     */
    public function getAllPermissions()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT id, name, display_name, description, category
                FROM permissions
                ORDER BY category, name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη φόρτωση των δικαιωμάτων", 0, $e);
        }
    }

    /**
     * Επιστρέφει τα δικαιώματα ενός ρόλου
     *
     * @param int $roleId Το ID του ρόλου
     * @return array Τα δικαιώματα του ρόλου
     */
    public function getRolePermissionsById($roleId)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.name, p.display_name, p.description, p.category
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id
                ORDER BY p.category, p.name
            ");
            $stmt->execute(['role_id' => $roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη φόρτωση των δικαιωμάτων του ρόλου", 0, $e, [
                'role_id' => $roleId
            ]);
        }
    }

    /**
     * Προσθέτει ένα δικαίωμα σε έναν ρόλο
     *
     * @param int $roleId Το ID του ρόλου
     * @param int $permissionId Το ID του δικαιώματος
     * @return bool Αν η προσθήκη ήταν επιτυχής
     */
    public function addPermissionToRole($roleId, $permissionId)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO role_permissions (role_id, permission_id)
                VALUES (:role_id, :permission_id)
                ON DUPLICATE KEY UPDATE role_id = VALUES(role_id)
            ");
            $result = $stmt->execute([
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);

            // Καθαρισμός του cache
            $this->clearRolePermissionsCache();

            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την προσθήκη δικαιώματος στον ρόλο", 0, $e, [
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);
        }
    }

    /**
     * Αφαιρεί ένα δικαίωμα από έναν ρόλο
     *
     * @param int $roleId Το ID του ρόλου
     * @param int $permissionId Το ID του δικαιώματος
     * @return bool Αν η αφαίρεση ήταν επιτυχής
     */
    public function removePermissionFromRole($roleId, $permissionId)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM role_permissions
                WHERE role_id = :role_id AND permission_id = :permission_id
            ");
            $result = $stmt->execute([
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);

            // Καθαρισμός του cache
            $this->clearRolePermissionsCache();

            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την αφαίρεση δικαιώματος από τον ρόλο", 0, $e, [
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);
        }
    }

    /**
     * Καθαρίζει το cache των δικαιωμάτων των ρόλων
     *
     * @return void
     */
    public function clearRolePermissionsCache()
    {
        self::$rolePermissionsCache = [];
        $this->userPermissions = [];
    }

    /**
     * Καθαρίζει το cache των ρόλων και δικαιωμάτων ενός χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @return void
     */
    public function clearUserCache($userId)
    {
        unset($this->userRoles[$userId]);
        unset($this->userPermissions[$userId]);
    }

    /**
     * Δημιουργεί έναν νέο ρόλο
     *
     * @param string $name Το όνομα του ρόλου
     * @param string $displayName Το εμφανιζόμενο όνομα του ρόλου
     * @param string $description Η περιγραφή του ρόλου
     * @param bool $isSystem Αν ο ρόλος είναι συστήματος
     * @return int Το ID του νέου ρόλου
     */
    public function createRole($name, $displayName, $description = '', $isSystem = false)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO roles (name, display_name, description, is_system)
                VALUES (:name, :display_name, :description, :is_system)
            ");
            $stmt->execute([
                'name' => $name,
                'display_name' => $displayName,
                'description' => $description,
                'is_system' => $isSystem ? 1 : 0
            ]);

            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία του ρόλου", 0, $e, [
                'name' => $name,
                'display_name' => $displayName
            ]);
        }
    }

    /**
     * Ενημερώνει έναν ρόλο
     *
     * @param int $roleId Το ID του ρόλου
     * @param string $displayName Το εμφανιζόμενο όνομα του ρόλου
     * @param string $description Η περιγραφή του ρόλου
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     */
    public function updateRole($roleId, $displayName, $description = '')
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE roles
                SET display_name = :display_name, description = :description
                WHERE id = :id
            ");
            $result = $stmt->execute([
                'id' => $roleId,
                'display_name' => $displayName,
                'description' => $description
            ]);

            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση του ρόλου", 0, $e, [
                'role_id' => $roleId,
                'display_name' => $displayName
            ]);
        }
    }

    /**
     * Διαγράφει έναν ρόλο
     *
     * @param int $roleId Το ID του ρόλου
     * @return bool Αν η διαγραφή ήταν επιτυχής
     */
    public function deleteRole($roleId)
    {
        try {
            // Έλεγχος αν ο ρόλος είναι συστήματος
            $stmt = $this->pdo->prepare("SELECT is_system FROM roles WHERE id = :id");
            $stmt->execute(['id' => $roleId]);
            $isSystem = $stmt->fetchColumn();

            if ($isSystem) {
                throw new ForbiddenException("Δεν μπορείτε να διαγράψετε έναν ρόλο συστήματος");
            }

            $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = :id");
            $result = $stmt->execute(['id' => $roleId]);

            // Καθαρισμός του cache
            $this->clearRolePermissionsCache();

            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή του ρόλου", 0, $e, [
                'role_id' => $roleId
            ]);
        }
    }
}
