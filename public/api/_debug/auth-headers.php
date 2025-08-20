<?php
header('Content-Type: application/json');

$all = [];
if (function_exists('getallheaders')) {
    $all = getallheaders();
}

$out = [
    '_SERVER' => [
        'HTTP_AUTHORIZATION'          => $_SERVER['HTTP_AUTHORIZATION']          ?? null,
        'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        'AUTH_TYPE'                   => $_SERVER['AUTH_TYPE']                   ?? null,
    ],
    'getallheaders' => $all,
];

echo json_encode($out, JSON_PRETTY_PRINT);
