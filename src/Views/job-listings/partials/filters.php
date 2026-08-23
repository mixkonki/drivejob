<?php

/**
 * Τα φίλτρα της λίστας αγγελιών.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΧΩΡΙΣΤΟ ΑΡΧΕΙΟ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Οι επιλογές των μενού ήταν γραμμένες με το χέρι μέσα στο view και είχαν
 * αποκλίνει από τη βάση:
 *
 *   Τύπος οχήματος:  5 επιλογές στο φίλτρο, 11 τύποι στη βάση.
 *                    Η επιλογή «Φορτηγό» (`truck`) δεν αντιστοιχούσε σε
 *                    ΚΑΜΙΑ αποθηκευμένη αγγελία — όλες είναι truck_medium,
 *                    truck_articulated κ.λπ. Επέλεγες «Φορτηγό» και έπαιρνες
 *                    μηδέν αποτελέσματα, ενώ υπήρχαν έντεκα.
 *
 *   Τύπος απασχόλησης: έλειπε η «Εποχική», που η φόρμα δημιουργίας
 *                    πρόσφερε ήδη.
 *
 * Τώρα η λίστα των οχημάτων παράγεται από τον VehicleTypes — μία πηγή
 * αλήθειας για φόρμα, φίλτρο, ετικέτες και εικονίδια.
 *
 * Μεταβλητές: $activeFilters, $locationOptions (από τον controller)
 */

use Drivejob\Helpers\VehicleTypes;

$activeFilters = $activeFilters ?? [];
$locationOptions = $locationOptions ?? [];

$sel = static function (string $key, string $value) use ($activeFilters): string {
    return ($activeFilters[$key] ?? null) === $value ? ' selected' : '';
};

$checked = static function (string $key) use ($activeFilters): string {
    return !empty($activeFilters[$key]) ? ' checked' : '';
};

$jobTypes = [
    'full_time'  => 'Πλήρης απασχόληση',
    'part_time'  => 'Μερική απασχόληση',
    'contract'   => 'Σύμβαση έργου',
    'temporary'  => 'Προσωρινή απασχόληση',
    'seasonal'   => 'Εποχική απασχόληση',
];
?>

<div class="search-filters">
    <form action="<?= BASE_URL ?>job-listings" method="GET">
        <div class="filter-group">
            <label for="listing_type">Τύπος αγγελίας</label>
            <select id="listing_type" name="listing_type">
                <option value="">Όλοι οι τύποι</option>
                <option value="job_offer"<?= $sel('listing_type', 'job_offer') ?>>Εταιρείες που ζητούν οδηγό</option>
                <option value="job_search"<?= $sel('listing_type', 'job_search') ?>>Οδηγοί που ζητούν εργασία</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="job_type">Τύπος απασχόλησης</label>
            <select id="job_type" name="job_type">
                <option value="">Όλοι οι τύποι</option>
                <?php foreach ($jobTypes as $code => $label) : ?>
                    <option value="<?= $code ?>"<?= $sel('job_type', $code) ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="vehicle_type">Τύπος οχήματος</label>
            <select id="vehicle_type" name="vehicle_type">
                <option value="">Όλοι οι τύποι</option>
                <?php foreach (VehicleTypes::groups() as $groupLabel => $codes) : ?>
                    <optgroup label="<?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($codes as $code) : ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"<?= $sel('vehicle_type', $code) ?>>
                                <?= htmlspecialchars(VehicleTypes::label($code), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group checkbox-group">
            <label>
                <input type="checkbox" name="adr_certificate" value="1"<?= $checked('adr_certificate') ?>>
                Απαιτείται ADR
            </label>
        </div>

        <div class="filter-group checkbox-group">
            <label>
                <input type="checkbox" name="operator_license" value="1"<?= $checked('operator_license') ?>>
                Απαιτείται άδεια χειριστή
            </label>
        </div>

        <div class="filter-group">
            <label for="location">Τοποθεσία</label>
            <?php
            /*
             * Datalist αντί για Google Places.
             *
             * Οι προτάσεις είναι οι πόλεις που έχουν ΗΔΗ αγγελίες — δείχνουν
             * στον οδηγό πού υπάρχει δουλειά, αντί να τον αφήνουν να
             * πληκτρολογήσει «Καστοριά» και να πάρει μηδέν αποτελέσματα.
             * Native στοιχείο: κανένα script, κανένας τρίτος, κανένα κλειδί.
             */
            ?>
            <input type="text" id="location" name="location" autocomplete="off"
                   list="dj-locations"
                   value="<?= htmlspecialchars((string) ($activeFilters['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Πόλη ή νομός">
            <?php if (!empty($locationOptions)) : ?>
                <datalist id="dj-locations">
                    <?php foreach ($locationOptions as $city) : ?>
                        <option value="<?= htmlspecialchars((string) $city, ENT_QUOTES, 'UTF-8') ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            <?php endif; ?>
        </div>

        <div class="filter-group filter-actions">
            <button type="submit" class="btn-primary">Αναζήτηση</button>
            <?php if (!empty($activeFilters)) : ?>
                <a href="<?= BASE_URL ?>job-listings" class="btn-secondary">Καθαρισμός</a>
            <?php endif; ?>
        </div>
    </form>
</div>
