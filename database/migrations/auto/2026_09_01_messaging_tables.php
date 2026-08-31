<?php

/**
 * Πίνακες μηνυμάτων & ειδοποιήσεων — επιτέλους μέσα στο αυτόματο κύκλωμα. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το /companies/messages έβγαζε ΛΕΥΚΗ ΣΕΛΙΔΑ στην παραγωγή ενώ τοπικά
 * δούλευε. Αιτία: οι πίνακες conversations/messages φτιάχνονταν μόνο
 * από τα ΧΕΙΡΟΚΙΝΗΤΑ scripts του database/migrations/ (create_messaging_
 * tables.php, fix_messaging_tables.php) που δεν τρέχουν ποτέ στο deploy.
 * Τοπικά είχαν τρέξει με το χέρι κάποια στιγμή — στην παραγωγή ποτέ.
 * Κλασικό «δουλεύει σε μένα».
 *
 * Αυτό το migration φέρνει την παραγωγή στο ΑΚΡΙΒΕΣ σχήμα που περιμένει
 * ο κώδικας (MessagingService + MessagesController), και είναι ασφαλές
 * να τρέξει και εκεί που οι πίνακες ήδη υπάρχουν σε παλιότερη μορφή:
 *
 *   - CREATE TABLE IF NOT EXISTS με το πλήρες σχήμα (νέες εγκαταστάσεις).
 *   - Έλεγχος στήλη-στήλη μέσω information_schema και ALTER μόνο για
 *     ό,τι λείπει (υπάρχουσες εγκαταστάσεις με μερικό σχήμα).
 *   - Backfill: participant1/2 από company_id/driver_id όπου είναι 0.
 *
 * Idempotent: δεύτερο τρέξιμο δεν βρίσκει τίποτα να προσθέσει.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
restore_exception_handler();
restore_error_handler();

/** Υπάρχει η στήλη στον πίνακα (στην τρέχουσα βάση); */
$hasColumn = static function (string $table, string $column) use ($pdo): bool {
    $q = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $q->execute([$table, $column]);
    return (bool) $q->fetchColumn();
};

$hasTable = static function (string $table) use ($pdo): bool {
    $q = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $q->execute([$table]);
    return (bool) $q->fetchColumn();
};

// ── 1. conversations ────────────────────────────────────────────────────
// Χωρίς FKs στο CREATE: αν στην παραγωγή οι companies/drivers έχουν
// διαφορετικό collation/τύπο id, το FK σκάει και ρίχνει ΟΛΟ το deploy —
// τα ορφανά καθαρίζονται σε επίπεδο εφαρμογής ούτως ή άλλως.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS conversations (
        id INT(11) NOT NULL AUTO_INCREMENT,
        participant1_id INT(11) NOT NULL,
        participant2_id INT(11) NOT NULL,
        participant1_type VARCHAR(20) NOT NULL DEFAULT 'driver',
        participant2_type VARCHAR(20) NOT NULL DEFAULT 'company',
        company_id INT(11) NOT NULL,
        driver_id INT(11) NOT NULL,
        job_id INT(11) DEFAULT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('active','archived','deleted') DEFAULT 'active',
        last_message_at TIMESTAMP NULL DEFAULT NULL,
        company_unread_count INT(11) DEFAULT 0,
        driver_unread_count INT(11) DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_company_status (company_id, status),
        KEY idx_driver_status (driver_id, status),
        KEY idx_last_message (last_message_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Στήλες που μπορεί να λείπουν από παλιότερη μορφή του πίνακα.
$convColumns = [
    'participant1_id'      => "ADD COLUMN participant1_id INT(11) NOT NULL DEFAULT 0 AFTER id",
    'participant2_id'      => "ADD COLUMN participant2_id INT(11) NOT NULL DEFAULT 0 AFTER participant1_id",
    'participant1_type'    => "ADD COLUMN participant1_type VARCHAR(20) NOT NULL DEFAULT 'driver' AFTER participant2_id",
    'participant2_type'    => "ADD COLUMN participant2_type VARCHAR(20) NOT NULL DEFAULT 'company' AFTER participant1_type",
    'last_message_at'      => "ADD COLUMN last_message_at TIMESTAMP NULL DEFAULT NULL",
    'company_unread_count' => "ADD COLUMN company_unread_count INT(11) DEFAULT 0",
    'driver_unread_count'  => "ADD COLUMN driver_unread_count INT(11) DEFAULT 0",
    'updated_at'           => "ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
];
foreach ($convColumns as $col => $ddl) {
    if (!$hasColumn('conversations', $col)) {
        $pdo->exec("ALTER TABLE conversations $ddl");
        echo "conversations: προστέθηκε $col\n";
    }
}

// Backfill: σε γραμμές πριν από τη διόρθωση του MessagingService τα
// participants μπορεί να είναι 0 — γεμίζουν από τα ειδικά ids.
// (participant1 = driver, participant2 = company — όπως τα defaults.)
$pdo->exec("
    UPDATE conversations
    SET participant1_id = driver_id, participant2_id = company_id
    WHERE participant1_id = 0 OR participant2_id = 0
");

// ── 2. messages ─────────────────────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id INT(11) NOT NULL AUTO_INCREMENT,
        conversation_id INT(11) NOT NULL,
        sender_type ENUM('company','driver') NOT NULL,
        sender_id INT(11) NOT NULL,
        receiver_id INT(11) NOT NULL,
        receiver_type VARCHAR(20) DEFAULT 'company',
        message TEXT NOT NULL,
        attachments LONGTEXT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        read_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_conversation_created (conversation_id, created_at),
        KEY idx_unread (conversation_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$msgColumns = [
    'receiver_id'   => "ADD COLUMN receiver_id INT(11) NOT NULL DEFAULT 0 AFTER sender_id",
    'receiver_type' => "ADD COLUMN receiver_type VARCHAR(20) DEFAULT 'company' AFTER receiver_id",
    'attachments'   => "ADD COLUMN attachments LONGTEXT DEFAULT NULL",
    'read_at'       => "ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL",
];
foreach ($msgColumns as $col => $ddl) {
    if (!$hasColumn('messages', $col)) {
        $pdo->exec("ALTER TABLE messages $ddl");
        echo "messages: προστέθηκε $col\n";
    }
}

// ── 3. notifications ────────────────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT(11) NOT NULL AUTO_INCREMENT,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        user_id INT(11) NOT NULL,
        user_type VARCHAR(20) NOT NULL,
        data LONGTEXT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        method VARCHAR(10) NOT NULL,
        sent_at DATETIME NOT NULL,
        read_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id, user_type),
        KEY type (type),
        KEY sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$notifColumns = [
    'method'  => "ADD COLUMN method VARCHAR(10) NOT NULL DEFAULT 'app'",
    'sent_at' => "ADD COLUMN sent_at DATETIME NULL DEFAULT NULL",
];
foreach ($notifColumns as $col => $ddl) {
    if (!$hasColumn('notifications', $col)) {
        $pdo->exec("ALTER TABLE notifications $ddl");
        echo "notifications: προστέθηκε $col\n";
    }
}

echo "Πίνακες μηνυμάτων: OK\n";
