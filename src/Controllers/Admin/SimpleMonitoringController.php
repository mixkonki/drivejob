<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Models\Admin\DummySystemMonitoringModel;
use Drivejob\Helpers\MonitoringHelper;

/**
 * SimpleMonitoringController - Απλοποιημένος controller για το σύστημα παρακολούθησης
 * 
 * Χρησιμοποιεί μόνο το DummySystemMonitoringModel για να αποφύγει προβλήματα
 * με τη σύνδεση στη βάση δεδομένων
 */
class SimpleMonitoringController
{
    private $model;

    public function __construct()
    {
        // Χρήση μόνο του dummy μοντέλου
        $this->model = new DummySystemMonitoringModel();
    }

    /**
     * Dashboard παρακολούθησης συστήματος
     */
    public function dashboard()
    {
        // Λήψη των δοκιμαστικών δεδομένων
        $systemStats = $this->model->getSystemStats();

        // Φόρτωση του view
        $this->loadView('Admin/monitoring/dashboard', [
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

        $errors = $this->model->getErrors($period, $page, $limit, $type, $search);

        $this->loadView('Admin/monitoring/errors', [
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
        $performanceData = $this->model->getPerformanceData($period);

        $this->loadView('Admin/monitoring/performance', [
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
        $usageData = $this->model->getUsageData($period);

        $this->loadView('Admin/monitoring/usage', [
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

        $logs = $this->model->getLogs($type, $page, $limit, $search, $dateFrom, $dateTo);

        $this->loadView('Admin/monitoring/logs', [
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

        $success = $this->model->createDatabaseBackup();
        if ($success) {
            $_SESSION['success_message'] = 'Το αντίγραφο ασφαλείας της βάσης δεδομένων δημιουργήθηκε επιτυχώς (προσομοίωση)';
        } else {
            $_SESSION['error_message'] = 'Σφάλμα κατά τη δημιουργία αντιγράφου ασφαλείας της βάσης δεδομένων';
        }

        $this->redirect('/admin/monitoring/dashboard');
    }

    /**
     * Φόρτωση ενός view
     *
     * @param string $view Το όνομα του view
     * @param array $data Δεδομένα που θα περαστούν στο view
     * @return void
     */
    private function loadView($view, $data = [])
    {
        // Εξαγωγή των δεδομένων σε μεταβλητές
        extract($data);

        // Ορισμός του μονοπατιού του view
        $viewPath = ROOT_DIR . '/src/Views/' . $view . '.php';

        // Έλεγχος αν υπάρχει το view
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            // Σφάλμα αν δεν βρεθεί το view
            die("View {$view} not found");
        }
    }

    /**
     * Ανακατεύθυνση σε άλλο URL
     *
     * @param string $url Το URL για ανακατεύθυνση
     * @return void
     */
    private function redirect($url)
    {
        if (strpos($url, 'http') !== 0) {
            // Αν δεν είναι πλήρες URL, προσθέτουμε το BASE_URL
            $url = BASE_URL . ltrim($url, '/');
        }

        header("Location: {$url}");
        exit;
    }
}
