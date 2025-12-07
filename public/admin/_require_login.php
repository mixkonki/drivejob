<?php

declare(strict_types=1);

// Light session-based guard for admin pages
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?err=7');
    exit;
}
