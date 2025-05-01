<?php
// fix_authcontroller.php

$file = 'src/Controllers/AuthController.php';
$content = file_get_contents($file);

// Αντικατάσταση του λάθους
$content = str_replace('}<?php', '}', $content);

// Αφαίρεση τυχόν γραμμών που περιέχουν μόνο <?php
$lines = explode("\n", $content);
$newLines = [];

foreach ($lines as $line) {
    if (trim($line) === '<?php') {
        continue; // Παράλειψη των γραμμών που περιέχουν μόνο <?php
    }
    $newLines[] = $line;
}

$content = implode("\n", $newLines);

// Δημιουργία backup
copy($file, $file . '.backup');

// Αποθήκευση του διορθωμένου αρχείου
file_put_contents($file, $content);

echo "Το αρχείο διορθώθηκε!\n";
