<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>
<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">

<main>
    <div class="container">
        <div class="profile-header">
            <div class="profile-image">
                <?php if (isset($driverData['profile_image']) && $driverData['profile_image']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Εικόνα προφίλ">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη εικόνα προφίλ">
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($driverData['first_name'] . ' ' . $driverData['last_name']); ?></h1>
                
                <?php if (isset($driverData['city']) && $driverData['city']): ?>
                    <p class="profile-location">
                        <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                        <?php echo htmlspecialchars($driverData['city'] . ', ' . $driverData['country']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="profile-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Επεξεργασία Προφίλ</a>
                    <?php if (isset($driverData['resume_file']) && $driverData['resume_file']): ?>
                        <a href="<?php echo BASE_URL . htmlspecialchars($driverData['resume_file']); ?>" class="btn-secondary" target="_blank">Προβολή Βιογραφικού</a>
                    <?php endif; ?>
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
                    <h2>Σχετικά με εμένα</h2>
                    <div class="profile-about">
                        <?php if (isset($driverData['about_me']) && $driverData['about_me']): ?>
                            <?php echo nl2br(htmlspecialchars($driverData['about_me'])); ?>
                        <?php else: ?>
                            <p class="profile-empty">Δεν έχετε προσθέσει πληροφορίες για τον εαυτό σας. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθέστε τώρα!</a></p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <section class="profile-section">
                    <h2>Προσόντα & Πιστοποιήσεις</h2>
                    <div class="profile-qualifications">
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                            </div>
                            <div class="qualification-info">
                                <h3>Έτη Εμπειρίας</h3>
                                <?php if (isset($driverData['experience_years']) && $driverData['experience_years']): ?>
                                    <p><?php echo $driverData['experience_years']; ?> έτη</p>
                                <?php else: ?>
                                    <p class="profile-empty">Δεν έχει καταχωρηθεί</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/license_icon.png" alt="Άδεια Οδήγησης">
                            </div>
                            <div class="qualification-info">
                                <h3>Άδεια Οδήγησης</h3>
                                <?php if (isset($driverData['driving_license']) && $driverData['driving_license']): ?>
                                    <p><?php echo htmlspecialchars($driverData['driving_license']); ?></p>
                                    <?php if (isset($driverData['driving_license_expiry']) && $driverData['driving_license_expiry']): ?>
                                        <p class="expiry-date">Λήξη: <?php echo date('d/m/Y', strtotime($driverData['driving_license_expiry'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="profile-empty">Δεν έχει καταχωρηθεί</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/adr_icon.png" alt="Πιστοποιητικό ADR">
                            </div>
                            <div class="qualification-info">
                                <h3>Πιστοποιητικό ADR</h3>
                                <?php if ($driverData['adr_certificate']): ?>
                                    <p>Ναι</p>
                                    <?php if (isset($driverData['adr_certificate_expiry']) && $driverData['adr_certificate_expiry']): ?>
                                        <p class="expiry-date">Λήξη: <?php echo date('d/m/Y', strtotime($driverData['adr_certificate_expiry'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Όχι</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/operator_icon.png" alt="Άδεια Χειριστή Μηχανημάτων">
                            </div>
                            <div class="qualification-info">
                                <h3>Άδεια Χειριστή Μηχανημάτων</h3>
                                <?php if ($driverData['operator_license']): ?>
                                    <p>Ναι</p>
                                    <?php if (isset($driverData['operator_license_expiry']) && $driverData['operator_license_expiry']): ?>
                                        <p class="expiry-date">Λήξη: <?php echo date('d/m/Y', strtotime($driverData['operator_license_expiry'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Όχι</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/training_icon.png" alt="Σεμινάρια Κατάρτισης">
                            </div>
                            <div class="qualification-info">
                                <h3>Σεμινάρια Κατάρτισης</h3>
                                <?php if ($driverData['training_seminars']): ?>
                                    <p>Ναι</p>
                                    <?php if (isset($driverData['training_details']) && $driverData['training_details']): ?>
                                        <div class="training-details">
                                            <?php echo nl2br(htmlspecialchars($driverData['training_details'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Όχι</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="profile-section">
                    <h2>Οι Αγγελίες μου</h2>
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
                            <a href="<?php echo BASE_URL; ?>my-listings" class="btn-secondary">Όλες οι αγγελίες μου</a>
                            <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα αγγελία</a>
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
                            <span><?php echo htmlspecialchars($driverData['email']); ?></span>
                        </li>
                        <li>
                            <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                            <span><?php echo htmlspecialchars($driverData['phone']); ?></span>
                        </li>
                        <?php if (isset($driverData['social_linkedin']) && $driverData['social_linkedin']): ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/linkedin_icon.png" alt="LinkedIn">
                                <a href="<?php echo htmlspecialchars($driverData['social_linkedin']); ?>" target="_blank">LinkedIn Προφίλ</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>
                
                <section class="profile-section">
                    <h2>Προτιμήσεις Εργασίας</h2>
                    <ul class="preferences-list">
                        <li>
                            <strong>Διαθεσιμότητα:</strong>
                            <span class="availability-status <?php echo $driverData['available_for_work'] ? 'available' : 'unavailable'; ?>">
                                <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                            </span>
                        </li>
                        <?php if (isset($driverData['preferred_job_type']) && $driverData['preferred_job_type']): ?>
                            <li>
                                <strong>Προτιμώμενος τύπος εργασίας:</strong>
                                <span><?php echo htmlspecialchars($driverData['preferred_job_type']); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (isset($driverData['preferred_location']) && $driverData['preferred_location']): ?>
                            <li>
                                <strong>Προτιμώμενη τοποθεσία:</strong>
                                <span><?php echo htmlspecialchars($driverData['preferred_location']); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>
                
                <?php if (isset($driverData['address']) && $driverData['address'] && isset($driverData['city']) && $driverData['city']): ?>
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
                                src="https://maps.google.com/maps?q=<?php echo urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']); ?>&output=embed"
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