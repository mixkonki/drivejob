<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<main>
    <div class="container">
        <h1>Ενημέρωση Βιογραφικού</h1>
        
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
        
        <form action="<?php echo BASE_URL; ?>drivers/update-resume" method="post" class="profile-form">
    <!-- Προσθέστε αυτή τη γραμμή για το CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <!-- Εδώ θα προσθέσετε τα πεδία της φόρμας για την επεξεργασία του βιογραφικού -->
            
            <!-- Ενότητα: Προσωπικά Στοιχεία -->
            <div class="form-section">
                <h2>Προσωπικά Στοιχεία</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="about_me">Περιγραφή / Σχετικά με εμένα:</label>
                        <textarea id="about_me" name="about_me" rows="5"><?php echo htmlspecialchars($driver['about_me'] ?? ''); ?></textarea>
                        <p class="form-help">Γράψτε μια σύντομη περιγραφή για τον εαυτό σας και την επαγγελματική σας εμπειρία.</p>
                    </div>
                </div>
                
                <!-- Προσθέστε κι άλλα πεδία όπως ηλικία, οικογενειακή κατάσταση, κλπ. -->
            </div>
            
            <!-- Ενότητα: Επαγγελματική Εμπειρία -->
            <div class="form-section">
                <h2>Επαγγελματική Εμπειρία</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="experience_years">Έτη Προϋπηρεσίας:</label>
                        <input type="number" id="experience_years" name="experience_years" min="0" max="50" value="<?php echo $driver['experience_years'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="work_experience">Αναλυτική Περιγραφή Εργασιακής Εμπειρίας:</label>
                        <textarea id="work_experience" name="work_experience" rows="8"><?php echo htmlspecialchars($driver['work_experience'] ?? ''); ?></textarea>
                        <p class="form-help">Περιγράψτε την εργασιακή σας εμπειρία, συμπεριλαμβάνοντας προηγούμενους εργοδότες, θέσεις και καθήκοντα.</p>
                    </div>
                </div>
            </div>
            
            <!-- Προσθέστε περισσότερες ενότητες όπως Δεξιότητες, Εκπαίδευση, κλπ. -->
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>