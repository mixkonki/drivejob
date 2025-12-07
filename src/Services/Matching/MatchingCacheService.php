<?php

namespace DriveJob\Services\Matching;

final class MatchingCacheService
{
    private string $dir;
    private int $defaultTtl;

    public function __construct(?string $dir = null, int $defaultTtl = 300)
    {
        $root = realpath(__DIR__ . "/../../..");
        $this->dir = $dir ?: $root . "/storage/cache/matching";
        if (!is_dir($this->dir)) @mkdir($this->dir, 0777, true);
        $this->defaultTtl = $defaultTtl;
    }

    private function keyPath(string $key): string
    {
        $hash = sha1($key);
        return $this->dir . "/" . $hash . ".json";
    }

    public function get(string $key)
    {
        $p = $this->keyPath($key);
        if (!is_file($p)) return null;
        $raw = @file_get_contents($p);
        if ($raw === false) return null;
        $j = json_decode($raw, true);
        if (!is_array($j)) return null;
        $exp = (int)($j["expires_at"] ?? 0);
        if ($exp > 0 && time() > $exp) {
            @unlink($p);
            return null;
        }
        return $j["value"] ?? null;
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $p = $this->keyPath($key);
        $exp = $ttl === null ? (time() + $this->defaultTtl) : (time() + max(1, $ttl));
        $blob = json_encode(["expires_at" => $exp, "value" => $value], JSON_UNESCAPED_UNICODE);
        return @file_put_contents($p, $blob, LOCK_EX) !== false;
    }

    public function delete(string $key): void
    {
        $p = $this->keyPath($key);
        if (is_file($p)) @unlink($p);
    }

    public function purgeExpired(): int
    {
        $count = 0;
        foreach (glob($this->dir . "/*.json") ?: [] as $f) {
            $raw = @file_get_contents($f);
            $j = $raw ? json_decode($raw, true) : null;
            $exp = (int)($j["expires_at"] ?? 0);
            if ($exp > 0 && time() > $exp) {
                @unlink($f);
                $count++;
            }
        }
        return $count;
    }
}
