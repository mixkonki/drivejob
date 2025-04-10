<?php
// src/Core/AuthMiddleware.php

namespace Drivejob\Core;

class AuthMiddleware
{
    /**
     * Ελέγχει αν ο χρήστης είναι συνδεδεμένος
     */
    public static function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει τον απαιτούμενο ρόλο
     */
    public static function hasRole($role)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    /**
     * Ελέγχει αν ο χρήστης έχει έναν από τους απαιτούμενους ρόλους
     */
    public static function hasAnyRole($roles)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
}