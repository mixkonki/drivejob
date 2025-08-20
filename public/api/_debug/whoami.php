<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware as Auth;

header('Content-Type: application/json');

$ok = Auth::requireLogin(true);
$user = \Drivejob\Middleware\AuthenticationMiddleware::getCurrentUser();

echo json_encode([
    'ok'   => (bool)$ok,
    'user' => $user,
    '_SERVER' => [
        'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
    ]
], JSON_PRETTY_PRINT);
