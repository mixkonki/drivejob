<?php

namespace Drivejob\Controllers;

use Drivejob\Core\BaseController;
use Drivejob\Models\AdminModel;
use Drivejob\Models\Driver\ProfileModel as DriverProfileModel;
use Drivejob\Models\Company\ProfileModel as CompanyProfileModel;
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
     * Εμφάνιση της σελίδας login για admins
     */
    public function showLoginForm()
    {
        // Αν είναι ήδη συνδεδεμένος admin, redirect στο dashboard
        if ($this->isAdminLoggedIn()) {
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->view('admin/login', [
            'title' => 'Admin Login - DriveJob',
            'error' => $_SESSION['error_message'] ?? null
        ]);

        unset($_SESSION['error_message']);
    }

    /**
     * Επεξεργασία login για admins
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/login');
            return;
        }

        $email = $this->sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error_message'] = 'Παρακαλώ συμπληρώστε όλα τα πεδία';
            $this->redirect('/admin/login');
            return;
        }

        $admin = $this->adminModel->authenticate($email, $password);

        if ($admin) {
            // Επιτυχής σύνδεση
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['admin_role'] = $admin['role_level'];
            $_SESSION['admin_permissions'] = json_decode($admin['permissions'], true);

            // Log της δραστηριότητας
            $this->activityLogger->log($admin['id'], 'login', null, null, [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            $this->redirect('/admin/dashboard');
        } else {
            $_SESSION['error_message'] = 'Λάθος email ή κωδικός πρόσβασης';
            $this->redirect('/admin/login');
        }
    }

    /**
     * Logout για admins
     */
    public function logout()
    {
        if (isset($_SESSION['admin_id'])) {
            $this->activityLogger->log($_SESSION['admin_id'], 'logout');
        }

        // Καθαρισμός admin session
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_role']);
        unset($_SESSION['admin_permissions']);

        $_SESSION['success_message'] = 'Αποσυνδεθήκατε επιτυχώς';
        $this->redirect('/admin/login');
    }

    /**
     * Admin Dashboard - Κεντρική σελίδα διαχείρισης
     */
    public function dashboard()
    {
        $this->requireAdminAuth();

        // Συλλογή στατιστικών για το dashboard
        $stats = $this->getDashboardStats();

        $this->view('admin/dashboard', [
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

        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';

        $users = $this->adminModel->getUsers($type, $page, 20, $search, $status);

        $this->view('admin/users', [
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
            $user = $this->driverModel->getDriverProfile($userId);
        } elseif ($userType === 'company') {
            $user = $this->companyModel->getCompanyProfile($userId);
        } else {
            $this->redirect('/admin/users');
            return;
        }

        if (!$user) {
            $_SESSION['error_message'] = 'Ο χρήστης δεν βρέθηκε';
            $this->redirect('/admin/users');
            return;
        }

        $this->view('admin/user-details', [
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
            $this->activityLogger->log($_SESSION['admin_id'], 'toggle_user_status', $userType, $userId);
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

        $this->view('admin/job-listings', [
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

        $this->view('admin/analytics', [
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

        $this->view('admin/settings', [
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
            $this->activityLogger->log($_SESSION['admin_id'], 'update_settings', 'system', null, $settings);
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

        $this->view('admin/activity-logs', [
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
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }

    /**
     * Απαίτηση admin authentication
     */
    private function requireAdminAuth()
    {
        if (!$this->isAdminLoggedIn()) {
            $_SESSION['error_message'] = 'Παρακαλώ συνδεθείτε ως διαχειριστής';
            $this->redirect('/admin/login');
            exit;
        }
    }

    /**
     * Έλεγχος δικαιωμάτων
     */
    private function requirePermission($resource, $action)
    {
        $permissions = $_SESSION['admin_permissions'] ?? [];

        if (!isset($permissions[$resource]) || !in_array($action, $permissions[$resource])) {
            $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα για αυτή την ενέργεια';
            $this->redirect('/admin/dashboard');
            exit;
        }
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
            'id' => $_SESSION['admin_id'],
            'email' => $_SESSION['admin_email'],
            'name' => $_SESSION['admin_name'],
            'role' => $_SESSION['admin_role'],
            'permissions' => $_SESSION['admin_permissions']
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
