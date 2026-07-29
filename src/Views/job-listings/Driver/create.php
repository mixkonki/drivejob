<?php

/**
 * Ενιαία φόρμα δημιουργίας αγγελίας από οδηγό
 * Υποστηρίζει τη δημιουργία αγγελιών για:
 * - Εμπορευματικές μεταφορές
 * - Επιβατικές μεταφορές
 * - Χειριστής μηχανημάτων έργου
 * - Βοηθός χειριστή μηχανημάτων έργου
 */

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ' . BASE_URL . 'auth/login');
    exit();
}

// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Δημιουργία Αγγελίας Αναζήτησης Εργασίας';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Σύνδεση με το CSS αρχείο της φόρμας αγγελιών
echo '<link rel="stylesheet" href="' . BASE_URL . 'css/job-listing-form.css">';

// Λήψη των αδειών και πιστοποιήσεων του οδηγού από το session
$driverLicenses = $_SESSION['driver_licenses'] ?? [];
$driverOperatorLicenses = $_SESSION['driver_operator_licenses'] ?? [];
$hasPEI = $_SESSION['driver_has_pei'] ?? false;
$hasADR = $_SESSION['driver_has_adr'] ?? false;
$hasTachograph = $_SESSION['driver_has_tachograph'] ?? false;
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Δημιουργία Αγγελίας Αναζήτησης Εργασίας</h1>

            <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

            <div class="card">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>job-listings/store" method="post" id="job-listing-form">
                        <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::generateToken() ?>">
                        <input type="hidden" name="listing_type" value="job_search">

                        <!-- Επιλογή τύπου αγγελίας -->
                        <div class="form-group mb-4">
                            <label for="job_category" class="form-label fw-bold">Τύπος Αγγελίας *</label>
                            <select class="form-control form-select" id="job_category" name="job_category" required onchange="updateFormFields()">
                                <option value="">Επιλέξτε τύπο αγγελίας</option>
                                <option value="cargo_transport" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'cargo_transport') ? 'selected' : '' ?>>Εμπορευματικές Μεταφορές</option>
                                <option value="passenger_transport" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'passenger_transport') ? 'selected' : '' ?>>Επιβατικές Μεταφορές</option>
                                <option value="machinery_operator" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'machinery_operator') ? 'selected' : '' ?>>Χειριστής Μηχανημάτων Έργου</option>
                                <option value="machinery_assistant" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'machinery_assistant') ? 'selected' : '' ?>>Βοηθός Χειριστή Μηχανημάτων Έργου</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['job_category'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['job_category'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Βασικές πληροφορίες αγγελίας -->
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">Τίτλος Αγγελίας *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                value="<?= isset($_SESSION['old_input']['title']) ? htmlspecialchars($_SESSION['old_input']['title']) : '' ?>">
                            <?php if (isset($_SESSION['errors']['title'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Περιγραφή *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?= isset($_SESSION['old_input']['description']) ? htmlspecialchars($_SESSION['old_input']['description']) : '' ?></textarea>
                            <?php if (isset($_SESSION['errors']['description'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="location" class="form-label">Τοποθεσία *</label>
                            <input type="text" class="form-control" id="location" name="location" required
                                value="<?= isset($_SESSION['old_input']['location']) ? htmlspecialchars($_SESSION['old_input']['location']) : '' ?>">
                            <?php if (isset($_SESSION['errors']['location'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['location'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="job_type" class="form-label">Τύπος Εργασίας *</label>
                            <select class="form-control form-select" id="job_type" name="job_type" required>
                                <option value="">Επιλέξτε τύπο εργασίας</option>
                                <option value="full_time" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'full_time') ? 'selected' : '' ?>>Πλήρης Απασχόληση</option>
                                <option value="part_time" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'part_time') ? 'selected' : '' ?>>Μερική Απασχόληση</option>
                                <option value="contract" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'contract') ? 'selected' : '' ?>>Σύμβαση</option>
                                <option value="temporary" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'temporary') ? 'selected' : '' ?>>Προσωρινή</option>
                                <option value="seasonal" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'seasonal') ? 'selected' : '' ?>>Εποχιακή</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['job_type'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['job_type'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Πεδία για Εμπορευματικές Μεταφορές -->
                        <div id="cargo_transport_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Εμπορευματικών Μεταφορών</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Διαθέσιμες Άδειες Οδήγησης</label>
                                <div class="alert alert-info">
                                    <?php if (!empty($driverLicenses)): ?>
                                        <p>Έχετε καταχωρήσει τις παρακάτω άδειες οδήγησης:</p>
                                        <ul>
                                            <?php foreach ($driverLicenses as $license): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($license['license_type']) ?></strong>
                                                    <?php if (!empty($license['expiry_date'])): ?>
                                                        (Λήξη: <?= date('d/m/Y', strtotime($license['expiry_date'])) ?>)
                                                    <?php endif; ?>
                                                    <?php if (!empty($license['has_pei']) && $license['has_pei']): ?>
                                                        <span class="badge bg-success">ΠΕΙ</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>Δεν έχετε καταχωρήσει άδειες οδήγησης. <a href="<?= BASE_URL ?>drivers/edit-profile">Προσθέστε τώρα</a> για καλύτερη προβολή της αγγελίας σας.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Προτιμώμενοι Τύποι Οχημάτων</label>
                                <div class="cargo-vehicles">
                                    <?php
                                    // Έλεγχος αδειών για εμφάνιση κατάλληλων οχημάτων
                                    $canDriveVan = true; // Όλοι μπορούν να οδηγήσουν βαν (κατηγορία B)
                                    $canDriveTruck = false;
                                    $canDriveHeavyTruck = false;
                                    $canDriveArticulated = false;

                                    foreach ($driverLicenses as $license) {
                                        if (in_array($license['license_type'], ['C', 'C1'])) {
                                            $canDriveTruck = true;
                                        }
                                        if ($license['license_type'] === 'C') {
                                            $canDriveHeavyTruck = true;
                                        }
                                        if (in_array($license['license_type'], ['CE', 'C1E'])) {
                                            $canDriveArticulated = true;
                                        }
                                    }
                                    ?>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('van', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveVan ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_van">Βαν</label>
                                        <?php if (!$canDriveVan): ?>
                                            <small class="text-muted">(Απαιτείται άδεια B)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_light" id="vehicle_truck_light" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('truck_light', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_light">Ελαφρύ Φορτηγό (έως 3.5τ)</label>
                                        <?php if (!$canDriveTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C1 ή C)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_medium" id="vehicle_truck_medium" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('truck_medium', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_medium">Μεσαίο Φορτηγό (3.5-7.5τ)</label>
                                        <?php if (!$canDriveTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C1 ή C)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_heavy" id="vehicle_truck_heavy" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('truck_heavy', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveHeavyTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_heavy">Βαρύ Φορτηγό (άνω των 7.5τ)</label>
                                        <?php if (!$canDriveHeavyTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_articulated" id="vehicle_truck_articulated" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('truck_articulated', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveArticulated ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_articulated">Αρθρωτό Φορτηγό (με ρυμουλκούμενο)</label>
                                        <?php if (!$canDriveArticulated): ?>
                                            <small class="text-muted">(Απαιτείται άδεια CE ή C1E)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_tanker" id="vehicle_truck_tanker" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('truck_tanker', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveHeavyTruck && $hasADR ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_tanker">Βυτιοφόρο</label>
                                        <?php if (!$canDriveHeavyTruck || !$hasADR): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C και πιστοποιητικό ADR)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_refrigerated" id="vehicle_truck_refrigerated" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('truck_refrigerated', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveHeavyTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_refrigerated">Ψυγείο</label>
                                        <?php if (!$canDriveHeavyTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Πιστοποιήσεις</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_adr" id="has_adr" value="1" <?= (isset($_SESSION['old_input']['has_adr']) && $_SESSION['old_input']['has_adr']) ? 'checked' : ($hasADR ? 'checked' : '') ?> <?= $hasADR ? '' : 'disabled' ?>>
                                    <label class="form-check-label" for="has_adr">Πιστοποιητικό ADR</label>
                                    <?php if (!$hasADR): ?>
                                        <small class="text-muted">(Δεν έχετε καταχωρήσει πιστοποιητικό ADR)</small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_tachograph" id="has_tachograph" value="1" <?= (isset($_SESSION['old_input']['has_tachograph']) && $_SESSION['old_input']['has_tachograph']) ? 'checked' : ($hasTachograph ? 'checked' : '') ?> <?= $hasTachograph ? '' : 'disabled' ?>>
                                    <label class="form-check-label" for="has_tachograph">Κάρτα Ταχογράφου</label>
                                    <?php if (!$hasTachograph): ?>
                                        <small class="text-muted">(Δεν έχετε καταχωρήσει κάρτα ταχογράφου)</small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_pei" id="has_pei" value="1" <?= (isset($_SESSION['old_input']['has_pei']) && $_SESSION['old_input']['has_pei']) ? 'checked' : ($hasPEI ? 'checked' : '') ?> <?= $hasPEI ? '' : 'disabled' ?>>
                                    <label class="form-check-label" for="has_pei">Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)</label>
                                    <?php if (!$hasPEI): ?>
                                        <small class="text-muted">(Δεν έχετε καταχωρήσει ΠΕΙ)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Πεδία για Επιβατικές Μεταφορές -->
                        <div id="passenger_transport_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Επιβατικών Μεταφορών</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Διαθέσιμες Άδειες Οδήγησης</label>
                                <div class="alert alert-info">
                                    <?php if (!empty($driverLicenses)): ?>
                                        <p>Έχετε καταχωρήσει τις παρακάτω άδειες οδήγησης:</p>
                                        <ul>
                                            <?php foreach ($driverLicenses as $license): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($license['license_type']) ?></strong>
                                                    <?php if (!empty($license['expiry_date'])): ?>
                                                        (Λήξη: <?= date('d/m/Y', strtotime($license['expiry_date'])) ?>)
                                                    <?php endif; ?>
                                                    <?php if (!empty($license['has_pei']) && $license['has_pei']): ?>
                                                        <span class="badge bg-success">ΠΕΙ</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>Δεν έχετε καταχωρήσει άδειες οδήγησης. <a href="<?= BASE_URL ?>drivers/edit-profile">Προσθέστε τώρα</a> για καλύτερη προβολή της αγγελίας σας.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Προτιμώμενοι Τύποι Οχημάτων</label>
                                <div class="passenger-vehicles">
                                    <?php
                                    // Έλεγχος αδειών για εμφάνιση κατάλληλων οχημάτων
                                    $canDriveCar = true; // Όλοι μπορούν να οδηγήσουν αυτοκίνητο (κατηγορία B)
                                    $canDriveVan = true; // Όλοι μπορούν να οδηγήσουν βαν (κατηγορία B)
                                    $canDriveBus = false;
                                    $canDriveMiniBus = false;

                                    foreach ($driverLicenses as $license) {
                                        if (in_array($license['license_type'], ['D', 'DE'])) {
                                            $canDriveBus = true;
                                        }
                                        if (in_array($license['license_type'], ['D1', 'D1E'])) {
                                            $canDriveMiniBus = true;
                                        }
                                    }
                                    ?>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="car" id="vehicle_car" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('car', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveCar ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_car">Αυτοκίνητο</label>
                                        <?php if (!$canDriveCar): ?>
                                            <small class="text-muted">(Απαιτείται άδεια B)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van_passenger" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('van', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveVan ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_van_passenger">Βαν</label>
                                        <?php if (!$canDriveVan): ?>
                                            <small class="text-muted">(Απαιτείται άδεια B)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="minibus" id="vehicle_minibus" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('minibus', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveMiniBus ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_minibus">Μικρό Λεωφορείο (έως 16 θέσεις)</label>
                                        <?php if (!$canDriveMiniBus): ?>
                                            <small class="text-muted">(Απαιτείται άδεια D1 ή D)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="bus" id="vehicle_bus" <?= (isset($_SESSION['old_input']['vehicle_types']) && in_array('bus', $_SESSION['old_input']['vehicle_types'])) ? 'checked' : '' ?> <?= $canDriveBus ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_bus">Λεωφορείο</label>
                                        <?php if (!$canDriveBus): ?>
                                            <small class="text-muted">(Απαιτείται άδεια D)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Πιστοποιήσεις</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_tachograph" id="has_tachograph_passenger" value="1" <?= (isset($_SESSION['old_input']['has_tachograph']) && $_SESSION['old_input']['has_tachograph']) ? 'checked' : ($hasTachograph ? 'checked' : '') ?> <?= $hasTachograph ? '' : 'disabled' ?>>
                                    <label class="form-check-label" for="has_tachograph_passenger">Κάρτα Ταχογράφου</label>
                                    <?php if (!$hasTachograph): ?>
                                        <small class="text-muted">(Δεν έχετε καταχωρήσει κάρτα ταχογράφου)</small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_pei" id="has_pei_passenger" value="1" <?= (isset($_SESSION['old_input']['has_pei']) && $_SESSION['old_input']['has_pei']) ? 'checked' : ($hasPEI ? 'checked' : '') ?> <?= $hasPEI ? '' : 'disabled' ?>>
                                    <label class="form-check-label" for="has_pei_passenger">Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)</label>
                                    <?php if (!$hasPEI): ?>
                                        <small class="text-muted">(Δεν έχετε καταχωρήσει ΠΕΙ)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Πεδία για Χειριστή Μηχανημάτων Έργου -->
                        <div id="machinery_operator_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Χειριστή Μηχανημάτων Έργου</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Διαθέσιμες Άδειες Χειριστή</label>
                                <div class="alert alert-info">
                                    <?php if (!empty($driverOperatorLicenses)): ?>
                                        <p>Έχετε καταχωρήσει τις παρακάτω άδειες χειριστή:</p>
                                        <ul>
                                            <?php foreach ($driverOperatorLicenses as $license): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($license['license_type'] ?? $license['speciality'] ?? 'Άδεια Χειριστή') ?></strong>
                                                    <?php if (!empty($license['expiry_date'])): ?>
                                                        (Λήξη: <?= date('d/m/Y', strtotime($license['expiry_date'])) ?>)
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>Δεν έχετε καταχωρήσει άδειες χειριστή μηχανημάτων έργου. <a href="<?= BASE_URL ?>drivers/edit-profile">Προσθέστε τώρα</a> για καλ
