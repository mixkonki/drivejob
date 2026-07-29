<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία του service για τα προφίλ οδηγών
$driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

// Λήψη του προφίλ του οδηγού
$driverProfile = $driverProfileService->getDriverProfile($driverId);

if (!$driverProfile) {
    $_SESSION['error_message'] = 'Δεν βρέθηκε το προφίλ του οδηγού.';
    header('Location: ' . BASE_URL . 'drivers/edit_profile.php');
    exit();
}

// Λήψη των αδειών οδήγησης του οδηγού
$driverLicenses = $driverProfile['licenses'] ?? [];
$driverLicenseTypes = array_column($driverLicenses, 'license_type');

// Λήψη των πιστοποιητικών ADR του οδηγού
$driverADR = $driverProfile['adr_certificates'][0] ?? null;

// Λήψη των πιστοποιητικών ΠΕΙ του οδηγού
$driverPEI = array_column(array_filter($driverLicenses, function ($license) {
    return isset($license['has_pei']) && $license['has_pei'] == 1;
}), 'license_type');
$hasPeiC = in_array('C', $driverPEI) || in_array('CE', $driverPEI) || in_array('C1', $driverPEI) || in_array('C1E', $driverPEI);
$hasPeiD = in_array('D', $driverPEI) || in_array('DE', $driverPEI) || in_array('D1', $driverPEI) || in_array('D1E', $driverPEI);

// Λήψη των ημερομηνιών λήξης ΠΕΙ
$peiCExpiryDate = $driverProfile['pei_c_expiry'] ?? null;
$peiDExpiryDate = $driverProfile['pei_d_expiry'] ?? null;

// Λήψη της κάρτας ταχογράφου του οδηγού
$driverTachograph = $driverProfile['tachograph_cards'][0] ?? null;

// Λήψη της άδειας χειριστή μηχανημάτων έργου του οδηγού
$driverOperator = $driverProfile['operator_licenses'][0] ?? null;
$driverOperatorSubSpecialities = $driverOperator['sub_specialities'] ?? [];

// Λήψη της προϋπηρεσίας του οδηγού
$driverVehicleExperience = $driverProfile['vehicle_experience'] ?? [];

// Λήψη των δεδομένων του οδηγού
$driverData = $driverProfile['data'] ?? [];

// Συμπερίληψη του αρχείου προβολής
include ROOT_DIR . '/src/Views/drivers/driver-rating.php';
