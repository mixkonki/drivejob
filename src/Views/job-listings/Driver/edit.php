<?php

/**
 * Φόρμα επεξεργασίας αγγελίας από οδηγό
 */

// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Επεξεργασία Αγγελίας Αναζήτησης Εργασίας';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Επεξεργασία Αγγελίας Αναζήτησης Εργασίας</h1>

            <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

            <div class="card">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>job-listings/update/<?= $listing['id'] ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::generateToken() ?>">
                        <input type="hidden" name="listing_type" value="job_search">

                        <div class="form-group mb-3">
                            <label for="title">Τίτλος Αγγελίας *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                value="<?= isset($_SESSION['old_input']['title']) ? htmlspecialchars($_SESSION['old_input']['title']) : htmlspecialchars($listing['title']) ?>">
                            <?php if (isset($_SESSION['errors']['title'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Περιγραφή *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?= isset($_SESSION['old_input']['description']) ? htmlspecialchars($_SESSION['old_input']['description']) : htmlspecialchars($listing['description']) ?></textarea>
                            <?php if (isset($_SESSION['errors']['description'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="location">Τοποθεσία *</label>
                            <input type="text" class="form-control" id="location" name="location" required
                                value="<?= isset($_SESSION['old_input']['location']) ? htmlspecialchars($_SESSION['old_input']['location']) : htmlspecialchars($listing['location']) ?>">
                            <?php if (isset($_SESSION['errors']['location'])): ?>
                                <div class="text-danger"><?= $_SESSION['errors']['location'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="job_type">Τύπος Εργασίας *</label>
                            <select class="form-control" id="job_type" name="job_type" required>
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

                        <div class="form-group mb-3">
                            <label for="salary_range">Επιθυμητό Εύρος Μισθού</label>
                            <select class="form-control" id="salary_range" name="salary_range">
                                <option value="">Επιλέξτε εύρος μισθού</option>
                                <option value="0-1000" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '0-1000') || (!isset($_SESSION['old_input']['salary_range']) && $listing['salary_range'] === '0-1000') ? 'selected' : '' ?>>Έως 1.000€</option>
                                <option value="1000-1500" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '1000-1500') || (!isset($_SESSION['old_input']['salary_range']) && $listing['salary_range'] === '1000-1500') ? 'selected' : '' ?>>1.000€ - 1.500€</option>
                                <option value="1500-2000" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '1500-2000') || (!isset($_SESSION['old_input']['salary_range']) && $listing['salary_range'] === '1500-2000') ? 'selected' : '' ?>>1.500€ - 2.000€</option>
                                <option value="2000-2500" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '2000-2500') || (!isset($_SESSION['old_input']['salary_range']) && $listing['salary_range'] === '2000-2500') ? 'selected' : '' ?>>2.000€ - 2.500€</option>
                                <option value="2500+" <?= (isset($_SESSION['old_input']['salary_range']) && $_SESSION['old_input']['salary_range'] === '2500+') || (!isset($_SESSION['old_input']['salary_range']) && $listing['salary_range'] === '2500+') ? 'selected' : '' ?>>2.500€ και άνω</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="availability">Διαθεσιμότητα</label>
                            <select class="form-control" id="availability" name="availability">
                                <option value="">Επιλέξτε διαθεσιμότητα</option>
                                <option value="immediate" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === 'immediate') || (!isset($_SESSION['old_input']['availability']) && $listing['availability'] === 'immediate') ? 'selected' : '' ?>>Άμεση</option>
                                <option value="1_week" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === '1_week') || (!isset($_SESSION['old_input']['availability']) && $listing['availability'] === '1_week') ? 'selected' : '' ?>>Εντός 1 εβδομάδας</option>
                                <option value="2_weeks" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === '2_weeks') || (!isset($_SESSION['old_input']['availability']) && $listing['availability'] === '2_weeks') ? 'selected' : '' ?>>Εντός 2 εβδομάδων</option>
                                <option value="1_month" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === '1_month') || (!isset($_SESSION['old_input']['availability']) && $listing['availability'] === '1_month') ? 'selected' : '' ?>>Εντός 1 μήνα</option>
                                <option value="negotiable" <?= (isset($_SESSION['old_input']['availability']) && $_SESSION['old_input']['availability'] === 'negotiable') || (!isset($_SESSION['old_input']['availability']) && $listing['availability'] === 'negotiable') ? 'selected' : '' ?>>Διαπραγματεύσιμη</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Προτιμώμενοι Τύποι Οχημάτων</label>
                            <?php
                            // Προετοιμασία των τύπων οχημάτων
                            $vehicleTypes = isset($_SESSION['old_input']['vehicle_types']) ? $_SESSION['old_input']['vehicle_types'] : $listing['vehicle_types'];
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="car" id="vehicle_car" <?= (is_array($vehicleTypes) && in_array('car', $vehicleTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="vehicle_car">Αυτοκίνητο</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="van" id="vehicle_van" <?= (is_array($vehicleTypes) && in_array('van', $vehicleTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="vehicle_van">Βαν</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="truck" id="vehicle_truck" <?= (is_array($vehicleTypes) && in_array('truck', $vehicleTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="vehicle_truck">Φορτηγό</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="bus" id="vehicle_bus" <?= (is_array($vehicleTypes) && in_array('bus', $vehicleTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="vehicle_bus">Λεωφορείο</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="machinery" id="vehicle_machinery" <?= (is_array($vehicleTypes) && in_array('machinery', $vehicleTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="vehicle_machinery">Μηχάνημα Έργου</label>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="additional_info">Επιπλέον Πληροφορίες</label>
                            <textarea class="form-control" id="additional_info" name="additional_info" rows="3"><?= isset($_SESSION['old_input']['additional_info']) ? htmlspecialchars($_SESSION['old_input']['additional_info']) : htmlspecialchars($listing['additional_info']) ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (isset($_SESSION['old_input']['is_active']) && $_SESSION['old_input']['is_active']) || (!isset($_SESSION['old_input']['is_active']) && $listing['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Ενεργή Αγγελία</label>
                            </div>
                        </div>

                        <div class="form-group text-center mt-4">
                            <button type="submit" class="btn btn-primary">Αποθήκευση Αλλαγών</button>
                            <a href="<?= BASE_URL ?>drivers/profile#my-listings" class="btn btn-secondary ml-2">Ακύρωση</a>
                        </div>
                    </form>

                    <div class="mt-4">
                        <form action="<?= BASE_URL ?>job-listings/destroy/<?= $listing['id'] ?>" method="post" onsubmit="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;');">
                            <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::generateToken() ?>">
                            <button type="submit" class="btn btn-danger">Διαγραφή Αγγελίας</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';

// Καθαρισμός των session μεταβλητών
unset($_SESSION['errors']);
unset($_SESSION['old_input']);
?>