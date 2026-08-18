<?php

/**
 * Ρυθμίσεις συστήματος ειδοποιήσεων DriveJob.
 *
 * ΙΣΤΟΡΙΚΟ ΑΣΦΑΛΕΙΑΣ: μέχρι το Πακέτο 9 το αρχείο περιείχε τον κωδικό SMTP
 * σε καθαρό κείμενο και ήταν καταχωρημένο στο git — άρα ορατός σε όποιον
 * είχε πρόσβαση στο repo. Πλέον όλα τα διαπιστευτήρια έρχονται από το .env.
 * Ο παλιός κωδικός παραμένει στο ιστορικό του git και πρέπει να αλλαχθεί
 * στον πάροχο.
 */

$env = static function (string $key, $default = null) {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
};

$isProduction = ($env('APP_ENV', 'production') === 'production');

return [
    // ─── Email ───────────────────────────────────────────────
    'smtp_host'     => $env('SMTP_HOST', ''),
    'smtp_port'     => (int) $env('SMTP_PORT', 587),
    'smtp_username' => $env('SMTP_USERNAME', ''),
    'smtp_password' => $env('SMTP_PASSWORD', ''),
    'sender_email'  => $env('SMTP_FROM_EMAIL', $env('SMTP_USERNAME', '')),
    'sender_name'   => $env('SMTP_FROM_NAME', 'DriveJob Ειδοποιήσεις'),

    // ─── SMS (χωρίς πάροχο ακόμη — μένει ανενεργό) ───────────
    'sms_api_key' => $env('SMS_API_KEY', ''),
    'sms_api_url' => $env('SMS_API_URL', ''),
    'sms_sender'  => $env('SMS_SENDER', 'DriveJob'),

    /**
     * debug_mode = αναλυτική καταγραφή (SMTPDebug=2 και logging των SMS).
     * ΔΕΝ είναι dry-run: τα emails και τα SMS αποστέλλονται κανονικά.
     * Σε παραγωγή μένει false ώστε να μη γεμίζουν τα logs με SMTP διάλογο.
     */
    'debug_mode' => filter_var(
        $env('NOTIFICATIONS_DEBUG', $isProduction ? 'false' : 'true'),
        FILTER_VALIDATE_BOOLEAN
    ),

    // ─── Πότε στέλνονται ειδοποιήσεις (ημέρες πριν τη λήξη) ──
    'notification_periods' => [
        'driving_license'  => [60, 30, 15, 7, 1],
        'pei'              => [60, 30, 15, 7, 1],
        'adr_certificate'  => [60, 30, 15, 7, 1],
        'tachograph_card'  => [60, 30, 15, 7, 1],
        'operator_license' => [180, 90, 30, 15],
        'special_license'  => [60, 30, 15, 7, 1],
    ],

    'max_notifications_per_run' => (int) $env('NOTIFICATIONS_MAX_PER_RUN', 100),

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) $env('NOTIFICATIONS_ADMIN_EMAILS', 'admin@drivejob.gr'))
    ))),

    'daily_report_enabled' => filter_var($env('NOTIFICATIONS_DAILY_REPORT', 'true'), FILTER_VALIDATE_BOOLEAN),
    'daily_report_time'    => $env('NOTIFICATIONS_DAILY_REPORT_TIME', '08:00'),

    // ─── Web push (ανενεργό μέχρι να οριστούν κλειδιά VAPID) ─
    'web_push_enabled'     => filter_var($env('WEB_PUSH_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'web_push_public_key'  => $env('WEB_PUSH_PUBLIC_KEY', ''),
    'web_push_private_key' => $env('WEB_PUSH_PRIVATE_KEY', ''),
];
