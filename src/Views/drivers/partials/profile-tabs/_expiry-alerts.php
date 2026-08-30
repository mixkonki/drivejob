<?php
/**
 * Ειδοποιήσεις λήξεων — ΜΟΝΟ ΟΤΑΝ ΥΠΑΡΧΟΥΝ. (30/08/2026)
 *
 * ΑΡΧΗ (δική σου, 25/08): «όταν πλησιάζει η ανανέωση τότε να βγαίνει
 * μήνυμα και σύνδεσμος — όχι μόνιμα μηνύματα καρφωτά». Αν δεν λήγει
 * τίποτα μέσα στο παράθυρο, το partial δεν τυπώνει ΤΙΠΟΤΑ: ούτε
 * πλαίσιο «όλα καλά», ούτε κενή περιοχή. Ένα προειδοποιητικό που
 * φαίνεται πάντα παύει να προειδοποιεί.
 *
 * ΣΕΙΡΑ: ό,τι έχει ΗΔΗ λήξει πρώτο — δεν είναι υπενθύμιση, είναι
 * πρόβλημα που ήδη κοστίζει δουλειές. Ο DriverCvService τα δίνει
 * ταξινομημένα.
 *
 * Περιμένει στο scope: $cv.
 */

$alerts = $cv['alerts'] ?? [];
if (empty($alerts)) {
    return;   // καμία λήξη κοντά: καμία εμφάνιση
}

$expiredCount = 0;
foreach ($alerts as $a) {
    if ($a['expired']) {
        $expiredCount++;
    }
}
$soonCount = count($alerts) - $expiredCount;
?>

<section class="expiry-alerts<?php echo $expiredCount > 0 ? ' expiry-alerts--critical' : ''; ?>">
    <div class="expiry-alerts-head">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <h3>
            <?php if ($expiredCount > 0 && $soonCount > 0) : ?>
                <?php echo $expiredCount; ?> <?php echo $expiredCount === 1 ? 'προσόν έχει λήξει' : 'προσόντα έχουν λήξει'; ?>,
                <?php echo $soonCount; ?> <?php echo $soonCount === 1 ? 'λήγει' : 'λήγουν'; ?> σύντομα
            <?php elseif ($expiredCount > 0) : ?>
                <?php echo $expiredCount; ?> <?php echo $expiredCount === 1 ? 'προσόν έχει λήξει' : 'προσόντα έχουν λήξει'; ?>
            <?php else : ?>
                <?php echo $soonCount; ?> <?php echo $soonCount === 1 ? 'προσόν λήγει' : 'προσόντα λήγουν'; ?> τους επόμενους 3 μήνες
            <?php endif; ?>
        </h3>
    </div>

    <ul class="expiry-list">
        <?php foreach ($alerts as $a) : ?>
            <li class="expiry-item<?php echo $a['expired'] ? ' expiry-item--expired' : ''; ?>">
                <span class="expiry-label"><?php echo htmlspecialchars($a['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="expiry-date">
                    <?php echo htmlspecialchars($a['date'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($a['expired']) : ?>
                        <em>έληξε</em>
                    <?php elseif ($a['days'] <= 30) : ?>
                        <em>σε <?php echo (int) $a['days']; ?> ημέρες</em>
                    <?php endif; ?>
                </span>
                <?php if (!empty($a['url'])) : ?>
                    <a class="expiry-action" href="<?php echo htmlspecialchars($a['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                        <?php echo htmlspecialchars($a['action'] ?: 'Ανανέωση', ENT_QUOTES, 'UTF-8'); ?> ↗
                    </a>
                <?php else : ?>
                    <span class="expiry-action expiry-action--none"></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
