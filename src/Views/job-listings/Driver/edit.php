<?php
// Φόρτωση του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Σύνδεση με το CSS αρχείο της φόρμας αγγελιών
echo '<link rel="stylesheet" href="' . BASE_URL . 'css/job-listing-form.css">';

// Λήψη των αδειών και πιστοποιήσεων του οδηγού από το session
$driverLicenses = $_SESSION['driver_licenses'] ?? [];
$driverOperatorLicenses = $_SESSION['driver_operator_licenses'] ?? [];
$hasPEI = $_SESSION['driver_has_pei'] ?? false;
$hasADR = $_SESSION['driver_has_adr'] ?? false;
$hasTachograph = $_SESSION['driver_has_tachograph'] ?? false;

// Προετοιμασία των τύπων οχημάτων
$vehicleTypes = [];
if (!empty($listing['vehicle_types'])) {
    $vehicleTypes = explode(',', $listing['vehicle_types']);
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Επεξεργασία Αγγελίας Αναζήτησης Εργασίας</h1>

            <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

            <div class="card">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>job-listings/update/<?= $listing['id'] ?>" method="post" id="job-listing-form">
                        <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::generateToken() ?>">
                        <input type="hidden" name="listing_type" value="job_search">

                        <!-- Επιλογή τύπου αγγελίας -->
                        <div class="form-group mb-4">
                            <label for="job_category" class="form-label fw-bold">Τύπος Αγγελίας *</label>
                            <select class="form-control form-select" id="job_category" name="job_category" required onchange="updateFormFields()">
                                <option value="">Επιλέξτε τύπο αγγελίας</option>
                                <option value="cargo_transport" <?= ($listing['job_category'] === 'cargo_transport') ? 'selected' : '' ?>>Εμπορευματικές Μεταφορές</option>
                                <option value="passenger_transport" <?= ($listing['job_category'] === 'passenger_transport') ? 'selected' : '' ?>>Επιβατικές Μεταφορές</option>
                                <option value="machinery_operator" <?= ($listing['job_category'] === 'machinery_operator') ? 'selected' : '' ?>>Χειριστής Μηχανημάτων Έργου</option>
                                <option value="machinery_assistant" <?= ($listing['job_category'] === 'machinery_assistant') ? 'selected' : '' ?>>Βοηθός Χειριστή Μηχανημάτων Έργου</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['job_category'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['job_category'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Βασικές πληροφορίες αγγελίας -->
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">Τίτλος Αγγελίας *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                value="<?= htmlspecialchars($listing['title']) ?>">
                            <?php if (isset($_SESSION['errors']['title'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Περιγραφή *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($listing['description']) ?></textarea>
                            <?php if (isset($_SESSION['errors']['description'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="location" class="form-label">Τοποθεσία *</label>
                            <input type="text" class="form-control" id="location" name="location" required
                                value="<?= htmlspecialchars($listing['location']) ?>">
                            <?php if (isset($_SESSION['errors']['location'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['location'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="job_type" class="form-label">Τύπος Εργασίας *</label>
                            <select class="form-control form-select" id="job_type" name="job_type" required>
                                <option value="">Επιλέξτε τύπο εργασίας</option>
                                <option value="full_time" <?= ($listing['job_type'] === 'full_time') ? 'selected' : '' ?>>Πλήρης Απασχόληση</option>
                                <option value="part_time" <?= ($listing['job_type'] === 'part_time') ? 'selected' : '' ?>>Μερική Απασχόληση</option>
                                <option value="contract" <?= ($listing['job_type'] === 'contract') ? 'selected' : '' ?>>Σύμβαση</option>
                                <option value="temporary" <?= ($listing['job_type'] === 'temporary') ? 'selected' : '' ?>>Προσωρινή</option>
                                <option value="seasonal" <?= ($listing['job_type'] === 'seasonal') ? 'selected' : '' ?>>Εποχιακή</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['job_type'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['job_type'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Πεδία για Εμπορευματικές Μεταφορές -->
                        <div id="cargo_transport_fields" class="category-fields" style="display: <?= ($listing['job_category'] === 'cargo_transport') ? 'block' : 'none' ?>;">
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
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van" <?= (in_array('van', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveVan ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_van">Βαν</label>
                                        <?php if (!$canDriveVan): ?>
                                            <small class="text-muted">(Απαιτείται άδεια B)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_light" id="vehicle_truck_light" <?= (in_array('truck_light', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_light">Ελαφρύ Φορτηγό (έως 3.5τ)</label>
                                        <?php if (!$canDriveTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C1 ή C)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_medium" id="vehicle_truck_medium" <?= (in_array('truck_medium', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_medium">Μεσαίο Φορτηγό (3.5-7.5τ)</label>
                                        <?php if (!$canDriveTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C1 ή C)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_heavy" id="vehicle_truck_heavy" <?= (in_array('truck_heavy', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveHeavyTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_heavy">Βαρύ Φορτηγό (άνω των 7.5τ)</label>
                                        <?php if (!$canDriveHeavyTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_articulated" id="vehicle_truck_articulated" <?= (in_array('truck_articulated', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveArticulated ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_articulated">Αρθρωτό Φορτηγό (με ρυμουλκούμενο)</label>
                                        <?php if (!$canDriveArticulated): ?>
                                            <small class="text-muted">(Απαιτείται άδεια CE ή C1E)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_tanker" id="vehicle_truck_tanker" <?= (in_array('truck_tanker', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveHeavyTruck && $hasADR ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_tanker">Βυτιοφόρο</label>
                                        <?php if (!$canDriveHeavyTruck || !$hasADR): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C και πιστοποιητικό ADR)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_refrigerated" id="vehicle_truck_refrigerated" <?= (in_array('truck_refrigerated', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveHeavyTruck ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_truck_refrigerated">Ψυγείο</label>
                                        <?php if (!$canDriveHeavyTruck): ?>
                                            <small class="text-muted">(Απαιτείται άδεια C)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Πεδία για Επιβατικές Μεταφορές -->
                        <div id="passenger_transport_fields" class="category-fields" style="display: <?= ($listing['job_category'] === 'passenger_transport') ? 'block' : 'none' ?>;">
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
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="car" id="vehicle_car" <?= (in_array('car', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveCar ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_car">Αυτοκίνητο</label>
                                        <?php if (!$canDriveCar): ?>
                                            <small class="text-muted">(Απαιτείται άδεια B)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van_passenger" <?= (in_array('van', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveVan ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_van_passenger">Βαν</label>
                                        <?php if (!$canDriveVan): ?>
                                            <small class="text-muted">(Απαιτείται άδεια B)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="minibus" id="vehicle_minibus" <?= (in_array('minibus', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveMiniBus ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_minibus">Μικρό Λεωφορείο (έως 16 θέσεις)</label>
                                        <?php if (!$canDriveMiniBus): ?>
                                            <small class="text-muted">(Απαιτείται άδεια D1 ή D)</small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="bus" id="vehicle_bus" <?= (in_array('bus', $vehicleTypes)) ? 'checked' : '' ?> <?= $canDriveBus ? '' : 'disabled' ?>>
                                        <label class="form-check-label" for="vehicle_bus">Λεωφορείο</label>
                                        <?php if (!$canDriveBus): ?>
                                            <small class="text-muted">(Απαιτείται άδεια D)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Επιπλέον πληροφορίες -->
                        <div class="form-group mb-3">
                            <label for="additional_info" class="form-label">Επιπλέον Πληροφορίες</label>
                            <textarea class="form-control" id="additional_info" name="additional_info" rows="3"><?= htmlspecialchars($listing['additional_info'] ?? '') ?></textarea>
                        </div>

                        <!-- Κατάσταση αγγελίας -->
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (!empty($listing['is_active']) && $listing['is_active'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Ενεργή Αγγελία</label>
                                <small class="form-text text-muted">Αν δεν είναι επιλεγμένο, η αγγελία δεν θα εμφανίζεται στις αναζητήσεις.</small>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Αποθήκευση Αγγελίας
                            </button>
                            <a href="<?= BASE_URL ?>job-listings/show/<?= $listing['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Ακύρωση
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateFormFields() {
        // Απόκρυψη όλων των πεδίων κατηγορίας
        document.querySelectorAll('.category-fields').forEach(function(field) {
            field.style.display = 'none';
        });

        // Εμφάνιση των πεδίων της επιλεγμένης κατηγορίας
        var selectedCategory = document.getElementById('job_category').value;
        if (selectedCategory) {
            var categoryFields = document.getElementById(selectedCategory + '_fields');
            if (categoryFields) {
                categoryFields.style.display = 'block';
            }
        }
    }

    // Αρχικοποίηση των πεδίων κατά τη φόρτωση της σελίδας
    document.addEventListener('DOMContentLoaded', function() {
        updateFormFields();
    });
</script>

<?php
// Φόρτωση του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>