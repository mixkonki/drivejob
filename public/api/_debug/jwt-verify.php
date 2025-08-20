<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Core\Jwt;

header('Content-Type: application/json');

$auth = $_SERVER['HTTP_AUTHORIZATION']          ?? '';
$auth = $auth ?: ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

if (!$auth && function_exists('getallheaders')) {
    $h = getallheaders();
    if (isset($h['Authorization'])) {
        $auth = $h['Authorization'];
    }
    if (!$auth) {
        foreach ($h as $k => $v) {
            if (strtolower($k) === 'authorization') {
                $auth = $v;
                break;
            }
        }
    }
}

$token = null;
if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
    $token = trim($m[1]);
}

$result = [
    'has_auth' => (bool)$auth,
    'raw_auth' => $auth,
    'has_token' => (bool)$token
];

try {
    if ($token && class_exists('\\Drivejob\\Core\\Jwt')) {
        $payload = Jwt::verify($token);
        $result['verified'] = (bool)$payload;
        $result['payload']  = $payload;
    } else {
        $result['verified'] = false;
        $result['payload']  = null;
    }
} catch (Throwable $e) {
    $result['verified'] = false;
    $result['error']    = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
