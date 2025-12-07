<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Controllers\BaseController;

/**
 * Admin Controller για το ενιαίο dashboard
 */
class AdminController extends BaseController
{
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
        $this->redirect('/drivejob/public/admin/index.php');
    }

    public function userDetails($userId, $userType)
    {
        $this->redirect('/drivejob/public/admin/index.php');
    }

    public function toggleUserStatus($userId, $userType)
    {
        $this->redirect('/drivejob/public/admin/index.php');
    }

    public function jobListings()
    {
        $this->redirect('/drivejob/public/admin/index.php');
    }

    public function analytics()
    {
        $this->redirect('/drivejob/public/admin/index.php');
    }

    public function settings()
    {
        $this->redirect('/drivejob/public/admin/index.php');
    }

    public function activityLogs()
    {
        $this->redirect('/drivejob/public/admin/index.php');
    }
}
