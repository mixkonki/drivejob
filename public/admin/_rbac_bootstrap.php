<?php

declare(strict_types=1);

/**
 * RBAC Bootstrap για admin pages
 * Παρέχει βασικές λειτουργίες χωρίς πλήρη RBAC implementation
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Επιστρέφει το τρέχον user ID από session
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Απλός έλεγχος αν ο χρήστης είναι συνδεδεμένος
 */
function requireLogin(): void
{
    if (!currentUserId()) {
        header('Location: /auth/login');
        exit;
    }
}

// Auto-require login για όλες τις admin σελίδες
requireLogin();
