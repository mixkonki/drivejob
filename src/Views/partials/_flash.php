<?php

/**
 * Καθολικά μηνύματα info/warning — εμφανίζονται ακριβώς κάτω από την κεφαλίδα,
 * σε ΟΛΕΣ τις σελίδες. (05/10/2026)
 *
 * Γιατί μόνο info/warning: το BaseController::redirectWithMessage() ξέρει
 * τέσσερις τύπους, αλλά 40 views τυπώνουν μόνα τους success/error_message.
 * Τα info/warning δεν τα τύπωνε ΚΑΝΕΙΣ — π.χ. «Είστε ήδη συνδεδεμένος» στο
 * /register χανόταν σιωπηλά. Εδώ αναλαμβάνουμε αυτά τα δύο, χωρίς να
 * διπλοτυπώνουμε τα άλλα.
 */

$djFlash = [];
foreach (['info_message' => 'info', 'warning_message' => 'warning'] as $key => $kind) {
    if (!empty($_SESSION[$key])) {
        $djFlash[] = ['kind' => $kind, 'text' => (string) $_SESSION[$key]];
        unset($_SESSION[$key]);
    }
}
if ($djFlash) : ?>
    <div class="dj-flash-wrap">
        <?php foreach ($djFlash as $f) : ?>
            <div class="dj-flash dj-flash--<?= $f['kind'] ?>" role="status">
                <?= htmlspecialchars($f['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
