<?php
// tools/run-sql.php
$dsn  = "mysql:host=127.0.0.1;port=3306;dbname=drivejob;charset=utf8mb4";
$user = "root";
$pass = ""; // ή βάλε τον σωστό κωδικό root

$sqlFile = $argv[1] ?? '';
if (!$sqlFile || !file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found: $sqlFile\n");
    exit(1);
}
$sql = file_get_contents($sqlFile);

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    $pdo->exec($sql);
    echo "OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(2);
}
