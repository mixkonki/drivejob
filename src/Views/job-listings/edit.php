<?php

/**
 * Ενιαίο view για την επεξεργασία αγγελίας
 * Προσαρμόζεται ανάλογα με τον τύπο του χρήστη (οδηγός ή εταιρεία)
 */

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

$userRole = $_SESSION['user_role'];

// Έλεγχος αν υπάρχει η αγγελία
if (!isset($listing) || empty($listing)) {
    header('Location: ' . BASE_URL . 'job-listings');
    exit();
}

// Έλεγχος αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
$isOwner = false;
if ($userRole === 'company' && !empty($listing['company_id']) && $_SESSION['user_id'] == $listing['company_id']) {
    $isOwner = true;
} elseif ($userRole === 'driver' && !empty($listing['driver_id']) && $_SESSION['user_id'] == $listing['driver_id']) {
    $isOwner = true;
}

if (!$isOwner) {
    header('Location: ' . BASE_URL . 'job-listings');
    exit();
}

// Φόρτωση του κατάλληλου view ανάλογα με τον τύπο του χρήστη
if ($userRole === 'company') {
    include ROOT_DIR . '/src/Views/job-listings/Company/edit.php';
} elseif ($userRole === 'driver') {
    include ROOT_DIR . '/src/Views/job-listings/Driver/edit.php';
} else {
    // Αν ο χρήστης δεν είναι ούτε οδηγός ούτε εταιρεία, ανακατεύθυνση στην αρχική σελίδα
    header('Location: ' . BASE_URL . 'home');
    exit();
}
