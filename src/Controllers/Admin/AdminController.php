<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Controllers\BaseController;
use Drivejob\Core\AuthMiddleware;

/**
 * Admin Controller για το ενιαίο dashboard
 */
class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Όλες οι admin σελίδες απαιτούν ρόλο admin
        AuthMiddleware::hasRole('admin');
    }

    /**
     * Εμφανίζει το κύριο admin dashboard
     */
    public function dashboard()
    {
        // Φόρτωση του ενιαίου dashboard view
        require_once __DIR__ . '/../../Views/admin/dashboard.php';
    }

    /**
     * Placeholder methods για τα υπόλοιπα admin routes
     */
    public function users()
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    public function userDetails($userId, $userType)
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    public function toggleUserStatus($userId, $userType)
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    public function jobListings()
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    public function analytics()
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    public function settings()
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    public function activityLogs()
    {
        $this->redirect(BASE_URL . 'admin/dashboard');
    }
}
