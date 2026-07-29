<?php

namespace Drivejob\Middleware;

use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;

class ApiAuthMiddleware
{
    /**
     * Έλεγχος authentication για API endpoints
     * 
     * @param array $allowedRoles Επιτρεπόμενοι ρόλοι (π.χ. ['driver', 'company'])
     * @return bool
     */
    public static function check(array $allowedRoles = [])
    {
        // Έναρξη session
        Session::start();

        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Session::has('user_id') || !Session::has('user_role')) {
            JsonResponse::error('Authentication required', 401);
            return false;
        }

        // Έλεγχος αν ο ρόλος του χρήστη επιτρέπεται
        if (!empty($allowedRoles) && !in_array(Session::get('user_role'), $allowedRoles)) {
            JsonResponse::error('Access denied for your role', 403);
            return false;
        }

        // Έλεγχος αν η session έχει λήξει
        if (Session::isExpired(3600)) { // 1 ώρα για API
            Session::destroy();
            JsonResponse::error('Session expired', 401);
            return false;
        }

        return true;
    }

    /**
     * Λήψη του τρέχοντος authenticated user
     * 
     * @return array|null
     */
    public static function getUser()
    {
        if (!Session::has('user_id')) {
            return null;
        }

        return [
            'id' => Session::get('user_id'),
            'role' => Session::get('user_role'),
            'type' => Session::get('user_type'),
            'name' => Session::get('user_name')
        ];
    }

    /**
     * Έλεγχος αν ο χρήστης είναι driver
     * 
     * @return bool
     */
    public static function isDriver()
    {
        return Session::get('user_role') === 'driver';
    }

    /**
     * Έλεγχος αν ο χρήστης είναι company
     * 
     * @return bool
     */
    public static function isCompany()
    {
        return Session::get('user_role') === 'company';
    }

    /**
     * Έλεγχος αν ο χρήστης είναι admin
     * 
     * @return bool
     */
    public static function isAdmin()
    {
        return Session::get('user_role') === 'admin';
    }
}
