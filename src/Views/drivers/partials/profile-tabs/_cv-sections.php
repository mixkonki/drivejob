<?php
/**
 * Προϋπηρεσία και σεμινάρια — ΙΔΙΟ στυλ ομάδας με τα τυπικά προσόντα.
 * (ξαναγράφτηκε 30/08, γλώσσες & δεξιότητες βγήκαν 31/08)
 *
 * Πριν ήταν `.profile-section` με απλό <h2>: οι πρώτες τρεις ενότητες
 * της σελίδας είχαν έγχρωμη κεφαλίδα και παχιά γραμμή, οι επόμενες
 * τρεις όχι — η σελίδα φαινόταν να τελειώνει στη μέση. Τώρα όλες
 * χρησιμοποιούν το ίδιο `.qgroup`.
 *
 * Όλα τα κείμενα έρχονται έτοιμα από τον DriverCvService.
 *
 * Περιμένει στο scope: $cv
 */

$exp = $cv['experience'] ?? ['items' => [], 'count' => 0];
$certs = $cv['certifications'] ?? ['items' => [], 'count' => 0];
?>

<?php // ═══ ΠΡΟΫΠΗΡΕΣΙΑ ══════════════════════════════════════════════ ?>
<section class="qgroup qgroup--exp">
    <header class="qgroup-head">
        <h3>Προϋπηρεσία</h3>
        <?php if ($exp['count'] > 0) : ?>
            <span class="qgroup-meta">Σύνολο <strong><?php echo htmlspecialchars($exp['total_label'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <?php endif; ?>
            <?php /* Deep link: πάει στην ακριβή καρτέλα/σελίδα επεξεργασίας. */ ?>
            <a class="qgroup-edit" href="<?php echo BASE_URL; ?>drivers/vehicle-experience?from=profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                Επεξεργασία
            </a>
    </header>
    <div class="qgroup-body">
        <?php if ($exp['count'] === 0) : ?>
            <p class="qrow-empty">
                Δεν έχει καταχωρηθεί.
                <a href="<?php echo BASE_URL; ?>drivers/vehicle-experience?from=profile">Προσθήκη</a>
            </p>
        <?php else : ?>
            <?php foreach ($exp['items'] as $item) : ?>
                <article class="qrow qrow--exp">
                    <div class="qrow-icon"><?php echo \Drivejob\Helpers\QualIcons::svg('experience'); ?></div>
                    <div class="qrow-main">
                        <h4>
                            <?php echo htmlspecialchars($item['category_label'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($item['current']) : ?><span class="qtag qtag--now">τρέχουσα</span><?php endif; ?>
                        </h4>
                        <?php if ($item['type_label'] !== '') : ?>
                            <p class="qrow-sub"><?php echo htmlspecialchars($item['type_label'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <p class="qrow-lines">
                            <?php foreach (array_filter([$item['period_label'], $item['transport_label'], $item['employment_label']]) as $bit) : ?>
                                <span class="qrow-line"><?php echo htmlspecialchars($bit, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </p>
                        <?php if ($item['description'] !== '') : ?>
                            <p class="qrow-desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="qrow-expiry">
                        <span class="qexp"><span class="qexp-label">Διάρκεια</span><?php echo htmlspecialchars($item['duration_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="qrow-status"></div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php // ═══ ΣΕΜΙΝΑΡΙΑ ════════════════════════════════════════════════ ?>
<section class="qgroup qgroup--sem">
    <header class="qgroup-head">
        <h3>Σεμινάρια &amp; Πιστοποιητικά</h3>
        <?php if ($certs['count'] > 0) : ?>
            <span class="qgroup-meta"><strong><?php echo (int) $certs['count']; ?></strong></span>
        <?php endif; ?>
            <?php /* Deep link: πάει στην ακριβή καρτέλα/σελίδα επεξεργασίας. */ ?>
            <a class="qgroup-edit" href="<?php echo BASE_URL; ?>drivers/certifications?from=profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                Επεξεργασία
            </a>
    </header>
    <div class="qgroup-body">
        <?php if ($certs['count'] === 0) : ?>
            <p class="qrow-empty">
                Δεν έχουν καταχωρηθεί.
                <a href="<?php echo BASE_URL; ?>drivers/certifications?from=profile">Προσθήκη</a>
            </p>
        <?php else : ?>
            <?php
            // Τα 5 πιο πρόσφατα· τα υπόλοιπα στην καρτέλα Προσόντα.
            $shown = array_slice($certs['items'], 0, 5);
            foreach ($shown as $c) :
            ?>
                <article class="qrow qrow--cert<?php echo $c['expired'] ? ' qrow--absent' : ''; ?>">
                    <div class="qrow-icon"><?php echo \Drivejob\Helpers\QualIcons::svg('seminar'); ?></div>
                    <div class="qrow-main">
                        <h4><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p class="qrow-lines">
                            <?php
                            $bits = array_filter([
                                $c['provider'],
                                $c['category_label'],
                                $c['date_label'],
                                $c['duration'] > 0 ? $c['duration'] . ' ώρες' : '',
                            ]);
                            foreach ($bits as $bit) :
                            ?>
                                <span class="qrow-line"><?php echo htmlspecialchars((string) $bit, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                    <div class="qrow-expiry">
                        <?php if ($c['expiry_label'] !== '') : ?>
                            <span class="qexp"><span class="qexp-label"><?php echo $c['expired'] ? 'Έληξε' : 'Λήξη'; ?></span><?php echo htmlspecialchars($c['expiry_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="qrow-status">
                        <?php if ($c['expired']) : ?><span class="qbadge qbadge--expired">Έληξε</span><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($certs['count'] > count($shown)) : ?>
                <p class="qrow-more"><a href="<?php echo BASE_URL; ?>drivers/certifications?from=profile">Όλα τα <?php echo (int) $certs['count']; ?> →</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
