<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Core\Controller;
use Drivejob\Models\Admin\SystemMonitoringModel;
use Drivejob\Models\Admin\SystemMonitoringModelFactory;
use Drivejob\Models\Admin\DummySystemMonitoringModel;
use Drivejob\Models\Admin\DummySystemMonitoringModelFactory;
use Drivejob\Helpers\MonitoringHelper;

class SystemMonitoringController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();

        // Προσπάθεια χρήσης του κανονικού μοντέλου
        try {
            $this->model = SystemMonitoringModelFactory::create();
        } catch (\Exception $e) {
            // Σε περίπτωση σφάλματος, χρήση του dummy μοντέλου
            $this->model = DummySystemMonitoringModelFactory::create();
        }
    }

    /**
     * Dashboard παρακολούθησης συστήματος
     */
    public function dashboard()
    {
        // Χρήση των δοκιμαστικών δεδομένων αν δεν υπάρχουν πραγματικά
        try {
            $systemStats = $this->model->getSystemStats();
            if (empty($systemStats)) {
                $systemStats = MonitoringHelper::getDefaultStats();
            }
        } catch (\Exception $e) {
            // Σε περίπτωση σφάλματος, χρησιμοποιούμε τα δοκιμαστικά δεδομένα
            $systemStats = MonitoringHelper::getDefaultStats();
        }

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
        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $type = $_GET['type'] ?? 'all';
        $search = $_GET['search'] ?? '';

        try {
            $errors = $this->model->getErrors($period, $page, $limit, $type, $search);
        } catch (\Exception $e) {
            $errors = [];
            $_SESSION['error_message'] = 'Σφάλμα κατά την ανάκτηση των σφαλμάτων: ' . $e->getMessage();
        }

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
        try {
            $performanceData = $this->model->getPerformanceData($period);
        } catch (\Exception $e) {
            $performanceData = [];
            $_SESSION['error_message'] = 'Σφάλμα κατά την ανάκτηση των δεδομένων απόδοσης: ' . $e->getMessage();
        }

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
        try {
            $usageData = $this->model->getUsageData($period);
        } catch (\Exception $e) {
            $usageData = [];
            $_SESSION['error_message'] = 'Σφάλμα κατά την ανάκτηση των δεδομένων χρήσης: ' . $e->getMessage();
        }

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
        $page = (int)($_GET['page'] ?? 1);
        $limit = 100;
        $search = $_GET['search'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        try {
            $logs = $this->model->getLogs($type, $page, $limit, $search, $dateFrom, $dateTo);
        } catch (\Exception $e) {
            $logs = [];
            $_SESSION['error_message'] = 'Σφάλμα κατά την ανάκτηση των αρχείων καταγραφής: ' . $e->getMessage();
        }

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
     * Λήψη αντιγράφου ασφαλείας βάσης δεδομένων
     */
    public function backupDatabase()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/monitoring/dashboard');
            return;
        }

        try {
            $success = $this->model->createDatabaseBackup();
            if ($success) {
                $_SESSION['success_message'] = 'Το αντίγραφο ασφαλείας της βάσης δεδομένων δημιουργήθηκε επιτυχώς';
            } else {
                $_SESSION['error_message'] = 'Σφάλμα κατά τη δημιουργία αντιγράφου ασφαλείας της βάσης δεδομένων';
            }
        } catch (\Exception $e) {
            $_SESSION['error_message'] = 'Σφάλμα: ' . $e->getMessage();
        }

        $this->redirect('/admin/monitoring/dashboard');
    }
}
