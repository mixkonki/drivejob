<?php

/**
 * Εισερχόμενα εταιρείας — λεπτό περιτύλιγμα του κοινού _inbox.php.
 * (ξαναγράφτηκε 01/09/2026 — βλ. σχόλιο στο _inbox.php για το γιατί)
 * Μεταβλητές από MessagesController::companyMessages.
 */

foreach ($conversations as &$c) {
    $c['counterpart_name'] = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
    $c['counterpart_image'] = $c['profile_image'] ?? null;
}
unset($c);

$threadBase = BASE_URL . 'companies/conversation?id=';
$emptyText = 'Όταν ξεκινήσεις συνομιλία με υποψήφιο από τις αιτήσεις του, θα εμφανιστεί εδώ.';

include ROOT_DIR . '/src/Views/partials/header.php';
include __DIR__ . '/_inbox.php';
include ROOT_DIR . '/src/Views/partials/footer.php';
