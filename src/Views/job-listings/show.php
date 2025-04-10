<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

// Ορισμός των επιπλέον CSS αρχείων
$css_files = ['css/job-listings.css'];
?>

<main>
    <div class="container">
        <div class="job-listing-detail-page">
            <!-- Επικεφαλίδα αγγελίας -->
            <div class="job-listing-header">
                <h1><?php echo htmlspecialchars($listing['title']); ?></h1>
                
                <div class="job-listing-meta">
                    <span class="job-type <?php echo $listing['job_type']; ?>">
                        <?php 
                        switch ($listing['job_type']) {
                            case 'full_time':
                                echo 'Πλήρης Απασχόληση';
                                break;
                            case 'part_time':
                                echo 'Μερική Απασχόληση';
                                break;
                            case 'contract':
                                echo 'Σύμβαση Έργου';
                                break;
                            case 'temporary':
                                echo 'Προσωρινή Απασχόληση';
                                break;
                        }
                        ?>
                    </span>
                    
                    <span class="listing-type <?php echo $listing['listing_type']; ?>">
                        <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Κύριο μέρος αγγελίας -->
            <div class="job-listing-content">
                <div class="job-listing-main">
                    <!-- Βασικές πληροφορίες -->
                    <section class="job-section">
                        <h2>Περιγραφή</h2>
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                        </div>
                    </section>
                    
                    <!-- Απαιτήσεις -->
                    <section class="job-section">
                        <h2>Απαιτήσεις</h2>
                        <ul class="job-requirements">
                            <li>
                                <strong>Τύπος οχήματος:</strong> 
                                <?php 
                                switch ($listing['vehicle_type']) {
                                    case 'car':
                                        echo 'Αυτοκίνητο';
                                        break;
                                    case 'van':
                                        echo 'Βαν';
                                        break;
                                    case 'truck':
                                        echo 'Φορτηγό';
                                        break;
                                    case 'bus':
                                        echo 'Λεωφορείο';
                                        break;
                                    case 'machinery':
                                        echo 'Μηχάνημα Έργου';
                                        break;
                                    default:
                                        echo htmlspecialchars($listing['vehicle_type']);
                                }
                                ?>
                            </li>
                            <li>
                                <strong>Απαιτούμενη άδεια:</strong> 
                                <?php echo htmlspecialchars($listing['required_license']); ?>
                            </li>
                            <?php if ($listing['experience_years']): ?>
                            <li>
                                <strong>Έτη εμπειρίας:</strong> 
                                <?php echo $listing['experience_years']; ?> έτη
                            </li>
                            <?php endif; ?>
                            <?php if ($listing['adr_certificate']): ?>
                            <li>
                                <strong>Πιστοποιητικό ADR:</strong> Απαιτείται
                            </li>
                            <?php endif; ?>
                            <?php if ($listing['operator_license']): ?>
                            <li>
                                <strong>Άδεια χειριστή μηχανημάτων:</strong> Απαιτείται
                            </li>
                            <?php endif; ?>
                            <?php if ($listing['required_training']): ?>
                            <li>
                                <strong>Απαιτούμενη εκπαίδευση:</strong> 
                                <?php echo nl2br(htmlspecialchars($listing['required_training'])); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </section>
                    
                    <?php if ($listing['benefits']): ?>
                    <section class="job-section">
                        <h2>Παροχές</h2>
                        <div class="job-benefits">
                            <?php echo nl2br(htmlspecialchars($listing['benefits'])); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                    <section class="job-section">
                        <h2>Ετικέτες</h2>
                        <div class="job-tags">
                            <?php foreach ($tags as $tag): ?>
                                <span class="job-tag"><?php echo htmlspecialchars($tag['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
                
                <div class="job-listing-sidebar">
                    <!-- Πληροφορίες χρήστη/εταιρείας -->
                    <div class="job-sidebar-section">
                        <h3>
                            <?php echo $listing['company_id'] ? 'Στοιχεία Εταιρείας' : 'Στοιχεία Οδηγού'; ?>
                        </h3>
                        <div class="user-info">
                            <?php if ($listing['company_id']): ?>
                                <h4><?php echo htmlspecialchars($author['company_name']); ?></h4>
                            <?php else: ?>
                                <h4><?php echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']); ?></h4>
                            <?php endif; ?>
                            
                            <?php if ($listing['contact_email']): ?>
                                <div class="contact-info">
                                    <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                                    <span><?php echo htmlspecialchars($listing['contact_email']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($listing['contact_phone']): ?>
                                <div class="contact-info">
                                    <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                                    <span><?php echo htmlspecialchars($listing['contact_phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="contact-actions">
                                <?php if ($listing['listing_type'] === 'job_offer' && isset($_SESSION['role']) && $_SESSION['role'] === 'driver'): ?>
                                    <a href="<?php echo BASE_URL; ?>job-applications/apply/<?php echo $listing['id']; ?>" class="btn-primary">Υποβολή Αίτησης</a>
                                <?php elseif ($listing['listing_type'] === 'job_search' && isset($_SESSION['role']) && $_SESSION['role'] === 'company'): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($listing['contact_email']); ?>" class="btn-primary">Επικοινωνία</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Βασικές πληροφορίες -->
                    <div class="job-sidebar-section">
                        <h3>Βασικές Πληροφορίες</h3>
                        <ul class="job-details-list">
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <span><?php echo htmlspecialchars($listing['location']); ?></span>
                            </li>
                            
                            <?php if ($listing['salary_min'] || $listing['salary_max']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/salary_icon.png" alt="Αμοιβή">
                                <span>
                                    <?php 
                                    if ($listing['salary_min'] && $listing['salary_max']) {
                                        echo number_format($listing['salary_min']) . '€ - ' . number_format($listing['salary_max']) . '€';
                                    } elseif ($listing['salary_min']) {
                                        echo 'Από ' . number_format($listing['salary_min']) . '€';
                                    } elseif ($listing['salary_max']) {
                                        echo 'Έως ' . number_format($listing['salary_max']) . '€';
                                    }
                                    
                                    if ($listing['salary_type']) {
                                        echo ' / ';
                                        switch ($listing['salary_type']) {
                                            case 'hourly':
                                                echo 'ώρα';
                                                break;
                                            case 'daily':
                                                echo 'ημέρα';
                                                break;
                                            case 'monthly':
                                                echo 'μήνα';
                                                break;
                                            case 'yearly':
                                                echo 'έτος';
                                                break;
                                        }
                                    }
                                    ?>
                                </span>
                            </li>
                            <?php endif; ?>
                            
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/date_icon.png" alt="Ημερομηνία">
                                <span>Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                            </li>
                            
                            <?php if ($listing['expires_at']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/expiry_icon.png" alt="Λήξη">
                                <span>Λήγει: <?php echo date('d/m/Y', strtotime($listing['expires_at'])); ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Χάρτης τοποθεσίας -->
                    <?php if ($listing['latitude'] && $listing['longitude']): ?>
                    <div class="job-sidebar-section">
                        <h3>Τοποθεσία</h3>
                        <div class="job-map">
                            <iframe
                                width="100%"
                                height="250"
                                frameborder="0"
                                scrolling="no"
                                marginheight="0"
                                marginwidth="0"
                                src="https://maps.google.com/maps?q=<?php echo $listing['latitude']; ?>,<?php echo $listing['longitude']; ?>&z=15&output=embed"
                            ></iframe>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Επιλογές χρήστη -->
            <?php if (isset($_SESSION['user_id']) && (
                  ($_SESSION['role'] === 'company' && $listing['company_id'] == $_SESSION['user_id']) || 
                  ($_SESSION['role'] === 'driver' && $listing['driver_id'] == $_SESSION['user_id'])
                )): ?>
                <div class="job-listing-actions">
                    <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                    <a href="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>