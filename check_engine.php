<?php

// Ορισμός του ROOT_DIR
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Έλεγχος του τύπου μηχανής του πίνακα companies
$stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'companies'");
$tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Πληροφορίες για τον πίνακα 'companies':\n";
echo "- Engine: " . $tableInfo['Engine'] . "\n";
echo "- Version: " . $tableInfo['Version'] . "\n";
echo "- Row_format: " . $tableInfo['Row_format'] . "\n";
echo "- Collation: " . $tableInfo['Collation'] . "\n";

// Έλεγχος του τύπου μηχανής του πίνακα drivers
$stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'drivers'");
$tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nΠληροφορίες για τον πίνακα 'drivers':\n";
echo "- Engine: " . $tableInfo['Engine'] . "\n";
echo "- Version: " . $tableInfo['Version'] . "\n";
echo "- Row_format: " . $tableInfo['Row_format'] . "\n";
echo "- Collation: " . $tableInfo['Collation'] . "\n";
