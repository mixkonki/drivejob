<!doctype html>
<html lang="el">

<head>
    <meta charset="utf-8" />
    <title>DriveJob • RBAC Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <script defer src="../assets/js/http.js"></script>
    <style>
        :root {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial
        }

        body {
            margin: 20px
        }

        h1 {
            margin: 0 0 16px
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04)
        }

        table {
            border-collapse: collapse;
            width: 100%
        }

        td,
        th {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: top
        }

        pre {
            background: #111;
            color: #eee;
            padding: 10px;
            border-radius: 10px;
            max-height: 280px;
            overflow: auto
        }

        .muted {
            opacity: .7
        }

        .row {
            display: flex;
            gap: 8px;
            align-items: center
        }

        button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
            cursor: pointer
        }

        button:hover {
            background: #f0f0f0
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace
        }
    </style>
</head>

<body>
    <h1>RBAC Admin Dashboard</h1>
    <div class="row">
        <div id="whoami" class="muted">Φόρτωση χρήστη...</div>
        <button id="refresh">Ανανέωση</button>
        <a class="muted mono" href="../api/csrf_token.php">csrf_token</a>
    </div>

    <div class="grid" style="margin-top:16px;">
        <div class="card">
            <h3>RBAC Matrix</h3>
            <table id="rolesTbl">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card">
            <h3>Users (primary role)</h3>
            <table id="usersTbl">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Primary Role</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card">
            <h3>Rate Limit Buckets</h3>
            <table id="rateTbl">
                <thead>
                    <tr>
                        <th>Bucket</th>
                        <th>Count</th>
                        <th>Reset (sec)</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card">
            <h3>Recent RBAC Logs</h3>
            <pre id="logs">(no lines)</pre>
        </div>
        <div class="card">
            <h3>Policy Introspection</h3>
            <div class="row">
                <input id="permInput" placeholder="π.χ. jobs.edit.any" style="flex:1;padding:6px 8px;border:1px solid #ccc;border-radius:8px" />
                <button id="checkWho">Who can?</button>
            </div>
            <div style="margin-top:8px">
                <div><b>Roles:</b> <span id="whoRoles" class="mono muted">—</span></div>
                <div style="margin-top:6px"><b>Sample users:</b>
                    <div id="whoUsers" class="mono muted">(none)</div>
                </div>
            </div>
            <div style="margin-top:12px" class="row">
                <input id="canUid" type="number" placeholder="user id" style="width:120px;padding:6px 8px;border:1px solid #ccc;border-radius:8px" />
                <input id="canPerm" placeholder="permission" style="flex:1;padding:6px 8px;border:1px solid #ccc;border-radius:8px" />
                <button id="checkCan">Can user?</button>
                <span id="canResult" class="mono muted"></span>
            </div>
        </div>
    </div>

    <script>
        async function loadAll() {
            const uid = new URLSearchParams(location.search).get("uid") || 1;
            const who = await DJHttp.get("../api/whoami.php?uid=" + uid);
            document.getElementById("whoami").textContent = "User #" + who.user_id + " • primary: " + (who.primary_role?.name || "—");

            const m = await DJHttp.get("../api/rbac_matrix.php?uid=" + uid);
            const rolesTbody = document.querySelector("#rolesTbl tbody");
            rolesTbody.innerHTML = "";
            m.roles.forEach(r => {
                const tr = document.createElement("tr");
                tr.innerHTML = "<td class='mono'>" + r.role + "</td><td>" + (r.perms || "<i>(none)</i>") + "</td>";
                rolesTbody.appendChild(tr);
            });
            const usersTbody = document.querySelector("#usersTbl tbody");
            usersTbody.innerHTML = "";
            m.users.forEach(u => {
                const tr = document.createElement("tr");
                tr.innerHTML = "<td class='mono'>" + u.id + "</td><td>" + u.username + "</td><td class='mono'>" + (u.primary_role || "—") + "</td>";
                usersTbody.appendChild(tr);
            });

            const rate = await DJHttp.get("../api/ratelimit_status.php?uid=" + uid);
            const rateTbody = document.querySelector("#rateTbl tbody");
            rateTbody.innerHTML = "";
            (rate.buckets || []).forEach(b => {
                const tr = document.createElement("tr");
                tr.innerHTML = "<td class='mono'>" + b.bucket + "</td><td>" + b.count + "</td><td>" + b.reset_in + "</td>";
                rateTbody.appendChild(tr);
            });

            const logs = await DJHttp.get("../api/rbac_logs.php?uid=" + uid);
            document.getElementById("logs").textContent = (logs.lines || []).join("\n") || "(no lines)";
        }
        document.getElementById("refresh").addEventListener("click", loadAll);
        loadAll();
    </script>
</body>

</html>