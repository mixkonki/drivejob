<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<main>
    <div class="container">
        <h1>Επεξεργασία Προφίλ Οδηγού</h1>
        
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
        
        <form action="<?php echo BASE_URL; ?>drivers/update-profile" method="POST" enctype="multipart/form-data" class="edit-profile-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            
            <!-- Εδώ τα πεδία της φόρμας -->
            <div class="form-group">
                <label for="first_name">Όνομα</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($driverData['first_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Επώνυμο</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($driverData['last_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Τηλέφωνο</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($driverData['phone'] ?? ''); ?>" required>
            </div>
            
            <!-- Προσθέστε περισσότερα πεδία ανάλογα με τις ανάγκες σας -->
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>drivers/driver_profile.php" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>