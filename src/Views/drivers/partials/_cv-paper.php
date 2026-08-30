<?php
/**
 * ΤΟ «ΧΑΡΤΙ» ΤΟΥ ΒΙΟΓΡΑΦΙΚΟΥ — κοινό partial. (εξήχθη 01/09/2026)
 *
 * Χρησιμοποιείται από ΔΥΟ σελίδες:
 *   1. drivers/cv — η προεπισκόπηση του ίδιου του οδηγού, με τους
 *      διακόπτες να κρυβοδείχνουν μέρη ζωντανά (data-part + hidden).
 *   2. drivers/profile/{id} — ό,τι βλέπει η ΕΤΑΙΡΕΙΑ (Φάση Α).
 *
 * Αυτό είναι όλο το νόημα: ο οδηγός βλέπει στην προεπισκόπηση ΑΚΡΙΒΩΣ
 * το αρχείο που θα δει ο εργοδότης, γιατί είναι το ίδιο αρχείο. Αν οι
 * δύο όψεις ήταν χωριστά templates, θα αποκλίνανε — και η υπόσχεση
 * «βλέπεις ό,τι θα δουν» θα γινόταν ψέμα με το πρώτο ξεχασμένο πεδίο.
 *
 * Περιμένει στο scope: $cvOptions, $driverData, $id, $exp, $groups,
 * $certs, $langs, $skills, $cvSummarySaved, $cvSummaryAuto
 */
?>
                <div class="cv-paper" id="cvPaper">

                    <header class="cvp-head">
                        <?php /* Η φωτογραφία υπάρχει πάντα στο DOM και κρύβεται με
                           κλάση: ο διακόπτης πρέπει να τη δείχνει ακαριαία, χωρίς
                           να ξαναφορτώσει η σελίδα. */ ?>
                        <div class="cvp-photo" data-part="photo" <?php echo empty($cvOptions['photo']) ? 'hidden' : ''; ?>>
                            <?php if (!empty($driverData['profile_image'])) : ?>
                                <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="">
                            <?php endif; ?>
                        </div>

                        <div class="cvp-head-main">
                            <h2><?php echo htmlspecialchars($id['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>

                            <p class="cvp-line1">
                                <span data-part="age" <?php echo empty($cvOptions['age']) ? 'hidden' : ''; ?>><?php echo htmlspecialchars((string) ($id['age'] !== null ? $id['age'] . ' ετών' : ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($id['location'] !== '') : ?>
                                    <span><?php echo htmlspecialchars($id['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </p>

                            <p class="cvp-line2">
                                <span data-part="phone" <?php echo empty($cvOptions['phone']) ? 'hidden' : ''; ?>><?php echo htmlspecialchars((string) ($driverData['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span data-part="email" <?php echo empty($cvOptions['email']) ? 'hidden' : ''; ?>><?php echo htmlspecialchars((string) ($driverData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>

                            <?php if (!empty($id['reach']['declared'])) : ?>
                                <p class="cvp-line2"><span><?php echo htmlspecialchars($id['reach']['label'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                            <?php endif; ?>

                            <p class="cvp-line2" data-part="rating" <?php echo (empty($cvOptions['rating']) || empty($driverData['rating_count'])) ? 'hidden' : ''; ?>>
                                <span>Αξιολόγηση <?php echo number_format((float) ($driverData['rating'] ?? 0), 1); ?>/5 από <?php echo (int) ($driverData['rating_count'] ?? 0); ?> εργοδότες</span>
                            </p>
                        </div>
                    </header>

                    <p class="cvp-summary" id="cvPaperSummary"><?php echo htmlspecialchars($cvSummarySaved !== '' ? $cvSummarySaved : $cvSummaryAuto, ENT_QUOTES, 'UTF-8'); ?></p>

                    <?php if ($exp['count'] > 0) : ?>
                        <section class="cvp-sec">
                            <h3>Προϋπηρεσία <small>Σύνολο <?php echo htmlspecialchars($exp['total_label'], ENT_QUOTES, 'UTF-8'); ?></small></h3>
                            <?php foreach ($exp['items'] as $item) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when"><?php echo htmlspecialchars($item['period_label'] ?: $item['duration_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="cvp-what">
                                        <strong>
                                            <?php echo htmlspecialchars($item['category_label'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ($item['type_label'] !== '') : ?> — <?php echo htmlspecialchars($item['type_label'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                            <?php if ($item['current']) : ?><span class="cvp-now">τρέχουσα</span><?php endif; ?>
                                        </strong>
                                        <span class="cvp-meta"><?php echo htmlspecialchars(implode('  ·  ', array_filter([$item['duration_label'], $item['transport_label'], $item['employment_label']])), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($item['description'] !== '') : ?>
                                            <span class="cvp-desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php
                    // Ομάδες όπου τίποτα δεν κατέχεται δεν μπαίνουν: ο εργοδότης
                    // δεν χρειάζεται λίστα με ό,τι ΔΕΝ έχει ο οδηγός.
                    $ownedGroups = [];
                    foreach ($groups as $g) {
                        $owned = array_filter($g['items'], static fn($i) => empty($i['absent']));
                        if ($owned) {
                            $ownedGroups[] = ['title' => $g['title'], 'meta' => $g['meta'] ?? null, 'items' => $owned];
                        }
                    }
                    ?>
                    <?php if ($ownedGroups) : ?>
                        <section class="cvp-sec">
                            <h3>Άδειες &amp; πιστοποιήσεις</h3>
                            <?php foreach ($ownedGroups as $g) : ?>
                                <div class="cvp-group">
                                    <h4>
                                        <?php echo htmlspecialchars($g['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($g['meta']) : ?>
                                            <small><?php echo htmlspecialchars($g['meta']['key'] . ' ' . $g['meta']['value'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php endif; ?>
                                    </h4>
                                    <?php foreach ($g['items'] as $item) : ?>
                                        <div class="cvp-qual">
                                            <?php
                                            $head = trim($item['title'] . (!empty($item['tag']) ? ' (' . $item['tag'] . ')' : ''));
                                            if (!empty($item['cats'])) {
                                                $head = ($head !== '' ? $head . ': ' : '') . implode(', ', $item['cats']);
                                            }
                                            ?>
                                            <?php if ($head !== '') : ?><strong><?php echo htmlspecialchars($head, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?>
                                            <?php if (!empty($item['subtitle'])) : ?><span class="cvp-sub"><?php echo htmlspecialchars($item['subtitle'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <?php
                                            $meta = [];
                                            foreach ($item['lines'] as $l) {
                                                $meta[] = trim(($l['key'] !== '' ? $l['key'] . ' ' : '') . $l['value']);
                                            }
                                            foreach ($item['expiries'] as $e) {
                                                $meta[] = $e['label'] . ' ' . $e['date'];
                                            }
                                            ?>
                                            <?php if ($meta) : ?><span class="cvp-meta"><?php echo htmlspecialchars(implode('  ·  ', $meta), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <?php if (!empty($item['covers_all'])) : ?>
                                                <span class="cvp-sub">Σύνολο μηχανημάτων της ειδικότητας</span>
                                            <?php elseif (!empty($item['subs'])) : ?>
                                                <span class="cvp-sub"><?php
                                                    $parts = [];
                                                    foreach ($item['subs'] as $sub) {
                                                        $parts[] = $sub['code'] . ' ' . $sub['name'] . ($sub['group'] !== '' ? ' (' . $sub['group'] . ')' : '');
                                                    }
                                                    echo htmlspecialchars(implode(' · ', $parts), ENT_QUOTES, 'UTF-8');
                                                ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php if ($certs['count'] > 0) : ?>
                        <section class="cvp-sec">
                            <h3>Επιμόρφωση &amp; σεμινάρια</h3>
                            <?php foreach ($certs['items'] as $c) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when"><?php echo htmlspecialchars($c['date_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="cvp-what">
                                        <strong><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="cvp-meta"><?php echo htmlspecialchars(implode('  ·  ', array_filter([
                                            $c['provider'],
                                            $c['category_label'],
                                            $c['duration'] > 0 ? $c['duration'] . ' ώρες' : '',
                                            $c['expiry_label'] !== '' ? ($c['expired'] ? 'έληξε ' : 'λήξη ') . $c['expiry_label'] : '',
                                        ])), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($langs) || !empty($skills['groups'])) : ?>
                        <section class="cvp-sec">
                            <h3>Γλώσσες &amp; δεξιότητες</h3>
                            <?php if ($langs) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when">Γλώσσες</div>
                                    <div class="cvp-what"><span class="cvp-plain"><?php
                                        $parts = [];
                                        foreach ($langs as $l) {
                                            $parts[] = $l['name'] . ' (' . $l['level_label'] . ')';
                                        }
                                        echo htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
                                    ?></span></div>
                                </article>
                            <?php endif; ?>
                            <?php foreach ($skills['groups'] as $g) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when"><?php echo htmlspecialchars($g['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="cvp-what"><span class="cvp-plain"><?php echo htmlspecialchars(implode(', ', $g['items']), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <p class="cvp-foot">Βιογραφικό από το DriveJob · <?php echo date('d/m/Y'); ?> · drivejob.gr</p>
                </div>
