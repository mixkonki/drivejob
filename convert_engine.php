<?php

// Ορισμός του ROOT_DIR
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

try {
    // Έλεγχος του τύπου μηχανής του πίνακα companies
    $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'companies'");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Τρέχων τύπος μηχανής του πίνακα 'companies': " . $tableInfo['Engine'] . "\n";

    if ($tableInfo['Engine'] === 'MyISAM') {
        // Μετατροπή του πίνακα companies από MyISAM σε InnoDB
        $alterTableQuery = "ALTER TABLE companies ENGINE = InnoDB";
        $pdo->exec($alterTableQuery);

        // Έλεγχος αν η μετατροπή ήταν επιτυχής
        $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'companies'");
        $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "Νέος τύπος μηχανής του πίνακα 'companies': " . $tableInfo['Engine'] . "\n";

        if ($tableInfo['Engine'] === 'InnoDB') {
            echo "Η μετατροπή του πίνακα 'companies' από MyISAM σε InnoDB ήταν επιτυχής.\n";
        } else {
            echo "Η μετατροπή του πίνακα 'companies' από MyISAM σε InnoDB απέτυχε.\n";
        }
    } else {
        echo "Ο πίνακας 'companies' χρησιμοποιεί ήδη τη μηχανή InnoDB.\n";
    }
} catch (PDOException $e) {
    die("Σφάλμα κατά τη μετατροπή του πίνακα: " . $e->getMessage() . "\n");
}
