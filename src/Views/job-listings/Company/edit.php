<?php

/**
 * Φόρμα επεξεργασίας αγγελίας από εταιρεία
 */

// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Επεξεργασία Αγγελίας Προσφοράς Εργασίας';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Σύνδεση με το CSS αρχείο της φόρμας αγγελιών
echo '<link rel="stylesheet" href="' . BASE_URL . 'css/job-listing-form.css">';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Επεξεργασία Αγγελίας Προσφοράς Εργασίας</h1>

            <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

            <div class="card">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>job-listings/update/<?= $listing['id'] ?>" method="post" id="job-listing-form">
                        <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::generateToken() ?>">
                        <input type="hidden" name="listing_type" value="job_offer">

                        <!-- Επιλογή τύπου αγγελίας -->
                        <div class="form-group mb-4">
                            <label for="job_category" class="form-label fw-bold">Τύπος Αγγελίας *</label>
                            <select class="form-control form-select" id="job_category" name="job_category" required onchange="updateFormFields()">
                                <option value="">Επιλέξτε τύπο αγγελίας</option>
                                <option value="cargo_transport" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'cargo_transport') || (!isset($_SESSION['old_input']['job_category']) && $listing['job_category'] === 'cargo_transport') ? 'selected' : '' ?>>Εμπορευματικές Μεταφορές</option>
                                <option value="passenger_transport" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'passenger_transport') || (!isset($_SESSION['old_input']['job_category']) && $listing['job_category'] === 'passenger_transport') ? 'selected' : '' ?>>Επιβατικές Μεταφορές</option>
                                <option value="machinery_operator" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'machinery_operator') || (!isset($_SESSION['old_input']['job_category']) && $listing['job_category'] === 'machinery_operator') ? 'selected' : '' ?>>Χειριστής Μηχανημάτων Έργου</option>
                                <option value="machinery_assistant" <?= (isset($_SESSION['old_input']['job_category']) && $_SESSION['old_input']['job_category'] === 'machinery_assistant') || (!isset($_SESSION['old_input']['job_category']) && $listing['job_category'] === 'machinery_assistant') ? 'selected' : '' ?>>Βοηθός Χειριστή Μηχανημάτων Έργου</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['job_category'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['job_category'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Βασικές πληροφορίες αγγελίας -->
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">Τίτλος Αγγελίας *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                value="<?= isset($_SESSION['old_input']['title']) ? htmlspecialchars($_SESSION['old_input']['title']) : htmlspecialchars($listing['title']) ?>">
                            <?php if (isset($_SESSION['errors']['title'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Περιγραφή *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?= isset($_SESSION['old_input']['description']) ? htmlspecialchars($_SESSION['old_input']['description']) : htmlspecialchars($listing['description']) ?></textarea>
                            <?php if (isset($_SESSION['errors']['description'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="location" class="form-label">Τοποθεσία *</label>
                            <input type="text" class="form-control" id="location" name="location" required
                                value="<?= isset($_SESSION['old_input']['location']) ? htmlspecialchars($_SESSION['old_input']['location']) : htmlspecialchars($listing['location']) ?>">
                            <?php if (isset($_SESSION['errors']['location'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['location'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="job_type" class="form-label">Τύπος Εργασίας *</label>
                            <select class="form-control form-select" id="job_type" name="job_type" required>
                                <option value="">Επιλέξτε τύπο εργασίας</option>
                                <option value="full_time" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'full_time') || (!isset($_SESSION['old_input']['job_type']) && $listing['job_type'] === 'full_time') ? 'selected' : '' ?>>Πλήρης Απασχόληση</option>
                                <option value="part_time" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'part_time') || (!isset($_SESSION['old_input']['job_type']) && $listing['job_type'] === 'part_time') ? 'selected' : '' ?>>Μερική Απασχόληση</option>
                                <option value="contract" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'contract') || (!isset($_SESSION['old_input']['job_type']) && $listing['job_type'] === 'contract') ? 'selected' : '' ?>>Σύμβαση</option>
                                <option value="temporary" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'temporary') || (!isset($_SESSION['old_input']['job_type']) && $listing['job_type'] === 'temporary') ? 'selected' : '' ?>>Προσωρινή</option>
                                <option value="seasonal" <?= (isset($_SESSION['old_input']['job_type']) && $_SESSION['old_input']['job_type'] === 'seasonal') || (!isset($_SESSION['old_input']['job_type']) && $listing['job_type'] === 'seasonal') ? 'selected' : '' ?>>Εποχιακή</option>
                            </select>
                            <?php if (isset($_SESSION['errors']['job_type'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['job_type'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Πεδία για Εμπορευματικές Μεταφορές -->
                        <div id="cargo_transport_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Εμπορευματικών Μεταφορών</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Απαιτούμενες Άδειες Οδήγησης</label>
                                <?php
                                // Προετοιμασία των απαιτούμενων αδειών
                                $requiredLicenses = isset($_SESSION['old_input']['required_licenses']) ? $_SESSION['old_input']['required_licenses'] : (isset($listing['required_licenses']) ? explode(',', $listing['required_licenses']) : []);
                                ?>
                                <div class="cargo-licenses">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="B" id="license_b" <?= is_array($requiredLicenses) && in_array('B', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_b">B - Επιβατικά αυτοκίνητα</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="BE" id="license_be" <?= is_array($requiredLicenses) && in_array('BE', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_be">BE - Επιβατικά με ρυμουλκούμενο</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="C1" id="license_c1" <?= is_array($requiredLicenses) && in_array('C1', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_c1">C1 - Φορτηγά < 7.5t</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="C1E" id="license_c1e" <?= is_array($requiredLicenses) && in_array('C1E', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_c1e">C1E - Φορτηγά < 7.5t με ρυμουλκούμενο</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="C" id="license_c" <?= is_array($requiredLicenses) && in_array('C', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_c">C - Φορτηγά > 7.5t</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="CE" id="license_ce" <?= is_array($requiredLicenses) && in_array('CE', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_ce">CE - Φορτηγά με ρυμουλκούμενο</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Τύποι Οχημάτων</label>
                                <?php
                                // Προετοιμασία των τύπων οχημάτων
                                $vehicleTypes = isset($_SESSION['old_input']['vehicle_types']) ? $_SESSION['old_input']['vehicle_types'] : (isset($listing['vehicle_types']) ? explode(',', $listing['vehicle_types']) : []);
                                ?>
                                <div class="cargo-vehicles">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van" <?= is_array($vehicleTypes) && in_array('van', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_van">Βαν</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_light" id="vehicle_truck_light" <?= is_array($vehicleTypes) && in_array('truck_light', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_truck_light">Ελαφρύ Φορτηγό (έως 3.5τ)</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_medium" id="vehicle_truck_medium" <?= is_array($vehicleTypes) && in_array('truck_medium', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_truck_medium">Μεσαίο Φορτηγό (3.5-7.5τ)</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_heavy" id="vehicle_truck_heavy" <?= is_array($vehicleTypes) && in_array('truck_heavy', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_truck_heavy">Βαρύ Φορτηγό (άνω των 7.5τ)</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_articulated" id="vehicle_truck_articulated" <?= is_array($vehicleTypes) && in_array('truck_articulated', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_truck_articulated">Αρθρωτό Φορτηγό (με ρυμουλκούμενο)</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_tanker" id="vehicle_truck_tanker" <?= is_array($vehicleTypes) && in_array('truck_tanker', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_truck_tanker">Βυτιοφόρο</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck_refrigerated" id="vehicle_truck_refrigerated" <?= is_array($vehicleTypes) && in_array('truck_refrigerated', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_truck_refrigerated">Ψυγείο</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Απαιτούμενες Πιστοποιήσεις</label>
                                <?php
                                // Προετοιμασία των απαιτούμενων πιστοποιήσεων
                                $requiredCertifications = isset($_SESSION['old_input']['required_certifications']) ? $_SESSION['old_input']['required_certifications'] : [];
                                ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_adr" value="1" id="req_adr" <?= (isset($_SESSION['old_input']['requires_adr']) && $_SESSION['old_input']['requires_adr']) || (!isset($_SESSION['old_input']['requires_adr']) && isset($listing['requires_adr']) && $listing['requires_adr']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="req_adr">Πιστοποιητικό ADR</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_tachograph" value="1" id="req_tachograph" <?= (isset($_SESSION['old_input']['requires_tachograph']) && $_SESSION['old_input']['requires_tachograph']) || (!isset($_SESSION['old_input']['requires_tachograph']) && isset($listing['requires_tachograph']) && $listing['requires_tachograph']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="req_tachograph">Κάρτα Ταχογράφου</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_pei" value="1" id="req_pei" <?= (isset($_SESSION['old_input']['requires_pei']) && $_SESSION['old_input']['requires_pei']) || (!isset($_SESSION['old_input']['requires_pei']) && isset($listing['requires_pei']) && $listing['requires_pei']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="req_pei">Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)</label>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="experience_years_cargo" class="form-label">Απαιτούμενη Εμπειρία</label>
                                <select class="form-control form-select" id="experience_years_cargo" name="experience_years">
                                    <option value="">Επιλέξτε απαιτούμενη εμπειρία</option>
                                    <option value="0" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '0') || (!isset($_SESSION['old_input']['experience_years']) && isset($listing['experience_years']) && $listing['experience_years'] === '0') ? 'selected' : '' ?>>Χωρίς εμπειρία</option>
                                    <option value="1" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '1') || (!isset($_SESSION['old_input']['experience_years']) && isset($listing['experience_years']) && $listing['experience_years'] === '1') ? 'selected' : '' ?>>Τουλάχιστον 1 έτος</option>
                                    <option value="2" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '2') || (!isset($_SESSION['old_input']['experience_years']) && isset($listing['experience_years']) && $listing['experience_years'] === '2') ? 'selected' : '' ?>>Τουλάχιστον 2 έτη</option>
                                    <option value="3" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '3') || (!isset($_SESSION['old_input']['experience_years']) && isset($listing['experience_years']) && $listing['experience_years'] === '3') ? 'selected' : '' ?>>Τουλάχιστον 3 έτη</option>
                                    <option value="5" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '5') || (!isset($_SESSION['old_input']['experience_years']) && isset($listing['experience_years']) && $listing['experience_years'] === '5') ? 'selected' : '' ?>>Τουλάχιστον 5 έτη</option>
                                    <option value="10" <?= (isset($_SESSION['old_input']['experience_years']) && $_SESSION['old_input']['experience_years'] === '10') || (!isset($_SESSION['old_input']['experience_years']) && isset($listing['experience_years']) && $listing['experience_years'] === '10') ? 'selected' : '' ?>>Τουλάχιστον 10 έτη</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="route_type" class="form-label">Τύπος Δρομολογίου</label>
                                <select class="form-control form-select" id="route_type" name="route_type">
                                    <option value="">Επιλέξτε τύπο δρομολογίου</option>
                                    <option value="local" <?= (isset($_SESSION['old_input']['route_type']) && $_SESSION['old_input']['route_type'] === 'local') || (!isset($_SESSION['old_input']['route_type']) && isset($listing['route_type']) && $listing['route_type'] === 'local') ? 'selected' : '' ?>>Τοπικό (εντός πόλης)</option>
                                    <option value="regional" <?= (isset($_SESSION['old_input']['route_type']) && $_SESSION['old_input']['route_type'] === 'regional') || (!isset($_SESSION['old_input']['route_type']) && isset($listing['route_type']) && $listing['route_type'] === 'regional') ? 'selected' : '' ?>>Περιφερειακό (εντός νομού)</option>
                                    <option value="national" <?= (isset($_SESSION['old_input']['route_type']) && $_SESSION['old_input']['route_type'] === 'national') || (!isset($_SESSION['old_input']['route_type']) && isset($listing['route_type']) && $listing['route_type'] === 'national') ? 'selected' : '' ?>>Εθνικό (εντός Ελλάδας)</option>
                                    <option value="international" <?= (isset($_SESSION['old_input']['route_type']) && $_SESSION['old_input']['route_type'] === 'international') || (!isset($_SESSION['old_input']['route_type']) && isset($listing['route_type']) && $listing['route_type'] === 'international') ? 'selected' : '' ?>>Διεθνές</option>
                                </select>
                            </div>
                        </div>

                        <!-- Πεδία για Επιβατικές Μεταφορές -->
                        <div id="passenger_transport_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Επιβατικών Μεταφορών</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Απαιτούμενες Άδειες Οδήγησης</label>
                                <div class="passenger-licenses">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="B" id="license_b_passenger" <?= is_array($requiredLicenses) && in_array('B', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_b_passenger">B - Επιβατικά αυτοκίνητα</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="D1" id="license_d1" <?= is_array($requiredLicenses) && in_array('D1', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_d1">D1 - Μικρά λεωφορεία</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="D1E" id="license_d1e" <?= is_array($requiredLicenses) && in_array('D1E', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_d1e">D1E - Μικρά λεωφορεία με ρυμουλκούμενο</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="D" id="license_d" <?= is_array($requiredLicenses) && in_array('D', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_d">D - Λεωφορεία</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="required_licenses[]" value="DE" id="license_de" <?= is_array($requiredLicenses) && in_array('DE', $requiredLicenses) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="license_de">DE - Λεωφορεία με ρυμουλκούμενο</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Τύποι Οχημάτων</label>
                                <div class="passenger-vehicles">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="car" id="vehicle_car" <?= is_array($vehicleTypes) && in_array('car', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_car">Αυτοκίνητο</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van_passenger" <?= is_array($vehicleTypes) && in_array('van', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_van_passenger">Βαν</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="minibus" id="vehicle_minibus" <?= is_array($vehicleTypes) && in_array('minibus', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_minibus">Μικρό Λεωφορείο (έως 16 θέσεις)</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="bus" id="vehicle_bus" <?= is_array($vehicleTypes) && in_array('bus', $vehicleTypes) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="vehicle_bus">Λεωφορείο</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Πεδία για Χειριστή Μηχανημάτων Έργου -->
                        <div id="machinery_operator_fields" class="category-fields" style="display: none;">
                            <h3 class="mt-4 mb-3">Στοιχεία Χειριστή Μηχανημάτων Έργου</h3>

                            <div class="form-group mb-3">
                                <label class="form-label">Τύποι Μηχανημάτων</label>
                                <div class="machinery-types">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox