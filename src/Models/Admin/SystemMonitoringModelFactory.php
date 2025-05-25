<?php

namespace Drivejob\Models\Admin;

use PDO;
use Drivejob\Core\Database;
use Drivejob\Models\Admin\SystemMonitoringModel;

/**
 * SystemMonitoringModelFactory - Εργοστάσιο για τη δημιουργία του SystemMonitoringModel
 * 
 * Παρέχει μια στατική μέθοδο για τη δημιουργία του SystemMonitoringModel
 * χωρίς να απαιτείται η άμεση παροχή της σύνδεσης PDO
 */
class SystemMonitoringModelFactory
{
    /**
     * Δημιουργεί και επιστρέφει ένα νέο αντικείμενο SystemMonitoringModel
     * 
     * @return SystemMonitoringModel
     */
    public static function create()
    {
        // Λήψη της σύνδεσης PDO από το Database singleton
        $pdo = Database::getInstance()->getConnection();

        // Δημιουργία και επιστροφή του μοντέλου
        return new SystemMonitoringModel($pdo);
    }
}
