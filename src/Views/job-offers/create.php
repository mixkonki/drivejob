<?php

/**
 * Φόρμα αποστολής προσφοράς — GET /job-offers/create/{listingId}
 *
 * Μεταβλητές από τον Driver\JobOfferController::create():
 *   $listing     — η αγγελία «ζητώ εργασία» του οδηγού
 *   $driver      — η εγγραφή του οδηγού (ΔΕΝ εμφανίζεται ονομαστικά)
 *   $driverLabel — «Οδηγός #84» ή το πραγματικό όνομα, αν έχει ξεκλειδώσει
 *   $listingId
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ Η ΦΟΡΜΑ ΕΙΝΑΙ ΠΡΟΣΥΜΠΛΗΡΩΜΕΝΗ ΑΠΟ ΤΗΝ ΑΓΓΕΛΙΑ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Ο οδηγός έχει ήδη γράψει τι ζητάει: τύπο εργασίας, όχημα, περιοχή, μισθό.
 * Αν η εταιρεία ξεκινήσει από κενή φόρμα, θα γράψει τη γενική της αγγελία
 * και η προσφορά θα απαντάει σε άλλη ερώτηση από αυτή που τέθηκε.
 *
 * Προσυμπληρώνουμε λοιπόν με τα αιτήματα του οδηγού και τα αφήνουμε
 * επεξεργάσιμα. Η εταιρεία βλέπει τι ζητήθηκε και αποφασίζει τι δίνει.
 */

use Drivejob\Core\CSRF;
use Drivejob\Core\Session;

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$listing = $listing ?? [];
$driver  = $driver ?? [];

$errors = Session::has('errors') ? (array) Session::get('errors') : [];
$old    = Session::has('old_input') ? (array) Session::get('old_input') : [];
Session::remove('errors');
Session::remove('old_input');

/**
 * Παλιά τιμή → αγγελία του οδηγού → κενό.
 *
 * Με αυτή τη σειρά, μια αποτυχημένη υποβολή δεν σβήνει ό,τι πληκτρολόγησε
 * η εταιρεία και δεν το αντικαθιστά με τα αρχικά του οδηγού.
 */
$val = function (string $field, ?string $fromListing = null, $default = '') use ($old, $listing) {
    if (array_key_exists($field, $old)) {
        return (string) $old[$field];
    }
    if ($fromListing !== null && !empty($listing[$fromListing])) {
        return (string) $listing[$fromListing];
    }

    return (string) $default;
};

$err = fn(string $f): string => isset($errors[$f])
    ? '<span class="err">' . htmlspecialchars((string) $errors[$f], ENT_QUOTES, 'UTF-8') . '</span>'
    : '';

$cls = fn(string $f): string => isset($errors[$f]) ? ' has-err' : '';

$jobTypes = [
    'full_time' => 'Πλήρης απασχόληση',
    'part_time' => 'Μερική απασχόληση',
    'contract'  => 'Σύμβαση έργου',
    'temporary' => 'Προσωρινή',
];

$periods = [
    'month' => 'Μηνιαίως',
    'hour'  => 'Ανά ώρα',
    'day'   => 'Ημερησίως',
    'week'  => 'Εβδομαδιαίως',
    'year'  => 'Ετησίως',
];

$driverId = (int) ($driver['id'] ?? 0);
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1>Αποστολή προσφοράς</h1>
    <p class="app-lead">
        <?php /* Χωρίς γενική πτώση: το «Οδηγός #84» δεν κλίνεται. */ ?>
        Απαντάς στην αγγελία «<?= htmlspecialchars((string) ($listing['title'] ?? 'Αναζήτηση εργασίας'), ENT_QUOTES, 'UTF-8') ?>»
        — παραλήπτης: <?= htmlspecialchars($driverLabel ?? 'οδηγός', ENT_QUOTES, 'UTF-8') ?>.
    </p>

    <?php include ROOT_DIR . '/src/Views/job-applications/partials/messages.php'; ?>

    <div class="app-note">
        Τα στοιχεία επικοινωνίας του οδηγού ξεκλειδώνουν μόνο αν δεχθεί την προσφορά.
        Μπορείς να στείλεις μία εκκρεμή προσφορά ανά οδηγό.
    </div>

    <?php if (!empty($listing)): ?>
        <div class="app-card" style="margin-bottom:1.5rem;">
            <h2>Τι ζητάει ο οδηγός</h2>
            <dl>
                <dt>Τύπος εργασίας</dt>
                <dd><?= htmlspecialchars(offerJobType($listing['job_type'] ?? null), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt>Όχημα</dt>
                <dd><?= htmlspecialchars((string) ($listing['vehicle_type'] ?? '—'), ENT_QUOTES, 'UTF-8') ?: '—' ?></dd>

                <dt>Περιοχή</dt>
                <dd><?= htmlspecialchars((string) ($listing['location'] ?? '—'), ENT_QUOTES, 'UTF-8') ?: '—' ?></dd>

                <dt>Αμοιβή που ζητά</dt>
                <dd><?= htmlspecialchars(offerSalary(
                        $listing['salary_min'] ?? null,
                        $listing['salary_max'] ?? null,
                        $listing['salary_period'] ?? null
                    ), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt>Εμπειρία</dt>
                <dd><?= (int) ($driver['experience_years'] ?? 0) ?> χρόνια</dd>
            </dl>
        </div>
    <?php endif; ?>

    <form class="app-form" method="POST"
          action="<?= BASE_URL ?>job-offers/send/<?= $driverId ?>"
          enctype="multipart/form-data">

        <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
        <input type="hidden" name="listing_id" value="<?= (int) ($listing['id'] ?? 0) ?>">

        <fieldset>
            <legend>Η θέση</legend>

            <div class="app-field<?= $cls('title') ?>">
                <label for="title">Τίτλος θέσης <span class="app-required">*</span></label>
                <input type="text" id="title" name="title" required maxlength="255"
                       value="<?= htmlspecialchars($val('title', 'title'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $err('title') ?>
            </div>

            <div class="app-field<?= $cls('description') ?>">
                <label for="description">Περιγραφή <span class="app-required">*</span></label>
                <textarea id="description" name="description" required><?= htmlspecialchars($val('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
                <span class="hint">Δρομολόγια, ωράριο, βάση, τι περιμένεις από τον οδηγό.</span>
                <?= $err('description') ?>
            </div>

            <div class="app-row">
                <div class="app-field<?= $cls('job_type') ?>">
                    <label for="job_type">Τύπος εργασίας <span class="app-required">*</span></label>
                    <select id="job_type" name="job_type" required>
                        <option value="">— Επίλεξε —</option>
                        <?php $sel = $val('job_type', 'job_type'); ?>
                        <?php foreach ($jobTypes as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $sel === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('job_type') ?>
                </div>

                <div class="app-field<?= $cls('vehicle_type') ?>">
                    <label for="vehicle_type">Τύπος οχήματος</label>
                    <input type="text" id="vehicle_type" name="vehicle_type" maxlength="50"
                           value="<?= htmlspecialchars($val('vehicle_type', 'vehicle_type'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $err('vehicle_type') ?>
                </div>
            </div>

            <div class="app-row">
                <div class="app-field<?= $cls('location') ?>">
                    <label for="location">Τοποθεσία <span class="app-required">*</span></label>
                    <input type="text" id="location" name="location" required maxlength="255"
                           value="<?= htmlspecialchars($val('location', 'location'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $err('location') ?>
                </div>

                <div class="app-field<?= $cls('start_date') ?>">
                    <label for="start_date">Ημερομηνία έναρξης</label>
                    <input type="date" id="start_date" name="start_date"
                           value="<?= htmlspecialchars($val('start_date'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $err('start_date') ?>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Αμοιβή και παροχές</legend>

            <div class="app-row-3">
                <div class="app-field<?= $cls('salary_min') ?>">
                    <label for="salary_min">Από (€)</label>
                    <input type="number" id="salary_min" name="salary_min" min="0" step="50"
                           value="<?= htmlspecialchars($val('salary_min', 'salary_min'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $err('salary_min') ?>
                </div>

                <div class="app-field<?= $cls('salary_max') ?>">
                    <label for="salary_max">Έως (€)</label>
                    <input type="number" id="salary_max" name="salary_max" min="0" step="50"
                           value="<?= htmlspecialchars($val('salary_max', 'salary_max'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $err('salary_max') ?>
                </div>

                <div class="app-field<?= $cls('salary_period') ?>">
                    <label for="salary_period">Περίοδος</label>
                    <select id="salary_period" name="salary_period">
                        <?php $selP = $val('salary_period', 'salary_period', 'month'); ?>
                        <?php foreach ($periods as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $selP === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('salary_period') ?>
                </div>
            </div>

            <div class="app-field<?= $cls('benefits') ?>">
                <label for="benefits">Παροχές</label>
                <textarea id="benefits" name="benefits" style="min-height:90px;"><?= htmlspecialchars($val('benefits'), ENT_QUOTES, 'UTF-8') ?></textarea>
                <span class="hint">Εταιρικό όχημα, κάρτα καυσίμων, ασφάλεια, εκτός έδρας, bonus.</span>
                <?= $err('benefits') ?>
            </div>
        </fieldset>

        <fieldset>
            <legend>Συνημμένα <span style="font-weight:400; color:#6b7280;">(προαιρετικά)</span></legend>

            <div class="app-row">
                <div class="app-field">
                    <label for="offer_document">Έγγραφο προσφοράς</label>
                    <input type="file" id="offer_document" name="offer_document" accept=".pdf,.doc,.docx">
                </div>

                <div class="app-field">
                    <label for="contract_template">Σχέδιο σύμβασης</label>
                    <input type="file" id="contract_template" name="contract_template" accept=".pdf,.doc,.docx">
                </div>
            </div>

            <div class="app-row">
                <div class="app-field">
                    <label for="job_description">Περιγραφή θέσης</label>
                    <input type="file" id="job_description" name="job_description" accept=".pdf,.doc,.docx">
                </div>

                <div class="app-field">
                    <label for="company_brochure">Εταιρικό έντυπο</label>
                    <input type="file" id="company_brochure" name="company_brochure" accept=".pdf,.doc,.docx">
                </div>
            </div>
        </fieldset>

        <div class="app-actions">
            <button type="submit" class="app-submit">Αποστολή προσφοράς</button>
            <a class="app-btn app-btn-quiet" href="<?= BASE_URL ?>job-listings/show/<?= (int) ($listing['id'] ?? 0) ?>"
               style="padding:.7rem 1.2rem;">Ακύρωση</a>
        </div>
    </form>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
