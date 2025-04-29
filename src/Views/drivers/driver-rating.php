<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-rating.css">

<main>
    <div class="container">
        <div class="page-header">
            <h1>Βαθμολογία Οδηγού</h1>
            <p>Η συνολική αξιολόγησή σας με βάση τα προσόντα, την ασφάλεια, τον επαγγελματισμό και τις τεχνικές σας δεξιότητες.</p>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="rating-container">
            <div class="overall-rating">
            <div class="score-circle">
    <svg viewBox="0 0 100 100">
        <circle class="score-background" cx="50" cy="50" r="45" fill="none"></circle>
        <circle class="score-progress" cx="50" cy="50" r="45" fill="none" style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo $driverRating['total_score']; ?>) / 100)"></circle>
    </svg>
    <div class="score-text">
        <span class="score-value"><?php echo round($driverRating['total_score']); ?></span>
        <span class="score-label">Συνολική Βαθμολογία</span>
    </div>
</div>
                
                <div class="rating-update">
                    <p>Τελευταία ενημέρωση: <?php echo date('d/m/Y H:i', strtotime($driverRating['last_updated'])); ?></p>
                    <a href="<?php echo BASE_URL; ?>drivers/refresh-rating" class="btn-refresh">Ανανέωση Βαθμολογίας</a>
                </div>
            </div>
            
            <div class="rating-categories">
                <div class="rating-category">
                    <h3>Προσόντα</h3>
                    <div class="category-score-container">
                        <div class="category-score"><?php echo round($driverRating['skills_score'], 1); ?>/25</div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($driverRating['skills_score'] / 25) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div class="category-details">
                        <p>Βαθμολογία με βάση τις άδειες οδήγησης, τα πιστοποιητικά σας και την επαγγελματική σας εμπειρία.</p>
                        <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-action">Ενημέρωση Προσόντων</a>
                    </div>
                </div>
                
                <div class="rating-category">
                    <h3>Ασφάλεια</h3>
                    <div class="category-score-container">
                        <div class="category-score"><?php echo round($driverRating['safety_score'], 1); ?>/30</div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($driverRating['safety_score'] / 30) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div class="category-details">
                        <p>Βαθμολογία με βάση το ιστορικό συμβάντων και τα δεδομένα τηλεματικής (αν υπάρχουν).</p>
                        <a href="<?php echo BASE_URL; ?>drivers/incident-history" class="btn-action">Ιστορικό Συμβάντων</a>
                    </div>
                </div>
                
                <div class="rating-category">
                    <h3>Επαγγελματισμός</h3>
                    <div class="category-score-container">
                        <div class="category-score"><?php echo round($driverRating['professionalism_score'], 1); ?>/25</div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($driverRating['professionalism_score'] / 25) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div class="category-details">
                        <p>Βαθμολογία με βάση τα στοιχεία της αυτοαξιολόγησής σας σχετικά με τον επαγγελματισμό σας.</p>
                        <a href="<?php echo BASE_URL; ?>drivers/update-assessment" class="btn-action">Συμπλήρωση Αυτοαξιολόγησης</a>
                    </div>
                </div>
                
                <div class="rating-category">
                    <h3>Τεχνικές Δεξιότητες</h3>
                    <div class="category-score-container">
                        <div class="category-score"><?php echo round($driverRating['technical_score'], 1); ?>/20</div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($driverRating['technical_score'] / 20) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div class="category-details">
                        <p>Βαθμολογία με βάση τα στοιχεία της αυτοαξιολόγησής σας σχετικά με τις τεχνικές σας δεξιότητες.</p>
                        <a href="<?php echo BASE_URL; ?>drivers/update-assessment" class="btn-action">Συμπλήρωση Αυτοαξιολόγησης</a>
                    </div>
                </div>
            </div>
            
            <div class="rating-improvement">
                <h3>Συμβουλές Βελτίωσης</h3>
                <ul class="improvement-tips">
                    <?php if ($driverRating['skills_score'] < 20): ?>
                        <li>
                            <i class="tip-icon skills-icon"></i>
                            <div class="tip-content">
                                <h4>Βελτίωση Προσόντων</h4>
                                <p>Προσθέστε επιπλέον πιστοποιήσεις και σεμινάρια στο προφίλ σας για να αυξήσετε τη βαθμολογία σας.</p>
                            </div>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($driverRating['safety_score'] < 24): ?>
                        <li>
                            <i class="tip-icon safety-icon"></i>
                            <div class="tip-content">
                                <h4>Βελτίωση Ασφάλειας</h4>
                                <p>Ακολουθήστε τους κανόνες οδικής ασφάλειας και αποφύγετε παραβάσεις για να βελτιώσετε τη βαθμολογία ασφάλειας.</p>
                            </div>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($driverRating['professionalism_score'] < 20): ?>
                        <li>
                            <i class="tip-icon professionalism-icon"></i>
                            <div class="tip-content">
                                <h4>Βελτίωση Επαγγελματισμού</h4>
                                <p>Συμπληρώστε την αυτοαξιολόγησή σας και εστιάστε στη βελτίωση της συνέπειας, της επικοινωνίας με πελάτες και της επαγγελματικής σας εμφάνισης.</p>
                            </div>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($driverRating['technical_score'] < 16): ?>
                        <li>
                            <i class="tip-icon technical-icon"></i>
                            <div class="tip-content">
                                <h4>Βελτίωση Τεχνικών Δεξιοτήτων</h4>
                                <p>Ενισχύστε τις τεχνικές σας γνώσεις για τη συντήρηση οχημάτων, την αντιμετώπιση προβλημάτων και τη διαχείριση φορτίου.</p>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <?php if (isset($telemetryData)): ?>
            <div class="telemetry-section">
                <h3>Δεδομένα Τηλεματικής</h3>
                <div class="telemetry-summary">
                    <div class="telemetry-score">
                    <div class="score-circle small">
    <svg viewBox="0 0 100 100">
        <circle class="score-background" cx="50" cy="50" r="45" fill="none"></circle>
        <circle class="score-progress" cx="50" cy="50" r="45" fill="none" style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo $telemetryData['score']; ?>) / 100)"></circle>
    </svg>
    <div class="score-text">
        <span class="score-value"><?php echo $telemetryData['score']; ?></span>
    </div>
</div>
                        <div class="score-label">Βαθμολογία Οδήγησης</div>
                    </div>
                    
                    <div class="telemetry-metrics">
                        <div class="metric-card">
                            <div class="metric-icon speed-icon"></div>
                            <div class="metric-details">
                                <h4>Μέση Ταχύτητα</h4>
                                <div class="metric-value"><?php echo number_format($telemetryData['avg_speed'], 1); ?> χλμ/ώρα</div>
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-icon brake-icon"></div>
                            <div class="metric-details">
                                <h4>Απότομα Φρεναρίσματα</h4>
                                <div class="metric-value"><?php echo $telemetryData['harsh_braking']; ?> περιστατικά</div>
                            </div>
                        </div>
                        
                        <div class="metric-card">
                            <div class="metric-icon acceleration-icon"></div>
                            <div class="metric-details">
                                <h4>Απότομες Επιταχύνσεις</h4>
                                <div class="metric-value"><?php echo $telemetryData['harsh_acceleration']; ?> περιστατικά</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="telemetry-info">
                    <p>Τα δεδομένα τηλεματικής συλλέχθηκαν στις <?php echo date('d/m/Y', strtotime($telemetryData['date_collected'])); ?> για διάστημα οδήγησης <?php echo number_format($telemetryData['total_distance']); ?> χλμ.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="telemetry-promotion">
                <h3>Βελτιώστε τη βαθμολογία σας με την εφαρμογή τηλεματικής</h3>
                <p>Κατεβάστε την εφαρμογή DriveJob Telemetry για να παρακολουθείτε την οδηγική σας συμπεριφορά και να βελτιώσετε τη βαθμολογία ασφάλειάς σας.</p>
                <div class="app-download">
                    <a href="#" class="btn-app-download">
                        <img src="<?php echo BASE_URL; ?>img/app_download.png" alt="Κατέβασμα Εφαρμογής">
                        <span>Κατέβασμα Εφαρμογής</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>