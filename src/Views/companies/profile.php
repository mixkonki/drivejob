<?php 
// Αυτό πρέπει να υπάρχει στην αρχή του αρχείου
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<!-- Σύνδεση με το CSS αρχείο του προφίλ εταιρείας -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/company_profile.css">


<main>
    <div class="container">
        <div class="profile-header">
            <div class="profile-image">
                <?php if (isset($companyData['company_logo']) && $companyData['company_logo']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($companyData['company_logo']); ?>" alt="Λογότυπο εταιρείας">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>img/default_company_logo.png" alt="Προεπιλεγμένο λογότυπο">
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($companyData['company_name']); ?></h1>
                
                <?php if (isset($companyData['city']) && $companyData['city']): ?>
                    <p class="profile-location">
                        <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                        <?php echo htmlspecialchars($companyData['city'] . ', ' . $companyData['country']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="profile-actions">
    <a href="<?php echo BASE_URL; ?>companies/edit_profile.php" class="btn-primary">Επεξεργασία Προφίλ</a>
    <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-secondary">Νέα Αγγελία</a>
</div>
            </div>
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
        
        <div class="profile-content">
            <div class="profile-main">
                <section class="profile-section">
                    <h2>Σχετικά με την Εταιρεία</h2>
                    <div class="profile-about">
                        <?php if (isset($companyData['description']) && $companyData['description']): ?>
                            <?php echo nl2br(htmlspecialchars($companyData['description'])); ?>
                        <?php else: ?>
                            <p class="profile-empty">Δεν έχετε προσθέσει περιγραφή για την εταιρεία σας. <a href="<?php echo BASE_URL; ?>companies/edit_profile.php">Προσθέστε τώρα!</a></p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <section class="profile-section">
                    <h2>Πληροφορίες Εταιρείας</h2>
                    <div class="company-info">
                        <div class="info-grid">
                            <?php if (isset($companyData['industry']) && $companyData['industry']): ?>
                                <div class="info-item">
                                    <div class="info-label">Κλάδος</div>
                                    <div class="info-value"><?php echo htmlspecialchars($companyData['industry']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($companyData['company_size']) && $companyData['company_size']): ?>
                                <div class="info-item">
                                    <div class="info-label">Μέγεθος Εταιρείας</div>
                                    <div class="info-value"><?php echo htmlspecialchars($companyData['company_size']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($companyData['foundation_year']) && $companyData['foundation_year']): ?>
                                <div class="info-item">
                                    <div class="info-label">Έτος Ίδρυσης</div>
                                    <div class="info-value"><?php echo htmlspecialchars($companyData['foundation_year']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($companyData['vat_number']) && $companyData['vat_number']): ?>
                                <div class="info-item">
                                    <div class="info-label">ΑΦΜ</div>
                                    <div class="info-value"><?php echo htmlspecialchars($companyData['vat_number']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                
                <section class="profile-section">
                    <h2>Οι Αγγελίες μας</h2>
                    <?php if (count($listings['results']) > 0): ?>
                        <div class="profile-listings">
                            <?php foreach ($listings['results'] as $listing): ?>
                                <div class="listing-item">
                                    <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                    <div class="listing-meta">
                                        <span class="listing-type"><?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?></span>
                                        <span class="listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                    </div>
                                    <p class="listing-status <?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="profile-section-footer">
    <a href="<?php echo BASE_URL; ?>my-listings/" class="btn-secondary">Όλες οι αγγελίες μας</a>
    <a href="<?php echo BASE_URL; ?>job-listings/create/" class="btn-primary">Νέα αγγελία</a>
</div>
                    <?php else: ?>
                        <p class="profile-empty">Δεν έχετε δημιουργήσει ακόμα αγγελίες. <a href="<?php echo BASE_URL; ?>job-listings/create">Δημιουργήστε την πρώτη σας αγγελία!</a></p>
                    <?php endif; ?>
                </section>
            </div>
            
            <div class="profile-sidebar">
                <section class="profile-section">
                    <h2>Στοιχεία Επικοινωνίας</h2>
                    <ul class="contact-list">
                        <li>
                            <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                            <span><?php echo htmlspecialchars($companyData['email']); ?></span>
                        </li>
                        <li>
                            <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                            <span><?php echo htmlspecialchars($companyData['phone']); ?></span>
                        </li>
                        
                        <?php if (isset($companyData['website']) && $companyData['website']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/website_icon.png" alt="Ιστοσελίδα">
                                <a href="<?php echo htmlspecialchars($companyData['website']); ?>" target="_blank"><?php echo htmlspecialchars($companyData['website']); ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (isset($companyData['social_linkedin']) && $companyData['social_linkedin']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/linkedin_icon.png" alt="LinkedIn">
                                <a href="<?php echo htmlspecialchars($companyData['social_linkedin']); ?>" target="_blank">LinkedIn</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (isset($companyData['social_facebook']) && $companyData['social_facebook']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/facebook_icon.png" alt="Facebook">
                                <a href="<?php echo htmlspecialchars($companyData['social_facebook']); ?>" target="_blank">Facebook</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (isset($companyData['social_twitter']) && $companyData['social_twitter']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/twitter_icon.png" alt="Twitter">
                                <a href="<?php echo htmlspecialchars($companyData['social_twitter']); ?>" target="_blank">Twitter</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>
                
                <section class="profile-section">
                    <h2>Υπεύθυνος Επικοινωνίας</h2>
                    <?php if (isset($companyData['contact_person']) && $companyData['contact_person']): ?>
                        <div class="contact-person">
                            <h3><?php echo htmlspecialchars($companyData['contact_person']); ?></h3>
                            <?php if (isset($companyData['position']) && $companyData['position']): ?>
                                <p class="contact-position"><?php echo htmlspecialchars($companyData['position']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="profile-empty">Δεν έχετε καταχωρήσει υπεύθυνο επικοινωνίας. <a href="<?php echo BASE_URL; ?>companies/edit_profile.php">Προσθέστε τώρα!</a></p>
                    <?php endif; ?>
                </section>
                
                <?php if (isset($companyData['address']) && $companyData['address'] && isset($companyData['city']) && $companyData['city']): ?>
                    <section class="profile-section">
                        <h2>Τοποθεσία</h2>
                        <div class="profile-map">
                            <iframe
                                width="100%"
                                height="200"
                                frameborder="0"
                                scrolling="no"
                                marginheight="0"
                                marginwidth="0"
                                src="https://maps.google.com/maps?q=<?php echo urlencode($companyData['address'] . ', ' . $companyData['city'] . ', ' . $companyData['country']); ?>&output=embed"
                            ></iframe>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>