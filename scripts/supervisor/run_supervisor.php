<?php

declare(strict_types=1);

/**
 * Minimal runner: τρέχει ένα απλό loop και φορτώνει το autoregister.
 * Αν υπάρχει πραγματικός Supervisor/Container στο project, μπορείς αργότερα
 * να αντικαταστήσεις αυτό το runner με τον δικό σου.
 */

$root = realpath(__DIR__ . "/../..");
$stopFile = $root . "/storage/supervisor.stop";
@unlink($stopFile);

// Fallback "registry"
class _SimpleRegistry
{
    private array $services = [];
    public function register($svc): void
    {
        $this->services[] = $svc;
    }
    public function all(): array
    {
        return $this->services;
    }
}

// Fallback "main"
class _SimpleMain
{
    private _SimpleRegistry $reg;
    private string $stopFile;
    public function __construct(_SimpleRegistry $reg, string $stopFile)
    {
        $this->reg = $reg;
        $this->stopFile = $stopFile;
    }
    public function addService($svc): void
    {
        $this->reg->register($svc);
    }
    public function runLoop(int $seconds = 60): void
    {
        $t0 = time();
        while (true) {
            foreach ($this->reg->all() as $svc) {
                if (is_object($svc) && method_exists($svc, "tick")) {
                    try {
                        $svc->tick();
                    } catch (\Throwable $e) { /* swallow */
                    }
                }
            }
            usleep(100_000); // 100ms
            if (is_file($this->stopFile)) break;
            if ((time() - $t0) >= $seconds) break;
        }
    }
}

// Fallback objects που περιμένει το autoregister
$registry = new _SimpleRegistry();
$main     = new _SimpleMain($registry, $stopFile);

// Φόρτωσε το autoregister (θα καταχωρήσει π.χ. MatchingWorkerService)
$auto = __DIR__ . "/autoregister_services.php";
if (is_file($auto)) {
    require $auto;
}

// Τρέξε το loop
$main->runLoop(3600);
