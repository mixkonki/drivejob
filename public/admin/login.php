<?php

declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$err = isset($_GET["err"]) ? (int)$_GET["err"] : 0;
$map = [
    0 => "",
    1 => "Εσφαλμένο email.",
    2 => "Εσφαλμένο συνθηματικό.",
    3 => "Πολλές προσπάθειες. Δοκίμασε ξανά σε λίγο.",
    4 => "Ο λογαριασμός είναι ανενεργός/κλειδωμένος.",
    7 => "Απαιτείται σύνδεση."
];
$msg = $map[$err] ?? "Σφάλμα σύνδεσης.";
?>
<!doctype html>
<html lang="el">

<head>
    <meta charset="utf-8">
    <title>Admin Login — DriveJob</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial, sans-serif;
            background: #0b0f14;
            color: #e8eef5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0
        }

        .card {
            background: #10151c;
            border: 1px solid #1c2430;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .35);
            padding: 28px;
            min-width: 320px;
            max-width: 380px
        }

        h1 {
            margin: 0 0 16px;
            font-size: 20px
        }

        label {
            display: block;
            margin: 12px 0 6px;
            color: #a8b3c2
        }

        input {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #2a3544;
            background: #0b1118;
            color: #e8eef5
        }

        button {
            margin-top: 18px;
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 0;
            background: #4f8cff;
            color: white;
            font-weight: 600;
            cursor: pointer
        }

        .err {
            background: #29141a;
            border: 1px solid #7a2e40;
            color: #ffb8c1;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 14px
        }

        .hint {
            color: #8ba3be;
            font-size: 12px;
            margin-top: 10px
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Σύνδεση Διαχειριστή</h1>
        <?php if ($err): ?>
            <div class="err"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="login_post.php" autocomplete="off" novalidate>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="admin@drivejob.gr" />
            <label for="password">Συνθηματικό</label>
            <input id="password" name="password" type="password" required value="admin123" />
            <button type="submit">Είσοδος</button>
        </form>
        <div class="hint">Tip (dev): admin@drivejob.gr / admin123</div>
    </div>
</body>

</html>