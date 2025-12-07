<?php

declare(strict_types=1);

/** RBAC bootstrap (χρησιμοποιούμε το API bootstrap και guard-άρουμε admin.access) */
$bootstrap = realpath(__DIR__ . "/../../api/_rbac_bootstrap.php");
if ($bootstrap === false || !is_file($bootstrap)) {
    http_response_code(500);
    echo "Bootstrap not found";
    exit;
}
require_once $bootstrap;

require_once __DIR__ . "/../../../src/RBAC/RBAC.php";

use DriveJob\RBAC\RBAC;

RBAC::requirePermission((int)currentUserId(), "admin.access");

$uid = (int)currentUserId();
?>
<!doctype html>
<html lang="el">

<head>
    <meta charset="utf-8" />
    <title>Matching KPIs</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        :root {
            --bg: #0b1020;
            --fg: #e7eefc;
            --muted: #9fb1d1;
            --card: #121a33;
            --ok: #37d67a;
            --warn: #f5a623;
            --bad: #ff5a5f;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            padding: 24px;
            background: var(--bg);
            color: var(--fg);
            font: 14px/1.4 system-ui, Segoe UI, Roboto, Helvetica, Arial
        }

        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr))
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .25)
        }

        .k {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em
        }

        .v {
            font-size: 28px;
            font-weight: 700;
            margin-top: 6px
        }

        .ok {
            color: var(--ok)
        }

        .warn {
            color: var(--warn)
        }

        .bad {
            color: var(--bad)
        }

        .row {
            display: flex;
            gap: 16px
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace
        }

        .small {
            font-size: 12px;
            color: var(--muted)
        }

        .footer {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        button {
            background: #1b264d;
            border: 1px solid #2a3a77;
            color: var(--fg);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer
        }

        button:hover {
            filter: brightness(1.1)
        }

        .spark {
            height: 40px;
            width: 100%;
        }
    </style>
</head>

<body>
    <h2 style="margin:0 0 16px 0;">Matching — KPIs</h2>
    <div id="grid" class="grid">
        <div class="card">
            <div class="k">p50</div>
            <div id="p50" class="v ok">—</div>
        </div>
        <div class="card">
            <div class="k">p95</div>
            <div id="p95" class="v warn">—</div>
        </div>
        <div class="card">
            <div class="k">p99</div>
            <div id="p99" class="v bad">—</div>
        </div>
        <div class="card">
            <div class="k">hit rate</div>
            <div id="hit" class="v ok">—</div>
        </div>
        <div class="card">
            <div class="k">queue</div>
            <div id="q" class="v">—</div>
        </div>
        <div class="card">
            <div class="k">last 10 (ms)</div>
            <svg id="spark" class="spark" viewBox="0 0 100 40" preserveAspectRatio="none"></svg>
            <div class="small mono" id="tail"></div>
        </div>
    </div>
    <div class="footer">
        <div class="small">Auto-refresh ανά 5s — uid=<?= $uid ?></div>
        <div class="row">
            <button id="refreshBtn">Refresh now</button>
            <button id="enqueueBtn">Enqueue 10 demo jobs</button>
            <a href="/drivejob/public/api/admin/matching_metrics.php?uid=<?= $uid ?>" target="_blank">
                <button>View JSON</button>
            </a>
        </div>
    </div>

    <script>
        const url = `/drivejob/public/api/admin/matching_metrics.php?uid=<?= $uid ?>`;
        const enqueueUrl = `/drivejob/public/api/admin/matching_enqueue_demo.php?uid=<?= $uid ?>&n=10`;
        const $ = (id) => document.getElementById(id);

        function fmtMs(n) {
            if (n == null) return "—";
            return n + "ms";
        }

        function fmtPct(x) {
            if (x == null) return "—";
            return (x * 100).toFixed(1) + "%";
        }

        function colorize($el, n, warn, bad) {
            $el.classList.remove("ok", "warn", "bad");
            if (n == null) return;
            if (n < warn) $el.classList.add("ok");
            else if (n < bad) $el.classList.add("warn");
            else $el.classList.add("bad");
        }

        function drawSpark(values) {
            const svg = $("spark");
            while (svg.firstChild) svg.removeChild(svg.firstChild);
            if (!values || values.length === 0) return;
            const ms = values.map(v => Number(v.duration_ms) || 0).reverse().slice(0, 10);
            const max = Math.max(1, ...ms);
            const pts = ms.map((v, i) => {
                const x = (i / (ms.length - 1 || 1)) * 100;
                const y = 40 - (v / max) * 40;
                return `${x},${y}`;
            }).join(" ");
            const poly = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
            poly.setAttribute("points", pts);
            poly.setAttribute("fill", "none");
            poly.setAttribute("stroke", "currentColor");
            poly.setAttribute("stroke-width", "2");
            svg.appendChild(poly);
            $("tail").textContent = ms.join(", ");
        }

        async function load() {
            try {
                const res = await fetch(url, {
                    cache: "no-store"
                });
                const j = await res.json();

                $("p50").textContent = fmtMs(j.p50_ms);
                colorize($("p50"), j.p50_ms, 5, 15);
                $("p95").textContent = fmtMs(j.p95_ms);
                colorize($("p95"), j.p95_ms, 20, 50);
                $("p99").textContent = fmtMs(j.p99_ms);
                colorize($("p99"), j.p99_ms, 40, 100);
                $("hit").textContent = fmtPct(j.hit_rate);
                $("q").textContent = String(j.queue_depth ?? "—");

                drawSpark(j.last_10 || []);
            } catch (e) {
                console.error(e);
                $("p50").textContent = "ERR";
                $("p95").textContent = "ERR";
                $("p99").textContent = "ERR";
                $("hit").textContent = "ERR";
                $("q").textContent = "ERR";
            }
        }

        $("refreshBtn").addEventListener("click", load);

        // Enqueue button functionality
        $("enqueueBtn").addEventListener("click", async () => {
            try {
                const res = await fetch(enqueueUrl);
                const j = await res.json();
                alert(`Enqueued: ${j.enqueued} jobs`);
                load(); // Refresh metrics after enqueuing
            } catch (e) {
                alert("Enqueue failed: " + e.message);
            }
        });

        load();
        setInterval(load, 5000);
    </script>
</body>

</html>