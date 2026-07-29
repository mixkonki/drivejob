<?php

namespace DriveJob\RBAC\Util;

final class Http
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    public static function jsonError(string $message, array $extra = [], int $code = 403): void
    {
        self::json(array_merge(['error' => 'Forbidden', 'message' => $message], $extra), $code);
    }
}
