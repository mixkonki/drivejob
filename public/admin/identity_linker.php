<?php
require_once __DIR__ . "/../api/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;

$uid = (int)(currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);
?>
<!doctype html>
<html lang="el">

<head>
    <meta charset="utf-8">
    <title>Identity Linker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #fafafa;
            margin: 0;
            padding: 24px;
            color: #222
        }

        .card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
            padding: 16px;
            max-width: 920px;
            margin: 0 auto 16px
        }

        .row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap
        }

        .mono {
            font-family: ui-monospace, Menlo, Consolas, monospace
        }

        input,
        select,
        button {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px
        }

        button {
            cursor: pointer
        }

        .list {
            border: 1px solid #eee;
            border-radius: 8px;
            background: #fff;
            max-height: 220px;
            overflow: auto;
            padding: 6px
        }

        .item {
            padding: 6px 8px;
            border-bottom: 1px solid #f2f2f2;
            cursor: pointer
        }

        .item:hover {
            background: #f7f7f7
        }

        .muted {
            color: #666
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>🔗 Link user ↔ company/driver</h2>
        <div class="row">
            <select id="entity">
                <option value="company">company</option>
                <option value="driver">driver</option>
            </select>
            <input id="entityId" type="number" placeholder="entity id" style="width:140px">
            <input id="userSearch" placeholder="αναζήτηση χρήστη (email/username)" style="flex:1">
            <button id="searchBtn">Search</button>
            <label class="row" style="gap:4px"><input id="override" type="checkbox"> override</label>
        </div>
        <div id="results" class="list mono" style="margin-top:8px;display:none"></div>
        <div class="row" style="margin-top:10px">
            <span> επιλεγμένος user_id: <b id="picked" class="mono muted">—</b></span>
            <button id="btnLink">Link</button>
            <button id="btnUnlink">Unlink</button>
            <span id="msg" class="mono muted"></span>
        </div>
    </div>

    <script>
        const state = {
            csrf: null,
            picked: null
        };
        async function getCSRF() {
            const r = await fetch("../api/csrf_token.php?uid=<?= $uid ?>");
            const j = await r.json();
            state.csrf = j.csrf_token;
            return j.csrf_token;
        }
        async function search() {
            const q = document.getElementById("userSearch").value.trim();
            if (!q) {
                return
            }
            const r = await fetch("../api/admin/users_overview.php?uid=<?= $uid ?>&limit=100&q=" + encodeURIComponent(q));
            const j = await r.json();
            const box = document.getElementById("results");
            box.innerHTML = "";
            (j.items || []).forEach(row => {
                const div = document.createElement("div");
                div.className = "item";
                div.textContent = `#${row.id} ${row.username}  [roles: ${row.roles||"-"}]`;
                div.onclick = () => {
                    state.picked = row.id;
                    document.getElementById("picked").textContent = String(row.id);
                };
                box.appendChild(div);
            });
            box.style.display = "block";
        }
        async function callLinker(action) {
            const entity = document.getElementById("entity").value;
            const id = parseInt(document.getElementById("entityId").value || "0");
            const override = document.getElementById("override").checked ? 1 : 0;
            const body = {
                entity,
                action,
                id,
                override
            };
            if (action === "link") {
                body.user_id = state.picked;
            }
            if (!id || (action === "link" && !state.picked)) {
                document.getElementById("msg").textContent = "⛔ συμπλήρωσε id/χρήστη";
                return;
            }

            const r = await fetch("../api/admin/link_identity.php?uid=<?= $uid ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": state.csrf
                },
                body: JSON.stringify(body)
            });
            const j = await r.json();
            document.getElementById("msg").textContent = r.ok ? JSON.stringify(j) : `⛔ ${r.status} ${JSON.stringify(j)}`;
        }
        document.getElementById("searchBtn").onclick = search;
        document.getElementById("btnLink").onclick = () => callLinker("link");
        document.getElementById("btnUnlink").onclick = () => callLinker("unlink");
        getCSRF();
    </script>
</body>

</html>