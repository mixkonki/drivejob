<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Συμπερίληψη του Logger
use Drivejob\Core\Logger;

// Αρχικοποίηση του Logger
Logger::init();
Logger::info("Φόρτωση της σελίδας edit_profile για τον οδηγό " . $driverId, ["page" => "EditProfile"]);

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);
?>

<?= \Drivejob\Helpers\Asset::css('css/driver-profile.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-edit-profile.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-skills.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/toggle-switch.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/vehicle-experience.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/form-buttons-fix.css') ?>
<?php /* ΤΕΛΕΥΤΑΙΟ επίτηδες: το πακέτο ευθυγράμμισης κερδίζει το cascade */ ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-edit-align.css') ?>
<?php /* Ο χάρτης της ακτίνας εργασίας (30/08). Το `async defer` δεν
   μπλοκάρει τη φόρτωση της φόρμας· αν το API δεν έρθει, το work-radius.js
   δουλεύει χωρίς χάρτη — δείκτης και λίστα πόλεων μένουν λειτουργικά. */ ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places" async defer></script>
<?php /* Τα παλιά tesseract-bundle/tesseract-fallback αφαιρέθηκαν: φόρτωναν
   έναν TesseractWrapper που δεν καλούσε κανείς — τα κουμπιά «Σκανάρισμα»
   δεν είχαν ΚΑΝΕΝΑΝ χειριστή. Ο πραγματικός χειριστής είναι το
   license-ocr.js, που φορτώνει το Tesseract μόνο όταν πατηθεί το κουμπί. */ ?>
<?= \Drivejob\Helpers\Asset::js('js/license-ocr.js', false) ?>
<?= \Drivejob\Helpers\Asset::js('js/driver_edit_profile.js', false) ?>
<?= \Drivejob\Helpers\Asset::js('js/license-validation.js', false) ?>
<?= \Drivejob\Helpers\Asset::js('js/country-phone-codes.js', false) ?>
<?= \Drivejob\Helpers\Asset::js('js/vehicle-experience.js', false) ?>
<?= \Drivejob\Helpers\Asset::js('js/driver-languages.js', true) ?>
<?php /* Το παλιό inline state των υποειδικοτήτων (window.selectedSubSpecialities
   κλπ) αφαιρέθηκε 25/08: το v2 των αδειών χειριστή δουλεύει με απλά
   πεδία φόρμας op_lic[N][...] — δες operator-licenses.php/.js */ ?>

<main>
    <div class="container">

        <h1>Επεξεργασία Προφίλ Οδηγού</h1>

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

        <form action="<?php echo BASE_URL; ?>drivers/update-profile" method="POST" enctype="multipart/form-data" id="driverProfileForm" class="edit-profile-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Καρτέλες φόρμας -->
            <div class="form-tabs">
                <?php /* Σειρά καρτελών (25/08, feedback Κώστα): Προσωπικά+Επικοινωνία μαζί,
                   ο ταχογράφος ΔΙΠΛΑ στην άδεια οδήγησης, οι ειδικές άδειες τελευταίες. */ ?>
                <nav class="tabs-nav">
                    <button type="button" class="tab-btn active" data-tab="personal-info">Προσωπικά & Επικοινωνία</button>
                    <button type="button" class="tab-btn" data-tab="driving-licenses">Άδειες Οδήγησης</button>
                    <button type="button" class="tab-btn" data-tab="tachograph-card">Κάρτα Ταχογράφου</button>
                    <button type="button" class="tab-btn" data-tab="adr-certificates">Πιστοποιητικά ADR</button>
                    <button type="button" class="tab-btn" data-tab="operator-licenses">Άδειες Χειριστή</button>
                    <button type="button" class="tab-btn" data-tab="special-licenses">Ειδικές Άδειες</button>
                    <button type="button" class="tab-btn" data-tab="skills-tab">Προσόντα & Πιστοποιήσεις</button>
                </nav>

                <div class="tab-content">
                    <!-- Προσωπικά Στοιχεία + Στοιχεία Επικοινωνίας (το contact-info
                         περιλαμβάνεται ΜΕΣΑ από το personal-info.php) -->
                    <?php include __DIR__ . '/partials/edit-tabs/personal-info.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/driving-licenses.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/tachograph-card.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/adr-certificates.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/operator-licenses.php'; ?>
                    <?php /* Οι ειδικές άδειες ΠΡΙΝ τα προσόντα (30/08): όλες οι
                       άδειες/πιστοποιητικά μαζί, τα προσόντα κλείνουν τη φόρμα. */ ?>
                    <?php include __DIR__ . '/partials/edit-tabs/special-licenses.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/skills.php'; ?>



            </div><!-- /.tab-content -->
            </div><!-- /.form-tabs -->

    <?php /* Μπάρα ενεργειών: toggle + κουμπιά σε ΜΙΑ γραμμή (25/08).
       Χωρίς την ετικέτα «Διαθεσιμότητα για εργασία:» — το κείμενο
       κατάστασης δίπλα στο toggle αρκεί. */ ?>
    <div class="form-actions">
        <div class="availability-toggle-container">
            <label class="toggle-switch-label">
                <span class="toggle-switch">
                    <input type="checkbox" name="available_for_work" id="available_for_work" class="toggle-switch-input" value="1" <?php echo $driverData['available_for_work'] ? 'checked' : ''; ?>>
                    <span class="toggle-switch-slider"></span>
                </span>
                <span class="toggle-switch-text">
                    <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η'; ?>
                </span>
            </label>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
            <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn-secondary">Ακύρωση</a>
        </div>

    </div>
    </form>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>