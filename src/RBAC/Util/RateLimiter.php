<?php

namespace DriveJob\RBAC\Util;

final class RateLimiter
{
    private static function fileFor(string $bucket): string
    {
        $safe = preg_replace("/[^A-Za-z0-9_.:-]/", "_", $bucket);
        return __DIR__ . "/../../../storage/cache/ratelimit/" . $safe . ".json";
    }

    /**
     * @return array{allowed:bool, remaining:int, reset:int}
     */
    public static function check(string $bucket, int $max, int $windowSec): array
    {
        $file = self::fileFor($bucket);
        $now  = time();
        $state = ["count" => 0, "reset" => $now + $windowSec];

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $j = \json_decode($raw, true);
                if (is_array($j) && isset($j["count"], $j["reset"])) {
                    $state = $j;
                }
            }
            if ($now >= $state["reset"]) {
                $state = ["count" => 0, "reset" => $now + $windowSec];
            }
        }

        $state["count"]++;
        $allowed = $state["count"] <= $max;
        @file_put_contents($file, \json_encode($state));

        $remaining = max(0, $max - $state["count"]);
        return ["allowed" => $allowed, "remaining" => $remaining, "reset" => $state["reset"]];
    }
}
