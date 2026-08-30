<?php
// Σελίδα «Ασφάλεια & Κωδικός» — η αλλαγή κωδικού μεταφέρθηκε εδώ από τη
// φόρμα επεξεργασίας προφίλ (25/08), στο πρότυπο των μεγάλων πλατφορμών:
// ο κωδικός είναι θέμα ΛΟΓΑΡΙΑΣΜΟΥ (μενού πάνω δεξιά), όχι προφίλ.
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<?= \Drivejob\Helpers\Asset::css('css/driver-edit-profile.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-edit-align.css') ?>

<main>
    <?php /* Τα πεδία κωδικού δεν κληρονομούν το box-sizing της φόρμας
       επεξεργασίας (δεν φορτώνεται εδώ το driver-edit-align.css) και στο
       κινητό ξεπερνούσαν το πλάτος της οθόνης κατά 13px. (30/08) */ ?>
    <style>
        .security-page input[type="password"] {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
    </style>

    <div class="container security-page" style="max-width: 680px;">

        <h1>Ασφάλεια &amp; Κωδικός</h1>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>drivers/change-password" method="POST" class="edit-profile-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <div class="form-section">
                <h3>Αλλαγή Κωδικού Πρόσβασης</h3>
                <p class="form-info">Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.</p>

                <div class="form-group">
                    <label for="current_password">Τρέχων Κωδικός</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">Νέος Κωδικός</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
                        <div id="password-strength" class="password-strength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Επιβεβαίωση Νέου Κωδικού</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>

                <div class="form-buttons" style="margin-top: .5rem;">
                    <button type="submit" class="btn-primary">Αλλαγή Κωδικού</button>
                    <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn-secondary">Επιστροφή στο Προφίλ</a>
                </div>
            </div>
        </form>

    </div>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
