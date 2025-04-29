<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-incidents.css">

<main>
    <div class="container">
        <div class="page-header">
            <h1>Καταχώρηση Συμβάντος</h1>
            <p>Καταχωρήστε ατυχήματα, παραβάσεις και άλλα συμβάντα που σχετίζονται με την οδήγησή σας.</p>
        </div>
        
        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Ανάκτηση σφαλμάτων και παλιών τιμών από το session
        $errors = $_SESSION['errors'] ?? [];
        $oldInput = $_SESSION['old_input'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old_input']);
        ?>
        
        <div class="incident-form-container">
            <form method="POST" action="<?php echo BASE_URL; ?>drivers/save-incident" id="incident-form">
                <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                
                <div class="form-row">
                    <div class="form-group <?php echo isset($errors['incident_type']) ? 'has-error' : ''; ?>">
                        <label for="incident_type">Τύπος Συμβάντος:</label>
                        <select id="incident_type" name="incident_type" required>
                            <option value="">Επιλέξτε τύπο συμβάντος</option>
                            <option value="accident" <?php echo isset($oldInput['incident_type']) && $oldInput['incident_type'] == 'accident' ? 'selected' : ''; ?>>Ατύχημα</option>
                            <option value="traffic_violation" <?php echo isset($oldInput['incident_type']) && $oldInput['incident_type'] == 'traffic_violation' ? 'selected' : ''; ?>>Παράβαση ΚΟΚ</option>
                            <option value="near_miss" <?php echo isset($oldInput['incident_type']) && $oldInput['incident_type'] == 'near_miss' ? 'selected' : ''; ?>>Παρ' ολίγον ατύχημα</option>
                            <option value="complaint" <?php echo isset($oldInput['incident_type']) && $oldInput['incident_type'] == 'complaint' ? 'selected' : ''; ?>>Παράπονο</option>
                            <option value="other" <?php echo isset($oldInput['incident_type']) && $oldInput['incident_type'] == 'other' ? 'selected' : ''; ?>>Άλλο</option>
                        </select>
                        <?php if (isset($errors['incident_type'])) : ?>
                            <div class="error-message"><?php echo $errors['incident_type']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group <?php echo isset($errors['incident_date']) ? 'has-error' : ''; ?>">
                        <label for="incident_date">Ημερομηνία Συμβάντος:</label>
                        <input type="date" id="incident_date" name="incident_date" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo $oldInput['incident_date'] ?? ''; ?>">
                        <?php if (isset($errors['incident_date'])) : ?>
                            <div class="error-message"><?php echo $errors['incident_date']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group <?php echo isset($errors['severity']) ? 'has-error' : ''; ?>">
                    <label for="severity">Σοβαρότητα:</label>
                    <div class="severity-selector">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <div class="severity-option">
                                <input type="radio" id="severity-<?php echo $i; ?>" name="severity" value="<?php echo $i; ?>" <?php echo (isset($oldInput['severity']) && $oldInput['severity'] == $i) ? 'checked' : ''; ?> required>
                                <label for="severity-<?php echo $i; ?>" class="severity-label severity-<?php echo $i; ?>">
                                    <span class="severity-number"><?php echo $i; ?></span>
                                    <span class="severity-text">
                                        <?php
                                        switch ($i) {
                                            case 1:
                                                echo "Ελάχιστη";
                                                break;
                                            case 2:
                                                echo "Μικρή";
                                                break;
                                            case 3:
                                                echo "Μέτρια";
                                                break;
                                            case 4:
                                                echo "Σημαντική";
                                                break;
                                            case 5:
                                                echo "Σοβαρή";
                                                break;
                                        }
                                        ?>
                                    </span>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <?php if (isset($errors['severity'])) : ?>
                        <div class="error-message"><?php echo $errors['severity']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?php echo isset($errors['description']) ? 'has-error' : ''; ?>">
                    <label for="description">Περιγραφή Συμβάντος:</label>
                    <textarea id="description" name="description" rows="5" required placeholder="Περιγράψτε το συμβάν με λεπτομέρειες..."><?php echo $oldInput['description'] ?? ''; ?></textarea>
                    <?php if (isset($errors['description'])) : ?>
                        <div class="error-message"><?php echo $errors['description']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="incident-note">
                    <p>Σημείωση: Η καταχώρηση συμβάντων είναι εθελοντική αλλά συνιστάται για την ακριβέστερη αξιολόγηση της οδηγικής σας συμπεριφοράς. Τα καταχωρημένα συμβάντα μπορεί να επηρεάσουν τη βαθμολογία ασφάλειάς σας.</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Υποβολή Συμβάντος</button>
                    <a href="<?php echo BASE_URL; ?>drivers/incident-history" class="btn-secondary">Ακύρωση</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
