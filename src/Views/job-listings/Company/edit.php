<?php

use Drivejob\Helpers\VehicleTypes;

/**
 * Επεξεργασία αγγελίας από την πλευρά της εταιρείας.
 *
 * ΓΙΑΤΙ ΞΑΝΑΓΡΑΦΤΗΚΕ: το προηγούμενο αρχείο ήταν κολοβό — 277 γραμμές που
 * σταματούσαν στη μέση ενός <input>, χωρίς κουμπί υποβολής και χωρίς footer.
 * Η σελίδα φόρτωνε μισή και ο χρήστης δεν μπορούσε να αποθηκεύσει τίποτα.
 *
 * Τα κουτάκια των οχημάτων παράγονται από τον VehicleTypes αντί να είναι
 * γραμμένα ένα προς ένα — έτσι φόρμα, έλεγχος εγκυρότητας και βάση δεν
 * μπορούν να ξαναποκλίνουν.
 *
 * Αναμένει: $listing (η αγγελία), προαιρετικά $listingVehicleTypes (πίνακας
 * κωδικών) και $errors / $old_input από αποτυχημένη υποβολή.
 */

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

/** Τιμή πεδίου: πρώτα ό,τι πληκτρολόγησε ο χρήστης, μετά ό,τι έχει η αγγελία. */
$val = static function (string $key, $default = '') use ($old, $listing) {
    return $old[$key] ?? $listing[$key] ?? $default;
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

$licenceOptions = [
    'B'   => 'Β — επιβατικά',
    'BE'  => 'Β+Ε — επιβατικό με ρυμουλκούμενο',
    'C1'  => 'Γ1 — φορτηγά έως 7,5 τόνους',
    'C'   => 'Γ — φορτηγά',
    'CE'  => 'Γ+Ε — συρμός',
    'D1'  => 'Δ1 — μικρά λεωφορεία',
    'D'   => 'Δ — λεωφορεία',
    'DE'  => 'Δ+Ε — λεωφορείο με ρυμουλκούμενο',
];

$machineryOptions = [
    'excavator' => 'Εκσκαφέας',
    'bulldozer' => 'Προωθητήρας (μπουλντόζα)',
    'loader'    => 'Φορτωτής',
    'crane'     => 'Γερανός',
    'forklift'  => 'Κλαρκ',
    'grader'    => 'Ισοπεδωτής (γκρέιντερ)',
    'other'     => 'Άλλο',
];

$selectedMachinery = $old['machinery_types'] ?? $listing['machinery_types'] ?? [];
if (is_string($selectedMachinery)) {
    $selectedMachinery = array_filter(array_map('trim', explode(',', $selectedMachinery)));
}
$selectedMachinery = (array) $selectedMachinery;

$jobCategory = $val('job_category');

include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container listing-form-page">

        <div class="page-head">
            <h1>Επεξεργασία αγγελίας</h1>
            <p class="muted">Οι αλλαγές γίνονται ορατές αμέσως μόλις αποθηκεύσεις.</p>
        </div>

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

        <form action="<?php echo BASE_URL; ?>job-listings/update/<?php echo (int) $listing['id']; ?>"
              method="post" class="listing-form">

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\Drivejob\Core\CSRF::generateToken()); ?>">
            <input type="hidden" name="listing_type" value="job_offer">

            <!-- ─────────────────────────── Βασικά ─────────────────────────── -->
            <fieldset>
                <legend>Η θέση</legend>

                <div class="field">
                    <label for="title">Τίτλος <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="255"
                           value="<?php echo htmlspecialchars($val('title')); ?>"
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
                        <label for="job_type">Τύπος απασχόλησης <span class="required">*</span></label>
                        <select id="job_type" name="job_type" required>
                            <?php
                            $jobTypes = [
                                'full_time' => 'Πλήρης απασχόληση',
                                'part_time' => 'Μερική απασχόληση',
                                'contract' => 'Σύμβαση έργου',
                                'temporary' => 'Προσωρινή',
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
                           value="<?php echo htmlspecialchars($val('location')); ?>"
                           placeholder="π.χ. Θεσσαλονίκη, Ελλάδα">
                </div>

                <div class="field">
                    <label for="description">Περιγραφή <span class="required">*</span></label>
                    <textarea id="description" name="description" rows="6" required
                              placeholder="Τι θα κάνει ο οδηγός, τι δρομολόγια, τι ωράριο."><?php echo htmlspecialchars($val('description')); ?></textarea>
                </div>
            </fieldset>

            <!-- ────────────────────────── Οχήματα ────────────────────────── -->
            <fieldset>
                <legend>Οχήματα</legend>
                <p class="hint">Επίλεξε όσα ισχύουν. Χρησιμοποιούνται για να ταιριάξει η αγγελία με τα προσόντα των οδηγών.</p>

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

            <!-- ──────────────────────── Προσόντα ──────────────────────── -->
            <fieldset>
                <legend>Απαιτούμενα προσόντα</legend>

                <div class="check-group">
                    <h4>Κατηγορίες διπλώματος</h4>
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
                            <input type="checkbox" name="has_adr" value="1"
                                <?php echo !empty($listing['adr_certificate']) ? 'checked' : ''; ?>>
                            <span>Πιστοποιητικό ADR</span>
                        </label>
                        <label class="check">
                            <input type="checkbox" name="has_pei" value="1"
                                <?php echo !empty($listing['requires_pei']) ? 'checked' : ''; ?>>
                            <span>ΠΕΙ σε ισχύ</span>
                        </label>
                        <label class="check">
                            <input type="checkbox" name="has_tachograph" value="1"
                                <?php echo !empty($listing['requires_tachograph']) ? 'checked' : ''; ?>>
                            <span>Κάρτα ταχογράφου</span>
                        </label>
                        <label class="check">
                            <input type="checkbox" name="has_operator_license" value="1"
                                <?php echo !empty($listing['operator_license']) ? 'checked' : ''; ?>>
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

                <div class="field">
                    <label for="experience_years">Ελάχιστη εμπειρία (έτη)</label>
                    <input type="number" id="experience_years" name="experience_years" min="0" max="50"
                           value="<?php echo (int) $val('experience_years', 0); ?>">
                </div>

                <div class="field">
                    <label for="requirements">Άλλες απαιτήσεις</label>
                    <textarea id="requirements" name="requirements" rows="3"
                              placeholder="π.χ. καθαρό μητρώο οδήγησης, γνώση αγγλικών."><?php echo htmlspecialchars($val('requirements')); ?></textarea>
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
                              placeholder="π.χ. εκτός έδρας, ιδιωτική ασφάλιση, εταιρικό κινητό."><?php echo htmlspecialchars($val('benefits')); ?></textarea>
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
                            $currentAvailability = $val('preferred_schedule');
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
                               value="<?php echo !empty($listing['expires_at']) ? date('Y-m-d', strtotime($listing['expires_at'])) : ''; ?>">
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
                               value="<?php echo htmlspecialchars((string) $val('contact_email', '')); ?>">
                    </div>
                    <div class="field">
                        <label for="contact_phone">Τηλέφωνο</label>
                        <input type="tel" id="contact_phone" name="contact_phone" maxlength="20"
                               value="<?php echo htmlspecialchars((string) $val('contact_phone', '')); ?>">
                    </div>
                </div>

                <div class="field">
                    <label for="additional_info">Επιπλέον πληροφορίες</label>
                    <textarea id="additional_info" name="additional_info" rows="3"><?php echo htmlspecialchars((string) $val('additional_info', '')); ?></textarea>
                </div>

                <div class="check-grid">
                    <label class="check">
                        <input type="checkbox" name="is_active" value="1"
                            <?php echo !empty($listing['is_active']) ? 'checked' : ''; ?>>
                        <span>Η αγγελία είναι ενεργή και ορατή</span>
                    </label>
                    <label class="check">
                        <input type="checkbox" name="is_urgent" value="1"
                            <?php echo !empty($listing['is_urgent']) ? 'checked' : ''; ?>>
                        <span>Επείγουσα πρόσληψη</span>
                    </label>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo (int) $listing['id']; ?>" class="btn-secondary">Προβολή αγγελίας</a>
                <a href="<?php echo BASE_URL; ?>job-listings/my-listings" class="btn-link">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<style>
    .listing-form-page { max-width: 860px; }
    .page-head { margin: 1.5rem 0 1rem; }
    .page-head h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
    .page-head .muted { color: #6b7280; margin: 0; font-size: .95rem; }

    .listing-form fieldset {
        border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 1.25rem 1.25rem 1rem; margin-bottom: 1.25rem;
    }
    .listing-form legend {
        padding: 0 .5rem; font-weight: 600; font-size: 1.05rem; color: #111827;
    }

    .field { margin-bottom: 1rem; }
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

    .check-group { margin-bottom: 1.1rem; }
    .check-group h4 { margin: 0 0 .5rem; font-size: .92rem; color: #374151; font-weight: 600; }
    .check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: .4rem .9rem; }
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
    // Τα μηχανήματα έργου αφορούν μόνο τις δύο σχετικές κατηγορίες.
    (function () {
        var category = document.getElementById('job_category');
        var machinery = document.getElementById('machinery-block');
        if (!category || !machinery) return;

        function toggle() {
            var v = category.value;
            machinery.style.display =
                (v === 'machinery_operator' || v === 'machinery_assistant') ? '' : 'none';
        }

        category.addEventListener('change', toggle);
        toggle();
    })();
</script>

<?php
include ROOT_DIR . '/src/Views/partials/footer.php';
