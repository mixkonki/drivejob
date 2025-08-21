<?php

namespace DriveJob\RBAC;

use PDO;
use DriveJob\RBAC\DB;

final class RBAC
{
    /** Per-request cache: userId => [perm => true, ...] */
    private static array $permCache = [];

    /** Φόρτωσε permissions χρήστη (idempotent). */
    public static function primePermissions(int $userId): void
    {
        if (isset(self::$permCache[$userId])) return;
        $sql = "
            SELECT DISTINCT p.name
            FROM user_roles ur
            JOIN role_permissions rp ON rp.role_id = ur.role_id
            JOIN permissions p       ON p.id       = rp.permission_id
            WHERE ur.user_id = :uid
        ";
        $st = DB::pdo()->prepare($sql);
        $st->execute([':uid' => $userId]);
        $perms = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $perms[$row['name']] = true;
        }
        self::$permCache[$userId] = $perms;
    }

    /** Όλα τα permissions από cache. */
    public static function getUserPermissions(int $userId): array
    {
        self::primePermissions($userId);
        return array_keys(self::$permCache[$userId] ?? []);
    }

    /** Έλεγχος permission (από cache). */
    public static function userCan(int $userId, string $permission): bool
    {
        self::primePermissions($userId);
        return isset(self::$permCache[$userId][$permission]);
    }

    /** Απαίτησε συγκεκριμένο permission (403 αν λείπει). */
    public static function requirePermission(int $userId, string $permission): void
    {
        if (!self::userCan($userId, $permission)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Forbidden', 'missing_permission' => $permission], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** Απαίτησε οποιοδήποτε από λίστα (403 αν κανένα). */
    public static function requireAny(int $userId, array $permissions): void
    {
        foreach ($permissions as $perm) {
            if (self::userCan($userId, $perm)) return;
        }
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden', 'missing_any_of' => $permissions], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Απαίτησε όλα (403 αν λείπει έστω ένα). */
    public static function requireAll(int $userId, array $permissions): void
    {
        foreach ($permissions as $perm) {
            if (!self::userCan($userId, $perm)) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Forbidden', 'missing_permission' => $perm], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    /**
     * Own vs Any guard:
     * Αν έχει permAny -> περνάει.
     * Αλλιώς απαιτεί permOwn ΚΑΙ επιτυχία στο $ownerCheck($userId).
     */
    public static function requireOwnerOrAny(int $userId, string $permOwn, string $permAny, callable $ownerCheck): void
    {
        if (self::userCan($userId, $permAny)) return;
        if (!self::userCan($userId, $permOwn) || !$ownerCheck($userId)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Forbidden',
                'missing' => [$permAny, $permOwn],
                'ownership' => 'failed'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /** Switch primary ρόλου (proc ή fallback UPDATE) + καθάρισμα cache. */
    public static function setPrimaryRole(int $userId, int $roleId): void
    {
        try {
            $st = DB::pdo()->prepare("CALL sp_set_primary_role(:uid, :rid)");
            $st->execute([':uid' => $userId, ':rid' => $roleId]);
        } catch (\Throwable $e) {
            $sql = "
                UPDATE user_roles
                SET is_primary = CASE WHEN role_id = :rid THEN 1 ELSE 0 END
                WHERE user_id = :uid
            ";
            $st = DB::pdo()->prepare($sql);
            $st->execute([':rid' => $roleId, ':uid' => $userId]);
        }
        unset(self::$permCache[$userId]);
    }

    /** Πάρε τρέχοντα primary ρόλο. */
    public static function getPrimaryRole(int $userId): ?array
    {
        $sql = "
            SELECT r.id, r.name
            FROM user_roles ur
            JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = :uid AND ur.is_primary = 1
            LIMIT 1
        ";
        $st = DB::pdo()->prepare($sql);
        $st->execute([':uid' => $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
