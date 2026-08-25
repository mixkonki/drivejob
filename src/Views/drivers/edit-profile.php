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



            <!-- Προϋπηρεσία σε Οχήματα -->
            <div class="form-section">
                <h3>Προϋπηρεσία σε Οχήματα</h3>

                <!-- Εμφάνιση του πίνακα προϋπηρεσίας -->
                <?php include ROOT_DIR . '/src/Views/drivers/vehicle-experience-summary.php'; ?>

                <div class="vehicle-experience-link" style="margin-top: 15px;">
                    <a href="<?php echo BASE_URL; ?>drivers/vehicle-experience" class="btn-primary">Διαχείριση Προϋπηρεσίας σε Οχήματα</a>
                </div>
            </div>

    </div>
    </div>
    <!-- Γλωσσικές Ικανότητες — αυτόνομες εγγραφές, άμεση αποθήκευση.
         Καμία σχέση με το κουμπί «Αποθήκευση Αλλαγών» της φόρμας: κάθε
         προσθήκη/διαγραφή γλώσσας γράφεται στη βάση τη στιγμή του κλικ
         (POST /drivers/languages), ίδια φιλοσοφία με την προϋπηρεσία. -->
    <div class="form-section" id="dj-languages">
        <h3>Γλωσσικές Ικανότητες</h3>
        <p class="form-info">Κάθε γλώσσα αποθηκεύεται αμέσως με το «Προσθήκη» — γράψε όσες γλώσσες γνωρίζεις.</p>

        <ul id="dj-lang-list" style="list-style:none; padding:0; margin:0 0 1rem; display:flex; flex-wrap:wrap; gap:.5rem;">
            <?php foreach (($driverLanguages ?? []) as $lang) : ?>
                <li data-id="<?php echo (int) $lang['id']; ?>"
                    style="display:flex; align-items:center; gap:.45rem; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px; padding:.3rem .4rem .3rem .9rem;">
                    <span><strong><?php echo htmlspecialchars($lang['language_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="color:#6b7280; font-size:.85em;">·
                            <?php echo ['native' => 'Μητρική', 'fluent' => 'Άριστα', 'good' => 'Καλά', 'basic' => 'Βασικά'][$lang['level']] ?? $lang['level']; ?></span>
                    </span>
                    <button type="button" class="dj-lang-del" data-id="<?php echo (int) $lang['id']; ?>" title="Διαγραφή"
                            style="border:0; background:#e5e7eb; color:#6b7280; border-radius:50%; width:22px; height:22px; line-height:1; cursor:pointer;">×</button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div style="display:flex; flex-wrap:wrap; gap:.6rem; align-items:end;">
            <div class="form-group" style="margin:0;">
                <label for="dj-lang-name">Γλώσσα</label>
                <input type="text" id="dj-lang-name" list="dj-lang-suggestions" placeholder="π.χ. Βουλγαρικά" maxlength="50" style="min-width:180px;">
                <datalist id="dj-lang-suggestions">
                    <option value="Ελληνικά"></option><option value="Αγγλικά"></option><option value="Γερμανικά"></option>
                    <option value="Γαλλικά"></option><option value="Ιταλικά"></option><option value="Ισπανικά"></option>
                    <option value="Ρωσικά"></option><option value="Βουλγαρικά"></option><option value="Ρουμανικά"></option>
                    <option value="Τουρκικά"></option><option value="Αλβανικά"></option><option value="Σερβικά"></option>
                    <option value="Πολωνικά"></option><option value="Ουκρανικά"></option><option value="Αραβικά"></option>
                </datalist>
            </div>
            <div class="form-group" style="margin:0;">
                <label for="dj-lang-level">Επίπεδο</label>
                <select id="dj-lang-level">
                    <option value="basic">Βασικά</option>
                    <option value="good" selected>Καλά</option>
                    <option value="fluent">Άριστα</option>
                    <option value="native">Μητρική Γλώσσα</option>
                </select>
            </div>
            <button type="button" id="dj-lang-add" class="btn-primary" style="margin:0;">Προσθήκη</button>
        </div>
        <div id="dj-lang-msg" style="display:none; margin-top:.6rem; padding:.45rem .7rem; border-radius:6px; font-size:.88rem;"></div>
    </div>

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