<?php

/**
 * Email Configuration
 * 
 * Ρυθμίσεις για την αποστολή email μέσω SMTP
 */

if (defined('SMTP_HOST')) {
    return; // ήδη φορτωμένο
}

// SMTP Server Settings
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');

// Sender Information
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? ($_ENV['SMTP_USERNAME'] ?? ''));
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'DriveJob');

// Email Settings
define('EMAIL_DEBUG', filter_var($_ENV['EMAIL_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN)); // Ενεργοποίηση debug mode για emails

/**
 * Οδηγίες για Gmail:
 * 1. Ενεργοποιήστε 2-factor authentication στο Gmail
 * 2. Δημιουργήστε App Password: https://myaccount.google.com/apppasswords
 * 3. Χρησιμοποιήστε το App Password αντί του κανονικού password
 * 
 * Για άλλους SMTP servers:
 * - Outlook/Hotmail: smtp.live.com, port 587
 * - Yahoo: smtp.mail.yahoo.com, port 587
 * - Custom: Χρησιμοποιήστε τις ρυθμίσεις του provider σας
 */
