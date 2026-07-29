<?php

namespace Drivejob\Models\Admin;

/**
 * Interface για τα monitoring models
 */
interface SystemMonitoringModelInterface
{
    /**
     * Λήψη τρέχουσας κατάστασης συστήματος
     */
    public function getSystemStatus();

    /**
     * Λήψη μετρικών απόδοσης
     */
    public function getPerformanceMetrics($period = '24h');

    /**
     * Λήψη πρόσφατων σφαλμάτων
     */
    public function getRecentErrors($limit = 50);

    /**
     * Λήψη στατιστικών χρήσης
     */
    public function getUsageStatistics($period = '7d');

    /**
     * Λήψη δεδομένων για γραφήματα
     */
    public function getChartData($chartType, $period = '7d');

    /**
     * Καταγραφή μετρικής απόδοσης
     */
    public function logPerformanceMetric($type, $value, $metadata = []);

    /**
     * Καταγραφή σφάλματος συστήματος
     */
    public function logSystemError($errorType, $message, $stackTrace = '', $userId = null);
}
