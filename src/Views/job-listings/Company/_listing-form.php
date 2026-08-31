<?php

use Drivejob\Helpers\VehicleTypes;

/**
 * Η ΜΙΑ φόρμα αγγελίας εταιρείας — δημιουργία ΚΑΙ επεξεργασία. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΕΝΑ PARTIAL ΚΑΙ ΟΧΙ ΔΥΟ ΦΟΡΜΕΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Μέχρι σήμερα υπήρχαν ΔΥΟ άσχετες μεταξύ τους φόρμες:
 *
 *   - Η επεξεργασία (edit.php) είχε ξαναγραφτεί σωστά: κατηγορίες
 *     διπλώματος με κωδικούς, οχήματα από τον VehicleTypes, απαιτήσεις
 *     ως απαιτήσεις.
 *   - Η δημιουργία (create.php) ήταν ΑΝΤΙΓΡΑΦΟ ΤΗΣ ΦΟΡΜΑΣ ΟΔΗΓΟΥ:
 *     διάβαζε τις άδειες οδήγησης από το session ΤΟΥ ΧΡΗΣΤΗ, έλεγε
 *     στην εταιρεία «Δεν έχετε καταχωρήσει ΠΕΙ» και ΑΠΕΝΕΡΓΟΠΟΙΟΥΣΕ
 *     τα κουτάκια απαιτήσεων αν ο λογαριασμός δεν είχε... δίπλωμα.
 *     Εταιρεία δεν μπορούσε ποτέ να ζητήσει ADR, και το απαιτούμενο
 *     δίπλωμα δεν είχε καν πεδίο — γι' αυτό η βάση είχε «C+E» και
 *     κενά: ό,τι υπήρχε είχε μπει από αλλού.
 *
 * Δύο φόρμες για το ίδιο πράγμα αποκλίνουν ΠΑΝΤΑ. Μία φόρμα, δύο
 * περιτυλίγματα (create.php / edit.php).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΑΥΤΑ ΤΑ ΛΕΞΙΚΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Κάθε επιλογή εδώ χρησιμοποιεί ΤΟΥΣ ΙΔΙΟΥΣ ΚΩΔΙΚΟΥΣ με το προφίλ του
 * οδηγού: κατηγορίες B/C1/C/C1E/CE/D1/D/D1E/DE όπως στο driver_licenses,
 * οχήματα από τον VehicleTypes, σημαίες ΠΕΙ/ADR/ταχογράφου/χειριστή όπως
 * οι στήλες του προφίλ. Όταν αγγελία και προφίλ μιλούν την ίδια γλώσσα,
 * το ταίριασμα γίνεται απλή σύγκριση — «ζητά CE, έχει CE» — αντί για
 * μαντεψιά πάνω σε ελεύθερο κείμενο («C+E» ≠ «CE» με ισότητα).
 *
 * Αναμένει στο scope:
 *   $formAction      — URL υποβολής
 *   $formSubmitLabel — κείμενο κουμπιού
 *   $listing         — array της αγγελίας (κενό [] στη δημιουργία)
 *   $listingVehicleTypes — προαιρετικά, κωδικοί οχημάτων
 *   $companyData     — προαιρετικά, για προσυμπλήρωση επικοινωνίας
 */

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

$listing = $listing ?? [];
$isEdit = !empty($listing['id']);

/** Τιμή πεδίου: πρώτα ό,τι πληκτρολόγησε ο χρήστης, μετά ό,τι έχει η αγγελία. */
$val = static function (string $key, $default = '') use ($old, $listing) {
    return $old[$key] ?? $listing[$key] ?? $default;
};

/** Σημαία: μετά από αποτυχημένη υποβολή μετράει ΜΟΝΟ το POST (unchecked
    checkbox δεν στέλνεται — αν κοιτούσαμε την αγγελία θα «ξαναγύριζε»). */
$flag = static function (string $postKey, string $column, $default = false) use ($old, $listing) {
    if ($old) {
        return !empty($old[$postKey]);
    }
    return $listing ? !empty($listing[$column]) : $default;
};

/** Επιλεγμένοι τύποι οχημάτων. */
$selectedVehicles = $old['vehicle_types']
    ?? $listingVehicleTypes
    ?? $listing['vehicle_types']
    ?? [];
if (is_string($selectedVehicles)) {
    $selectedVehicles = array_filter(array_map('trim', explode(',', $selectedVehicles)));
}
$selectedVehicles = array_map([VehicleTypes::class, 'normalise'], (array) $selectedVehicles);

/** Επιλεγμένες κατηγορίες διπλώματος. */
$selectedLicences = $old['required_licenses'] ?? $listing['required_license'] ?? [];
if (is_string($selectedLicences)) {
    $selectedLicences = array_filter(array_map('trim', explode(',', $selectedLicences)));
}
$selectedLicences = (array) $selectedLicences;

/*
 * Οι κατηγορίες με τη σειρά των αλυσίδων του διπλώματος. Οι ίδιοι
 * κωδικοί με τον πίνακα driver_licenses και τον LicenseCollector —
 * αυτό είναι όλο το νόημα.
 */
$licenceOptions = [
    'B' => 'Β — επιβατικό / βαν',
    'BE' => 'ΒΕ — με ρυμουλκούμενο',
    'C1' => 'Γ1 — φορτηγά έως 7,5t',
    'C' => 'Γ — φορτηγά',
    'C1E' => 'Γ1Ε — Γ1 με ρυμουλκούμενο',
    'CE' => 'ΓΕ — συρμός / νταλίκα',
    'D1' => 'Δ1 — μικρά λεωφορεία',
    'D' => 'Δ — λεωφορεία',
    'D1E' => 'Δ1Ε — Δ1 με ρυμουλκούμενο',
    'DE' => 'ΔΕ — λεωφορείο με ρυμουλκούμενο',
];

$machineryOptions = [
    'excavator' => 'Εκσκαφέας (τσάπα)',
    'bulldozer' => 'Προωθητήρας (μπουλντόζα)',
    'loader' => 'Φορτωτής',
    'grader' => 'Ισοπεδωτής (γκρέιντερ)',
    'crane' => 'Γερανός',
    'forklift' => 'Περονοφόρο (κλαρκ)',
    'other' => 'Άλλο',
];

$selectedMachinery = $old['machinery_types'] ?? $listing['machinery_types'] ?? [];
if (is_string($selectedMachinery)) {
    $selectedMachinery = array_filter(array_map('trim', explode(',', $selectedMachinery)));
}
$selectedMachinery = (array) $selectedMachinery;

$jobCategory = $val('job_category');
?>

<?php if (!empty($errors)) : ?>
    <div class="form-errors" role="alert">
        <strong>Δεν αποθηκεύτηκε — έλεγξε τα παρακάτω:</strong>
        <ul>
            <?php foreach ($errors as $error) : ?>
                <li><?php echo htmlspecialchars(is_array($error) ? implode(' ', $error) : $error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($formAction); ?>" method="post" class="listing-form">

    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\Drivejob\Core\CSRF::token()); ?>">
    <input type="hidden" name="listing_type" value="job_offer">

    <!-- ─────────────────────────── Βασικά ─────────────────────────── -->
    <fieldset>
        <legend>Η θέση</legend>

        <div class="field">
            <label for="title">Τίτλος <span class="required">*</span></label>
            <input type="text" id="title" name="title" required maxlength="255"
                   value="<?php echo htmlspecialchars((string) $val('title')); ?>"
                   placeholder="π.χ. Οδηγός βυτιοφόρου ADR — διεθνή δρομολόγια">
        </div>

        <div class="field-row">
            <div class="field">
                <label for="job_category">Κατηγορία <span class="required">*</span></label>
                <select id="job_category" name="job_category" required>
                    <option value="">Επίλεξε κατηγορία</option>
                    <?php
                    $categories = [
                        'cargo_transport' => 'Εμπορευματικές μεταφορές',
                        'passenger_transport' => 'Επιβατικές μεταφορές',
                        'machinery_operator' => 'Χειριστής μηχανημάτων έργου',
                        'machinery_assistant' => 'Βοηθός χειριστή μηχανημάτων έργου',
                    ];
                    foreach ($categories as $code => $label) : ?>
                        <option value="<?php echo $code; ?>" <?php echo $jobCategory === $code ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="job_type">Απασχόληση <span class="required">*</span></label>
                <select id="job_type" name="job_type" required>
                    <option value="">Επίλεξε</option>
                    <?php
                    $jobTypes = [
                        'full_time' => 'Πλήρης',
                        'part_time' => 'Μερική',
                        'contract' => 'Σύμβαση έργου',
                        'temporary' => 'Προσωρινή',
                        'seasonal' => 'Εποχιακή',
                    ];
                    foreach ($jobTypes as $code => $label) : ?>
                        <option value="<?php echo $code; ?>" <?php echo $val('job_type') === $code ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="location">Τοποθεσία <span class="required">*</span></label>
            <input type="text" id="location" name="location" required maxlength="255"
                   value="<?php echo htmlspecialchars((string) $val('location')); ?>"
                   placeholder="π.χ. Θεσσαλονίκη">
            <p class="hint">Γράψε την πόλη — χρησιμοποιείται για το ταίριασμα απόστασης με τους οδηγούς.</p>
        </div>

        <div class="field">
            <label for="description">Περιγραφή <span class="required">*</span></label>
            <textarea id="description" name="description" rows="6" required
                      placeholder="Τι θα κάνει ο οδηγός, τι δρομολόγια, τι ωράριο."><?php echo htmlspecialchars((string) $val('description')); ?></textarea>
        </div>
    </fieldset>

    <!-- ────────────────────────── Οχήματα ────────────────────────── -->
    <fieldset>
        <legend>Οχήματα</legend>
        <p class="hint">Τι θα οδηγεί. Χρησιμοποιείται για να βρει η αγγελία τους σωστούς οδηγούς.</p>

        <?php foreach (VehicleTypes::groups() as $groupName => $codes) : ?>
            <div class="check-group">
                <h4><?php echo htmlspecialchars($groupName); ?></h4>
                <div class="check-grid">
                    <?php foreach ($codes as $code) : ?>
                        <label class="check">
                            <input type="checkbox" name="vehicle_types[]" value="<?php echo $code; ?>"
                                <?php echo in_array($code, $selectedVehicles, true) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars(VehicleTypes::label($code)); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </fieldset>

    <!-- ──────────────────────── Απαιτήσεις ──────────────────────── -->
    <fieldset>
        <legend>Απαιτούμενα προσόντα</legend>
        <p class="hint">Ό,τι τσεκάρεις εδώ συγκρίνεται αυτόματα με το προφίλ κάθε οδηγού —
            ο οδηγός θα δει «σου λείπει το Χ» και εσύ ποιοι το καλύπτουν.</p>

        <div class="check-group">
            <h4>Κατηγορίες διπλώματος</h4>
            <p class="hint">Τσέκαρε ό,τι αρκεί για τη θέση — η ανώτερη κατηγορία καλύπτει τις
                κατώτερες της αλυσίδας της (οδηγός με ΓΕ έχει υποχρεωτικά και Γ).</p>
            <div class="check-grid">
                <?php foreach ($licenceOptions as $code => $label) : ?>
                    <label class="check">
                        <input type="checkbox" name="required_licenses[]" value="<?php echo $code; ?>"
                            <?php echo in_array($code, $selectedLicences, true) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="check-group">
            <h4>Πιστοποιητικά</h4>
            <div class="check-grid">
                <label class="check">
                    <input type="checkbox" name="has_pei" value="1"
                        <?php echo $flag('has_pei', 'requires_pei') ? 'checked' : ''; ?>>
                    <span>ΠΕΙ σε ισχύ</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="has_adr" value="1"
                        <?php echo $flag('has_adr', 'adr_certificate') ? 'checked' : ''; ?>>
                    <span>Πιστοποιητικό ADR</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="has_tachograph" value="1"
                        <?php echo $flag('has_tachograph', 'requires_tachograph') ? 'checked' : ''; ?>>
                    <span>Κάρτα ψηφιακού ταχογράφου</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="has_operator_license" id="has_operator_license" value="1"
                        <?php echo $flag('has_operator_license', 'operator_license') ? 'checked' : ''; ?>>
                    <span>Άδεια χειριστή μηχανημάτων έργου</span>
                </label>
            </div>
        </div>

        <div class="check-group" id="machinery-block">
            <h4>Μηχανήματα έργου</h4>
            <div class="check-grid">
                <?php foreach ($machineryOptions as $code => $label) : ?>
                    <label class="check">
                        <input type="checkbox" name="machinery_types[]" value="<?php echo $code; ?>"
                            <?php echo in_array($code, $selectedMachinery, true) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="experience_years">Ελάχιστη εμπειρία (έτη)</label>
                <input type="number" id="experience_years" name="experience_years" min="0" max="50"
                       value="<?php echo (int) $val('experience_years', 0); ?>">
            </div>
        </div>

        <div class="field">
            <label for="requirements">Άλλες απαιτήσεις</label>
            <textarea id="requirements" name="requirements" rows="3"
                      placeholder="π.χ. καθαρό μητρώο οδήγησης, γνώση αγγλικών."><?php echo htmlspecialchars((string) $val('requirements')); ?></textarea>
        </div>
    </fieldset>

    <!-- ───────────────────────── Αμοιβή ───────────────────────── -->
    <fieldset>
        <legend>Αμοιβή και όροι</legend>

        <div class="field-row">
            <div class="field">
                <label for="salary_min">Από (€)</label>
                <input type="number" id="salary_min" name="salary_min" min="0" step="50"
                       value="<?php echo htmlspecialchars((string) $val('salary_min', '')); ?>">
            </div>
            <div class="field">
                <label for="salary_max">Έως (€)</label>
                <input type="number" id="salary_max" name="salary_max" min="0" step="50"
                       value="<?php echo htmlspecialchars((string) $val('salary_max', '')); ?>">
            </div>
            <div class="field">
                <label for="salary_type">Ανά</label>
                <select id="salary_type" name="salary_type">
                    <?php
                    $salaryTypes = [
                        'monthly' => 'Μήνα',
                        'yearly' => 'Έτος',
                        'daily' => 'Ημέρα',
                        'hourly' => 'Ώρα',
                    ];
                    foreach ($salaryTypes as $code => $label) : ?>
                        <option value="<?php echo $code; ?>" <?php echo $val('salary_type', 'monthly') === $code ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="benefits">Παροχές</label>
            <textarea id="benefits" name="benefits" rows="3"
                      placeholder="π.χ. εκτός έδρας, ιδιωτική ασφάλιση, εταιρικό κινητό."><?php echo htmlspecialchars((string) $val('benefits')); ?></textarea>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="availability">Έναρξη</label>
                <select id="availability" name="availability">
                    <?php
                    $availability = [
                        '' => 'Δεν ορίζεται',
                        'immediate' => 'Άμεσα',
                        '1_week' => 'Εντός εβδομάδας',
                        '2_weeks' => 'Εντός δύο εβδομάδων',
                        '1_month' => 'Εντός μήνα',
                        'negotiable' => 'Κατόπιν συνεννόησης',
                    ];
                    $currentAvailability = (string) $val('preferred_schedule');
                    foreach ($availability as $code => $label) : ?>
                        <option value="<?php echo $code; ?>" <?php echo $currentAvailability === $code ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="expires_at">Λήγει</label>
                <input type="date" id="expires_at" name="expires_at"
                       value="<?php echo !empty($listing['expires_at']) ? date('Y-m-d', strtotime($listing['expires_at'])) : date('Y-m-d', strtotime('+90 days')); ?>">
                <?php if (!$isEdit) : ?>
                    <p class="hint">Προεπιλογή: 90 ημέρες από σήμερα.</p>
                <?php endif; ?>
            </div>
        </div>
    </fieldset>

    <!-- ─────────────────────── Επικοινωνία ─────────────────────── -->
    <fieldset>
        <legend>Επικοινωνία</legend>

        <div class="field-row">
            <div class="field">
                <label for="contact_email">Email</label>
                <input type="email" id="contact_email" name="contact_email" maxlength="255"
                       value="<?php echo htmlspecialchars((string) $val('contact_email', $companyData['email'] ?? '')); ?>">
            </div>
            <div class="field">
                <label for="contact_phone">Τηλέφωνο</label>
                <input type="tel" id="contact_phone" name="contact_phone" maxlength="20"
                       value="<?php echo htmlspecialchars((string) $val('contact_phone', $companyData['phone'] ?? '')); ?>">
            </div>
        </div>

        <div class="field">
            <label for="additional_info">Επιπλέον πληροφορίες</label>
            <textarea id="additional_info" name="additional_info" rows="3"><?php echo htmlspecialchars((string) $val('additional_info', '')); ?></textarea>
        </div>

        <div class="check-grid">
            <label class="check">
                <input type="checkbox" name="is_active" value="1"
                    <?php echo $flag('is_active', 'is_active', true) ? 'checked' : ''; ?>>
                <span>Η αγγελία είναι ενεργή και ορατή</span>
            </label>
            <label class="check">
                <input type="checkbox" name="is_urgent" value="1"
                    <?php echo $flag('is_urgent', 'is_urgent') ? 'checked' : ''; ?>>
                <span>Επείγουσα πρόσληψη</span>
            </label>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?php echo htmlspecialchars($formSubmitLabel); ?></button>
        <?php if ($isEdit) : ?>
            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo (int) $listing['id']; ?>" class="btn-secondary">Προβολή αγγελίας</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>job-listings/my-listings" class="btn-link">Ακύρωση</a>
    </div>
</form>

<style>
    .listing-form-page { max-width: 860px; }
    .page-head { margin: 1.5rem 0 1rem; }
    .page-head h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
    .page-head .muted { color: #6b7280; margin: 0; font-size: .95rem; }

    .listing-form fieldset {
        border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 1.25rem 1.25rem 1rem; margin-bottom: 1.25rem;
        background: #fff; box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
    }
    .listing-form legend {
        padding: 0 .5rem; font-weight: 600; font-size: 1.05rem; color: #111827;
    }

    .field { margin-bottom: 1rem; }
    /* Χωρίς αυτό, width:100% + padding + border = 1px οριζόντιο scroll
       στα κινητά — μετρημένο στο πέρασμα πλατών (01/09). */
    .listing-form input, .listing-form select, .listing-form textarea { box-sizing: border-box; }
    .field label { display: block; margin-bottom: .35rem; font-weight: 500; font-size: .93rem; }
    .field input[type="text"], .field input[type="number"], .field input[type="email"],
    .field input[type="tel"], .field input[type="date"], .field select, .field textarea {
        width: 100%; padding: .55rem .7rem; border: 1px solid #d1d5db;
        border-radius: 7px; font: inherit; background: #fff;
    }
    .field textarea { resize: vertical; }
    .field .required { color: #dc2626; }

    .field-row { display: flex; gap: 1rem; flex-wrap: wrap; }
    .field-row .field { flex: 1 1 180px; }

    .hint { color: #6b7280; font-size: .88rem; margin: 0 0 .9rem; }
    .field .hint, .check-group .hint { margin: .3rem 0 .5rem; }

    .check-group { margin-bottom: 1.1rem; }
    .check-group h4 { margin: 0 0 .5rem; font-size: .92rem; color: #374151; font-weight: 600; }
    .check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(230px, 100%), 1fr)); gap: .4rem .9rem; }
    .check { display: flex; align-items: center; gap: .5rem; font-size: .93rem; cursor: pointer; }
    .check input { width: auto; margin: 0; }

    .form-errors {
        background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
        padding: .9rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem;
    }
    .form-errors ul { margin: .5rem 0 0; padding-left: 1.2rem; }

    .form-actions { display: flex; align-items: center; gap: .75rem; margin: 1.5rem 0 2.5rem; flex-wrap: wrap; }
    .btn-link { color: #6b7280; text-decoration: none; font-size: .93rem; }
    .btn-link:hover { text-decoration: underline; }

    @media (max-width: 640px) {
        .field-row { flex-direction: column; gap: 0; }
    }
</style>

<script>
    /*
     * Η φόρμα ακολουθεί την κατηγορία:
     *   - Μηχανήματα έργου: φαίνονται μόνο στις δύο σχετικές κατηγορίες,
     *     και η «Άδεια χειριστή» προτσεκάρεται στη θέση χειριστή —
     *     δεν νοείται χειριστής χωρίς άδεια. Ο βοηθός ΔΕΝ τη χρειάζεται.
     */
    (function () {
        var category = document.getElementById('job_category');
        var machinery = document.getElementById('machinery-block');
        var operatorFlag = document.getElementById('has_operator_license');
        if (!category || !machinery) return;

        var operatorTouched = false;
        if (operatorFlag) {
            operatorFlag.addEventListener('change', function () { operatorTouched = true; });
        }

        function toggle(userAction) {
            var v = category.value;
            var isMachinery = (v === 'machinery_operator' || v === 'machinery_assistant');
            machinery.style.display = isMachinery ? '' : 'none';

            // Προεπιλογή, όχι επιβολή: μόνο σε ενέργεια χρήστη και μόνο
            // αν δεν έχει πειράξει ο ίδιος το κουτάκι.
            if (userAction && operatorFlag && !operatorTouched) {
                if (v === 'machinery_operator') {
                    operatorFlag.checked = true;
                } else if (v === 'machinery_assistant') {
                    operatorFlag.checked = false;
                }
            }
        }

        category.addEventListener('change', function () { toggle(true); });
        toggle(false);
    })();
</script>
