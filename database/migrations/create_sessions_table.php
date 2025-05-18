<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν υπάρχει ο πίνακας sessions
$stmt = $pdo->prepare("SHOW TABLES LIKE 'sessions'");
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

if (!$tableExists) {
    echo "Ο πίνακας 'sessions' δεν υπάρχει. Δημιουργία...\n";

    // Δημιουργία του πίνακα sessions
    $sql = "CREATE TABLE sessions (
        id VARCHAR(128) NOT NULL PRIMARY KEY,
        user_id INT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        payload TEXT NOT NULL,
        last_activity INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $pdo->exec($sql);
        echo "Ο πίνακας 'sessions' δημιουργήθηκε με επιτυχία.\n";
    } catch (\PDOException $e) {
        echo "Σφάλμα κατά τη δημιουργία του πίνακα 'sessions': " . $e->getMessage() . "\n";
    }
} else {
    echo "Ο πίνακας 'sessions' υπάρχει ήδη.\n";

    // Έλεγχος αν ο πίνακας έχει τις απαραίτητες στήλες
    $stmt = $pdo->prepare("SHOW COLUMNS FROM sessions");
    $stmt->execute();
    $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    $requiredColumns = ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'];
    $missingColumns = array_diff($requiredColumns, $columns);

    if (!empty($missingColumns)) {
        echo "Λείπουν οι ακόλουθες στήλες από τον πίνακα 'sessions': " . implode(', ', $missingColumns) . "\n";

        // Προσθήκη των στηλών που λείπουν
        foreach ($missingColumns as $column) {
            $sql = "";
            switch ($column) {
                case 'id':
                    $sql = "ALTER TABLE sessions ADD COLUMN id VARCHAR(128) NOT NULL PRIMARY KEY";
                    break;
                case 'user_id':
                    $sql = "ALTER TABLE sessions ADD COLUMN user_id INT NULL";
                    break;
                case 'ip_address':
                    $sql = "ALTER TABLE sessions ADD COLUMN ip_address VARCHAR(45) NULL";
                    break;
                case 'user_agent':
                    $sql = "ALTER TABLE sessions ADD COLUMN user_agent TEXT NULL";
                    break;
                case 'payload':
                    $sql = "ALTER TABLE sessions ADD COLUMN payload TEXT NOT NULL";
                    break;
                case 'last_activity':
                    $sql = "ALTER TABLE sessions ADD COLUMN last_activity INT NOT NULL";
                    break;
            }

            try {
                if (!empty($sql)) {
                    $pdo->exec($sql);
                    echo "Η στήλη '$column' προστέθηκε με επιτυχία.\n";
                }
            } catch (\PDOException $e) {
                echo "Σφάλμα κατά την προσθήκη της στήλης '$column': " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "Ο πίνακας 'sessions' έχει όλες τις απαραίτητες στήλες.\n";
    }
}

echo "Ολοκλήρωση ελέγχου του πίνακα 'sessions'.\n";
