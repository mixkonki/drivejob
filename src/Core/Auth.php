<?php

namespace Drivejob\Core;

use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Company\CompaniesModel;

/**
 * Κλάση για τη διαχείριση της αυθεντικοποίησης χρηστών
 */
class Auth
{
    /**
     * Έλεγχος αν ο χρήστης είναι συνδεδεμένος
     *
     * @return bool Αν ο χρήστης είναι συνδεδεμένος
     */
    public static function isLoggedIn()
    {
        return Session::has('user_id') && Session::has('user_role');
    }

    /**
     * Έλεγχος αν ο χρήστης έχει συγκεκριμένο ρόλο
     *
     * @param string $role Ο ρόλος προς έλεγχο
     * @return bool Αν ο χρήστης έχει τον συγκεκριμένο ρόλο
     */
    public static function hasRole($role)
    {
        return self::isLoggedIn() && Session::get('user_role') === $role;
    }

    /**
     * Έλεγχος αν ο χρήστης έχει έναν από τους συγκεκριμένους ρόλους
     *
     * @param array $roles Οι ρόλοι προς έλεγχο
     * @return bool Αν ο χρήστης έχει έναν από τους συγκεκριμένους ρόλους
     */
    public static function hasAnyRole(array $roles)
    {
        return self::isLoggedIn() && in_array(Session::get('user_role'), $roles);
    }

    /**
     * Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης του αντικειμένου
     *
     * @param int $ownerId ID του ιδιοκτήτη του αντικειμένου
     * @return bool Αν ο χρήστης είναι ο ιδιοκτήτης
     */
    public static function isOwner($ownerId)
    {
        return self::isLoggedIn() && Session::get('user_id') == $ownerId;
    }

    /**
     * Έλεγχος αν ο χρήστης είναι επαληθευμένος
     *
     * @param \PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return bool Αν ο χρήστης είναι επαληθευμένος
     */
    public static function isVerified($pdo)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $role = Session::get('user_role');
        $userId = Session::get('user_id');

        if ($role === 'driver') {
            $model = new ProfileModel($pdo);
            $user = $model->getDriverById($userId);
        } else {
            $model = new CompaniesModel($pdo);
            $user = $model->getCompanyById($userId);
        }

        return $user && $user['is_verified'] == 1;
    }

    /**
     * Σύνδεση χρήστη
     *
     * @param int $userId ID του χρήστη
     * @param string $role Ρόλος του χρήστη
     * @param string $userName Όνομα του χρήστη
     * @return void
     */
    public static function login($userId, $role, $userName)
    {
        Session::set('user_id', $userId);
        Session::set('user_role', $role);
        Session::set('user_name', $userName);
        Session::set('last_activity', time());
    }

    /**
     * Αποσύνδεση χρήστη
     *
     * @return void
     */
    public static function logout()
    {
        Session::destroy();
    }

    /**
     * Έλεγχος αν η συνεδρία έχει λήξει
     *
     * @param int $maxIdleTime Μέγιστος χρόνος αδράνειας σε δευτερόλεπτα
     * @return bool Αν η συνεδρία έχει λήξει
     */
    public static function isSessionExpired($maxIdleTime = 1800)
    {
        if (!self::isLoggedIn()) {
            return true;
        }

        $lastActivity = Session::get('last_activity');
        if (!$lastActivity) {
            return true;
        }

        if ((time() - $lastActivity) > $maxIdleTime) {
            return true;
        }

        // Ενημέρωση του χρόνου τελευταίας δραστηριότητας
        Session::set('last_activity', time());
        return false;
    }

    /**
     * Ανανέωση της συνεδρίας
     *
     * @return void
     */
    public static function refreshSession()
    {
        if (self::isLoggedIn()) {
            Session::set('last_activity', time());
        }
    }
}
