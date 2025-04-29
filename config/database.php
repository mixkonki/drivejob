<?php
// Σύνδεση στη βάση δεδομένων
$host = 'localhost'; // Διεύθυνση του διακομιστή MySQL
$db = 'drivejob'; // Όνομα βάσης δεδομένων
$user = 'root'; // Όνομα χρήστη MySQL
$pass = ''; // Κωδικός MySQL (άφησε κενό αν δεν υπάρχει)

// Σύνδεση PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Σφάλμα σύνδεσης: " . $e->getMessage());
}
?>
