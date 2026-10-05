<?php

// API Routes
// The $router variable is already available from config/routes.php

// Check if router is available
if (!isset($router)) {
    throw new Exception('Router not initialized. This file should be included from config/routes.php');
}

// Company Features API Routes
$router->group(['prefix' => 'api/company'], function ($router) {
    // Fleet Management
    $router->get('/fleet/vehicles', 'Drivejob\Controllers\Api\CompanyFeaturesController@getFleetVehicles');
    $router->post('/fleet/vehicles', 'Drivejob\Controllers\Api\CompanyFeaturesController@addVehicle');
    $router->get('/fleet/analytics', 'Drivejob\Controllers\Api\CompanyFeaturesController@getFleetAnalytics');

    // Driver Management
    $router->get('/drivers/stats', 'Drivejob\Controllers\Api\CompanyFeaturesController@getDriverStats');

    // Subscription Management
    $router->post('/subscription/upgrade', 'Drivejob\Controllers\Api\CompanyFeaturesController@upgradeSubscription');

    // Compliance Management
    $router->get('/compliance/documents', 'Drivejob\Controllers\Api\CompanyFeaturesController@getComplianceDocuments');
});

/*
 * ══════════════════════════════════════════════════════════════════════
 *  api/matching/* — ΑΦΑΙΡΕΘΗΚΕ (05/10/2026)
 * ══════════════════════════════════════════════════════════════════════
 * Οι τέσσερις διαδρομές (driver/matches, job/candidates, calculate,
 * insights) έτρεχαν τον παλιό MatchingEngine πάνω στον πίνακα
 * matching_scores: επέστρεφαν σκορ «5000» και «Εξαιρετική αντιστοιχία!»
 * για οτιδήποτε, και το job/candidates έδινε ΟΝΟΜΑ + EMAIL κάθε οδηγού
 * σε κάθε εταιρεία — χωρίς τον έλεγχο σταδιακής αποκάλυψης (Visibility).
 *
 * Κανένα ζωντανό view δεν τις καλούσε πλέον: η καρτέλα ταιριασμάτων του
 * οδηγού και οι υποψήφιοι της εταιρίας περνούν από τον RequirementsMatcher
 * στους κανονικούς controllers. Οι μόνοι καταναλωτές ήταν νεκρά partials
 * (matching-widget, candidates-widget-*, company-profile-with-tabs/new-layout)
 * που μεταφέρθηκαν στο _to_delete/nekroi.
 *
 * Αν ξαναχρειαστεί JSON API ταιριάσματος, χτίζεται πάνω στον
 * RequirementsMatcher και περνά από το Visibility — όχι από εδώ.
 */

// Include this file in your main routes file
