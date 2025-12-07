<?php

/**
 * Test page για έλεγχο session και cookies
 */
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;

// Start session
Session::start();

// Set a test value
if (!Session::has('test_counter')) {
    Session::set('test_counter', 1);
} else {
    Session::set('test_counter', Session::get('test_counter') + 1);
}

// Generate CSRF token
$csrfToken = CSRF::generateToken();

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session & Cookie Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .info {
            color: blue;
        }

        pre {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        button:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <h1>🔍 Session & Cookie Test</h1>

    <div class="box">
        <h2>Session Status</h2>
        <p class="<?= Session::isStarted() ? 'success' : 'error' ?>">
            Session Status: <?= Session::isStarted() ? '✅ ACTIVE' : '❌ NOT ACTIVE' ?>
        </p>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
        <p><strong>Session Name:</strong> <?= session_name() ?></p>
        <p><strong>Test Counter:</strong> <?= Session::get('test_counter') ?>
            <span class="info">(Ανανεώστε τη σελίδα για να αυξηθεί)</span>
        </p>
    </div>

    <div class="box">
        <h2>Cookie Parameters</h2>
        <pre><?php print_r(session_get_cookie_params()); ?></pre>
    </div>

    <div class="box">
        <h2>CSRF Token</h2>
        <p><strong>Token:</strong> <?= substr($csrfToken, 0, 20) ?>...</p>
        <p class="success">✅ CSRF Token δημιουργήθηκε επιτυχώς</p>
    </div>

    <div class="box">
        <h2>Browser Cookies</h2>
        <pre><?php print_r($_COOKIE); ?></pre>
    </div>

    <div class="box">
        <h2>Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div class="box">
        <h2>Ενέργειες</h2>
        <button onclick="location.reload()">🔄 Ανανέωση Σελίδας</button>
        <button onclick="location.href='<?= BASE_URL ?>login.php'">🔐 Πήγαινε στο Login</button>
        <button onclick="clearCookies()">🗑️ Καθαρισμός Cookies (F12)</button>
    </div>

    <div class="box">
        <h2>Διαγνωστικά</h2>
        <?php if (Session::get('test_counter') > 1): ?>
            <p class="success">✅ Το Session λειτουργεί! (Counter: <?= Session::get('test_counter') ?>)</p>
            <p class="info">Τα cookies αποθηκεύονται σωστά στον browser σας.</p>
        <?php else: ?>
            <p class="info">ℹ️ Πρώτη φόρτωση. Ανανεώστε τη σελίδα για να δείτε αν το session διατηρείται.</p>
        <?php endif; ?>
    </div>

    <script>
        function clearCookies() {
            alert('Πατήστε F12 → Application/Storage → Cookies → Διαγράψτε όλα τα cookies για localhost');
        }
    </script>
</body>

</html>