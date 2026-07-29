<?php

declare(strict_types=1);
@ini_set("display_errors", "0");
session_start();
?>
<!doctype html>
<html lang="el">

<head>
    <meta charset="utf-8">
    <title>Σύνδεση</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Μικρό, απλό UI -->
    <style>
        body {
            font-family: system-ui, Arial, sans-serif;
            background: #0b0f17;
            color: #e6edf3;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 360px;
            background: #111827;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .35)
        }

        h1 {
            font-size: 18px;
            margin: 0 0 14px
        }

        label {
            display: block;
            margin: 10px 0 6px;
            color: #cbd5e1
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #374151;
            background: #0b1220;
            color: #e6edf3;
        }

        button {
            width: 100%;
            margin-top: 14px;
            padding: 12px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-weight: 600;
            cursor: pointer
        }

        .hint {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 8px
        }

        .error {
            background: #7f1d1d;
            color: #fecaca;
            padding: 8px 10px;
            border-radius: 8px;
            margin-bottom: 12px
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Σύνδεση</h1>
        <?php if (isset($_GET['err']) && $_GET['err'] === 'csrf'): ?>
            <div class="error">CSRF token άκυρο ή λείπει. Δοκίμασε ξανά.</div>
        <?php elseif (isset($_GET['err']) && $_GET['err'] === '429'): ?>
            <div class="error">Πολλές προσπάθειες. Δοκίμασε ξανά σε λίγο.</div>
        <?php elseif (isset($_GET['err']) && $_GET['err'] === 'auth'): ?>
            <div class="error">Εσφαλμένο email ή συνθηματικό.</div>
        <?php endif; ?>

        <form method="post" action="/drivejob/public/auth/login_post.php">
            <input type="hidden" name="csrf_token" id="csrf_token" value="">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" autocomplete="username" required>
            <label for="pass">Συνθηματικό</label>
            <input type="password" id="pass" name="pass" autocomplete="current-password" required>
            <button type="submit">Είσοδος</button>
            <div class="hint">Tip (dev): admin@drivejob.gr / admin123</div>
        </form>
    </div>

    <script>
        (async function() {
            try {
                const r = await fetch("/drivejob/public/api/csrf_token.php", {
                    credentials: "same-origin"
                });
                const j = await r.json();
                var el = document.getElementById("csrf_token");
                if (el && j && j.csrf_token) el.value = j.csrf_token;
            } catch (e) {
                /* αν αποτύχει, θα κοπεί από τον server */ }
        })();
    </script>
</body>

</html>