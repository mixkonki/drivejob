<?php

/**
 * Συνομιλία εταιρείας — λεπτό περιτύλιγμα του κοινού _thread.php.
 * (ξαναγράφτηκε 01/09/2026 — βλ. σχόλιο στο _thread.php για το γιατί)
 * Μεταβλητές από MessagesController::companyConversation.
 */

$conversation['counterpart_name'] = trim(($conversation['first_name'] ?? '') . ' ' . ($conversation['last_name'] ?? ''));
$meType = 'company';
$backUrl = BASE_URL . 'companies/messages';

include ROOT_DIR . '/src/Views/partials/header.php';
include __DIR__ . '/_thread.php';
include ROOT_DIR . '/src/Views/partials/footer.php';
