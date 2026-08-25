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
<script>
    // Αρχικοποίηση δεδομένων από τη βάση
    window.driverOperatorSubSpecialities = [];
    window.selectedSubSpecialities = [];

    <?php if (!empty($driverOperatorSubSpecialities)): ?>
        <?php foreach ($driverOperatorSubSpecialities as $spec): ?>
            window.driverOperatorSubSpecialities.push({
                sub_speciality: "<?php echo $spec['sub_speciality']; ?>",
                group_type: "<?php echo $spec['group_type'] ?? 'A'; ?>",
                name: "<?php echo $spec['name'] ?? ''; ?>"
            });
            window.selectedSubSpecialities.push("<?php echo $spec['sub_speciality']; ?>");
        <?php endforeach; ?>
    <?php endif; ?>

    // Μετατροπή των δεδομένων από PHP σε JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Αρχικοποίηση του global αντικειμένου από τα δεδομένα της βάσης
        window.allSelectedSubSpecialities = {};

        // Αν υπάρχουν δεδομένα από τη βάση, τα προσθέτουμε
        if (window.driverOperatorSubSpecialities && window.driverOperatorSubSpecialities.length > 0) {
            window.driverOperatorSubSpecialities.forEach(spec => {
                if (spec.sub_speciality) {
                    window.allSelectedSubSpecialities[spec.sub_speciality] = {
                        checked: true,
                        group: spec.group_type || 'A'
                    };
                }
            });
        }
    });
</script>

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
                <nav class="tabs-nav">
                    <button type="button" class="tab-btn active" data-tab="personal-info">Προσωπικά Στοιχεία</button>
                    <button type="button" class="tab-btn" data-tab="contact-info">Στοιχεία Επικοινωνίας</button>
                    <button type="button" class="tab-btn" data-tab="driving-licenses">Άδειες Οδήγησης</button>
                    <button type="button" class="tab-btn" data-tab="adr-certificates">Πιστοποιητικά ADR</button>
                    <button type="button" class="tab-btn" data-tab="operator-licenses">Άδειες Χειριστή Μηχανημάτων</button>
                    <button type="button" class="tab-btn" data-tab="tachograph-card">Κάρτα Ψηφιακού Ταχογράφου</button>
                    <button type="button" class="tab-btn" data-tab="special-licenses">Ειδικές Άδειες</button>
                    <button type="button" class="tab-btn" data-tab="skills-tab">Προσόντα & Πιστοποιήσεις</button>
                </nav>

                <div class="tab-content">
                    <!-- Καρτέλα Προσωπικών Στοιχείων -->
                    <?php include __DIR__ . '/partials/edit-tabs/personal-info.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/contact-info.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/driving-licenses.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/adr-certificates.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/operator-licenses.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/tachograph-card.php'; ?>
                    <?php include __DIR__ . '/partials/edit-tabs/special-licenses.php'; ?>
            <?php include __DIR__ . '/partials/edit-tabs/skills.php'; ?>



            </div><!-- /.tab-content -->
            </div><!-- /.form-tabs -->

    <!-- Κουμπιά αποθήκευσης και ακύρωσης -->
    <div class="form-actions">
        <div class="availability-toggle-container">
            <label class="toggle-switch-label">
                <span class="toggle-label-text">Διαθεσιμότητα για εργασία:</span>
                <span class="toggle-switch">
                    <input type="checkbox" name="available_for_work" id="available_for_work" class="toggle-switch-input" value="1" <?php echo $driverData['available_for_work'] ? 'checked' : ''; ?>>
                    <span class="toggle-switch-slider"></span>
                </span>
                <span class="toggle-switch-text">
                    <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
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