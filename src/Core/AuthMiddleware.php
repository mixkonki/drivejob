<?php

namespace Drivejob\Core;

use Drivejob\Core\Exceptions\AuthException;
use Drivejob\Core\Exceptions\ForbiddenException;
use Drivejob\Core\Exceptions\BadRequestException;

class AuthMiddleware
{
    /**
     * @var RoleManager Ο διαχειριστής ρόλων
     */
    protected static $roleManager;

    /**
     * Επιστρέφει τον διαχειριστή ρόλων
     *
     * @return RoleManager Ο διαχειριστής ρόλων
     */
    public static function getRoleManager()
    {
        if (!isset(self::$roleManager)) {
            self::$roleManager = new RoleManager();
        }

        return self::$roleManager;
    }

    /**
     * Ελέγχει αν ο χρήστης είναι συνδεδεμένος
     *
     * @throws AuthException Αν ο χρήστης δεν είναι συνδεδεμένος
     * @return bool true αν ο χρήστης είναι συνδεδεμένος
     */
    public static function isLoggedIn()
    {
        Logger::debug("AuthMiddleware::isLoggedIn - Checking if user is logged in");
        Logger::debug("Session data: " . print_r($_SESSION, true));

        // Βεβαιωνόμαστε ότι η συνεδρία είναι ενεργή
        Session::start();
        if (!Session::has('user_id')) {
            Logger::debug("No user_id in session - redirecting to login");

            // Αποθήκευση της τρέχουσας URL για επιστροφή μετά τη σύνδεση
            if (isset($_SERVER['REQUEST_URI'])) {
                Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);
            }

            throw AuthException::sessionExpired();
        }

        Logger::debug("User is logged in with ID: " . Session::get('user_id'));
        return true;
    }

    /**
     * Ελέγχει αν ο χρήστης έχει τον συγκεκριμένο ρόλο
     *
     * @param string|array $role Ο ρόλος ή ένας πίνακας με ρόλους
     * @throws AuthException Αν ο χρήστης δεν είναι συνδεδεμένος
     * @throws ForbiddenException Αν ο χρήστης δεν έχει τον απαιτούμενο ρόλο
     * @return bool true αν ο χρήστης έχει τον ρόλο
     */
    public static function hasRole($role)
    {
        Logger::debug("AuthMiddleware::hasRole - Checking if user has role: " . (is_array($role) ? implode(', ', $role) : $role));

        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();

        $userRole = Session::get('role');

        // Έλεγχος αν ο χρήστης έχει τον απαιτούμενο ρόλο
        if (is_array($role)) {
            if (!in_array($userRole, $role)) {
                Logger::debug("Role mismatch - user does not have any of the required roles");
                $requiredRolesStr = implode(', ', $role);
                throw ForbiddenException::role($requiredRolesStr, $userRole);
            }
        } else {
            if ($userRole !== $role) {
                Logger::debug("Role mismatch - user does not have required role");
                throw ForbiddenException::role($role, $userRole);
            }
        }

        Logger::debug("User has required role, continuing");
        return true;
    }

    /**
     * Ελέγχει αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
     *
     * @param array $roles Οι ρόλοι
     * @throws AuthException Αν ο χρήστης δεν είναι συνδεδεμένος
     * @throws ForbiddenException Αν ο χρήστης δεν έχει κανέναν από τους απαιτούμενους ρόλους
     * @return bool true αν ο χρήστης έχει έναν από τους ρόλους
     */
    public static function hasAnyRole($roles)
    {
        return self::hasRole($roles);
    }

    /**
     * Ελέγχει αν ο χρήστης έχει το συγκεκριμένο δικαίωμα
     *
     * @param string|array $permission Το δικαίωμα ή ένας πίνακας με δικαιώματα
     * @throws AuthException Αν ο χρήστης δεν είναι συνδεδεμένος
     * @return bool true αν ο χρήστης έχει το δικαίωμα
     */
    public static function hasPermission($permission)
    {
        Logger::debug("AuthMiddleware::hasPermission - Checking if user has permission: " . (is_array($permission) ? implode(', ', $permission) : $permission));

        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();

        // Προσωρινή λύση: επιστρέφουμε πάντα true καθώς δεν έχουμε πίνακες δικαιωμάτων
        Logger::debug("User has required permission, continuing");
        return true;
    }

    /**
     * Ελέγχει αν ο χρήστης είναι ο ιδιοκτήτης του αντικειμένου
     *
     * @param int $ownerId ID του ιδιοκτήτη του αντικειμένου
     * @throws AuthException Αν ο χρήστης δεν είναι συνδεδεμένος
     * @throws ForbiddenException Αν ο χρήστης δεν είναι ο ιδιοκτήτης
     * @return bool true αν ο χρήστης είναι ο ιδιοκτήτης
     */
    public static function isOwner($ownerId)
    {
        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();

        $userId = Session::get('user_id');
        if ($userId != $ownerId) {
            Logger::debug("Owner check failed - user id: $userId, owner id: $ownerId");
            throw ForbiddenException::ownership('resource', $ownerId, $userId);
        }

        return true;
    }

    /**
     * Ελέγχει αν ο χρήστης έχει επαληθεύσει το email του
     *
     * @throws AuthException Αν ο χρήστης δεν είναι συνδεδεμένος
     * @throws ForbiddenException Αν ο χρήστης δεν έχει επαληθεύσει το email του
     * @return bool true αν ο χρήστης έχει επαληθεύσει το email του
     */
    public static function isVerified()
    {
        // Πρώτα ελέγχουμε αν ο χρήστης είναι συνδεδεμένος
        self::isLoggedIn();

        // Λήψη του χρήστη από τη βάση δεδομένων
        $container = Container::getInstance();
        $pdo = $container->get('pdo');
        $role = Session::get('role');
        $userId = Session::get('user_id');

        if ($role === 'driver') {
            $sql = "SELECT is_verified FROM drivers WHERE id = ?";
        } else {
            $sql = "SELECT is_verified FROM companies WHERE id = ?";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result || !$result['is_verified']) {
            Logger::debug("Verification check failed - user id: $userId, role: $role");
            throw new ForbiddenException("Ο λογαριασμός σας δεν έχει επαληθευτεί. Παρακαλώ ελέγξτε το email σας.");
        }

        return true;
    }

    /**
     * Προστασία από επιθέσεις CSRF
     *
     * @param string $token Token CSRF από τη φόρμα
     * @throws BadRequestException Αν το token δεν είναι έγκυρο
     * @return bool true αν το token είναι έγκυρο
     */
    public static function validateCSRF($token)
    {
        if (!isset($token) || !CSRF::validateToken($token)) {
            Logger::debug("CSRF validation failed");
            throw new BadRequestException("Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.");
        }

        return true;
    }

    /**
     * Έλεγχος αν η συνεδρία έχει λήξει λόγω αδράνειας
     *
     * @param int $maxIdleTime Μέγιστος χρόνος αδράνειας σε δευτερόλεπτα
     * @throws AuthException Αν η συνεδρία έχει λήξει
     * @return bool true αν η συνεδρία είναι ενεργή
     */
    public static function checkSessionTimeout($maxIdleTime = 1800) // 30 λεπτά προεπιλογή
    {
        if (Session::isExpired($maxIdleTime)) {
            // Καταστροφή της συνεδρίας
            Session::destroy();
            throw AuthException::sessionExpired();
        }

        return true;
    }
}
