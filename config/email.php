<?php

/**
 * Email Configuration
 * 
 * Ρυθμίσεις για την αποστολή email μέσω SMTP
 */

// SMTP Server Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Αντικαταστήστε με το email σας
define('SMTP_PASSWORD', 'your-app-password'); // Αντικαταστήστε με το app password σας

// Sender Information
define('SMTP_FROM_EMAIL', 'noreply@drivejob.gr');
define('SMTP_FROM_NAME', 'DriveJob Platform');

// Email Settings
define('EMAIL_DEBUG', false); // Ενεργοποίηση debug mode για emails

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
