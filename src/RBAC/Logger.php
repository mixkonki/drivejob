<?php

namespace DriveJob\RBAC;

final class Logger
{
    public static function deny(string $reason, array $ctx = []): void
    {
        $dir = __DIR__ . "/../../storage/logs";
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $line = date("c") . " DENY " . $reason . " " . json_encode($ctx, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($dir . "/rbac.log", $line, FILE_APPEND);
    }
}
