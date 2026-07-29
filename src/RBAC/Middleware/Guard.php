<?php

namespace DriveJob\RBAC\Middleware;

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Logger;
use DriveJob\RBAC\Util\Http;

final class Guard
{
    public static function requirePermission(int $userId, string $permission, array $ctx = []): void
    {
        if (!RBAC::userCan($userId, $permission)) {
            Logger::deny("missing_permission", ["uid" => $userId, "required" => $permission] + $ctx);
            Http::jsonError("Missing permission", ["required" => $permission] + $ctx, 403);
            exit;
        }
    }

    public static function requireAny(int $userId, array $permissions, array $ctx = []): void
    {
        foreach ($permissions as $perm) {
            if (RBAC::userCan($userId, $perm)) return;
        }
        Logger::deny("missing_any", ["uid" => $userId, "required_any" => $permissions] + $ctx);
        Http::jsonError("Missing any of required permissions", ["required_any" => $permissions] + $ctx, 403);
        exit;
    }

    public static function requireAll(int $userId, array $permissions, array $ctx = []): void
    {
        foreach ($permissions as $perm) {
            if (!RBAC::userCan($userId, $perm)) {
                Logger::deny("missing_all", ["uid" => $userId, "missing" => $perm, "required_all" => $permissions] + $ctx);
                Http::jsonError("Missing permission", ["missing" => $perm, "required_all" => $permissions] + $ctx, 403);
                exit;
            }
        }
    }

    /**
     * Own vs Any:
     * - Αν έχει permAny -> OK
     * - Αλλιώς απαιτεί permOwn ΚΑΙ επιτυχία στο $ownerCheck($userId)
     */
    public static function requireOwnerOrAny(int $userId, string $permOwn, string $permAny, callable $ownerCheck, array $ctx = []): void
    {
        if (RBAC::userCan($userId, $permAny)) return;
        $isOwner = $ownerCheck($userId);
        if (!RBAC::userCan($userId, $permOwn) || !$isOwner) {
            Logger::deny("owner_or_any_failed", ["uid" => $userId, "perm_own" => $permOwn, "perm_any" => $permAny, "owner" => $isOwner] + $ctx);
            Http::jsonError("Owner or any permission required", [
                "required_any" => $permAny,
                "required_own" => $permOwn,
                "owner" => $isOwner
            ] + $ctx, 403);
            exit;
        }
    }
}
