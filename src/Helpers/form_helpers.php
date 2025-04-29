<?php
// src/Helpers/form_helpers.php

/**
 * Βοηθητική συνάρτηση για την εμφάνιση παλιών τιμών φόρμας
 */
// Ενεργοποίηση output buffering
ob_start();

function old($field, $default = '') {
    global $companyData;
    
    if (isset($_SESSION['old_input'][$field])) {
        return $_SESSION['old_input'][$field];
    } elseif (isset($companyData[$field])) {
        return $companyData[$field];
    }
    
    return $default;
}

/**
 * Βοηθητική συνάρτηση για τον έλεγχο αν υπάρχει σφάλμα
 */
function hasError($field) {
    return isset($_SESSION['errors'][$field]);
}

/**
 * Βοηθητική συνάρτηση για την εμφάνιση μηνύματος σφάλματος
 */
function getError($field) {
    return $_SESSION['errors'][$field] ?? '';
}