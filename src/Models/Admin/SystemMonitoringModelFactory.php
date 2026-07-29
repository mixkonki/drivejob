<?php

namespace Drivejob\Models\Admin;

use PDO;

/**
 * Factory για τη δημιουργία του SystemMonitoringModel
 */
class SystemMonitoringModelFactory
{
    /**
     * Δημιουργία instance του SystemMonitoringModel
     */
    public static function create(): SystemMonitoringModel
    {
        try {
            $pdo = require ROOT_DIR . '/config/database.php';
            return new SystemMonitoringModel($pdo);
        } catch (\Exception $e) {
            error_log("SystemMonitoringModelFactory Error: " . $e->getMessage());
            throw $e;
        }
    }
}
