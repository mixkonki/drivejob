<?php

/**
 * Ενιαία φόρμα δημιουργίας αγγελίας από οδηγό
 * Υποστηρίζει τη δημιουργία αγγελιών για:
 * - Εμπορευματικές μεταφορές
 * - Επιβατικές μεταφορές
 * - Χειριστής μηχανημάτων έργου
 * - Βοηθός χειριστή μηχανημάτων έργου
 */

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
                                        <p>Δεν έχετε καταχωρήσει άδειες χειριστή μηχανημάτων έργου. <a href="<?= BASE_URL ?>drivers/edit-profile">Προσθέστε τώρα</a> για καλύτερη προβολή της αγγελίας σας.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Προτιμώμενοι Τύποι Μηχανημάτων</label>
                                <div class="machinery-types">
                                    <?php
                                    // Έλεγχος αδειών για εμφάνιση κατάλληλων μηχανημάτων
                                    $hasExcavatorLicense = false;
                                    $hasBulldozerLicense = false;
                                    $hasCraneLicense = false;
                                    $hasForkliftLicense = false;
                                    $hasLoaderLicense = false;
                                    $hasGraderLicense = false;

                                    foreach ($driverOperatorLicenses as $license) {
                                        $licenseType = $license['license_type'] ?? $license['speciality'] ?? '';
                                        if (stripos($licenseType, 'εκσκαφέα') !== false) {
                                            $hasExcavatorLicense = true;
                                        }
                                        if (stripos($licenseType, 'μπουλντόζα') !== false || stripos($licenseType, 'προωθητή') !== false) {
                                            $hasBulldozerLicense = true;
                                        }
                                        if (stripos($licenseType, 'γερανό') !== false) {
                                            $hasCraneLicense = true;
                                        }
                                        if (stripos($licenseType, 'περονοφόρο') !== false || stripos($licenseType, 'κλαρκ') !== false) {
                                            $hasForkliftLicense = true;
                                        }
                                        if (stripos($licenseType, 'φορτωτή') !== false) {
                                            $hasLoaderLicense = true;
                                        }
                                        if (stripos($licenseType, 'ισοπεδωτή') !== false || stripos($licenseType, 'grader') !== false) {
                                            $hasGraderLicense = true;
                                        }
                                    }
                                    ?>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="excavator" id="machinery_excavator" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('excavator', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?> <?= $hasExcavatorLicense ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="machinery_excavator">Εκσκαφέας</label>
                                        <?php if (!$hasExcavatorLicense): ?>
                                            <small class="text-muted">(Απαιτείται άδεια χειριστή εκσκαφέα)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="bulldozer" id="machinery_bulldozer" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('bulldozer', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?> <?= $hasBulldozerLicense ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="machinery_bulldozer">Μπουλντόζα</label>
                                        <?php if (!$hasBulldozerLicense): ?>
                                            <small class="text-muted">(Απαιτείται άδεια χειριστή μπουλντόζας)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="crane" id="machinery_crane" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('crane', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?> <?= $hasCraneLicense ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="machinery_crane">Γερανός</label>
                                        <?php if (!$hasCraneLicense): ?>
                                            <small class="text-muted">(Απαιτείται άδεια χειριστή γερανού)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="forklift" id="machinery_forklift" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('forklift', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?> <?= $hasForkliftLicense ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="machinery_forklift">Περονοφόρο</label>
                                        <?php if (!$hasForkliftLicense): ?>
                                            <small class="text-muted">(Απαιτείται άδεια χειριστή περονοφόρου)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="loader" id="machinery_loader" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('loader', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?> <?= $hasLoaderLicense ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="machinery_loader">Φορτωτής</label>
                                        <?php if (!$hasLoaderLicense): ?>
                                            <small class="text-muted">(Απαιτείται άδεια χειριστή φορτωτή)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="grader" id="machinery_grader" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('grader', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?> <?= $hasGraderLicense ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="machinery_grader">Ισοπεδωτής</label>
                                        <?php if (!$hasGraderLicense): ?>
                                            <small class="text-muted">(Απαιτείται άδεια χειριστή ισοπεδωτή)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="experience_years" class="form-label">Έτη Εμπειρίας ως Χειριστής *</label>
                                <select class="form-control form-select" id="experience_years" name="experience_years" required>
                                    <option value="">Επιλέξτε έτη εμπειρίας</option>
                                    <option value="0-1" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '0-1') ? 'selected' : '' ?>>Λιγότερο από 1 έτος</option>
                                    <option value="1-3" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '1-3') ? 'selected' : '' ?>>1-3 έτη</option>
                                    <option value="3-5" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '3-5') ? 'selected' : '' ?>>3-5 έτη</option>
                                    <option value="5-10" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '5-10') ? 'selected' : '' ?>>5-10 έτη</option>
                                    <option value="10+" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '10+') ? 'selected' : '' ?>>10+ έτη</option>
                                </select>
                            </div>
                        </div>

                        <!-- Πεδία για Βοηθό Χειριστή Μηχανημάτων Έργου -->
                        <div id="machinery_assistant_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Βοηθού Χειριστή Μηχανημάτων Έργου</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Προτιμώμενοι Τύποι Μηχανημάτων</label>
                                <div class="machinery-assistant-types">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="excavator" id="machinery_excavator_assistant" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('excavator', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="machinery_excavator_assistant">Εκσκαφέας</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="bulldozer" id="machinery_bulldozer_assistant" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('bulldozer', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="machinery_bulldozer_assistant">Μπουλντόζα</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="crane" id="machinery_crane_assistant" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('crane', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="machinery_crane_assistant">Γερανός</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="forklift" id="machinery_forklift_assistant" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('forklift', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="machinery_forklift_assistant">Περονοφόρο</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="loader" id="machinery_loader_assistant" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('loader', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="machinery_loader_assistant">Φορτωτής</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="machinery_types[]" value="other" id="machinery_other_assistant" <?= (isset($_SESSION['old_input']['machinery_types']) && in_array('other', $_SESSION['old_input']['machinery_types'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="machinery_other_assistant">Άλλο</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="experience_years_assistant" class="form-label">Έτη Εμπειρίας ως Βοηθός *</label>
                                <select class="form-control form-select" id="experience_years_assistant" name="experience_years" required>
                                    <option value="">Επιλέξτε έτη εμπειρίας</option>
                                    <option value="0-1" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '0-1') ? 'selected' : '' ?>>Λιγότερο από 1 έτος</option>
                                    <option value="1-3" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '1-3') ? 'selected' : '' ?>>1-3 έτη</option>
                                    <option value="3-5" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '3-5') ? 'selected' : '' ?>>3-5 έτη</option>
                                    <option value="5+" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '5+') ? 'selected' : '' ?>>5+ έτη</option>
                                </select>
                            </div>
                        </div>

                        <!-- Κοινά πεδία για όλους τους τύπους αγγελιών -->
                        <div class="form-group mb-3">
                            <label for="salary_range" class="form-label">Επιθυμητό Εύρος Μισθού</label>
                            <select class="form-control form-select" id="salary_range" name="salary_range">
                                <option value="">Επιλέξτε εύρος μισθού</option>
                                <option value="0-1000" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '0-1000') ? 'selected' : '' ?>>Έως 1.000€</option>
                                <option value="1000-1500" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '1000-1500') ? 'selected' : '' ?>>1.000€ - 1.500€</option>
                                <option value="1500-2000" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '1500-2000') ? 'selected' : '' ?>>1.500€ - 2.000€</option>
                                <option value="2000-2500" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '2000-2500') ? 'selected' : '' ?>>2.000€ - 2.500€</option>
                                <option value="2500+" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '2500+') ? 'selected' : '' ?>>2.500€ και άνω</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="availability" class="form-label">Διαθεσιμότητα</label>
                            <select class="form-control form-select" id="availability" name="availability">
                                <option value="">Επιλέξτε διαθεσιμότητα</option>
                                <option value="immediate" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === 'immediate') ? 'selected' : '' ?>>Άμεση</option>
                                <option value="1_week" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === '1_week') ? 'selected' : '' ?>>Εντός 1 εβδομάδας</option>
                                <option value="2_weeks" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === '2_weeks') ? 'selected' : '' ?>>Εντός 2 εβδομάδων</option>
                                <option value="1_month" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === '1_month') ? 'selected' : '' ?>>Εντός 1 μήνα</option>
                                <option value="negotiable" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === 'negotiable') ? 'selected' : '' ?>>Διαπραγματεύσιμη</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="additional_info" class="form-label">Επιπλέον Πληροφορίες</label>
                            <textarea class="form-control" id="additional_info" name="additional_info" rows="3"><?= isset($_SESSION['old_input']['additional_info']) ? htmlspecialchars($_SESSION['old_input']['additional_info']) : '' ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Ενεργή Αγγελία</label>
                            </div>
                        </div>

                        <div class="form-group text-center mt-4">
                            <button type="submit" class="btn btn-primary">Δημιουργία Αγγελίας</button>
                            <a href="<?= BASE_URL ?>drivers/profile" class="btn btn-secondary ml-2">Ακύρωση</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Ενημέρωση των πεδίων της φόρμας ανάλογα με τον επιλεγμένο τύπο αγγελίας
    function updateFormFields() {
        const jobCategory = document.getElementById('job_category').value;
        const categoryFields = document.querySelectorAll('.category-fields');

        // Απόκρυψη όλων των πεδίων κατηγορίας
        categoryFields.forEach(field => {
            field.style.display = 'none';
        });

        // Εμφάνιση των κατάλληλων πεδίων ανάλογα με την επιλογή
        if (jobCategory === 'cargo_transport') {
            document.getElementById('cargo_transport_fields').style.display = 'block';
        } else if (jobCategory === 'passenger_transport') {
            document.getElementById('passenger_transport_fields').style.display = 'block';
        } else if (jobCategory === 'machinery_operator') {
            document.getElementById('machinery_operator_fields').style.display = 'block';
        } else if (jobCategory === 'machinery_assistant') {
            document.getElementById('machinery_assistant_fields').style.display = 'block';
        }
    }

    // Αρχικοποίηση των πεδίων της φόρμας με βάση τον επιλεγμένο τύπο αγγελίας
    document.addEventListener('DOMContentLoaded', function() {
        updateFormFields();
    });
</script>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';

// Καθαρισμός των session μεταβλητών
unset($_SESSION['errors']);
unset($_SESSION['old_input']);
?>