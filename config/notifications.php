    <?php
/**
 * Ρυθμίσεις για το σύστημα ειδοποιήσεων της εφαρμογής DriveJob
 * Τοποθέτηση: /config/notifications.php
 */

return [
    // Ρυθμίσεις SMTP για αποστολή email
    'smtp_host' => 'smtp.thessdrive.gr',
    'smtp_port' => 587,
    'smtp_username' => 'info@thessdrive.gr',
    'smtp_password' => 'inf1q2w!Q@W',
    'sender_email' => 'info@thessdrive.gr',
    'sender_name' => 'DriveJob Ειδοποιήσεις',
    // Ρυθμίσεις SMS API
    'sms_api_key' => 'your_sms_api_key_here',
    'sms_api_url' => 'https://api.yoursmsservice.gr/send', // Αλλάξτε με το URL του παρόχου SMS
    'sms_sender' => 'DriveJob',           // Όνομα αποστολέα (έως 11 χαρακτήρες συνήθως)
    
    // Λειτουργία debug - Όταν είναι true, δεν στέλνονται πραγματικά SMS 
    // και τα emails καταγράφονται αντί να αποσταλούν
    'debug_mode' => true,
    
    // Ρυθμίσεις για το πότε θα στέλνονται οι ειδοποιήσεις (ημέρες πριν τη λήξη)
    'notification_periods' => [
        'driving_license' => [60, 30, 15, 7, 1], // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'pei' => [60, 30, 15, 7, 1],              // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'adr_certificate' => [60, 30, 15, 7, 1],  // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'tachograph_card' => [60, 30, 15, 7, 1],  // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
        'operator_license' => [180, 90, 30, 15],  // 6 μήνες, 3 μήνες, 1 μήνας, 15 ημέρες πριν
        'special_license' => [60, 30, 15, 7, 1]   // 2 μήνες, 1 μήνας, 15 μέρες, 1 εβδομάδα, 1 ημέρα πριν
    ],
    
    // Μέγιστος αριθμός ειδοποιήσεων που θα σταλούν σε ένα cron job
    'max_notifications_per_run' => 100,
    
    // Επιπλέον email για κοινοποιήσεις (διοικητικές ειδοποιήσεις)
    'admin_emails' => [
        'admin@drivejob.gr',
        // 'operations@drivejob.gr'
    ],
    
    // Λειτουργία αναφορών - Στέλνει καθημερινή αναφορά στους διαχειριστές
    'daily_report_enabled' => true,
    'daily_report_time' => '08:00', // Ώρα αποστολής της καθημερινής αναφοράς
    
    // Ρυθμίσεις ειδοποιήσεων UI (web push notifications)
    'web_push_enabled' => true,
    'web_push_public_key' => 'your_web_push_public_key',
    'web_push_private_key' => 'your_web_push_private_key',
];