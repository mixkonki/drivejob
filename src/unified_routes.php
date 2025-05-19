<?php
// src/unified_routes.php
// Προτεινόμενες ενοποιημένες διαδρομές για τις αγγελίες εργασίας

// Δημιουργία του router (εάν δεν έχει περαστεί ως παράμετρος)
if (!isset($router)) {
    $router = new Drivejob\Core\Router();
}

use Drivejob\Core\Router;
use Drivejob\Controllers\UnifiedJobListingController;

// Διαδρομές για τις αγγελίες (χρησιμοποιώντας τον ενοποιημένο controller)
$router->get('/job-listings', [UnifiedJobListingController::class, 'index']);
$router->get('/job-listings/show/{id}', [UnifiedJobListingController::class, 'show']);
$router->get('/job-listings/company/{id}', [UnifiedJobListingController::class, 'companyListings']);
$router->get('/job-listings/driver/{id}', [UnifiedJobListingController::class, 'driverListings']);
$router->get('/job-listings/my-listings', [UnifiedJobListingController::class, 'myListings']);

// Διαδρομές για τη δημιουργία αγγελιών (ενοποιημένες)
$router->get('/job-listings/create', [UnifiedJobListingController::class, 'create']);
$router->post('/job-listings/store', [UnifiedJobListingController::class, 'store']);

// Διαδρομές για την επεξεργασία αγγελιών (ενοποιημένες)
$router->get('/job-listings/edit/{id}', [UnifiedJobListingController::class, 'edit']);
$router->post('/job-listings/update/{id}', [UnifiedJobListingController::class, 'update']);

// Διαδρομές για τη διαγραφή αγγελιών (ενοποιημένες)
$router->get('/job-listings/delete/{id}', [UnifiedJobListingController::class, 'delete']);
$router->post('/job-listings/destroy/{id}', [UnifiedJobListingController::class, 'destroy']);

// Επιστροφή του router για χρήση σε άλλα αρχεία
return $router;
