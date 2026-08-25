<?php

/**
 * Γλωσσικές ικανότητες ως αυτόνομες εγγραφές — πίνακας driver_languages.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Το παλιό μοντέλο ήταν 5 καρφωτές στήλες στον πίνακα drivers
 * (language_greek…language_italian) + ένα ζευγάρι «άλλη γλώσσα». Ένας
 * οδηγός διεθνών μεταφορών με βουλγαρικά ΚΑΙ τουρκικά δεν χωρούσε —
 * και στις μεταφορές αυτό δεν είναι σπάνιο, είναι ο κανόνας των
 * βαλκανικών δρομολογίων.
 *
 * Νέο μοντέλο: μία γραμμή ανά γλώσσα, ελεύθερο όνομα, επίπεδο από το
 * ίδιο ENUM. Ίδια φιλοσοφία με την προϋπηρεσία οχημάτων: αυτόνομες
 * εγγραφές, άμεση αποθήκευση ανά προσθήκη/διαγραφή.
 *
 * Οι παλιές στήλες ΔΕΝ διαγράφονται (τις διαβάζουν ακόμη το PDF
 * βιογραφικού και το οπτικό βιογραφικό)· συγχρονίζονται αυτόματα από
 * τον νέο πίνακα σε κάθε αλλαγή (SkillModel::syncLegacyLanguageColumns).
 * Θα φύγουν στο beta-cleanup, μαζί με την αναθεώρηση του βιογραφικού.
 *
 * Idempotent: CREATE TABLE IF NOT EXISTS + μεταφορά δεδομένων ΜΟΝΟ για
 * οδηγούς που δεν έχουν καμία γραμμή στον νέο πίνακα.
 */

require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "── Πίνακας driver_languages ─────────────────────────────\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS driver_languages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        driver_id INT NOT NULL,
        language_name VARCHAR(50) NOT NULL,
        level ENUM('native','fluent','good','basic') NOT NULL DEFAULT 'good',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_driver_language (driver_id, language_name),
        KEY idx_driver (driver_id),
        CONSTRAINT fk_driver_languages_driver FOREIGN KEY (driver_id)
            REFERENCES drivers (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "   πίνακας: OK\n";

/*
 * Μεταφορά υπαρχόντων δεδομένων από τις στήλες του drivers.
 * Μόνο για οδηγούς χωρίς καμία εγγραφή στον νέο πίνακα — ξανατρέξιμο
 * του migration δεν διπλασιάζει τίποτα.
 */
$map = [
    'language_greek' => 'Ελληνικά',
    'language_english' => 'Αγγλικά',
    'language_german' => 'Γερμανικά',
    'language_french' => 'Γαλλικά',
    'language_italian' => 'Ιταλικά',
];

$drivers = $pdo->query(
    'SELECT d.id, d.language_greek, d.language_english, d.language_german,
            d.language_french, d.language_italian,
            d.language_other_name, d.language_other_level
     FROM drivers d
     LEFT JOIN driver_languages dl ON dl.driver_id = d.id
     WHERE dl.id IS NULL'
)->fetchAll(PDO::FETCH_ASSOC);

$insert = $pdo->prepare(
    'INSERT IGNORE INTO driver_languages (driver_id, language_name, level) VALUES (?, ?, ?)'
);

$levels = ['native', 'fluent', 'good', 'basic'];
$migrated = 0;

foreach ($drivers as $driver) {
    foreach ($map as $column => $name) {
        $level = $driver[$column] ?? null;
        if (in_array($level, $levels, true)) {
            $insert->execute([$driver['id'], $name, $level]);
            $migrated += $insert->rowCount();
        }
    }

    $otherName = trim((string) ($driver['language_other_name'] ?? ''));
    $otherLevel = $driver['language_other_level'] ?? null;
    if ($otherName !== '' && in_array($otherLevel, $levels, true)) {
        $insert->execute([$driver['id'], mb_substr($otherName, 0, 50), $otherLevel]);
        $migrated += $insert->rowCount();
    }
}

echo "   μεταφέρθηκαν γραμμές: $migrated (από " . count($drivers) . " οδηγούς χωρίς εγγραφές)\n";

$total = (int) $pdo->query('SELECT COUNT(*) FROM driver_languages')->fetchColumn();
echo "   σύνολο στον πίνακα: $total\n";
echo "── Ολοκληρώθηκε ─────────────────────────────────────────\n";
