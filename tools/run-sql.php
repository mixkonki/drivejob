<?php
// tools/run-sql.php
// Simple SQL migration runner for WAMP/Windows environment

$dsn  = "mysql:host=127.0.0.1;port=3306;dbname=drivejob;charset=utf8mb4";
$user = "root";
$pass = ""; // κενός κωδικός για WAMP root

$sqlFile = $argv[1] ?? '';
if (!$sqlFile || !file_exists($sqlFile)) {
    fwrite(STDERR, "Usage: php run-sql.php <sql-file>\n");
    fwrite(STDERR, "SQL file not found: $sqlFile\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Failed to read SQL file: $sqlFile\n");
    exit(1);
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "Executing SQL from: $sqlFile\n";
    echo "Connected to database: drivejob\n";
    echo "----------------------------------------\n";

    $pdo->exec($sql);

    echo "----------------------------------------\n";
    echo "Migration executed successfully!\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error executing SQL migration:\n");
    fwrite(STDERR, $e->getMessage() . "\n");
    fwrite(STDERR, "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n");
    exit(2);
}
