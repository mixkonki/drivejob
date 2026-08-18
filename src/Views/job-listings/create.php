<?php

/**
 * Ενιαίο view για τη δημιουργία αγγελίας
 * Προσαρμόζεται ανάλογα με τον τύπο του χρήστη (οδηγός ή εταιρεία)
 */

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ' . BASE_URL . 'auth/login');
    exit();
}

$userRole = $_SESSION['user_role'];

// Φόρτωση του κατάλληλου view ανάλογα με τον τύπο του χρήστη
if ($userRole === 'company') {
    include ROOT_DIR . '/src/Views/job-listings/Company/create.php';
} elseif ($userRole === 'driver') {
    include ROOT_DIR . '/src/Views/job-listings/Driver/create.php';
} else {
    // Αν ο χρήστης δεν είναι ούτε οδηγός ούτε εταιρεία, ανακατεύθυνση στην αρχική σελίδα
    header('Location: ' . BASE_URL);
    exit();
}
