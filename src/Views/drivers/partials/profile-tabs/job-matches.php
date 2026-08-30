<?php
/**
 * Καρτέλα «Ταιριάσματα Εργασίας». (ξαναγράφτηκε 01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΛΛΑΞΕ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η καρτέλα καλούσε τον EnhancedMatchingService/MatchingEngine — τον
 * ΤΡΙΤΟ μηχανισμό ταιριάσματος του project (μαζί με MatchingModel και
 * AI\MatchingService), με δικά του βάρη και δικό του «overall_score».
 * Τρεις υπολογισμοί για το ίδιο ζευγάρι οδηγού-αγγελίας σημαίνει τρία
 * διαφορετικά ποσοστά, και κανείς δεν ξέρει ποιο ισχύει.
 *
 * Τώρα: RequirementsMatcher — η ΜΙΑ σύγκριση απαιτήσεων, η ίδια που
 * βλέπει και η εταιρεία. Και το σημαντικότερο: κάθε κάρτα δείχνει ΤΙ
 * ΛΕΙΠΕΙ. Το «73%» δεν λέει τίποτα· το «λείπει ADR» λέει στον οδηγό
 * ακριβώς ποιο σεμινάριο τον χωρίζει από τη θέση.
 *
 * Περιμένει στο scope: $driverData (το πλήρες προφίλ του γονικού view)
 */

use Drivejob\Core\Database;
use Drivejob\Services\Matching\RequirementsMatcher;

$matches = [];
try {
    $pdo = Database::getInstance()->getConnection();
    $matcher = new RequirementsMatcher($pdo);

    /*
     * Όλες οι ενεργές προσφορές — το ταίριασμα κρίνει, όχι το SQL.
     * Με λίγες εκατοντάδες αγγελίες αυτό είναι φθηνό· όταν γίνουν
     * χιλιάδες, το προ-φιλτράρισμα μπαίνει εδώ (region, transport_type).
     */
    $stmt = $pdo->prepare(
        "SELECT jl.*, c.company_name
         FROM job_listings jl
         LEFT JOIN companies c ON c.id = jl.company_id
         WHERE jl.listing_type = 'job_offer' AND jl.is_active = 1
           AND (jl.expires_at IS NULL OR jl.expires_at >= NOW())
         ORDER BY jl.created_at DESC
         LIMIT 200"
    );
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $listing) {
        $r = $matcher->match($listing, $driverData);
        $listing['match'] = $r;
        $matches[] = $listing;
    }

    // Καλύτερο ταίριασμα πρώτο· ισοβαθμία λύνεται από τη μικρότερη απόσταση.
    usort($matches, static function ($a, $b) {
        return ($b['match']['percent'] <=> $a['match']['percent'])
            ?: (($a['match']['distance_km'] ?? 9999) <=> ($b['match']['distance_km'] ?? 9999));
    });
    $matches = array_slice($matches, 0, 10);
} catch (Throwable $e) {
    \Drivejob\Core\Logger::error('Ταιριάσματα καρτέλας οδηγού: ' . $e->getMessage());
    $matches = [];
}
?>
            <!-- Καρτέλα Ταιριασμάτων Εργασίας -->
            <div class="tab-pane" id="job-matches">
                <div class="job-matches-container">
                    <h2>Προτεινόμενες Θέσεις Εργασίας</h2>

                    <?php if (!$matches) : ?>
                        <p class="qrow-empty">Δεν υπάρχουν ενεργές αγγελίες αυτή τη στιγμή.</p>
                    <?php else : ?>
                        <div class="matched-listings">
                            <?php foreach ($matches as $m) : ?>
                                <?php $pct = $m['match']['percent']; ?>
                                <div class="job-match-card">
                                    <div class="match-percentage <?php echo $pct >= 90 ? 'high' : ($pct >= 70 ? 'medium' : 'low'); ?>">
                                        <?php echo $pct; ?>% ταίριασμα
                                    </div>
                                    <div class="match-details">
                                        <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo (int) $m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?></a></h3>
                                        <div class="match-meta">
                                            <span class="company"><?php echo htmlspecialchars($m['company_name'] ?? ''); ?></span>
                                            <span class="location"><?php echo htmlspecialchars($m['location'] ?? ''); ?><?php
                                                echo $m['match']['distance_km'] !== null
                                                    ? ' · ' . round($m['match']['distance_km']) . ' χλμ'
                                                    : ''; ?></span>
                                        </div>

                                        <?php /* ΤΟ «ΤΙ ΛΕΙΠΕΙ» — η πληροφορία που κάνει το
                                           ποσοστό χρήσιμο. Πράσινο ό,τι καλύπτει, κόκκινο
                                           ό,τι όχι· καμία περιγραφή δεν το λέει καλύτερα. */ ?>
                                        <p class="match-reqs">
                                            <?php foreach ($m['match']['met'] as $item) : ?>
                                                <span class="mreq mreq--ok">✓ <?php echo htmlspecialchars($item); ?></span>
                                            <?php endforeach; ?>
                                            <?php foreach ($m['match']['missing'] as $item) : ?>
                                                <span class="mreq mreq--no"><?php echo htmlspecialchars($item); ?></span>
                                            <?php endforeach; ?>
                                        </p>

                                        <div class="match-actions">
                                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo (int) $m['id']; ?>" class="btn-primary">Προβολή & Αίτηση</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>job-listings" class="btn-secondary">Όλες οι αγγελίες</a>
                        </div>
                    <?php endif; ?>
                </div>

                <style>
                    /* Πλακίδια απαιτήσεων — τοπικά, δεν τα θέλει άλλη σελίδα ακόμη. */
                    .match-reqs { display: flex; flex-wrap: wrap; gap: .3rem .4rem; margin: .5rem 0; }
                    .mreq {
                        font-size: .74rem; font-weight: 600;
                        padding: .15rem .5rem; border-radius: 999px; white-space: nowrap;
                    }
                    .mreq--ok { background: #eefaf1; color: #15803d; }
                    .mreq--no { background: #fff1f0; color: #b3261e; }
                </style>
            </div>
