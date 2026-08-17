<?php

declare(strict_types=1);

// Light session-based guard for admin pages
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Απαιτείται σύνδεση ΚΑΙ ρόλος admin
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
if (empty($_SESSION['user_id']) || $role !== 'admin') {
    header('Location: /auth/access-denied');
    exit;
}
