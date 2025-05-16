<?php
// Αρχείο για τον έλεγχο των δεδομένων της φόρμας

// Ενεργοποίηση εμφάνισης σφαλμάτων
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Έλεγχος αν έχουν υποβληθεί δεδομένα
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>Δεδομένα POST</h1>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<h2>Δεξιότητες</h2>";
    if (isset($_POST['skills'])) {
        echo "<pre>";
        print_r($_POST['skills']);
        echo "</pre>";
    } else {
        echo "<p>Δεν βρέθηκαν δεξιότητες στα δεδομένα POST.</p>";
    }

    echo "<h2>Επιλογές για εμπορευματικές μεταφορές</h2>";
    if (isset($_POST['freight_only'])) {
        echo "<pre>";
        print_r($_POST['freight_only']);
        echo "</pre>";
    } else {
        echo "<p>Δεν βρέθηκαν επιλογές για εμπορευματικές μεταφορές στα δεδομένα POST.</p>";
    }
} else {
    echo "<h1>Δεν έχουν υποβληθεί δεδομένα POST</h1>";
    echo "<p>Αυτό το script πρέπει να κληθεί από μια φόρμα με μέθοδο POST.</p>";
}
