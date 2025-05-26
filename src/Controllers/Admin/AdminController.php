<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Controllers\BaseController;
use Drivejob\Models\Admin\AdminModel;
use Drivejob\Models\Driver\ProfileModel as DriverProfileModel;
use Drivejob\Models\Company\CompaniesModel as CompanyProfileModel;
use Drivejob\Services\AdminActivityLogger;

/**
 * AdminController - Διαχείριση του admin panel
 * 
 * Παρέχει όλες τις λειτουργίες διαχείρισης για τους administrators
 * του συστήματος DriveJob
 */
class AdminController extends BaseController
{
    private $adminModel;
    private $driverModel;
    private $companyModel;
    private $activityLogger;

    public function __construct($pdo = null)
    {
        parent::__construct();

        if ($pdo === null) {
            $pdo = require ROOT_DIR . '/config/database.php';
        }

        $this->adminModel = new AdminModel($pdo);
        $this->driverModel = new DriverProfileModel($pdo);
        $this->companyModel = new CompanyProfileModel($pdo);
        $this->activityLogger = new AdminActivityLogger($pdo);
    }


    /**
     * Admin Dashboard - Κεντρική σελίδα διαχείρισης
     */
    public function dashboard()
    {
        $this->requireAdminAuth();

        // Συλλογή στατιστικών για το dashboard
        $stats = $this->getDashboardStats();

        $this->view('Admin/dashboard', [
            'title' => 'Admin Dashboard - DriveJob',
            'stats' => $stats,
            'admin' => $this->getCurrentAdmin()
        ]);
    }

    /**
     * Διαχείριση χρηστών (οδηγοί και εταιρείες)
     */
    public function users($type = 'all')
    {
        $this->requireAdminAuth();
        $this->requirePermission('users', 'read');

        // Έλεγχος αν υπάρχει type στο GET, αλλιώς χρήση του parameter
        $type = $_GET['type'] ?? $type;
        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';

        $users = $this->adminModel->getUsers($type, $page, 20, $search, $status);

        $this->view('Admin/users', [
            'title' => 'Διαχείριση Χρηστών - Admin',
            'users' => $users,
            'type' => $type,
            'search' => $search,
            'status' => $status,
            'currentPage' => $page
        ]);
    }

    /**
     * Προβολή λεπτομερειών χρήστη
     */
    public function userDetails($userId, $userType)
    {
        $this->requireAdminAuth();
        $this->requirePermission('users', 'read');

        if ($userType === 'driver') {
            $user = $this->driverModel->getDriverById($userId);
        } elseif ($userType === 'company') {
            $user = $this->companyModel->getCompanyById($userId);
        } else {
            $this->redirect('/admin/users');
            return;
        }

        if (!$user) {
            $_SESSION['error_message'] = 'Ο χρήστης δεν βρέθηκε';
            $this->redirect('/admin/users');
            return;
        }

        $this->view('Admin/user-details', [
            'title' => 'Λεπτομέρειες Χρήστη - Admin',
            'user' => $user,
            'userType' => $userType
        ]);
    }

    /**
     * Ενεργοποίηση/Απενεργοποίηση χρήστη
     */
    public function toggleUserStatus($userId, $userType)
    {
        $this->requireAdminAuth();
        $this->requirePermission('users', 'update');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/users');
            return;
        }

        $success = $this->adminModel->toggleUserStatus($userId, $userType);

        if ($success) {
            $this->activityLogger->log($_SESSION['user_id'], 'toggle_user_status', $userType, $userId);
            $_SESSION['success_message'] = 'Η κατάσταση του χρήστη ενημερώθηκε επιτυχώς';
        } else {
            $_SESSION['error_message'] = 'Σφάλμα κατά την ενημέρωση της κατάστασης';
        }

        $this->redirect("/admin/user-details/{$userId}/{$userType}");
    }

    /**
     * Διαχείριση αγγελιών εργασίας
     */
    public function jobListings()
    {
        $this->requireAdminAuth();
        $this->requirePermission('job_listings', 'read');

        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';

        $listings = $this->adminModel->getJobListings($page, 20, $search, $status);

        $this->view('Admin/job-listings', [
            'title' => 'Διαχείριση Αγγελιών - Admin',
            'listings' => $listings,
            'search' => $search,
            'status' => $status,
            'currentPage' => $page
        ]);
    }

    /**
     * Στατιστικά και Analytics
     */
    public function analytics()
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'analytics');

        $period = $_GET['period'] ?? '30days';
        $analytics = $this->adminModel->getAnalytics($period);

        $this->view('Admin/analytics', [
            'title' => 'Στατιστικά & Analytics - Admin',
            'analytics' => $analytics,
            'period' => $period
        ]);
    }

    /**
     * Ρυθμίσεις συστήματος
     */
    public function settings()
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'settings');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateSettings();
            return;
        }

        $settings = $this->adminModel->getSystemSettings();

        $this->view('Admin/settings', [
            'title' => 'Ρυθμίσεις Συστήματος - Admin',
            'settings' => $settings
        ]);
    }

    /**
     * Ενημέρωση ρυθμίσεων συστήματος
     */
    private function updateSettings()
    {
        $this->requirePermission('system', 'settings');

        $settings = [
            'site_name' => $this->sanitize($_POST['site_name'] ?? ''),
            'site_description' => $this->sanitize($_POST['site_description'] ?? ''),
            'admin_email' => $this->sanitizeEmail($_POST['admin_email'] ?? ''),
            'maintenance_mode' => isset($_POST['maintenance_mode']),
            'registration_enabled' => isset($_POST['registration_enabled']),
            'email_verification_required' => isset($_POST['email_verification_required'])
        ];

        $success = $this->adminModel->updateSystemSettings($settings);

        if ($success) {
            $this->activityLogger->log($_SESSION['user_id'], 'update_settings', 'system', null, $settings);
            $_SESSION['success_message'] = 'Οι ρυθμίσεις ενημερώθηκαν επιτυχώς';
        } else {
            $_SESSION['error_message'] = 'Σφάλμα κατά την ενημέρωση των ρυθμίσεων';
        }

        $this->redirect('/admin/settings');
    }

    /**
     * Logs δραστηριότητας
     */
    public function activityLogs()
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'logs');

        $page = (int)($_GET['page'] ?? 1);
        $adminId = $_GET['admin_id'] ?? null;
        $action = $_GET['action'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $logs = $this->adminModel->getActivityLogs($page, 50, $adminId, $action, $dateFrom, $dateTo);

        $this->view('Admin/activity-logs', [
            'title' => 'Logs Δραστηριότητας - Admin',
            'logs' => $logs,
            'filters' => [
                'admin_id' => $adminId,
                'action' => $action,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'currentPage' => $page
        ]);
    }

    /**
     * Έλεγχος αν ο admin είναι συνδεδεμένος
     */
    private function isAdminLoggedIn()
    {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['user_role']) &&
            $_SESSION['user_role'] === 'admin';
    }

    /**
     * Απαίτηση admin authentication
     */
    private function requireAdminAuth()
    {
        if (!$this->isAdminLoggedIn()) {
            $_SESSION['error_message'] = 'Παρακαλώ συνδεθείτε ως διαχειριστής';
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $this->redirect(BASE_URL . 'auth/login');
            exit;
        }
    }

    /**
     * Έλεγχος δικαιωμάτων
     */
    private function requirePermission($resource, $action)
    {
        // Προσωρινά επιτρέπουμε όλες τις ενέργειες για τους admins
        // Μπορείτε να προσθέσετε πιο λεπτομερή έλεγχο δικαιωμάτων αργότερα
        return true;
    }

    /**
     * Λήψη τρέχοντος admin
     */
    private function getCurrentAdmin()
    {
        if (!$this->isAdminLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'name' => $_SESSION['user_name'] ?? 'Administrator',
            'role' => 'super_admin',
            'permissions' => ['all'] // Προσωρινά δίνουμε όλα τα δικαιώματα
        ];
    }

    /**
     * Συλλογή στατιστικών για το dashboard
     */
    private function getDashboardStats()
    {
        return [
            'total_drivers' => $this->adminModel->getTotalDrivers(),
            'total_companies' => $this->adminModel->getTotalCompanies(),
            'total_job_listings' => $this->adminModel->getTotalJobListings(),
            'active_matches' => $this->adminModel->getActiveMatches(),
            'new_registrations_today' => $this->adminModel->getNewRegistrationsToday(),
            'new_job_listings_today' => $this->adminModel->getNewJobListingsToday(),
            'recent_activity' => $this->adminModel->getRecentActivity(10)
        ];
    }
}
