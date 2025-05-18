<?php

/**
 * Script για τον έλεγχο των controllers
 * 
 * Αυτό το script ελέγχει αν οι controllers υπάρχουν και αν οι μέθοδοι τους είναι προσβάσιμες
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/bootstrap.php';

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Έλεγχος αν έχει αφαιρεθεί ο παλιός controller συμβατότητας για τους οδηγούς
$driversControllerPath = __DIR__ . '/Controllers/DriversController.php';
if (file_exists($driversControllerPath)) {
    echo "ΠΡΟΕΙΔΟΠΟΙΗΣΗ: Ο παλιός controller συμβατότητας για τους οδηγούς εξακολουθεί να υπάρχει.\n";
    echo "Αυτός ο controller έχει αντικατασταθεί από τον \Drivejob\Controllers\Driver\DriversController.\n";
    echo "Συνιστάται η αφαίρεση του αρχείου για την αποφυγή σύγχυσης.\n";
} else {
    echo "Ο παλιός controller συμβατότητας για τους οδηγούς έχει αφαιρεθεί επιτυχώς.\n";
}

// Έλεγχος αν έχει αφαιρεθεί ο παλιός controller συμβατότητας για τις εταιρείες
$companiesControllerPath = __DIR__ . '/Controllers/CompaniesController.php';
if (file_exists($companiesControllerPath)) {
    echo "ΠΡΟΕΙΔΟΠΟΙΗΣΗ: Ο παλιός controller συμβατότητας για τις εταιρείες εξακολουθεί να υπάρχει.\n";
    echo "Αυτός ο controller έχει αντικατασταθεί από τον \Drivejob\Controllers\Company\CompaniesController.\n";
    echo "Συνιστάται η αφαίρεση του αρχείου για την αποφυγή σύγχυσης.\n";
} else {
    echo "Ο παλιός controller συμβατότητας για τις εταιρείες έχει αφαιρεθεί επιτυχώς.\n";
}

// Έλεγχος αν υπάρχει ο controller για τις αγγελίες
$unifiedJobListingControllerPath = __DIR__ . '/Controllers/UnifiedJobListingController.php';
if (file_exists($unifiedJobListingControllerPath)) {
    echo "Ο controller για τις αγγελίες υπάρχει.\n";

    // Έλεγχος αν μπορεί να δημιουργηθεί ο controller
    try {
        $unifiedJobListingController = new \Drivejob\Controllers\UnifiedJobListingController($pdo);
        echo "Ο controller για τις αγγελίες μπορεί να δημιουργηθεί.\n";

        // Έλεγχος αν υπάρχει η μέθοδος myListings
        if (method_exists($unifiedJobListingController, 'myListings')) {
            echo "Η μέθοδος myListings υπάρχει στον controller για τις αγγελίες.\n";
        } else {
            echo "Η μέθοδος myListings δεν υπάρχει στον controller για τις αγγελίες.\n";
        }
    } catch (\Exception $e) {
        echo "Σφάλμα κατά τη δημιουργία του controller για τις αγγελίες: " . $e->getMessage() . "\n";
    }
} else {
    echo "Ο controller για τις αγγελίες δεν υπάρχει.\n";
}

// Έλεγχος αν υπάρχει ο controller για τους οδηγούς (νέος)
$driversControllerPath = __DIR__ . '/Controllers/Driver/DriversController.php';
if (file_exists($driversControllerPath)) {
    echo "Ο controller για τους οδηγούς (νέος) υπάρχει.\n";

    // Έλεγχος αν μπορεί να δημιουργηθεί ο controller
    try {
        $driversController = new \Drivejob\Controllers\Driver\DriversController($pdo);
        echo "Ο controller για τους οδηγούς (νέος) μπορεί να δημιουργηθεί.\n";

        // Έλεγχος αν υπάρχει η μέθοδος profile
        if (method_exists($driversController, 'profile')) {
            echo "Η μέθοδος profile υπάρχει στον controller για τους οδηγούς (νέος).\n";
        } else {
            echo "Η μέθοδος profile δεν υπάρχει στον controller για τους οδηγούς (νέος).\n";
        }
    } catch (\Exception $e) {
        echo "Σφάλμα κατά τη δημιουργία του controller για τους οδηγούς (νέος): " . $e->getMessage() . "\n";
    }
} else {
    echo "Ο controller για τους οδηγούς (νέος) δεν υπάρχει.\n";
}

// Έλεγχος αν υπάρχει ο controller για τις εταιρείες (νέος)
$companiesControllerPath = __DIR__ . '/Controllers/Company/CompaniesController.php';
if (file_exists($companiesControllerPath)) {
    echo "Ο controller για τις εταιρείες (νέος) υπάρχει.\n";

    // Έλεγχος αν μπορεί να δημιουργηθεί ο controller
    try {
        $companiesController = new \Drivejob\Controllers\Company\CompaniesController($pdo);
        echo "Ο controller για τις εταιρείες (νέος) μπορεί να δημιουργηθεί.\n";

        // Έλεγχος αν υπάρχει η μέθοδος profile
        if (method_exists($companiesController, 'profile')) {
            echo "Η μέθοδος profile υπάρχει στον controller για τις εταιρείες (νέος).\n";
        } else {
            echo "Η μέθοδος profile δεν υπάρχει στον controller για τις εταιρείες (νέος).\n";
        }
    } catch (\Exception $e) {
        echo "Σφάλμα κατά τη δημιουργία του controller για τις εταιρείες (νέος): " . $e->getMessage() . "\n";
    }
} else {
    echo "Ο controller για τις εταιρείες (νέος) δεν υπάρχει.\n";
}

// Έλεγχος αν υπάρχουν τα views για τις αγγελίες
$driverMyListingsViewPath = __DIR__ . '/Views/job-listings/Driver/my-listings.php';
if (file_exists($driverMyListingsViewPath)) {
    echo "Το view για τις αγγελίες του οδηγού υπάρχει.\n";
} else {
    echo "Το view για τις αγγελίες του οδηγού δεν υπάρχει.\n";
}

$companyMyListingsViewPath = __DIR__ . '/Views/job-listings/Company/my-listings.php';
if (file_exists($companyMyListingsViewPath)) {
    echo "Το view για τις αγγελίες της εταιρείας υπάρχει.\n";
} else {
    echo "Το view για τις αγγελίες της εταιρείας δεν υπάρχει.\n";
}

// Έλεγχος αν υπάρχει η διαδρομή /my-listings στο αρχείο routes.php
$routesPath = __DIR__ . '/../config/routes.php';
$routesContent = file_get_contents($routesPath);

if (strpos($routesContent, "'my-listings'") !== false) {
    echo "Η διαδρομή /my-listings υπάρχει στο αρχείο routes.php.\n";
} else {
    echo "Η διαδρομή /my-listings δεν υπάρχει στο αρχείο routes.php.\n";
}

echo "Ο έλεγχος των controllers ολοκληρώθηκε με επιτυχία.\n";
