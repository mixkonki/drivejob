<?php

// Σύνδεση με τη βάση δεδομένων
$pdo = new PDO('mysql:host=localhost;dbname=drivejob', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Έλεγχος για τον πίνακα job_listings
$stmt = $pdo->query('SHOW TABLES LIKE "job_listings"');
if ($stmt->rowCount() > 0) {
    echo "Πίνακας job_listings υπάρχει\n";
    $stmt = $pdo->query('DESCRIBE job_listings');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Ο πίνακας job_listings δεν υπάρχει\n";
}

// Έλεγχος για τον πίνακα driver_job_listings
$stmt = $pdo->query('SHOW TABLES LIKE "driver_job_listings"');
if ($stmt->rowCount() > 0) {
    echo "\nΠίνακας driver_job_listings υπάρχει\n";
    $stmt = $pdo->query('DESCRIBE driver_job_listings');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "\nΟ πίνακας driver_job_listings δεν υπάρχει\n";

    // Έλεγχος για εναλλακτικούς πίνακες
    $stmt = $pdo->query('SHOW TABLES LIKE "%job%"');
    echo "Πίνακες που περιέχουν 'job':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row[0] . "\n";
    }
}

// Έλεγχος για τον πίνακα job_matches
$stmt = $pdo->query('SHOW TABLES LIKE "job_matches"');
if ($stmt->rowCount() > 0) {
    echo "\nΠίνακας job_matches υπάρχει\n";
    $stmt = $pdo->query('DESCRIBE job_matches');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "\nΟ πίνακας job_matches δεν υπάρχει\n";
}

// Έλεγχος για τον πίνακα job_applications
$stmt = $pdo->query('SHOW TABLES LIKE "job_applications"');
if ($stmt->rowCount() > 0) {
    echo "\nΠίνακας job_applications υπάρχει\n";
    $stmt = $pdo->query('DESCRIBE job_applications');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "\nΟ πίνακας job_applications δεν υπάρχει\n";
}
