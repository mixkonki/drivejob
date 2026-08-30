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
                        <?php /* Τυπικά προσόντα σε ΟΠΤΙΚΕΣ ΟΜΑΔΕΣ (30/08).
                           Ο ενιαίος πίνακας 4 στηλών έδινε την ίδια βαρύτητα σε
                           δίπλωμα, ΠΕΙ, ADR, ταχογράφο, μηχανήματα έργου και
                           ειδικές άδειες — τίποτα δεν ξεχώριζε. Δες το partial
                           για τη λογική των τεσσάρων ομάδων. */ ?>
                        <section class="profile-section profile-section--quals">
                            <h2>Επαγγελματικά Προσόντα &amp; Άδειες</h2>
                            <?php include __DIR__ . '/_qualification-groups.php'; ?>
                        </section>

                        <?php /* Προϋπηρεσία, σεμινάρια, γλώσσες, δεξιότητες (30/08).
                           Έλειπαν εντελώς από την επισκόπηση ενώ είναι ακριβώς
                           τα πεδία που ζητά το βιογραφικό. Όλα τα κείμενα
                           έρχονται έτοιμα από τον DriverCvService. */ ?>
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

                        <!-- Ενότητα Διαθεσιμότητας -->
                        <section class="profile-section availability-section">
                            <h3>Κατάσταση Διαθεσιμότητας</h3>
                            <div class="availability-status <?php echo $driverData['available_for_work'] ? 'available' : 'unavailable'; ?>">
                                <span class="status-icon"></span>
                                <span class="status-text">
                                    <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                                </span>
                            </div>
                            <p class="availability-note">Μπορείτε να αλλάξετε την κατάσταση διαθεσιμότητάς σας από την <a href="<?php echo BASE_URL; ?>drivers/edit-profile">επεξεργασία προφίλ</a>.</p>
                        </section>
                        <?php /* «Σχετικά με εμένα»: ΑΦΑΙΡΕΘΗΚΕ 30/08.
                           Το πεδίο είχε φύγει από τη φόρμα επεξεργασίας στις 25/08
                           αλλά έμεινε στην προβολή — έδειχνε παλιό περιεχόμενο που
                           ο οδηγός ΔΕΝ είχε τρόπο να διορθώσει. Ένα πεδίο ή
                           επεξεργάζεται και φαίνεται, ή δεν υπάρχει καθόλου.
                           (Η στήλη about_me μένει στη βάση· δεν χάνεται τίποτα αν
                           αποφασίσουμε να την επαναφέρουμε ως «Λίγα λόγια για
                           εμένα» στο βιογραφικό.) */ ?>
                        <!-- Ενότητα Στοιχείων Επικοινωνίας -->
                        <section class="profile-section">
                            <h2>Στοιχεία Επικοινωνίας</h2>
                            <ul class="contact-list">
                                <li>
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/email_icon.png') ?>" alt="Email">
                                    <span><?php echo htmlspecialchars($driverData['email']); ?></span>
                                </li>
                                <li>
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/phone_icon.png') ?>" alt="Τηλέφωνο">
                                    <span><?php echo htmlspecialchars($driverData['phone']); ?></span>
                                </li>
                                <?php if (isset($driverData['landline']) && $driverData['landline']) : ?>
                                    <li>
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/landline_icon.png') ?>" alt="Σταθερό Τηλέφωνο">
                                        <span><?php echo htmlspecialchars($driverData['landline']); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if (isset($driverData['social_linkedin']) && $driverData['social_linkedin']) : ?>
                                    <li>
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/linkedin_icon.png') ?>" alt="LinkedIn">
                                        <a href="<?php echo htmlspecialchars($driverData['social_linkedin']); ?>" target="_blank">LinkedIn Προφίλ</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </section>
                        <!-- Απόρρητο & δεδομένα (GDPR — Πακέτο 7) -->
                        <section class="profile-section privacy-section">
                            <h2>Απόρρητο &amp; Δεδομένα</h2>
                            <p style="margin:6px 0 12px; color:#555;">Διαχειριστείτε τα προσωπικά σας δεδομένα (GDPR).</p>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <a href="<?php echo BASE_URL; ?>gdpr/export" class="btn-secondary" style="padding:8px 16px; border-radius:6px; text-decoration:none; background:#eceff1; color:#333;">⬇️ Εξαγωγή δεδομένων (JSON)</a>
                                <a href="<?php echo BASE_URL; ?>gdpr/delete" style="padding:8px 16px; border-radius:6px; text-decoration:none; background:#fdecea; color:#b71c1c;">🗑️ Διαγραφή λογαριασμού</a>
                            </div>
                        </section>
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
                        <section class="profile-section reach-section">
                            <h2>Περιοχή Εργασίας</h2>
                            <?php if (!empty($driverData['city'])) : ?>
                                <p class="reach-base">
                                    <span class="reach-key">Έδρα</span>
                                    <?php echo htmlspecialchars(trim(($driverData['address'] ?? '') . ' ' . $driverData['city']), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($reach['declared'])) : ?>
                                <p class="reach-label"><?php echo htmlspecialchars($reach['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php if (!empty($reach['travel'])) : ?>
                                    <p class="reach-extra">Δέχομαι ταξίδια εκτός έδρας</p>
                                <?php endif; ?>
                            <?php else : ?>
                                <p class="reach-missing">
                                    Δεν έχετε δηλώσει πόσο μακριά δέχεστε να εργαστείτε.
                                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Δηλώστε το τώρα</a> —
                                    χωρίς αυτό οι προτάσεις εργασίας δεν φιλτράρονται σωστά κατά απόσταση.
                                </p>
                            <?php endif; ?>
                        </section>

                    </div>
                </div>
            </div>

