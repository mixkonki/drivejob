<?php /* Καρτέλα «overview» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <!-- Καρτέλα Επισκόπησης -->
            <div class="tab-pane active" id="overview">
                <?php /* Λήξεις ΠΡΩΤΑ και έξω από τις στήλες: αν κάτι έχει λήξει, ο
                   οδηγός πρέπει να το δει πριν από οτιδήποτε άλλο. Το partial
                   δεν τυπώνει τίποτα όταν δεν λήγει τίποτα. */ ?>
                <?php include __DIR__ . '/_expiry-alerts.php'; ?>

                <div class="profile-content">
                    <div class="profile-main">
                        <?php /* ΟΛΕΣ οι ενότητες με το ΙΔΙΟ στυλ ομάδας (οδηγία 30/08):
                           προσόντα, προϋπηρεσία, σεμινάρια, γλώσσες. Το εξωτερικό
                           «Επαγγελματικά Προσόντα & Άδειες» αφαιρέθηκε — ήταν
                           τίτλος πάνω από τίτλους, χωρίς να προσθέτει τίποτα.
                           Όλο το περιεχόμενο έρχεται έτοιμο από τον
                           DriverCvService: ίδια πηγή με το PDF. */ ?>
                        <?php include __DIR__ . '/_qualification-groups.php'; ?>
                        <?php include __DIR__ . '/_cv-sections.php'; ?>
                    </div>

                    <div class="profile-sidebar">
                        <?php /* 30/08: το «AI Προτάσεις Εργασίας» αφαιρέθηκε από την
                           επισκόπηση. Οι προτάσεις ανήκουν στα Ταιριάσματα, και το
                           θέμα ταιριάσματα/αγγελίες θα ξαναδουλευτεί συνολικά —
                           μέχρι τότε δεν έχει θέση εδώ ένα widget που επαναλαμβάνει
                           μισοφτιαγμένη λειτουργία άλλης καρτέλας.
                           (Το partials/ai-matching-widget.php παραμένει στον κώδικα.) */ ?>

                        <!-- Messages Widget -->
                        <?php include dirname(__DIR__, 2) . '/partials/messages-widget.php'; ?>

                        <?php /* Η ΔΙΑΘΕΣΙΜΟΤΗΤΑ μετακόμισε στην κεφαλίδα, δίπλα στο
                           όνομα (30/08). Ήταν κάρτα με τίτλο, εικονίδιο και μια
                           πρόταση οδηγιών — τρία στοιχεία για να πουν μία λέξη.
                           Ως σήμα στην κεφαλίδα φαίνεται αμέσως, χωρίς να
                           καταλαμβάνει χώρο. */ ?>
                        <?php /* «Σχετικά με εμένα»: ΑΦΑΙΡΕΘΗΚΕ 30/08.
                           Το πεδίο είχε φύγει από τη φόρμα επεξεργασίας στις 25/08
                           αλλά έμεινε στην προβολή — έδειχνε παλιό περιεχόμενο που
                           ο οδηγός ΔΕΝ είχε τρόπο να διορθώσει. Ένα πεδίο ή
                           επεξεργάζεται και φαίνεται, ή δεν υπάρχει καθόλου.
                           (Η στήλη about_me μένει στη βάση· δεν χάνεται τίποτα αν
                           αποφασίσουμε να την επαναφέρουμε ως «Λίγα λόγια για
                           εμένα» στο βιογραφικό.) */ ?>
                        <?php /* Επικοινωνία: μία συμπαγής λίστα με inline SVG αντί για
                           PNG που κατά τόπους λείπουν (404). */ ?>
                        <section class="qgroup qgroup--contact">
                            <header class="qgroup-head"><h3>Επικοινωνία</h3></header>
                            <div class="qgroup-body">
                                <ul class="contact-list">
                                    <li>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>
                                        <a href="mailto:<?php echo htmlspecialchars($driverData['email']); ?>"><?php echo htmlspecialchars($driverData['email']); ?></a>
                                    </li>
                                    <?php if (!empty($driverData['phone'])) : ?>
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                                            <a href="tel:<?php echo htmlspecialchars($driverData['phone']); ?>"><?php echo htmlspecialchars($driverData['phone']); ?></a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($driverData['landline'])) : ?>
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.9 2.2z"/></svg>
                                            <a href="tel:<?php echo htmlspecialchars($driverData['landline']); ?>"><?php echo htmlspecialchars($driverData['landline']); ?></a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($driverData['social_linkedin'])) : ?>
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                                            <a href="<?php echo htmlspecialchars($driverData['social_linkedin']); ?>" target="_blank" rel="noopener">LinkedIn</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </section>
                        <?php /* GDPR: δύο σύνδεσμοι στο τέλος, χωρίς κάρτα και χωρίς
                           επεξηγηματική πρόταση. Είναι δικαιώματα που πρέπει να
                           υπάρχουν, όχι λειτουργίες που χρησιμοποιεί κανείς
                           καθημερινά — δεν διεκδικούν χώρο στη μέση της σελίδας. */ ?>
                        <p class="privacy-links">
                            <a href="<?php echo BASE_URL; ?>gdpr/export">Εξαγωγή δεδομένων</a>
                            <a href="<?php echo BASE_URL; ?>gdpr/delete" class="is-danger">Διαγραφή λογαριασμού</a>
                        </p>
                        <?php
                        /*
                         * ΑΚΤΙΝΑ ΕΡΓΑΣΙΑΣ — αντικατέστησε τον χάρτη Google (30/08).
                         *
                         * Ο χάρτης έπιανε 200px ύψος για να δείξει στον οδηγό πού
                         * μένει ο ίδιος: μηδενική πληροφορία, εξωτερικό iframe,
                         * κόστος API και θέμα GDPR.
                         *
                         * Στη θέση του μπαίνει κάτι που ΛΕΙΠΕΙ πραγματικά: πόσο
                         * μακριά δέχεται να εργαστεί. Το πεδίο preferred_radius
                         * υπάρχει στη βάση από την αρχή και το ΔΙΑΒΑΖΕΙ το
                         * ταίριασμα (MatchingModel), αλλά δεν το συμπλήρωνε ποτέ
                         * κανείς γιατί δεν υπήρχε πουθενά στη φόρμα — έμενε 0,
                         * το ταίριασμα έπεφτε σε προεπιλογή, και έβγαζε αγγελίες
                         * Αθηνών σε οδηγό Θεσσαλονίκης.
                         */
                        $reach = $cv['identity']['reach'] ?? ['declared' => false, 'label' => '', 'travel' => false];
                        ?>
                        <section class="qgroup qgroup--reach">
                            <header class="qgroup-head">
                                <h3>Περιοχή Εργασίας</h3>
                                <?php if (!empty($driverData['city'])) : ?>
                                    <span class="qgroup-meta"><strong><?php echo htmlspecialchars($driverData['city'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                                <?php endif; ?>
                            </header>
                            <div class="qgroup-body">
                                <?php if (!empty($reach['declared'])) : ?>
                                    <?php /* Ο ΙΔΙΟΣ κώδικας με τη φόρμα, χωρίς τα χειριστήρια:
                                       ο χάρτης δείχνει τον κύκλο της ακτίνας και από κάτω οι
                                       πόλεις που πέφτουν μέσα. Το work-radius.js αντέχει την
                                       απουσία slider — μόνο ζωγραφίζει. */ ?>
                                    <div id="workRadius" class="wr wr--view"
                                         data-lat="<?php echo htmlspecialchars((string) ($driverData['latitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                         data-lng="<?php echo htmlspecialchars((string) ($driverData['longitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                         data-city="<?php echo htmlspecialchars((string) ($driverData['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                         data-address="<?php echo htmlspecialchars(trim(($driverData['address'] ?? '') . ' ' . ($driverData['city'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">

                                        <input type="hidden" id="preferred_radius" value="<?php echo (int) ($reach['radius'] ?: ($reach['relocate'] ? 9999 : 0)); ?>">
                                        <p class="wr-view-label" id="radiusReadout"><?php echo htmlspecialchars($reach['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <div id="radiusMap" class="wr-map" aria-hidden="true"></div>
                                        <p id="radiusCoverage" class="wr-coverage"></p>
                                        <?php if (!empty($reach['travel'])) : ?>
                                            <p class="wr-view-extra">Δέχεται ταξίδια εκτός έδρας</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="qrow-empty">
                                        Δεν έχετε δηλώσει ακτίνα εργασίας.
                                        <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Δηλώστε την</a> —
                                        χωρίς αυτήν οι προτάσεις δεν φιλτράρονται κατά απόσταση.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </section>
                        <?= \Drivejob\Helpers\Asset::js('js/work-radius.js', true) ?>

                    </div>
                </div>
            </div>

