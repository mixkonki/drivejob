<?php

/**
 * Συνομιλία οδηγού — λεπτό περιτύλιγμα του κοινού _thread.php.
 * (ξαναγράφτηκε 01/09/2026 — βλ. σχόλιο στο _thread.php για το γιατί)
 * Μεταβλητές από MessagesController::driverConversation.
 */

$conversation['counterpart_name'] = $conversation['company_name'] ?? '';
$meType = 'driver';
$backUrl = BASE_URL . 'drivers/messages';

include ROOT_DIR . '/src/Views/partials/header.php';
include __DIR__ . '/_thread.php';
include ROOT_DIR . '/src/Views/partials/footer.php';
