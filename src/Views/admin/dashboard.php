<?php

declare(strict_types=1);
/**
 * Unified Admin Dashboard (single source of truth)
 * DriveJob • Admin Dashboard
 * - Inline KPIs (fetch JSON from admin APIs)
 * - No iframes
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; connect-src 'self';");

// Το user id προέρχεται ΜΟΝΟ από το session (ο controller έχει ήδη ελέγξει ρόλο admin)
$uid = (int)($_SESSION["user_id"] ?? 0);

$base = rtrim(defined('BASE_URL') ? BASE_URL : '/', '/');
$q = "?uid=" . $uid;
$API_METRICS = $base . "/api/admin/metrics" . $q;
$API_PROM    = $base . "/api/admin/metrics-prom" . $q;
$API_ENQ10   = $base . "/api/admin/enqueue-demo" . $q . "&n=10";

?>
<!doctype html>
<html lang="el">

<head>
    <meta charset="utf-8" />
    <title>DriveJob • Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --ink: #e5e7eb;
            --muted: #9ca3af;
            --ok: #10b981;
            --warn: #f59e0b;
            --bad: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 14px/1.45 system-ui, -apple-system, Segoe UI, Roboto, Arial;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #1f2937;
        }

        main {
            padding: 20px;
            max-width: 1280px;
            margin: 0 auto;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid #1f2937;
            border-radius: 14px;
            padding: 16px;
        }

        .span-12 {
            grid-column: span 12;
        }

        .span-8 {
            grid-column: span 8;
        }

        .span-4 {
            grid-column: span 4;
        }

        h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .kpis {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .kpi {
            background: #0b1220;
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 12px;
            min-height: 76px;
        }

        .kpi .label {
            color: var(--muted);
            font-size: 12px;
        }

        .kpi .val {
            font-size: 22px;
            margin-top: 6px;
        }

        .row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            appearance: none;
            border: 1px solid #374151;
            background: #1f2937;
            color: var(--ink);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
        }

        code {
            color: #93c5fd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        td,
        th {
            border-bottom: 1px solid #1f2937;
            padding: 6px 8px;
        }
    </style>
</head>

<body>
    <header>
        <div><strong>DriveJob • Admin Dashboard</strong></div>
        <div class="row">
            <a class="btn" href="<?= htmlspecialchars($API_METRICS) ?>" target="_blank">Metrics JSON</a>
            <a class="btn" href="<?= htmlspecialchars($API_PROM) ?>" target="_blank">Prometheus</a>
            <button class="btn" id="enqueue">+ Enqueue 10 demo jobs</button>
        </div>
    </header>
    <main>
        <section class="card span-12">
            <h3>Matching KPIs</h3>
            <div class="kpis" id="kpis">
                <div class="kpi">
                    <div class="label">Samples</div>
                    <div class="val" id="k_samples">—</div>
                </div>
                <div class="kpi">
                    <div class="label">p50 (ms)</div>
                    <div class="val" id="k_p50">—</div>
                </div>
                <div class="kpi">
                    <div class="label">p95 (ms)</div>
                    <div class="val" id="k_p95">—</div>
                </div>
                <div class="kpi">
                    <div class="label">p99 (ms)</div>
                    <div class="val" id="k_p99">—</div>
                </div>
                <div class="kpi">
                    <div class="label">Cache Hit</div>
                    <div class="val" id="k_hit">—</div>
                </div>
            </div>
            <div class="row" style="margin-top:10px;color:var(--muted)">
                Queue depth: <code id="k_qd">—</code>
            </div>
            <div id="last10" style="margin-top:12px"></div>
            <div id="flash" style="margin-top:10px;color:var(--muted)"></div>
        </section>
    </main>

    <script nonce="<?= htmlspecialchars($nonce) ?>">
        const $ = (id) => document.getElementById(id);
        const fmt = (v) => v === null || v === undefined ? "—" : String(v);

        async function loadMetrics() {
            try {
                const res = await fetch("<?= addslashes($API_METRICS) ?>", {
                    credentials: "same-origin"
                });
                const j = await res.json();
                $("k_samples").textContent = fmt(j.samples);
                $("k_p50").textContent = fmt(j.p50_ms);
                $("k_p95").textContent = fmt(j.p95_ms);
                $("k_p99").textContent = fmt(j.p99_ms);
                $("k_hit").textContent = (j.hit_rate != null) ? (Math.round(j.hit_rate * 1000) / 10 + "%") : "—";
                $("k_qd").textContent = fmt(j.queue_depth);

                const rows = (j.last_10 || []).map((r, i) => (
                    `<tr><td>${i+1}</td><td>${r.duration_ms}</td><td>${r.cache_hit}</td><td>${r.created_at}</td></tr>`
                )).join("");
                $("last10").innerHTML = `
          <table>
            <thead><tr><th>#</th><th>ms</th><th>hit</th><th>time</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>`;
            } catch (e) {
                $("flash").textContent = "metrics error";
            }
        }
        setInterval(loadMetrics, 5000);
        loadMetrics();

        $("enqueue").addEventListener("click", async () => {
            $("flash").textContent = "enqueue...";
            try {
                const res = await fetch("<?= addslashes($API_ENQ10) ?>", {
                    credentials: "same-origin"
                });
                const t = await res.text();
                try {
                    const j = JSON.parse(t);
                    $("flash").textContent = j.ok ? "OK (10 jobs enqueued)" : "error";
                } catch {
                    $("flash").textContent = "OK";
                }
                setTimeout(loadMetrics, 700);
            } catch {
                $("flash").textContent = "network error";
            }
        });
    </script>
</body>

</html>