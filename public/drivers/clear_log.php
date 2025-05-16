<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Content-Type: text/plain');
    echo "ERROR: Μη εξουσιοδοτημένη πρόσβαση";
    exit();
}

// Ορισμός των αρχείων καταγραφής
$logFiles = [
    'vehicle_experience_debug.log' => ROOT_DIR . '/logs/vehicle_experience_debug.log',
    'error.log' => ROOT_DIR . '/logs/error.log',
    'info.log' => ROOT_DIR . '/logs/info.log',
    'debug.log' => ROOT_DIR . '/logs/debug.log'
];

// Έλεγχος αν το αρχείο καταγραφής υπάρχει
if (!isset($_GET['log']) || !isset($logFiles[$_GET['log']])) {
    header('Content-Type: text/plain');
    echo "ERROR: Μη έγκυρο αρχείο καταγραφής";
    exit();
}

$logFile = $logFiles[$_GET['log']];

// Έλεγχος αν το αρχείο υπάρχει
if (!file_exists($logFile)) {
    header('Content-Type: text/plain');
    echo "ERROR: Το αρχείο καταγραφής δεν βρέθηκε";
    exit();
}

// Καθαρισμός του αρχείου καταγραφής
try {
    // Δημιουργία αντιγράφου ασφαλείας
    $backupFile = $logFile . '.bak';
    copy($logFile, $backupFile);

    // Καθαρισμός του αρχείου
    file_put_contents($logFile, '');

    // Προσθήκη μιας αρχικής γραμμής
    $timestamp = date('Y-m-d H:i:s');
    $message = "=== $timestamp === Το αρχείο καταγραφής καθαρίστηκε από τον χρήστη {$_SESSION['user_id']} ({$_SESSION['email']}) ===\n";
    file_put_contents($logFile, $message, FILE_APPEND);

    header('Content-Type: text/plain');
    echo "SUCCESS: Το αρχείο καταγραφής καθαρίστηκε επιτυχώς";
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo "ERROR: Σφάλμα κατά τον καθαρισμό του αρχείου καταγραφής: " . $e->getMessage();
}
