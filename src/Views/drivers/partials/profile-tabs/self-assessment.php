<?php /* Καρτέλα «self-assessment» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <!-- Καρτέλα Αξιολόγησης Οδηγού -->
            <div class="tab-pane" id="self-assessment">
                <div class="profile-content">
                    <div class="driver-rating-container">
                        <h2>Αξιολόγηση Οδηγού</h2>

                        <div class="rating-main-layout">
                            <!-- Αριστερό τμήμα (3/4) με την αξιολόγηση -->
                            <div class="rating-main-column">
                                <!-- Κεντρικό τμήμα με τη συνολική βαθμολογία -->
                                <div class="rating-overview">
                                    <div class="rating-circle-container">
                                        <div class="score-circle">
                                            <svg viewBox="0 0 100 100">
                                                <circle class="score-background" cx="50" cy="50" r="45" fill="none"></circle>
                                                <circle class="score-progress" cx="50" cy="50" r="45" fill="none"
                                                    style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo isset($driverRating['total_score']) ? $driverRating['total_score'] : 0; ?>) / 100)"></circle>
                                            </svg>
                                            <div class="score-text">
                                                <span class="score-value"><?php echo isset($driverRating['total_score']) ? round($driverRating['total_score']) : '0'; ?></span>
                                                <span class="score-label">Συνολική<br>Βαθμολογία</span>
                                            </div>
                                        </div>
                                        <div class="rating-info">
                                            <p>Τελευταία ενημέρωση: <?php echo isset($driverRating['last_updated']) ? date('d/m/Y', strtotime($driverRating['last_updated'])) : date('d/m/Y'); ?></p>
                                        </div>
                                    </div>

                                    <!-- Επιμέρους κατηγορίες βαθμολογίας -->
                                    <div class="rating-categories">
                                        <div class="rating-category">
                                            <h3>Προσόντα</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['skills_score']) ? round($driverRating['skills_score'], 1) : '0'; ?>/25</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['skills_score']) ? ($driverRating['skills_score'] / 25) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rating-category">
                                            <h3>Ασφάλεια</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['safety_score']) ? round($driverRating['safety_score'], 1) : '0'; ?>/30</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['safety_score']) ? ($driverRating['safety_score'] / 30) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rating-category">
                                            <h3>Επαγγελματισμός</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['professionalism_score']) ? round($driverRating['professionalism_score'], 1) : '0'; ?>/25</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['professionalism_score']) ? ($driverRating['professionalism_score'] / 25) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rating-category">
                                            <h3>Τεχνικές Δεξιότητες</h3>
                                            <div class="category-score-container">
                                                <div class="category-score"><?php echo isset($driverRating['technical_score']) ? round($driverRating['technical_score'], 1) : '0'; ?>/20</div>
                                                <div class="progress-bar">
                                                    <div class="progress" style="width: <?php echo isset($driverRating['technical_score']) ? ($driverRating['technical_score'] / 20) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Κουμπιά ενεργειών κάτω από την αξιολόγηση -->
                                <div class="rating-actions">
                                    <a href="<?php echo BASE_URL; ?>drivers/driver-rating" class="btn-action">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/rating_icon.png') ?>" alt="Αναλυτική Βαθμολογία" class="action-icon">
                                        <span>Αναλυτική Βαθμολογία</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>drivers/incident-history" class="btn-action">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/history_icon.png') ?>" alt="Ιστορικό Συμβάντων" class="action-icon">
                                        <span>Ιστορικό Συμβάντων</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>drivers/report-incident" class="btn-action">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/report_icon.png') ?>" alt="Αναφορά Συμβάντος" class="action-icon">
                                        <span>Αναφορά Συμβάντος</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>drivers/update-assessment" class="btn-action">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/assessment_icon.png') ?>" alt="Συμπλήρωση Αυτοαξιολόγησης" class="action-icon">
                                        <span>Συμπλήρωση Αυτοαξιολόγησης</span>
                                    </a>
                                </div>
                                <!-- Τομέας τηλεματικής αν υπάρχει -->
                                <?php if (isset($telemetryData) && !empty($telemetryData)) : ?>
                                    <div class="telemetry-section">
                                        <h3>Δεδομένα Τηλεματικής</h3>
                                        <div class="telemetry-summary">
                                            <div class="telemetry-score">
                                                <div class="score-circle small">
                                                    <svg viewBox="0 0 100 100">
                                                        <circle class="score-background" cx="50" cy="50" r="45" fill="none"></circle>
                                                        <circle class="score-progress" cx="50" cy="50" r="45" fill="none"
                                                            style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo $telemetryData['score']; ?>) / 100)"></circle>
                                                    </svg>
                                                    <div class="score-text">
                                                        <span class="score-value"><?php echo $telemetryData['score']; ?></span>
                                                    </div>
                                                </div>
                                                <div class="score-label">Βαθμολογία Οδήγησης</div>
                                            </div>

                                            <div class="telemetry-metrics">
                                                <div class="metric-item">
                                                    <div class="metric-label">Μέση Ταχύτητα</div>
                                                    <div class="metric-value"><?php echo number_format($telemetryData['avg_speed'], 1); ?> χλμ/ώρα</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-label">Απότομα Φρεναρίσματα</div>
                                                    <div class="metric-value"><?php echo $telemetryData['harsh_braking']; ?></div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-label">Απότομες Επιταχύνσεις</div>
                                                    <div class="metric-value"><?php echo $telemetryData['harsh_acceleration']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Δεξιό τμήμα (1/4) με πρόσφατα συμβάντα -->
                            <div class="rating-side-column">
                                <!-- Προώθηση εφαρμογής τηλεματικής -->
                                <div class="telemetry-promotion">
                                    <h3>Βελτιώστε τη βαθμολογία σας</h3>
                                    <p>Κατεβάστε την εφαρμογή DriveJob Telemetry για αυτόματη παρακολούθηση της οδηγικής συμπεριφοράς σας.</p>
                                    <a href="#" class="btn-app-download">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/app_download.png') ?>" alt="Κατέβασμα Εφαρμογής">
                                        <span>Κατέβασμα Εφαρμογής</span>
                                    </a>
                                </div>

                                <!-- Πρόσφατα συμβάντα -->
                                <div class="recent-incidents">
                                    <h3>Πρόσφατα Συμβάντα</h3>
                                    <?php if (isset($recentIncidents) && !empty($recentIncidents)) : ?>
                                        <div class="incidents-summary">
                                            <?php foreach (array_slice($recentIncidents, 0, 3) as $incident) : ?>
                                                <div class="incident-item severity-<?php echo $incident['severity']; ?>">
                                                    <div class="incident-date"><?php echo date('d/m/Y', strtotime($incident['incident_date'])); ?></div>
                                                    <div class="incident-type">
                                                        <?php
                                                        $typeLabels = [
                                                            'accident' => 'Ατύχημα',
                                                            'traffic_violation' => 'Παράβαση ΚΟΚ',
                                                            'near_miss' => 'Παρ\' ολίγον ατύχημα',
                                                            'complaint' => 'Παράπονο',
                                                            'other' => 'Άλλο'
                                                        ];
                                                        echo isset($typeLabels[$incident['incident_type']]) ? $typeLabels[$incident['incident_type']] : $incident['incident_type'];
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else : ?>
                                        <p class="no-incidents">Δεν έχετε καταχωρήσει συμβάντα.</p>
                                    <?php endif; ?>
                                </div>



                                <!-- Συμβουλές βελτίωσης -->
                                <div class="improvement-tips">
                                    <h3>Συμβουλές Βελτίωσης</h3>
                                    <div class="tip-item">
                                        <i class="tip-icon safety-icon"></i>
                                        <div class="tip-content">
                                            <h4>Βελτίωση Ασφάλειας</h4>
                                            <p>Τηρείτε τα όρια ταχύτητας και αποφεύγετε τις απότομες κινήσεις.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Προτάσεις εκπαίδευσης -->
                        <div class="training-suggestions">
                            <h3>Προτάσεις Εκπαίδευσης</h3>
                            <div class="training-courses">
                                <div class="course-item">
                                    <div class="course-icon"><img src="<?= \Drivejob\Helpers\Asset::url('img/course_icon.png') ?>" alt="Σεμινάριο"></div>
                                    <div class="course-details">
                                        <h4>Αμυντική Οδήγηση</h4>
                                        <p>Σεμινάριο αμυντικής οδήγησης για επαγγελματίες οδηγούς</p>
                                        <a href="#" class="course-link">Περισσότερα &raquo;</a>
                                    </div>
                                </div>
                                <div class="course-item">
                                    <div class="course-icon"><img src="<?= \Drivejob\Helpers\Asset::url('img/course_icon.png') ?>" alt="Σεμινάριο"></div>
                                    <div class="course-details">
                                        <h4>Διαχείριση Οδηγικού Στρες</h4>
                                        <p>Τεχνικές διαχείρισης άγχους κατά την οδήγηση</p>
                                        <a href="#" class="course-link">Περισσότερα &raquo;</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
