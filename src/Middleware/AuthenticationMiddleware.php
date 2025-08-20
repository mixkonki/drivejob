<?php

namespace Drivejob\Middleware;

use Drivejob\Core\Session;
use Drivejob\Core\RoleManager;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;

/**
 * Enhanced Authentication & Authorization Middleware
 * 
 * Ενοποιημένο middleware για authentication και permission-based authorization
 * Χρησιμοποιεί RoleManager για granular permission control
 */
class AuthenticationMiddleware
{
    private static $roleManager = null;

    /**
     * Initialize RoleManager instance
     */
    private static function getRoleManager()
    {
        if (self::$roleManager === null) {
            $pdo = Database::getInstance()->getConnection();
            self::$roleManager = new RoleManager($pdo);
        }
        return self::$roleManager;
    }

    /**
     * Απαιτεί ο χρήστης να είναι συνδεδεμένος
     * 
     * @param bool $isApiRequest Αν είναι API request (JSON response) ή web request (redirect)
     */
    public static function requireLogin($isApiRequest = false)
    {
        Session::start();

        // 🆕 Bearer token → hydrate session if valid
        if (!Session::has('user_id')) {
            // Robust Authorization header extraction
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $auth = $auth ?: ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

            if (!$auth && function_exists('getallheaders')) {
                $h = getallheaders();
                if (isset($h['Authorization'])) {
                    $auth = $h['Authorization'];
                }
                if (!$auth) {
                    foreach ($h as $k => $v) {
                        if (strtolower($k) === 'authorization') {
                            $auth = $v;
                            break;
                        }
                    }
                }
            }

            if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
                $token = $m[1];
                try {
                    if (class_exists('\\Drivejob\\Core\\Jwt')) {
                        $payload = \Drivejob\Core\Jwt::verify($token);
                        if ($payload && isset($payload['sub'], $payload['role'])) {
                            $_SESSION['user_id']     = (int)$payload['sub'];
                            $_SESSION['user_role']   = $payload['role'];
                            $_SESSION['user_name']   = $payload['name']   ?? '';
                            $_SESSION['user_email']  = $payload['email']  ?? '';
                            $_SESSION['is_verified'] = (bool)($payload['is_verified'] ?? false);
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore; θα γυρίσουμε 401 παρακάτω αν δεν υπάρχει session
                }
            }
        }

        if (!self::isAuthenticated()) {
            if ($isApiRequest) {
                self::sendUnauthorizedResponse('Authentication required');
                return false;
            } else {
                // Store current URL for redirect after login
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                header('Location: ' . BASE_URL . 'auth/login');
                exit();
            }
        }

        return true;
    }

    /**
     * Απαιτεί ο χρήστης να έχει συγκεκριμένο ρόλο
     * 
     * @param string|array $roles Ρόλος ή array ρόλων
     * @param bool $isApiRequest Αν είναι API request
     */
    public static function requireRole($roles, $isApiRequest = false)
    {
        if (!self::requireLogin($isApiRequest)) {
            return false;
        }

        // ✅ Shortcut: Αν ο ρόλος από Session/JWT ταιριάζει, επιτρέπουμε άμεσα
        $rolesArr    = is_array($roles) ? $roles : [$roles];
        $currentRole = \Drivejob\Core\Session::get('user_role');
        if ($currentRole && in_array($currentRole, $rolesArr, true)) {
            return true;
        }

        $userId = Session::get('user_id');
        $roleManager = self::getRoleManager();

        if (!$roleManager->hasRole($userId, $roles)) {
            if ($isApiRequest) {
                self::sendForbiddenResponse('Insufficient role privileges');
                return false;
            } else {
                header('Location: ' . BASE_URL . 'auth/access-denied');
                exit();
            }
        }

        return true;
    }

    /**
     * Απαιτεί ο χρήστης να έχει συγκεκριμένο permission
     * 
     * @param string|array $permissions Permission ή array permissions
     * @param bool $isApiRequest Αν είναι API request
     */
    public static function requirePermission($permissions, $isApiRequest = false)
    {
        if (!self::requireLogin($isApiRequest)) {
            return false;
        }

        $userId = Session::get('user_id');
        $roleManager = self::getRoleManager();

        if (!$roleManager->hasPermission($userId, $permissions)) {
            if ($isApiRequest) {
                self::sendForbiddenResponse('Insufficient permissions', [
                    'required_permissions' => is_array($permissions) ? $permissions : [$permissions],
                    'user_permissions' => $roleManager->loadUserPermissions($userId)
                ]);
                return false;
            } else {
                header('Location: ' . BASE_URL . 'auth/access-denied');
                exit();
            }
        }

        return true;
    }

    /**
     * Απαιτεί ο χρήστης να είναι επαληθευμένος
     * 
     * @param bool $isApiRequest Αν είναι API request
     */
    public static function requireVerified($isApiRequest = false)
    {
        if (!self::requireLogin($isApiRequest)) {
            return false;
        }

        if (!self::isVerified()) {
            if ($isApiRequest) {
                self::sendForbiddenResponse('Account verification required');
                return false;
            } else {
                header('Location: ' . BASE_URL . 'auth/verification-required');
                exit();
            }
        }

        return true;
    }

    /**
     * API-specific authentication check με role και permission support
     * 
     * @param array $allowedRoles Επιτρεπόμενοι ρόλοι
     * @param array $requiredPermissions Απαιτούμενα permissions
     * @param bool $requireVerification Αν απαιτείται verification
     * @return bool
     */
    public static function checkApiAccess(
        array $allowedRoles = [],
        array $requiredPermissions = [],
        bool $requireVerification = true
    ) {
        // Authentication check
        if (!self::requireLogin(true)) {
            return false;
        }

        // Verification check
        if ($requireVerification && !self::requireVerified(true)) {
            return false;
        }

        // Role check
        if (!empty($allowedRoles) && !self::requireRole($allowedRoles, true)) {
            return false;
        }

        // Permission check
        if (!empty($requiredPermissions) && !self::requirePermission($requiredPermissions, true)) {
            return false;
        }

        return true;
    }

    /**
     * Middleware για API endpoints με permission-based access control
     * 
     * Usage: AuthenticationMiddleware::apiGuard(['matching.view', 'jobs.read'])
     */
    public static function apiGuard(array $requiredPermissions = [], array $allowedRoles = [])
    {
        return self::checkApiAccess($allowedRoles, $requiredPermissions, true);
    }

    /**
     * Middleware για admin-only API endpoints
     */
    public static function requireAdmin($isApiRequest = true)
    {
        return self::requireRole(['admin'], $isApiRequest);
    }

    /**
     * Middleware για driver-only API endpoints
     */
    public static function requireDriver($isApiRequest = true)
    {
        return self::requireRole(['driver'], $isApiRequest);
    }

    /**
     * Middleware για company-only API endpoints
     */
    public static function requireCompany($isApiRequest = true)
    {
        return self::requireRole(['company'], $isApiRequest);
    }

    /**
     * Middleware για driver ή company API endpoints
     */
    public static function requireDriverOrCompany($isApiRequest = true)
    {
        return self::requireRole(['driver', 'company'], $isApiRequest);
    }

    /**
     * Advanced permission check με module.action format
     * 
     * @param string $permission Format: 'module.action' (e.g., 'matching.view', 'jobs.create')
     * @param bool $isApiRequest
     * @return bool
     */
    public static function requireModulePermission(string $permission, bool $isApiRequest = true): bool
    {
        if (!self::requireLogin($isApiRequest)) {
            return false;
        }

        $userId = Session::get('user_id');
        $roleManager = self::getRoleManager();

        // Check exact permission
        if ($roleManager->hasPermission($userId, $permission)) {
            return true;
        }

        // Check wildcard permissions (e.g., 'matching.*' covers 'matching.view')
        $parts = explode('.', $permission);
        if (count($parts) === 2) {
            $wildcardPermission = $parts[0] . '.*';
            if ($roleManager->hasPermission($userId, $wildcardPermission)) {
                return true;
            }
        }

        // Check admin override (admins have all permissions)
        if ($roleManager->hasRole($userId, 'admin')) {
            return true;
        }

        if ($isApiRequest) {
            self::sendForbiddenResponse('Insufficient permissions', [
                'required_permission' => $permission,
                'user_permissions' => $roleManager->loadUserPermissions($userId)
            ]);
            return false;
        } else {
            header('Location: ' . BASE_URL . 'auth/access-denied');
            exit();
        }
    }

    /**
     * Resource ownership check για API endpoints
     * 
     * @param string $resourceType Type of resource (e.g., 'job', 'profile', 'message')
     * @param int $resourceId ID του resource
     * @param bool $isApiRequest
     * @return bool
     */
    public static function requireResourceOwnership(string $resourceType, int $resourceId, bool $isApiRequest = true): bool
    {
        if (!self::requireLogin($isApiRequest)) {
            return false;
        }

        $userId = Session::get('user_id');
        $userRole = Session::get('user_role');

        // Admin can access everything
        if (self::hasRole(['admin'])) {
            return true;
        }

        $pdo = Database::getInstance()->getConnection();

        try {
            switch ($resourceType) {
                case 'job':
                    // Check if user owns the job listing
                    if ($userRole === 'company') {
                        $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ?");
                        $stmt->execute([$resourceId, $userId]);
                    } elseif ($userRole === 'driver') {
                        $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND driver_id = ?");
                        $stmt->execute([$resourceId, $userId]);
                    } else {
                        return false;
                    }
                    break;

                case 'profile':
                    // Check if user owns the profile
                    if ($userRole === 'driver') {
                        $stmt = $pdo->prepare("SELECT id FROM drivers WHERE id = ?");
                        $stmt->execute([$resourceId]);
                    } elseif ($userRole === 'company') {
                        $stmt = $pdo->prepare("SELECT id FROM companies WHERE id = ?");
                        $stmt->execute([$resourceId]);
                    } else {
                        return false;
                    }

                    // Additional check: resource must belong to current user
                    if ($resourceId !== $userId) {
                        return false;
                    }
                    break;

                case 'message':
                    // Check if user is sender or receiver of the message
                    $stmt = $pdo->prepare("
                        SELECT id FROM messages 
                        WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
                    ");
                    $stmt->execute([$resourceId, $userId, $userId]);
                    break;

                default:
                    // Unknown resource type
                    if ($isApiRequest) {
                        self::sendForbiddenResponse('Unknown resource type', ['resource_type' => $resourceType]);
                        return false;
                    } else {
                        header('Location: ' . BASE_URL . 'auth/access-denied');
                        exit();
                    }
            }

            if (!$stmt->fetch()) {
                if ($isApiRequest) {
                    self::sendForbiddenResponse('Resource access denied', [
                        'resource_type' => $resourceType,
                        'resource_id' => $resourceId
                    ]);
                    return false;
                } else {
                    header('Location: ' . BASE_URL . 'auth/access-denied');
                    exit();
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("Resource ownership check failed: " . $e->getMessage());
            if ($isApiRequest) {
                self::sendForbiddenResponse('Access check failed');
                return false;
            } else {
                header('Location: ' . BASE_URL . 'auth/access-denied');
                exit();
            }
        }
    }

    /**
     * Ελέγχει αν ο χρήστης είναι authenticated
     */
    public static function isAuthenticated(): bool
    {
        Session::start();
        return Session::has('user_id') && Session::has('user_role');
    }

    /**
     * Ελέγχει αν ο χρήστης είναι verified
     */
    public static function isVerified(): bool
    {
        return Session::get('is_verified', false);
    }

    /**
     * Ελέγχει αν ο χρήστης έχει ρόλο
     */
    public static function hasRole($roles): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        $userId = Session::get('user_id');
        $roleManager = self::getRoleManager();

        return $roleManager->hasRole($userId, $roles);
    }

    /**
     * Ελέγχει αν ο χρήστης έχει permission
     */
    public static function hasPermission($permissions): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        $userId = Session::get('user_id');
        $roleManager = self::getRoleManager();

        return $roleManager->hasPermission($userId, $permissions);
    }

    /**
     * Επιστρέφει τον τρέχοντα χρήστη
     */
    public static function getCurrentUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => Session::get('user_id'),
            'role' => Session::get('user_role'),
            'type' => Session::get('user_type'),
            'name' => Session::get('user_name'),
            'email' => Session::get('user_email'),
            'is_verified' => Session::get('is_verified', false)
        ];
    }

    /**
     * Στέλνει unauthorized response (401) με ενιαίο JSON schema
     */
    private static function sendUnauthorizedResponse(string $message, array $details = [])
    {
        http_response_code(401);
        header('Content-Type: application/json');

        $response = [
            'error' => [
                'code' => 401,
                'message' => $message
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        echo json_encode($response);
        exit();
    }

    /**
     * Στέλνει forbidden response (403) με ενιαίο JSON schema
     */
    private static function sendForbiddenResponse(string $message, array $details = [])
    {
        http_response_code(403);
        header('Content-Type: application/json');

        $response = [
            'error' => [
                'code' => 403,
                'message' => $message
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        echo json_encode($response);
        exit();
    }

    // =====================================================
    // LEGACY METHODS - Maintained για backward compatibility
    // =====================================================

    /**
     * @deprecated Use requireRole() instead
     */
    public static function requireAnyRole(array $roles)
    {
        return self::requireRole($roles, false);
    }

    /**
     * @deprecated Use requireVerified() instead
     */
    public static function requireVerifiedLegacy($pdo)
    {
        return self::requireVerified(false);
    }
}
