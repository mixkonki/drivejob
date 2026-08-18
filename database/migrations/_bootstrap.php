<?php

/**
 * Κοινή σύνδεση για τα migrations (Πακέτο 9).
 *
 * Διαβάζει τα στοιχεία από το .env μέσω του config/db.php — ώστε τα migrations
 * να τρέχουν σωστά και σε παραγωγή, χωρίς καρφωμένα credentials.
 *
 * Χρήση μέσα σε migration:   $pdo = require __DIR__ . '/_bootstrap.php';
 */

$root = dirname(__DIR__, 2);

if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}
if (class_exists(\Dotenv\Dotenv::class) && is_file($root . '/.env')) {
    \Dotenv\Dotenv::createImmutable($root)->safeLoad();
}

$cfg = require $root . '/config/db.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $cfg['host'],
    $cfg['port'] ?? '3306',
    $cfg['database'],
    $cfg['charset'] ?? 'utf8mb4'
);

try {
    return new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options'] ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "❌ Αποτυχία σύνδεσης στη βάση: " . $e->getMessage() . "\n");
    fwrite(STDERR, "   Έλεγξε τα DB_* στο .env του project.\n");
    exit(1);
}
