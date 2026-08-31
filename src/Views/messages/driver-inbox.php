<?php

/**
 * Εισερχόμενα οδηγού — λεπτό περιτύλιγμα του κοινού _inbox.php.
 * (ξαναγράφτηκε 01/09/2026 — βλ. σχόλιο στο _inbox.php για το γιατί)
 * Μεταβλητές από MessagesController::driverMessages.
 */

foreach ($conversations as &$c) {
    $c['counterpart_name'] = $c['company_name'] ?? '';
    $c['counterpart_image'] = $c['company_logo'] ?? null;
}
unset($c);

$threadBase = BASE_URL . 'drivers/conversation?id=';
$emptyText = 'Όταν μια εταιρεία επικοινωνήσει μαζί σου για αίτησή σου, η συνομιλία θα εμφανιστεί εδώ.';

include ROOT_DIR . '/src/Views/partials/header.php';
include __DIR__ . '/_inbox.php';
include ROOT_DIR . '/src/Views/partials/footer.php';
