<?php
require 'src/bootstrap.php';

$pdo = $container->get('pdo');
$stmt = $pdo->query('DESCRIBE drivers');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
