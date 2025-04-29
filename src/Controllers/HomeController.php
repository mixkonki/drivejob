<?php

namespace Drivejob\Controllers;

class HomeController
{
    public function renderHomePage()
    {
        // Εκτύπωση της τρέχουσας διαδρομής
       // echo "Η τρέχουσα διαδρομή (__DIR__) είναι: " . __DIR__ . "<br>";

        // Χρήση dirname για τη σωστή διαδρομή
        $configFile = dirname(__DIR__, 2) . '/config/config.php';
        //echo "Η διαδρομή που υπολογίζεται είναι: " . $configFile . "<br>";

        if (file_exists($configFile)) {
            require_once $configFile; // Φορτώνουμε το config.php
        } else {
            echo "Το αρχείο ρυθμίσεων (config.php) δεν βρέθηκε. Επικοινωνήστε με τον διαχειριστή.";
            return;
        }

        // Ελέγξτε αν το view αρχείο υπάρχει
        $viewFile = dirname(__DIR__, 2) . '/public/index.view.php';
       // echo "Η διαδρομή που υπολογίζεται για το view είναι: " . $viewFile . "<br>";
        if (file_exists($viewFile)) {
            include $viewFile; // Φορτώνουμε το view
        } else {
            echo "Η αρχική σελίδα δεν βρέθηκε. Επικοινωνήστε με τον διαχειριστή.";
        }
    }
}


