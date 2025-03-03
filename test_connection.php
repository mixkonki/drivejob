<?php
require_once 'config/database.php';

if ($pdo) {
    echo "Η σύνδεση στη βάση δεδομένων ήταν επιτυχής!";
} else {
    echo "Αποτυχία σύνδεσης.";
}
?>
