<?php

/**
 * Εκτέλεση όλων των δοκιμών
 * 
 * Αυτό το αρχείο εκτελεί όλες τις δοκιμές του έργου
 * χρησιμοποιώντας το PHPUnit
 */

// Ορισμός του ROOT_DIR
define('ROOT_DIR', dirname(__DIR__));

// Έλεγχος αν το PHPUnit είναι εγκατεστημένο
if (!file_exists(ROOT_DIR . '/vendor/bin/phpunit')) {
    echo "Το PHPUnit δεν είναι εγκατεστημένο. Παρακαλώ εκτελέστε 'composer require --dev phpunit/phpunit'.\n";
    exit(1);
}

// Εκτέλεση των δοκιμών
echo "Εκτέλεση όλων των δοκιμών...\n";
$command = ROOT_DIR . '/vendor/bin/phpunit --configuration ' . ROOT_DIR . '/phpunit.xml.dist';
$output = [];
$returnVar = 0;

exec($command, $output, $returnVar);

// Εμφάνιση των αποτελεσμάτων
echo implode("\n", $output) . "\n";

// Έξοδος με τον κωδικό επιστροφής του PHPUnit
exit($returnVar);
