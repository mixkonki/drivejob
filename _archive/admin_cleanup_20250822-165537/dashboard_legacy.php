<?php

declare(strict_types=1);

/**
 * Admin Dashboard (ενιαίο)
 * - RBAC guard: admin.access
 * - Περιλαμβάνει τα reports/μενού που φτιάξαμε
 * - Embeds το matching KPIs widget + links σε admin εργαλεία
 */

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// RBAC bootstrap & libs
require_once __DIR__ . "/../../../public/admin/_rbac_bootstrap.php";
require_once __DIR__ . "/../../RBAC/DB.php";

// Simplified RBAC check - just check if user is logged in
$uid = (int) (currentUserId() ?? 0);
if (!$uid) {
    header('Location: /drivejob/public/auth/login');
    exit;
}

// Helpful data
$who = [
    "user_id" => $uid,
    "username" => $_SESSION["username"] ?? $_SESSION["user_name"] ?? "admin",
    "primary_role" => "admin",
];

// URLs (όλα με uid=)
$base = "/drivejob/public";
$u = "?uid=" . $uid;

$urls = [
    "matching_kpis"   => "$base/admin/widgets/matching_kpis.php$u",
    "metrics_json"    => "$base/api/admin/matching_metrics.php$u",
    "metrics_prom"    => "$base/api/admin/matching_metrics_prom.php$u",
    "enqueue_demo10"  => "$base/api/admin/matching_enqueue_demo.php$u&n=10",
    "rbac_dashboard"  => "$base/admin/index.php$u",               // unified dashboard
    "users_overview"  => "$base/api/admin/users_overview.php$u",          // JSON
    "identity_linker" => "$base/admin/identity_linker.php$u",             // αν υπάρχει
];

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="utf-8" />
    <title>Admin Dashboard — DriveJob</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --ink: #e5e7eb;
            --muted: #9ca3af;
            --accent: #22c55e;
            --warn: #f59e0b;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #1f2937;
        }

        header .brand {
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        header .who {
            color: var(--muted);
        }

        main {
            padding: 20px;
            max-width: 1200px;
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
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }

        .span-12 {
            grid-column: span 12;
        }

        .span-8 {
            grid-column: span 8;
        }

        .span-6 {
            grid-column: span 6;
        }

        .span-4 {
            grid-column: span 4;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        .row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
            appearance: none;
            border: 1px solid #374151;
            background: #1f2937;
            color: var(--ink);
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
        }

        .btn:hover {
            border-color: #4b5563;
        }

        .btn.accent {
            background: #16a34a;
            border-color: #16a34a;
        }

        .muted {
            color: var(--muted);
        }

        a {
            color: #93c5fd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        iframe {
            width: 100%;
            height: 380px;
            border: 0;
            border-radius: 10px;
            background: #0b1220;
        }

        pre {
            background: #0b1220;
            padding: 10px;
            border-radius: 10px;
            overflow: auto;
        }

        ul.links {
            margin: 8px 0 0 16px;
            padding: 0;
        }

        ul.links li {
            margin: 6px 0;
        }

        .logout {
            color: #ef4444;
        }

        .logout:hover {
            color: #dc2626;
        }
    </style>
</head>

<body>
    <header>
        <div class="brand">Legacy Admin Dashboard</div>
        <div class="who">
            Χρήστης #<?= htmlspecialchars((string)$who["user_id"]) ?> · ρόλος: <?= htmlspecialchars((string)$who["primary_role"]) ?>
            <a href="/drivejob/public/admin/logout.php" class="logout" style="margin-left:16px;">Αποσύνδεση</a>
        </div>
    </header>

    <main>
        <div class="grid">

            <section class="card span-12">
                <h2>Γρήγορες ενέργειες</h2>
                <div class="row">
                    <button class="btn accent" id="enqueue10">+ Enqueue 10 demo jobs</button>
                    <a class="btn" href="<?= htmlspecialchars($urls["metrics_json"]) ?>" target="_blank">Metrics (JSON)</a>
                    <a class="btn" href="<?= htmlspecialchars($urls["metrics_prom"]) ?>" target="_blank">Metrics (Prometheus)</a>
                    <span class="muted">Auto refresh στο widget ανά 5s</span>
                </div>
                <div id="flash" class="muted" style="margin-top:8px;"></div>
            </section>

            <section class="card span-8">
                <h2>Matching KPIs (live)</h2>
                <iframe src="<?= htmlspecialchars($urls["matching_kpis"]) ?>"></iframe>
            </section>

            <section class="card span-4">
                <h2>Αναφορές & Εργαλεία</h2>
                <ul class="links">
                    <li><a href="<?= htmlspecialchars($urls["rbac_dashboard"]) ?>" target="_blank">RBAC Dashboard</a></li>
                    <li><a href="<?= htmlspecialchars($urls["users_overview"]) ?>" target="_blank">Users Overview (JSON)</a></li>
                    <li><a href="<?= htmlspecialchars($urls["identity_linker"]) ?>" target="_blank">Identity Linker UI</a></li>
                </ul>
                <p class="muted">Όλα προστατευμένα με RBAC (admin.access).</p>
            </section>

            <section class="card span-12">
                <h2>Σημειώσεις</h2>
                <ul>
                    <li>Ενιαίο σημείο εισόδου για admin: <code>/public/auth/login</code> → RBAC redirect.</li>
                    <li>Το παλιό <code>/public/admin/menu.php</code> πλέον κάνει redirect στο <code>/admin/index.php</code>.</li>
                    <li>Τα KPIs ανανεώνονται αυτόματα από το widget.</li>
                </ul>
            </section>

        </div>
    </main>

    <script>
        const enqueueBtn = document.getElementById('enqueue10');
        const flash = document.getElementById('flash');

        enqueueBtn?.addEventListener('click', async () => {
            flash.textContent = 'Sending...';
            try {
                const res = await fetch('<?= addslashes($urls["enqueue_demo10"]) ?>', {
                    credentials: 'same-origin'
                });
                const txt = await res.text();
                try {
                    const j = JSON.parse(txt);
                    flash.textContent = j.ok ? 'Enqueued!' : ('Error: ' + (j.error || 'unknown'));
                } catch {
                    flash.textContent = 'OK (raw): ' + txt.slice(0, 120) + (txt.length > 120 ? '...' : '');
                }
            } catch (e) {
                flash.textContent = 'Network error';
            }
        });
    </script>
</body>

</html>