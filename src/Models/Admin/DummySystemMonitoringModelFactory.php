<?php

namespace Drivejob\Models\Admin;

/**
 * DummySystemMonitoringModelFactory - Εργοστάσιο για τη δημιουργία του DummySystemMonitoringModel
 * 
 * Παρέχει μια στατική μέθοδο για τη δημιουργία του DummySystemMonitoringModel
 */
class DummySystemMonitoringModelFactory
{
    /**
     * Δημιουργεί και επιστρέφει ένα νέο αντικείμενο DummySystemMonitoringModel
     * 
     * @return DummySystemMonitoringModel
     */
    public static function create()
    {
        return new DummySystemMonitoringModel();
    }
}
