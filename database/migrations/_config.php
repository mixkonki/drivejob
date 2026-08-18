<?php

/**
 * Ρυθμίσεις βάσης για migrations (Πακέτο 9) — από το .env, όχι καρφωμένες.
 * Χρήση:  $cfg = require __DIR__ . '/_config.php';
 */

$root = dirname(__DIR__, 2);

if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}
if (class_exists(\Dotenv\Dotenv::class) && is_file($root . '/.env')) {
    \Dotenv\Dotenv::createImmutable($root)->safeLoad();
}

return require $root . '/config/db.php';
