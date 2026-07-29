<?php

namespace DriveJob\RBAC;

use PDO;

final class DB
{
    private static ?PDO $pdo = null;

    /** Επιστρέφει PDO connection (singleton). */
    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $config = include __DIR__ . '/../../config/db.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }
}
