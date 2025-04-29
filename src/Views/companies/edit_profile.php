<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];

// Καθαρισμός των session μεταβλητών μετά την ανάκτησή τους
unset($_SESSION['errors'], $_SESSION['old_input']);

// Σημείωση: Οι συναρτήσεις old(), hasError() και getError() ήδη ορίζονται στο form_helpers.php
// και δεν πρέπει να οριστούν ξανά εδώ
?>

<main>
    <div class="container">
        <h1>Επεξεργασία Προφίλ Εταιρείας</h1>
        
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
        
        <form action="<?php echo BASE_URL; ?>companies/update-profile" method="POST" enctype="multipart/form-data" class="edit-profile-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            
            <div class="form-tabs">
                <div class="tab-nav">
                    <button type="button" class="tab-btn active" data-tab="basic-info">Βασικές Πληροφορίες</button>
                    <button type="button" class="tab-btn" data-tab="company-details">Στοιχεία Εταιρείας</button>
                    <button type="button" class="tab-btn" data-tab="location">Τοποθεσία</button>
                    <button type="button" class="tab-btn" data-tab="contact">Επικοινωνία</button>
                    <button type="button" class="tab-btn" data-tab="social">Κοινωνικά Δίκτυα</button>
                </div>
                
                <div class="tab-content">
                    <!-- Βασικές Πληροφορίες -->
                    <div class="tab-pane active" id="basic-info">
                        <h2>Βασικές Πληροφορίες</h2>
                        
                        <div class="form-group <?php echo hasError('company_name') ? 'has-error' : ''; ?>">
                            <label for="company_name">Όνομα Εταιρείας</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo old('company_name', $companyData['company_name'] ?? ''); ?>" required>
                            <?php if (hasError('company_name')): ?>
                                <div class="error-message"><?php echo getError('company_name'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_logo">Λογότυπο Εταιρείας</label>
                            <?php if (isset($companyData['company_logo']) && $companyData['company_logo']): ?>
                                <div class="current-logo">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($companyData['company_logo']); ?>" alt="Τρέχον λογότυπο">
                                    <p>Τρέχον λογότυπο</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="company_logo" name="company_logo" accept="image/jpeg, image/png, image/gif">
                            <p class="form-hint">Μέγιστο μέγεθος: 2MB. Επιτρεπόμενοι τύποι: JPEG, PNG, GIF</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Περιγραφή Εταιρείας</label>
                            <textarea id="description" name="description" rows="6"><?php echo old('description', $companyData['description'] ?? ''); ?></textarea>
                            <p class="form-hint">Περιγράψτε την εταιρεία σας, τις δραστηριότητες και το όραμά σας.</p>
                        </div>
                    </div>
                    
                    <!-- Στοιχεία Εταιρείας -->
                    <div class="tab-pane" id="company-details">
                        <h2>Στοιχεία Εταιρείας</h2>
                        
                        <div class="form-group">
                            <label for="industry">Κλάδος</label>
                            <select id="industry" name="industry">
                                <option value="">Επιλέξτε Κλάδο</option>
                                <option value="Μεταφορές & Logistics" <?php echo old('industry', $companyData['industry'] ?? '') === 'Μεταφορές & Logistics' ? 'selected' : ''; ?>>Μεταφορές & Logistics</option>
                                <option value="Κατασκευές" <?php echo old('industry', $companyData['industry'] ?? '') === 'Κατασκευές' ? 'selected' : ''; ?>>Κατασκευές</option>
                                <option value="Βιομηχανία" <?php echo old('industry', $companyData['industry'] ?? '') === 'Βιομηχανία' ? 'selected' : ''; ?>>Βιομηχανία</option>
                                <option value="Τρόφιμα & Ποτά" <?php echo old('industry', $companyData['industry'] ?? '') === 'Τρόφιμα & Ποτά' ? 'selected' : ''; ?>>Τρόφιμα & Ποτά</option>
                                <option value="Λιανεμπόριο" <?php echo old('industry', $companyData['industry'] ?? '') === 'Λιανεμπόριο' ? 'selected' : ''; ?>>Λιανεμπόριο</option>
                                <option value="Υπηρεσίες" <?php echo old('industry', $companyData['industry'] ?? '') === 'Υπηρεσίες' ? 'selected' : ''; ?>>Υπηρεσίες</option>
                                <option value="Άλλο" <?php echo old('industry', $companyData['industry'] ?? '') === 'Άλλο' ? 'selected' : ''; ?>>Άλλο</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_size">Μέγεθος Εταιρείας</label>
                            <select id="company_size" name="company_size">
                                <option value="">Επιλέξτε Μέγεθος</option>
                                <option value="1-10 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '1-10 εργαζόμενοι' ? 'selected' : ''; ?>>1-10 εργαζόμενοι</option>
                                <option value="11-50 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '11-50 εργαζόμενοι' ? 'selected' : ''; ?>>11-50 εργαζόμενοι</option>
                                <option value="51-200 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '51-200 εργαζόμενοι' ? 'selected' : ''; ?>>51-200 εργαζόμενοι</option>
                                <option value="201-500 εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '201-500 εργαζόμενοι' ? 'selected' : ''; ?>>201-500 εργαζόμενοι</option>
                                <option value="501+ εργαζόμενοι" <?php echo old('company_size', $companyData['company_size'] ?? '') === '501+ εργαζόμενοι' ? 'selected' : ''; ?>>501+ εργαζόμενοι</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="foundation_year">Έτος Ίδρυσης</label>
                            <input type="number" id="foundation_year" name="foundation_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo old('foundation_year', $companyData['foundation_year'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="vat_number">ΑΦΜ</label>
                            <input type="text" id="vat_number" name="vat_number" value="<?php echo old('vat_number', $companyData['vat_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Τοποθεσία -->
                    <div class="tab-pane" id="location">
                        <h2>Τοποθεσία</h2>
                        
                        <div class="form-group">
                            <label for="address">Διεύθυνση</label>
                            <input type="text" id="address" name="address" value="<?php echo old('address', $companyData['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="city">Πόλη</label>
                            <input type="text" id="city" name="city" value="<?php echo old('city', $companyData['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Χώρα</label>
                            <input type="text" id="country" name="country" value="<?php echo old('country', $companyData['country'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">Ταχυδρομικός Κώδικας</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo old('postal_code', $companyData['postal_code'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Επικοινωνία -->
                    <div class="tab-pane" id="contact">
                        <h2>Στοιχεία Επικοινωνίας</h2>
                        
                        <div class="form-group <?php echo hasError('phone') ? 'has-error' : ''; ?>">
                            <label for="phone">Τηλέφωνο</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo old('phone', $companyData['phone'] ?? ''); ?>" required>
                            <?php if (hasError('phone')): ?>
                                <div class="error-message"><?php echo getError('phone'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?php echo hasError('website') ? 'has-error' : ''; ?>">
                            <label for="website">Ιστοσελίδα</label>
                            <input type="url" id="website" name="website" value="<?php echo old('website', $companyData['website'] ?? ''); ?>" placeholder="https://www.example.com">
                            <?php if (hasError('website')): ?>
                                <div class="error-message"><?php echo getError('website'); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_person">Υπεύθυνος Επικοινωνίας</label>
                            <input type="text" id="contact_person" name="contact_person" value="<?php echo old('contact_person', $companyData['contact_person'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Θέση</label>
                            <input type="text" id="position" name="position" value="<?php echo old('position', $companyData['position'] ?? ''); ?>" placeholder="π.χ. Διευθυντής HR">
                        </div>
                    </div>
                    
                    <!-- Κοινωνικά Δίκτυα -->
                    <div class="tab-pane" id="social">
                        <h2>Κοινωνικά Δίκτυα</h2>
                        
                        <div class="form-group">
                            <label for="social_linkedin">LinkedIn</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" value="<?php echo old('social_linkedin', $companyData['social_linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/company/yourcompany">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_facebook">Facebook</label>
                            <input type="url" id="social_facebook" name="social_facebook" value="<?php echo old('social_facebook', $companyData['social_facebook'] ?? ''); ?>" placeholder="https://www.facebook.com/yourcompany">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_twitter">Twitter</label>
                            <input type="url" id="social_twitter" name="social_twitter" value="<?php echo old('social_twitter', $companyData['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourcompany">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>companies/company_profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<script>
    // Χειρισμός των tabs
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Αφαίρεση της κλάσης active από όλα τα tabs
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Προσθήκη της κλάσης active στο επιλεγμένο tab
                btn.classList.add('active');
                const tabId = btn.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
</script>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>