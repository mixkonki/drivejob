<?php /* Καρτέλα «overview» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <!-- Καρτέλα Επισκόπησης -->
            <div class="tab-pane active" id="overview">
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
                        <!-- Τμήμα Σχετικά με εμένα -->
                        <section class="profile-section">
                            <h2>Σχετικά με εμένα</h2>
                            <div class="profile-about">
                                <?php if (isset($driverData['about_me']) && $driverData['about_me']) : ?>
                                    <?php echo nl2br(htmlspecialchars($driverData['about_me'])); ?>
                                <?php else : ?>
                                    <p class="profile-empty">Δεν έχετε προσθέσει πληροφορίες για τον εαυτό σας. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθέστε τώρα!</a></p>
                                <?php endif; ?>
                            </div>
                        </section>
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
                        <!-- Ενότητα Τοποθεσίας -->
                        <?php if (isset($driverData['address']) && $driverData['address'] && isset($driverData['city']) && $driverData['city']) : ?>
                            <section class="profile-section">
                                <div class="location-details">
                                    <div class="location-address">
                                        <h2>Τοποθεσία: <span><?php echo htmlspecialchars($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']); ?></span></h2>



                                    </div>
                                </div>
                                <div class="profile-map">
                                    <iframe
                                        width="100%"
                                        height="200"
                                        frameborder="0"
                                        scrolling="no"
                                        marginheight="0"
                                        marginwidth="0"
                                        src="https://maps.google.com/maps?q=<?php echo urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']); ?>&output=embed"></iframe>
                                </div>
                            </section>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

