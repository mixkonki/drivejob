<?php

/**
 * Cron: έλεγχος αδειών που λήγουν και αποστολή ειδοποιήσεων.
 *
 * Εκτέλεση:  php scripts/cron/check_expiring_licenses.php
 *
 * ΙΣΤΟΡΙΚΟ: η προηγούμενη έκδοση άνοιγε δική της σύνδεση PDO με
 * σκληροκωδικοποιημένα τοπικά credentials (localhost/drivejob/root/κενό) και
 * έγραφε τα πάντα μόνο σε αρχείο log — σε παραγωγή αποτύγχανε αμίλητη.
 * Επίσης δημιουργούσε πίνακες κατά την εκτέλεση· αυτό ανήκει στα migrations.
 *
 * Τώρα: σύνδεση από το .env, έξοδος και στην κονσόλα και στο log,
 * σφάλματα με μη μηδενικό exit code ώστε ο cron να τα αναφέρει.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

date_default_timezone_set('Europe/Athens');

$logFile = ROOT_DIR . '/logs/license_notifications_' . date('Y-m-d') . '.log';
if (!is_dir(dirname($logFile))) {
    @mkdir(dirname($logFile), 0775, true);
}
ini_set('error_log', $logFile);
ini_set('log_errors', '1');

$say = static function (string $message): void {
    echo $message, "\n";
    error_log($message);
};

$say('🔔 Έλεγχος αδειών που λήγουν — ' . date('Y-m-d H:i:s'));

try {
    $pdo    = require ROOT_DIR . '/config/database.php';
    $config = require ROOT_DIR . '/config/notifications.php';

    if ($config['smtp_host'] === '') {
        $say('⚠️  Δεν έχει οριστεί SMTP_HOST στο .env — δεν θα σταλεί κανένα email.');
    }
    if ($config['debug_mode']) {
        $say('ℹ️  debug_mode ενεργό — αναλυτική καταγραφή SMTP (τα emails στέλνονται κανονικά).');
    }

    $emailService = new \Drivejob\Services\EmailService(
        $config['smtp_host'],
        $config['smtp_port'],
        $config['smtp_username'],
        $config['smtp_password'],
        $config['sender_email'],
        $config['sender_name'],
        $config['debug_mode']
    );

    $smsService = new \Drivejob\Services\SmsService(
        $config['sms_api_key'],
        $config['sms_api_url'],
        $config['sms_sender'],
        $config['debug_mode']
    );

    $service = new \Drivejob\Services\NotificationServices($pdo, $emailService, $smsService, $config);
    $results = $service->checkAndSendLicenseExpiryNotifications();

    $total = 0;
    if (is_array($results)) {
        foreach ($results as $category => $items) {
            $count = is_array($items) ? count($items) : 0;
            $total += $count;
            if ($count > 0) {
                $say(sprintf('   %-20s %d', $category, $count));
            }
        }
    } else {
        $say('⚠️  Η υπηρεσία δεν επέστρεψε αποτελέσματα.');
    }

    $say(sprintf('✅ Ολοκληρώθηκε — %d ειδοποιήσεις.', $total));
    exit(0);
} catch (Throwable $e) {
    $say('❌ ΣΦΑΛΜΑ: ' . $e->getMessage());
    error_log($e->getTraceAsString());
    exit(1);
}
