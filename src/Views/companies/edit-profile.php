<?php

/**
 * Επεξεργασία προφίλ εταιρείας. (ξαναγράφτηκε 01/09/2026 — Φάση Β)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΝΤΙΚΑΤΕΣΤΗΣΕ
 * ══════════════════════════════════════════════════════════════════════
 *
 * 575 γραμμές όπου τα πραγματικά στοιχεία της εταιρείας ήταν χωμένα
 * ανάμεσα σε ολόκληρα «προϊόντα» που δεν υπάρχουν:
 *
 *   «DriveFleet Solutions» με Asset Management & AI Route Optimization,
 *   «DriveManager Pro» με «Ψηφιακούς Φακέλους Οδηγών» και KPIs,
 *   «DriveJob Legal Hub» με «AI-Powered Compliance Assistant»,
 *   «Πακέτο Συνδρομής» με modules και API Access,
 *   πιστοποιήσεις ISO/SQAS/GDP-Φάρμακα με σκέτα checkboxes.
 *
 * Μια φόρμα που ζητά από πραγματική επιχείρηση να «ενεργοποιήσει το
 * Applicant Tracking System» που δεν υπάρχει, δεν είναι φιλόδοξη — είναι
 * αναξιόπιστη. Και η αναξιοπιστία στη ΦΟΡΜΑ μολύνει την εμπιστοσύνη σε
 * ό,τι αληθινό υπάρχει δίπλα της. Όταν κάποιο από αυτά χτιστεί στ'
 * αλήθεια, ξαναμπαίνει — με λειτουργία, όχι με checkbox.
 *
 * Η φόρμα τώρα: τα στοιχεία που βλέπει ο οδηγός και το ταίριασμα —
 * ταυτότητα, περιγραφή, στόλος, είδη μεταφορών, επικοινωνία.
 *
 * ΤΟ EMAIL ΕΜΦΑΝΙΖΕΤΑΙ ΑΛΛΑ ΔΕΝ ΑΛΛΑΖΕΙ ΕΔΩ: είναι το όνομα σύνδεσης.
 * Αλλαγή χωρίς επαλήθευση της νέας διεύθυνσης = ένα ανοιχτό session
 * αρκεί για να χαθεί ο λογαριασμός. Θα αποκτήσει δική του ροή με
 * επιβεβαίωση, όπως ο κωδικός.
 */

include ROOT_DIR . '/src/Views/partials/header.php';
// Η old() ορίζεται στο src/helpers.php (φορτώνεται από το bootstrap).

$transportTypes = json_decode($companyData['transport_types'] ?? '[]', true) ?: [];
$operatingCountries = json_decode($companyData['operating_countries'] ?? '[]', true) ?: [];
?>

<?= \Drivejob\Helpers\Asset::css('css/job-listings.css') ?>

<main>
    <div class="container listing-form-page">

        <div class="page-head">
            <h1>Επεξεργασία προφίλ εταιρείας</h1>
            <p class="muted">Αυτά βλέπουν οι οδηγοί όταν ανοίγουν τις αγγελίες σας.</p>
        </div>

        <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

        <form action="<?php echo BASE_URL; ?>companies/update-profile" method="POST"
              enctype="multipart/form-data" class="listing-form">
            <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::token(); ?>">

            <!-- ─────────────────────── Ταυτότητα ─────────────────────── -->
            <fieldset>
                <legend>Η εταιρεία</legend>

                <div class="field">
                    <label for="company_name">Επωνυμία <span class="required">*</span></label>
                    <input type="text" id="company_name" name="company_name" required maxlength="255"
                           value="<?php echo old('company_name', $companyData['company_name'] ?? ''); ?>">
                </div>

                <div class="field">
                    <label for="company_logo">Λογότυπο</label>
                    <input type="file" id="company_logo" name="company_logo" accept="image/jpeg,image/png,image/gif">
                    <p class="hint">JPEG/PNG/GIF έως 2MB. Το τρέχον λογότυπο μένει αν δεν επιλέξεις νέο.</p>
                </div>

                <div class="field">
                    <label for="description">Περιγραφή</label>
                    <textarea id="description" name="description" rows="5"
                              placeholder="Τι μεταφέρετε, πού, με τι στόλο — ό,τι θα λέγατε σε οδηγό που ρωτά για εσάς."><?php echo old('description', $companyData['description'] ?? ''); ?></textarea>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="industry">Κλάδος</label>
                        <select id="industry" name="industry">
                            <option value="">Επίλεξε</option>
                            <?php
                            $industries = ['Μεταφορές & Logistics', 'Κατασκευές', 'Βιομηχανία',
                                'Τρόφιμα & Ποτά', 'Λιανεμπόριο', 'Τουρισμός & Μετακινήσεις', 'Άλλο'];
                            foreach ($industries as $ind) : ?>
                                <option value="<?php echo htmlspecialchars($ind); ?>"
                                    <?php echo old('industry', $companyData['industry'] ?? '') === $ind ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ind); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="company_size">Εργαζόμενοι</label>
                        <select id="company_size" name="company_size">
                            <option value="">Επίλεξε</option>
                            <?php foreach (['1-10', '11-50', '51-200', '201-500', '500+'] as $size) : ?>
                                <option value="<?php echo $size; ?>"
                                    <?php echo old('company_size', $companyData['company_size'] ?? '') === $size ? 'selected' : ''; ?>>
                                    <?php echo $size; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="foundation_year">Έτος ίδρυσης</label>
                        <input type="number" id="foundation_year" name="foundation_year" min="1900" max="<?php echo date('Y'); ?>"
                               value="<?php echo old('foundation_year', $companyData['foundation_year'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="vat_number">ΑΦΜ</label>
                        <input type="text" id="vat_number" name="vat_number" maxlength="20"
                               value="<?php echo old('vat_number', $companyData['vat_number'] ?? ''); ?>">
                    </div>
                </div>
            </fieldset>

            <!-- ──────────────────── Στόλος & μεταφορές ────────────────── -->
            <fieldset>
                <legend>Στόλος και μεταφορές</legend>

                <div class="field-row">
                    <div class="field">
                        <label for="fleet_size">Οχήματα στόλου</label>
                        <input type="number" id="fleet_size" name="fleet_size" min="0" max="10000"
                               value="<?php echo (int) old('fleet_size', $companyData['fleet_size'] ?? 0); ?>">
                    </div>
                    <div class="field">
                        <label for="active_drivers">Οδηγοί που απασχολείτε</label>
                        <input type="number" id="active_drivers" name="active_drivers" min="0" max="10000"
                               value="<?php echo (int) old('active_drivers', $companyData['active_drivers'] ?? 0); ?>">
                    </div>
                </div>

                <div class="check-group">
                    <h4>Είδη μεταφορών</h4>
                    <div class="check-grid">
                        <?php
                        $availableTypes = [
                            'national' => 'Εθνικές μεταφορές',
                            'international' => 'Διεθνείς μεταφορές',
                            'urban' => 'Αστικές διανομές',
                            'refrigerated' => 'Ψυγεία',
                            'hazmat' => 'Επικίνδυνα φορτία (ADR)',
                            'bulk' => 'Χύδην φορτία',
                            'container' => 'Containers',
                            'vehicle_transport' => 'Μεταφορά οχημάτων',
                            'livestock' => 'Μεταφορά ζώων',
                            'oversized' => 'Υπερμεγέθη φορτία',
                        ];
                        foreach ($availableTypes as $value => $label) : ?>
                            <label class="check">
                                <input type="checkbox" name="transport_types[]" value="<?php echo $value; ?>"
                                    <?php echo in_array($value, $transportTypes, true) ? 'checked' : ''; ?>>
                                <span><?php echo $label; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="check-group">
                    <label class="check">
                        <input type="checkbox" name="operates_internationally" id="operates_internationally" value="1"
                            <?php echo !empty($companyData['operates_internationally']) ? 'checked' : ''; ?>>
                        <span>Εκτελούμε διεθνή δρομολόγια</span>
                    </label>
                </div>

                <div class="check-group" id="countries-block">
                    <h4>Χώρες δραστηριοποίησης</h4>
                    <div class="check-grid">
                        <?php
                        $countries = ['Ελλάδα', 'Βουλγαρία', 'Ρουμανία', 'Σερβία', 'Βόρεια Μακεδονία',
                            'Αλβανία', 'Τουρκία', 'Ιταλία', 'Γερμανία', 'Αυστρία', 'Ουγγαρία',
                            'Πολωνία', 'Τσεχία', 'Ολλανδία', 'Βέλγιο', 'Γαλλία', 'Ισπανία'];
                        foreach ($countries as $countryName) : ?>
                            <label class="check">
                                <input type="checkbox" name="operating_countries[]" value="<?php echo htmlspecialchars($countryName); ?>"
                                    <?php echo in_array($countryName, $operatingCountries, true) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($countryName); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </fieldset>

            <!-- ───────────────────── Έδρα & επικοινωνία ───────────────── -->
            <fieldset>
                <legend>Έδρα και επικοινωνία</legend>

                <div class="field">
                    <label>Email σύνδεσης</label>
                    <input type="text" value="<?php echo htmlspecialchars($companyData['email'] ?? ''); ?>" disabled>
                    <p class="hint">Το email σύνδεσης δεν αλλάζει από εδώ — θα αποκτήσει δική του
                        διαδικασία με επιβεβαίωση, όπως ο κωδικός.</p>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="contact_person">Υπεύθυνος επικοινωνίας</label>
                        <input type="text" id="contact_person" name="contact_person" maxlength="120"
                               value="<?php echo old('contact_person', $companyData['contact_person'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="position">Θέση</label>
                        <input type="text" id="position" name="position" maxlength="120"
                               value="<?php echo old('position', $companyData['position'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="phone">Τηλέφωνο</label>
                        <input type="tel" id="phone" name="phone" maxlength="20"
                               value="<?php echo old('phone', $companyData['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="address">Διεύθυνση</label>
                        <input type="text" id="address" name="address" maxlength="255"
                               value="<?php echo old('address', $companyData['address'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="city">Πόλη</label>
                        <input type="text" id="city" name="city" maxlength="100"
                               value="<?php echo old('city', $companyData['city'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="postal_code">Τ.Κ.</label>
                        <input type="text" id="postal_code" name="postal_code" maxlength="10"
                               value="<?php echo old('postal_code', $companyData['postal_code'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="country">Χώρα</label>
                        <input type="text" id="country" name="country" maxlength="100"
                               value="<?php echo old('country', $companyData['country'] ?? 'Ελλάδα'); ?>">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="website">Ιστοσελίδα</label>
                        <input type="url" id="website" name="website" maxlength="255" placeholder="https://…"
                               value="<?php echo old('website', $companyData['website'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="social_linkedin">LinkedIn</label>
                        <input type="url" id="social_linkedin" name="social_linkedin" maxlength="255" placeholder="https://linkedin.com/company/…"
                               value="<?php echo old('social_linkedin', $companyData['social_linkedin'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="social_facebook">Facebook</label>
                        <input type="url" id="social_facebook" name="social_facebook" maxlength="255" placeholder="https://facebook.com/…"
                               value="<?php echo old('social_facebook', $companyData['social_facebook'] ?? ''); ?>">
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>companies/profile" class="btn-link">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<style>
    /* Δανείζεται τη φόρμα των αγγελιών (listing-form) — ίδια σχεδίαση
       σε όλες τις φόρμες της εταιρείας. Εδώ μόνο ό,τι λείπει. */
    .listing-form-page { max-width: 860px; margin: 0 auto; padding: 0 1rem; }
    .page-head { margin: 1.5rem 0 1rem; }
    .page-head h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
    .page-head .muted { color: #6b7280; margin: 0; font-size: .95rem; }
    .listing-form fieldset { border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.25rem 1.25rem 1rem; margin-bottom: 1.25rem; }
    .listing-form legend { padding: 0 .5rem; font-weight: 600; font-size: 1.05rem; color: #111827; }
    .listing-form input, .listing-form select, .listing-form textarea { box-sizing: border-box; }
    .field { margin-bottom: 1rem; }
    .field label { display: block; margin-bottom: .35rem; font-weight: 500; font-size: .93rem; }
    .field input, .field select, .field textarea {
        width: 100%; padding: .55rem .7rem; border: 1px solid #d1d5db;
        border-radius: 7px; font: inherit; background: #fff;
    }
    .field input:disabled { background: #f3f4f6; color: #6b7280; }
    .field textarea { resize: vertical; }
    .field .required { color: #dc2626; }
    .field-row { display: flex; gap: 1rem; flex-wrap: wrap; }
    .field-row .field { flex: 1 1 180px; }
    .hint { color: #6b7280; font-size: .85rem; margin: .3rem 0 0; }
    .check-group { margin-bottom: 1.1rem; }
    .check-group h4 { margin: 0 0 .5rem; font-size: .92rem; color: #374151; font-weight: 600; }
    .check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(200px, 100%), 1fr)); gap: .4rem .9rem; }
    .check { display: flex; align-items: center; gap: .5rem; font-size: .93rem; cursor: pointer; }
    .check input { width: auto; margin: 0; }
    .form-actions { display: flex; align-items: center; gap: .75rem; margin: 1.5rem 0 2.5rem; flex-wrap: wrap; }
    .btn-link { color: #6b7280; text-decoration: none; font-size: .93rem; }
    @media (max-width: 640px) { .field-row { flex-direction: column; gap: 0; } }
</style>

<script>
    // Οι χώρες εμφανίζονται μόνο για διεθνή δρομολόγια.
    (function () {
        var flag = document.getElementById('operates_internationally');
        var block = document.getElementById('countries-block');
        if (!flag || !block) return;
        function toggle() { block.style.display = flag.checked ? '' : 'none'; }
        flag.addEventListener('change', toggle);
        toggle();
    })();
</script>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
