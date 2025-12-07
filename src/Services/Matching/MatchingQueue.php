<?php

namespace DriveJob\Services\Matching;

final class MatchingQueue
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $root = realpath(__DIR__ . "/../../..");
        $this->dir = $dir ?: $root . "/storage/queue/matching";
        if (!is_dir($this->dir)) @mkdir($this->dir, 0777, true);
    }

    public function enqueue(array $payload): string
    {
        $id = sprintf("%s-%06d", date("YmdHis"), random_int(0, 999999));
        $path = $this->dir . "/" . $id . ".json";
        $payload["__enqueued_at"] = date("c");
        file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $id;
    }

    public function dequeue(): ?array
    {
        $files = glob($this->dir . "/*.json") ?: [];
        sort($files, SORT_STRING);
        foreach ($files as $f) {
            // Best-effort lock via rename to .lock
            $lock = $f . ".lock";
            if (@rename($f, $lock)) {
                $raw = @file_get_contents($lock);
                @unlink($lock);
                $j = $raw ? json_decode($raw, true) : null;
                return is_array($j) ? $j : null;
            }
        }
        return null;
    }

    public function size(): int
    {
        return count(glob($this->dir . "/*.json") ?: []);
    }
}
