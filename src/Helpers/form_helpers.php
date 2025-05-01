<?php

// src/Helpers/form_helpers.php


/**
 * Βοηθητική συνάρτηση για τον έλεγχο αν υπάρχει σφάλμα
 */
function hasError($field)
{

    return isset($_SESSION['errors'][$field]);
}

/**
 * Βοηθητική συνάρτηση για την εμφάνιση μηνύματος σφάλματος
 */
function getError($field)
{

    return $_SESSION['errors'][$field] ?? '';
}
