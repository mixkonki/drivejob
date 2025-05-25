<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Controllers\BaseController;
use Drivejob\Models\Admin\SystemMonitoringModel;
use Drivejob\Services\AdminActivityLogger;

/**
 * SystemMonitoringController - Διαχείριση παρακολούθησης συστήματος
 * 
 * Παρέχει λειτουργίες για την παρακολούθηση της απόδοσης και των σφαλμάτων
 * του συστήματος DriveJob
 */
class SystemMonitoringController extends BaseController
{
    private $monitoringModel;
    private $activityLogger;

    public function __construct($pdo = null)
    {
        parent::__construct();

        if ($pdo === null) {
            $pdo = require ROOT_DIR . '/config/database.php';
        }

        $this->monitoringModel = new SystemMonitoringModel($pdo);
        $this->activityLogger = new AdminActivityLogger($pdo);
    }

    /**
     * Dashboard παρακολούθησης συστήματος
     */
    public function dashboard()
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'monitoring');

        // Συλλογή στατιστικών συστήματος
        $systemStats = $this->getSystemStats();

        $this->view('Admin/monitoring/dashboard', [
            'title' => 'System Monitoring - DriveJob',
            'systemStats' => $systemStats
        ]);
    }

    /**
     * Προβολή σφαλμάτων συστήματος
     */
    public function errors($period = '7days')
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'monitoring');

        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $type = $_GET['type'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $errors = $this->monitoringModel->getErrors($period, $page, $limit, $type, $search);

        $this->view('Admin/monitoring/errors', [
            'title' => 'System Errors - DriveJob',
            'errors' => $errors,
            'period' => $period,
            'type' => $type,
            'search' => $search
        ]);
    }

    /**
     * Προβολή στατιστικών απόδοσης
     */
    public function performance($period = '7days')
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'monitoring');

        $performanceData = $this->monitoringModel->getPerformanceData($period);

        $this->view('Admin/monitoring/performance', [
            'title' => 'System Performance - DriveJob',
            'performanceData' => $performanceData,
            'period' => $period
        ]);
    }

    /**
     * Προβολή στατιστικών χρήσης
     */
    public function usage($period = '30days')
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'monitoring');

        $usageData = $this->monitoringModel->getUsageData($period);

        $this->view('Admin/monitoring/usage', [
            'title' => 'System Usage - DriveJob',
            'usageData' => $usageData,
            'period' => $period
        ]);
    }

    /**
     * Προβολή αρχείων καταγραφής
     */
    public function logs($type = 'all')
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'monitoring');

        $page = (int)($_GET['page'] ?? 1);
        $limit = 100;
        $search = $_GET['search'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $logs = $this->monitoringModel->getLogs($type, $page, $limit, $search, $dateFrom, $dateTo);

        $this->view('Admin/monitoring/logs', [
            'title' => 'System Logs - DriveJob',
            'logs' => $logs,
            'type' => $type,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }

    /**
     * Εκκαθάριση αρχείων καταγραφής
     */
    public function clearLogs($type = 'all')
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/monitoring/logs');
            return;
        }

        $olderThan = $_POST['older_than'] ?? 30; // Ημέρες
        $success = $this->monitoringModel->clearLogs($type, $olderThan);

        if ($success) {
            $this->activityLogger->log($_SESSION['user_id'], 'clear_logs', 'system', null, [
                'type' => $type,
                'older_than' => $olderThan
            ]);
            $_SESSION['success_message'] = 'Τα αρχεία καταγραφής εκκαθαρίστηκαν επιτυχώς';
        } else {
            $_SESSION['error_message'] = 'Σφάλμα κατά την εκκαθάριση των αρχείων καταγραφής';
        }

        $this->redirect('/admin/monitoring/logs');
    }

    /**
     * Λήψη αντιγράφου ασφαλείας βάσης δεδομένων
     */
    public function backupDatabase()
    {
        $this->requireAdminAuth();
        $this->requirePermission('system', 'admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/monitoring/dashboard');
            return;
        }

        $success = $this->monitoringModel->createDatabaseBackup();

        if ($success) {
            $this->activityLogger->log($_SESSION['user_id'], 'backup_database', 'system', null);
            $_SESSION['success_message'] = 'Το αντίγραφο ασφαλείας της βάσης δεδομένων δημιουργήθηκε επιτυχώς';
        } else {
            $_SESSION['error_message'] = 'Σφάλμα κατά τη δημιουργία αντιγράφου ασφαλείας της βάσης δεδομένων';
        }

        $this->redirect('/admin/monitoring/dashboard');
    }

    /**
     * Έλεγχος αν ο admin είναι συνδεδεμένος
     */
    private function isAdminLoggedIn()
    {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['role']) &&
            $_SESSION['role'] === 'admin';
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
     * Συλλογή στατιστικών συστήματος
     */
    private function getSystemStats()
    {
        return [
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ],
            'database' => $this->monitoringModel->getDatabaseStats(),
            'errors' => $this->monitoringModel->getErrorStats(),
            'performance' => $this->monitoringModel->getPerformanceStats(),
            'usage' => $this->monitoringModel->getUsageStats(),
            'backups' => $this->monitoringModel->getBackupStats()
        ];
    }
}
